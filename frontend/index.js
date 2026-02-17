/* ========================================================
   TacLineup — Dinamik veri akışı
   index.php'den videos & categories alır, DOM'u günceller
   ======================================================== */

(function () {
  'use strict';

  const $ = (s, p) => (p || document).querySelector(s);
  const $$ = (s, p) => [...(p || document).querySelectorAll(s)];

  let DATA = { base_path: '', categories: { navbar: [], game_content: [] }, videos: [] };
  let currentNavId = null;  // navbar'dan seçilen oyun/kategori id (veya slug)
  let activeFilterIds = []; // sidebar'dan seçilen kategori id'leri (AND değil OR: en az biri eşleşsin)
  let favorites = JSON.parse(localStorage.getItem('taclineup_favs') || '[]');

  const gridView = $('#gridView');
  const detailView = $('#detailView');
  const lineupGrid = $('#lineupGrid');
  const contentTitle = $('#contentTitle');
  const resultCount = $('#resultCount');
  const searchInput = $('#searchInput');
  const sidebar = $('#sidebar');
  const mobileToggle = $('#mobileFilterToggle');
  const gameTabs = $('#gameTabs');
  const sidebarFilterGroups = $('#sidebarFilterGroups');

  const MAP_COLORS = [
    ['#3a1f32', '#5e2744'],
    ['#1f2d3a', '#2d4a5e'],
    ['#2a3a1f', '#3d5e2d'],
    ['#3a2e1f', '#5e4a2d'],
    ['#1f2f3a', '#2d5a6e'],
    ['#1f3a35', '#2d6e5e'],
    ['#2d1f3a', '#4a2d6e'],
    ['#3a1f2d', '#6e2d4e'],
    ['#3a291f', '#6e4a2d'],
    ['#1f1f3a', '#3a3a5e'],
  ];

  function getCardColors(video, index) {
    const catNames = (DATA.categories.game_content || [])
      .filter(c => video.category_ids && video.category_ids.includes(c.id))
      .map(c => c.name)
      .join('');
    const hash = catNames ? [...catNames].reduce((a, b) => (a + b.charCodeAt(0)) | 0, 0) : index;
    const pair = MAP_COLORS[Math.abs(hash) % MAP_COLORS.length];
    return pair;
  }

  function categoryNameById(id) {
    const all = (DATA.categories.navbar || []).concat(DATA.categories.game_content || []);
    const c = all.find(x => x.id === id);
    return c ? c.name : '';
  }

  function categoryNamesByIds(ids) {
    return (ids || []).map(id => categoryNameById(id)).filter(Boolean).join(', ') || '—';
  }

  // ─── API ─────────────────────────────────────────────
  async function loadData() {
    lineupGrid.innerHTML = '<div class="loading-placeholder">Yükleniyor…</div>';
    try {
      const res = await fetch('index.php');
      const json = await res.json();
      if (!json.success || json.error) {
        lineupGrid.innerHTML = '<div class="loading-placeholder">Veri yüklenemedi.</div>';
        return;
      }
      DATA = {
        base_path: json.base_path || '',
        categories: json.categories || { navbar: [], game_content: [] },
        videos: json.videos || [],
      };
      buildNavbar();
      buildSidebarFilters();
      currentNavId = null;
      renderCards();
    } catch (e) {
      lineupGrid.innerHTML = '<div class="loading-placeholder">Bağlantı hatası.</div>';
    }
  }

  // ─── Navbar: Sadece ilk 2 kategori (Valorant | CS2) + isteğe "Tümü" ───
  function buildNavbar() {
    gameTabs.innerHTML = '';
    const navs = (DATA.categories.navbar || []).slice(0, 2);
    // "Tümü" sekmesi: navbar filtresi olmadan tüm videolar
    const allBtn = document.createElement('button');
    allBtn.className = 'game-tab' + (currentNavId === null ? ' active' : '');
    allBtn.dataset.id = '';
    allBtn.innerHTML = '<span class="tab-dot val-dot"></span> Tümü';
    allBtn.addEventListener('click', () => {
      currentNavId = null;
      $$('.game-tab').forEach(t => t.classList.remove('active'));
      allBtn.classList.add('active');
      document.body.classList.remove('cs2-mode');
      resetFilters();
      renderCards();
    });
    gameTabs.appendChild(allBtn);
    navs.forEach((cat, i) => {
      const isVal = (cat.slug || cat.name || '').toLowerCase().includes('valorant') || i === 0;
      const btn = document.createElement('button');
      btn.className = 'game-tab' + (currentNavId === cat.id ? ' active' : '');
      btn.dataset.id = cat.id;
      btn.dataset.slug = cat.slug || '';
      btn.innerHTML = `<span class="tab-dot ${isVal ? 'val-dot' : 'cs2-dot'}"></span> ${escapeHtml(cat.name)}`;
      btn.addEventListener('click', () => {
        currentNavId = cat.id;
        $$('.game-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        document.body.classList.toggle('cs2-mode', !isVal);
        resetFilters();
        renderCards();
      });
      gameTabs.appendChild(btn);
    });
    if (navs.length && currentNavId == null) {
      currentNavId = null;
    }
  }

  // Grup başlığına göre stil: Agent → agent-grid/chip, Map → map-list/chip, Ability → ability-list/chip
  function getSidebarGroupStyle(title) {
    const t = (title || '').toLowerCase();
    if (t.includes('agent')) return { wrapClass: 'filter-group', listClass: 'agent-grid', chipClass: 'agent-chip', useIcon: true };
    if (t.includes('map') || t.includes('harita')) return { wrapClass: 'filter-group', listClass: 'map-list', chipClass: 'map-chip', useIcon: false };
    if (t.includes('ability') || t.includes('yetene') || t.includes('utility')) return { wrapClass: 'filter-group', listClass: 'ability-list', chipClass: 'ability-chip', useIcon: true };
    return { wrapClass: 'filter-group', listClass: 'map-list', chipClass: 'map-chip', useIcon: false };
  }

  function groupOrderKey(title) {
    const t = (title || '').toLowerCase();
    if (t.includes('agent')) return 0;
    if (t.includes('map') || t.includes('harita')) return 1;
    if (t.includes('ability') || t.includes('yetene') || t.includes('utility')) return 2;
    return 3;
  }

  // ─── Sidebar (type=game_content): Agent, Map, Ability Type grupları ───
  function buildSidebarFilters() {
    sidebarFilterGroups.innerHTML = '';
    const list = DATA.categories.game_content || [];
    const byParent = {};
    list.forEach(c => {
      const pid = c.parent_id != null ? c.parent_id : 'root';
      if (!byParent[pid]) byParent[pid] = [];
      byParent[pid].push(c);
    });
    const parentNames = {};
    list.forEach(c => { if (c.parent_id) parentNames[c.parent_id] = categoryNameById(c.parent_id); });

    const parentIds = Object.keys(byParent).filter(k => k !== 'root').map(Number).sort((a, b) => a - b);
    const groups = [
      { pid: 'root', title: 'Diğer' },
      ...parentIds.map(pid => ({ pid, title: parentNames[pid] || 'Filtre' }))
    ];
    groups.sort((a, b) => groupOrderKey(a.title) - groupOrderKey(b.title));

    groups.forEach(({ pid, title }) => {
      const items = byParent[pid] || [];
      if (items.length === 0) return;
      const style = getSidebarGroupStyle(title);
      const wrap = document.createElement('div');
      wrap.className = style.wrapClass;
      wrap.innerHTML = `<h4 class="filter-title">${escapeHtml(title)}</h4><div class="filter-chip-list"></div>`;
      const chipList = wrap.querySelector('.filter-chip-list');
      chipList.classList.add(style.listClass);
      items.forEach(cat => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = style.chipClass;
        chip.dataset.categoryId = cat.id;
        chip.title = cat.name;
        if (style.useIcon && cat.icon) {
          const span = document.createElement('span');
          span.className = style.chipClass === 'agent-chip' ? 'agent-avatar' : 'ability-icon';
          span.textContent = cat.icon;
          chip.appendChild(span);
        }
        if (style.chipClass === 'ability-chip' && cat.icon) {
          chip.appendChild(document.createTextNode(' ' + cat.name));
        } else {
          chip.appendChild(document.createTextNode(style.chipClass === 'agent-chip' && cat.icon ? '' : cat.name));
        }
        chip.addEventListener('click', () => {
          const id = cat.id;
          const idx = activeFilterIds.indexOf(id);
          if (chip.classList.contains('active')) {
            chip.classList.remove('active');
            if (idx > -1) activeFilterIds.splice(idx, 1);
          } else {
            chip.classList.add('active');
            if (idx === -1) activeFilterIds.push(id);
          }
          renderCards();
        });
        chipList.appendChild(chip);
      });
      sidebarFilterGroups.appendChild(wrap);
    });
  }

  function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  function resetFilters() {
    activeFilterIds = [];
    $$('.filter-chip, .map-chip').forEach(c => c.classList.remove('active'));
    searchInput.value = '';
  }

  $('#clearFilters').addEventListener('click', () => { resetFilters(); renderCards(); });

  // ─── Filtreleme & kartlar ───────────────────────────
  function getFilteredVideos() {
    let list = DATA.videos.slice();
    const q = searchInput.value.toLowerCase().trim();
    if (q) {
      list = list.filter(v =>
        (v.title || '').toLowerCase().includes(q) ||
        (v.description || '').toLowerCase().includes(q) ||
        (v.category_ids || []).some(cid => (categoryNameById(cid) || '').toLowerCase().includes(q))
      );
    }
    if (activeFilterIds.length) {
      list = list.filter(v =>
        (v.category_ids || []).some(id => activeFilterIds.includes(id))
      );
    }
    if (currentNavId != null) {
      list = list.filter(v =>
        (v.category_ids || []).includes(currentNavId)
      );
      // Seçili sekmede hiç video yoksa tüm yayındaki videoları göster (kategori eşleşmese bile)
      if (list.length === 0 && DATA.videos.length > 0) {
        list = DATA.videos.slice();
        if (q) {
          list = list.filter(v =>
            (v.title || '').toLowerCase().includes(q) ||
            (v.description || '').toLowerCase().includes(q) ||
            (v.category_ids || []).some(cid => (categoryNameById(cid) || '').toLowerCase().includes(q))
          );
        }
        if (activeFilterIds.length) {
          list = list.filter(v =>
            (v.category_ids || []).some(id => activeFilterIds.includes(id))
          );
        }
      }
    }
    return list;
  }

  function renderCards() {
    const items = getFilteredVideos();
    contentTitle.textContent = currentNavId != null ? (categoryNameById(currentNavId) || 'Lineups') + ' Lineups' : 'Lineups';
    resultCount.textContent = `${items.length} lineup${items.length !== 1 ? 's' : ''} found`;

    if (items.length === 0) {
      lineupGrid.innerHTML = '<div class="loading-placeholder">Bu filtreye uygun video yok.</div>';
      return;
    }

    const navs = (DATA.categories.navbar || []).slice(0, 2);
    const currentTabIndex = currentNavId == null ? -1 : navs.findIndex(n => n.id === currentNavId);
    const isValorant = currentTabIndex <= 0;
    const gameBadgeClass = isValorant ? 'val-badge' : 'cs2-badge';
    const gameBadgeText = isValorant ? 'VAL' : 'CS2';

    lineupGrid.innerHTML = items.map((video, idx) => {
      const [c1, c2] = getCardColors(video, idx);
      const catLabel = categoryNamesByIds(video.category_ids);
      return `
        <div class="lineup-card" data-id="${video.id}" style="animation-delay:${idx * 0.04}s">
          <div class="card-thumb" style="background:linear-gradient(135deg,${c1},${c2})">
            <div class="card-overlay"></div>
            <span class="card-game-badge ${gameBadgeClass}">${gameBadgeText}</span>
            ${video.thumbnail_url ? `<img src="${escapeHtml(video.thumbnail_url)}" alt="" loading="lazy"/>` : ''}
          </div>
          <div class="card-body">
            <div class="card-top-row">
              <span class="card-agent">${escapeHtml(catLabel) || '—'}</span>
            </div>
            <div class="card-title">${escapeHtml(video.title)}</div>
            <div class="card-desc">${escapeHtml((video.description || '').slice(0, 80))}${(video.description || '').length > 80 ? '…' : ''}</div>
            <div class="card-footer">
              <span class="card-map">${escapeHtml(catLabel)}</span>
            </div>
          </div>
        </div>`;
    }).join('');

    $$('.lineup-card').forEach(card => {
      card.addEventListener('click', () => openDetail(Number(card.dataset.id)));
    });
  }

  function fullVideoUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    const base = DATA.base_path || document.location.pathname.replace(/\/frontend\/.*$/, '') || '';
    const p = path.startsWith('/') ? path : '/' + path;
    const full = base ? (base.replace(/\/$/, '') + p) : p;
    return full || path;
  }

  // Admin'de yüklenen videoyu PHP üzerinden oynat (404 önler)
  function streamVideoUrl(videoPath) {
    if (!videoPath) return '';
    const base = DATA.base_path || document.location.pathname.replace(/\/frontend\/.*$/, '') || '';
    const name = videoPath.split('/').pop() || videoPath.replace(/^.*[\\/]/, '');
    const stream = (base ? base.replace(/\/$/, '') : '') + '/frontend/video.php?f=' + encodeURIComponent(name);
    return stream;
  }

  function updateVideoDebug(video, fullSrc, videoEl) {
    const panel = $('#videoDebugPanel');
    const pre = $('#videoDebugContent');
    const link = $('#videoDebugLink');
    if (!panel || !pre) return;
    const lines = [];
    lines.push('base_path (API): ' + JSON.stringify(DATA.base_path));
    lines.push('video_url (DB):  ' + JSON.stringify(video.video_url || '(yok)'));
    lines.push('Tam URL:         ' + (fullSrc || '(yok)'));
    lines.push('Durum:           yükleniyor...');
    pre.textContent = lines.join('\n');
    link.href = fullSrc ? (fullSrc.startsWith('http') ? fullSrc : (window.location.origin + fullSrc)) : '#';
    link.style.display = fullSrc ? '' : 'none';
    if (!fullSrc) {
      lines[3] = 'Durum:           Bu videoda video_url yok (admin panelden video yükleyin).';
      pre.textContent = lines.join('\n');
      panel.classList.add('visible');
      return;
    }
    var debugStatus = 'yükleniyor...';
    function setStatus(s) {
      debugStatus = s;
      lines[3] = 'Durum:           ' + s;
      pre.textContent = lines.join('\n');
    }
    videoEl.onerror = function () {
      var msg = 'Video elementi hata verdi. ';
      if (videoEl.error) {
        if (videoEl.error.code === 2) msg += 'MEDIA_ERR_NETWORK (ağ/404?).';
        else if (videoEl.error.code === 4) msg += 'MEDIA_ERR_SRC_NOT_SUPPORTED.';
        else msg += 'Code: ' + videoEl.error.code;
      }
      setStatus(msg);
    };
    videoEl.onloadeddata = function () {
      setStatus('OK — video yüklendi.');
    };
    videoEl.oncanplay = function () {
      setStatus('OK — oynatılabilir.');
    };
    fetch(fullSrc, { method: 'HEAD' })
      .then(function (r) {
        setStatus('HTTP ' + r.status + (r.status === 200 ? ' — dosya var.' : ' — dosya bulunamadı veya erişilemiyor (404?).'));
      })
      .catch(function (e) {
        setStatus('İstek hatası: ' + (e.message || e));
      });
    panel.classList.add('visible');
  }

  // ─── Detail view ─────────────────────────────────────
  function openDetail(id) {
    const video = DATA.videos.find(v => v.id === id);
    if (!video) return;

    const [c1, c2] = getCardColors(video, 0);
    const media = $('#detailMedia');
    media.style.background = `linear-gradient(135deg,${c1},${c2})`;
    const img = $('#detailImage');
    const videoEl = $('#detailVideo');
    const overlay = $('#detailPlayOverlay');
    img.src = video.thumbnail_url ? fullVideoUrl(video.thumbnail_url) : '';
    img.alt = video.title;

    if (video.video_url) {
      const src = streamVideoUrl(video.video_url);
      videoEl.src = src;
      videoEl.poster = video.thumbnail_url ? fullVideoUrl(video.thumbnail_url) : '';
      videoEl.classList.add('visible');
      img.classList.remove('visible');
      overlay.classList.add('visible');
      overlay.onclick = function () {
        videoEl.play().catch(() => {});
        overlay.classList.remove('visible');
      };
      videoEl.onplay = function () { overlay.classList.remove('visible'); };
      videoEl.onpause = function () { overlay.classList.add('visible'); };
      videoEl.onended = function () { overlay.classList.add('visible'); };
      updateVideoDebug(video, src, videoEl);
    } else {
      videoEl.src = '';
      videoEl.classList.remove('visible');
      img.classList.add('visible');
      overlay.classList.add('visible');
      overlay.onclick = null;
      updateVideoDebug(video, '', videoEl);
    }
    img.classList.toggle('visible', !video.video_url);

    $('#detailTitle').textContent = video.title;
    $('#detailDesc').textContent = video.description || '—';
    $('#detailMap').textContent = categoryNamesByIds(video.category_ids);
    $('#detailAgent').textContent = categoryNamesByIds(video.category_ids);
    $('#detailCategories').textContent = categoryNamesByIds(video.category_ids);

    const badges = $('#detailBadges');
    badges.innerHTML = '<span class="badge difficulty-badge">Lineup</span>';

    const stepsBlock = $('#detailStepsContent');
    if (video.description) {
      stepsBlock.innerHTML = '<p class="detail-desc">' + escapeHtml(video.description) + '</p>';
    } else {
      stepsBlock.innerHTML = '<p class="detail-desc">—</p>';
    }

    const favBtn = $('#favBtn');
    const isFav = favorites.includes(String(video.id));
    favBtn.classList.toggle('is-fav', isFav);
    favBtn.querySelector('svg').style.fill = isFav ? 'var(--accent)' : 'none';
    favBtn.onclick = () => toggleFav(video.id);

    $('#copyLinkBtn').onclick = () => {
      const url = window.location.href.split('#')[0] + '#video/' + (video.slug || video.id);
      navigator.clipboard.writeText(url).then(() => showToast('Link kopyalandı!'));
    };

    gridView.classList.add('hidden');
    detailView.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closeDetail() {
    const videoEl = $('#detailVideo');
    if (videoEl) {
      videoEl.pause();
      videoEl.removeAttribute('src');
    }
    detailView.classList.add('hidden');
    gridView.classList.remove('hidden');
  }
  $('#backBtn').addEventListener('click', closeDetail);

  function toggleFav(id) {
    const sid = String(id);
    const idx = favorites.indexOf(sid);
    if (idx > -1) favorites.splice(idx, 1);
    else favorites.push(sid);
    localStorage.setItem('taclineup_favs', JSON.stringify(favorites));
    const favBtn = $('#favBtn');
    const isFav = favorites.includes(sid);
    favBtn.classList.toggle('is-fav', isFav);
    favBtn.querySelector('svg').style.fill = isFav ? 'var(--accent)' : 'none';
    showToast(isFav ? 'Favorilere eklendi!' : 'Favorilerden çıkarıldı');
  }

  function showToast(msg) {
    const toast = $('#toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2000);
  }

  // ─── Search & keyboard ───────────────────────────────
  searchInput.addEventListener('input', () => renderCards());
  document.addEventListener('keydown', e => {
    if (e.key === '/' && document.activeElement !== searchInput) {
      e.preventDefault();
      searchInput.focus();
    }
    if (e.key === 'Escape') {
      searchInput.blur();
      if (!detailView.classList.contains('hidden')) closeDetail();
    }
  });

  // ─── Mobile sidebar ───────────────────────────────────
  mobileToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== mobileToggle && !mobileToggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });

  // ─── Init ────────────────────────────────────────────
  loadData();
})();
