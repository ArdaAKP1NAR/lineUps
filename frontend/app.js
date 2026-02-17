/* ========================================================
   TacLineup — App Logic
   Game switching, filter system, card rendering, detail view
   ======================================================== */

; (function () {
    'use strict';

    // ─── DATA ──────────────────────────────────────────────
    const LINEUPS = {
        valorant: [
            { id: 'v1', title: 'A-Site Heaven Smoke', desc: 'Block off Heaven sightline on A-site for a safe plant.', agent: 'Brimstone', agentIcon: '🎯', map: 'Bind', ability: 'Smoke', difficulty: 'Easy', technique: 'Standing', side: 'Attack', rating: 4.5, votes: 203, pro: false },
            { id: 'v2', title: 'B-Long Flash Entry', desc: 'Pop-flash B-Long to blind defenders holding the angle.', agent: 'Phoenix', agentIcon: '🔥', map: 'Bind', ability: 'Flash', difficulty: 'Easy', technique: 'Running', side: 'Attack', rating: 4.1, votes: 98, pro: false },
            { id: 'v3', title: 'Mid Doors Recon Bolt', desc: 'Reveal all enemies pushing through Mid from attacker spawn.', agent: 'Sova', agentIcon: '🏹', map: 'Ascent', ability: 'Recon', difficulty: 'Medium', technique: 'Standing', side: 'Defense', rating: 4.8, votes: 312, pro: true },
            { id: 'v4', title: 'A-Main Toxic Screen', desc: 'Wall off A-Main to force enemies through a narrow gap.', agent: 'Viper', agentIcon: '🧪', map: 'Haven', ability: 'Smoke', difficulty: 'Medium', technique: 'Standing', side: 'Defense', rating: 4.3, votes: 145, pro: false },
            { id: 'v5', title: 'C-Long Molly Deny', desc: 'Post-plant molly to deny defuse from C-Long position.', agent: 'Brimstone', agentIcon: '🎯', map: 'Haven', ability: 'Molly', difficulty: 'Pro', technique: 'Jump-throw', side: 'Attack', rating: 4.9, votes: 421, pro: true },
            { id: 'v6', title: 'Hookah Flash Clear', desc: 'Curveball into Hookah to clear close angles quickly.', agent: 'Phoenix', agentIcon: '🔥', map: 'Bind', ability: 'Flash', difficulty: 'Easy', technique: 'Standing', side: 'Attack', rating: 3.9, votes: 67, pro: false },
            { id: 'v7', title: 'B-Main Fake Smoke', desc: 'Throw decoy smoke on B to rotate enemies while hitting A.', agent: 'Omen', agentIcon: '👤', map: 'Ascent', ability: 'Smoke', difficulty: 'Medium', technique: 'Standing', side: 'Attack', rating: 4.0, votes: 88, pro: false },
            { id: 'v8', title: 'Knife Point Recon Dart', desc: 'Reveal B-site from Mid-Courtyard for a fast B execute.', agent: 'Sova', agentIcon: '🏹', map: 'Ascent', ability: 'Recon', difficulty: 'Pro', technique: 'Jump-throw', side: 'Attack', rating: 4.7, votes: 256, pro: true },
            { id: 'v9', title: 'Raze Blast Pack Entry', desc: 'Satchel jump onto A-site boxes catching defenders off guard.', agent: 'Raze', agentIcon: '💣', map: 'Split', ability: 'Molly', difficulty: 'Pro', technique: 'Running', side: 'Attack', rating: 4.6, votes: 189, pro: true },
            { id: 'v10', title: 'KAY/O Suppression Flash', desc: 'Suppress defenders on B-site with a well-placed knife.', agent: 'KAY/O', agentIcon: '🤖', map: 'Icebox', ability: 'Flash', difficulty: 'Medium', technique: 'Standing', side: 'Attack', rating: 4.2, votes: 110, pro: false },
            { id: 'v11', title: 'Killjoy Turret Trick', desc: 'Place turret to detect flanks from A-Short reliably.', agent: 'Killjoy', agentIcon: '⚙️', map: 'Breeze', ability: 'Recon', difficulty: 'Easy', technique: 'Standing', side: 'Defense', rating: 4.0, votes: 74, pro: false },
            { id: 'v12', title: 'Cypher Cam Default', desc: 'Classic top-of-box camera covering the entire A-site.', agent: 'Cypher', agentIcon: '🎩', map: 'Fracture', ability: 'Recon', difficulty: 'Easy', technique: 'Standing', side: 'Defense', rating: 4.3, votes: 132, pro: false },
        ],
        cs2: [
            { id: 'c1', title: 'A-Site Default Smoke', desc: 'Standard smoke to block CT vision on A-site.', agent: 'Smoke', agentIcon: '💨', map: 'Mirage', ability: 'Smoke', difficulty: 'Easy', technique: 'Standing', side: 'Attack', rating: 4.4, votes: 534, pro: false },
            { id: 'c2', title: 'Jungle Smoke from T-Spawn', desc: 'Smoke Jungle to isolate connector rotations.', agent: 'Smoke', agentIcon: '💨', map: 'Mirage', ability: 'Smoke', difficulty: 'Medium', technique: 'Jump-throw', side: 'Attack', rating: 4.7, votes: 389, pro: true },
            { id: 'c3', title: 'B Apartments Flash', desc: 'Pop-flash into B Apartments for a quick entry.', agent: 'Flash', agentIcon: '⚡', map: 'Mirage', ability: 'Flash', difficulty: 'Easy', technique: 'Running', side: 'Attack', rating: 4.2, votes: 201, pro: false },
            { id: 'c4', title: 'Banana Molotov', desc: 'Deny banana push with a deep molotov from CT spawn.', agent: 'Molotov', agentIcon: '🔥', map: 'Inferno', ability: 'Molotov', difficulty: 'Easy', technique: 'Standing', side: 'Defense', rating: 4.6, votes: 445, pro: false },
            { id: 'c5', title: 'Long A Flash Pop', desc: 'Entry flash for Long A to blind pit and car positions.', agent: 'Flash', agentIcon: '⚡', map: 'Dust2', ability: 'Flash', difficulty: 'Medium', technique: 'Running', side: 'Attack', rating: 4.3, votes: 278, pro: false },
            { id: 'c6', title: 'Xbox Smoke from T-Spawn', desc: 'Consistent Xbox smoke thrown from T-Spawn.', agent: 'Smoke', agentIcon: '💨', map: 'Dust2', ability: 'Smoke', difficulty: 'Easy', technique: 'Jump-throw', side: 'Attack', rating: 4.8, votes: 612, pro: true },
            { id: 'c7', title: 'Window HE Grenade', desc: 'Deal damage to AWPer holding window from outside.', agent: 'HE', agentIcon: '💥', map: 'Mirage', ability: 'HE', difficulty: 'Medium', technique: 'Standing', side: 'Attack', rating: 3.8, votes: 95, pro: false },
            { id: 'c8', title: 'Ramp Smoke', desc: 'Consistent ramp smoke to block CTs from seeing ramp.', agent: 'Smoke', agentIcon: '💨', map: 'Nuke', ability: 'Smoke', difficulty: 'Pro', technique: 'Jump-throw', side: 'Attack', rating: 4.5, votes: 167, pro: true },
            { id: 'c9', title: 'Inferno Arch Flash', desc: 'Flash Arch side from Alt-Mid to support A-site takes.', agent: 'Flash', agentIcon: '⚡', map: 'Inferno', ability: 'Flash', difficulty: 'Medium', technique: 'Running', side: 'Attack', rating: 4.1, votes: 143, pro: false },
            { id: 'c10', title: 'Pit Molotov A-Site', desc: 'Molotov pit position to deny AWP angle on A-site.', agent: 'Molotov', agentIcon: '🔥', map: 'Inferno', ability: 'Molotov', difficulty: 'Easy', technique: 'Standing', side: 'Attack', rating: 4.4, votes: 312, pro: false },
            { id: 'c11', title: 'B-Site HE Stack', desc: 'Double HE combo onto B-site plant position for max damage.', agent: 'HE', agentIcon: '💥', map: 'Overpass', ability: 'HE', difficulty: 'Pro', technique: 'Standing', side: 'Defense', rating: 4.0, votes: 78, pro: false },
            { id: 'c12', title: 'CT Smoke Retake', desc: 'Quick CT smoke for retaking A-site from Short.', agent: 'Smoke', agentIcon: '💨', map: 'Anubis', ability: 'Smoke', difficulty: 'Medium', technique: 'Standing', side: 'Defense', rating: 4.2, votes: 102, pro: false },
        ]
    };

    // ─── Card thumbnail gradient palettes ────────────────
    const MAP_COLORS = {
        Bind: ['#3a1f32', '#5e2744'],
        Ascent: ['#1f2d3a', '#2d4a5e'],
        Haven: ['#2a3a1f', '#3d5e2d'],
        Split: ['#3a2e1f', '#5e4a2d'],
        Icebox: ['#1f2f3a', '#2d5a6e'],
        Breeze: ['#1f3a35', '#2d6e5e'],
        Fracture: ['#2d1f3a', '#4a2d6e'],
        Lotus: ['#3a1f2d', '#6e2d4e'],
        Sunset: ['#3a291f', '#6e4a2d'],
        Mirage: ['#3a351f', '#5e552d'],
        Inferno: ['#3a1f1f', '#5e2d2d'],
        Nuke: ['#1f3a2a', '#2d5e3d'],
        Dust2: ['#3a331f', '#5e4e2d'],
        Overpass: ['#1f2d3a', '#2d475e'],
        Vertigo: ['#2a2a3a', '#3d3d5e'],
        Anubis: ['#3a2d1f', '#5e3d2d'],
        Ancient: ['#1f3a28', '#2d5e3a'],
    };

    // ─── STATE ────────────────────────────────────────────
    let currentGame = 'valorant';
    let activeFilters = { agent: null, map: null, ability: null };
    let favorites = JSON.parse(localStorage.getItem('taclineup_favs') || '[]');

    // ─── DOM REFS ─────────────────────────────────────────
    const $ = (s, p) => (p || document).querySelector(s);
    const $$ = (s, p) => [...(p || document).querySelectorAll(s)];

    const gridView = $('#gridView');
    const detailView = $('#detailView');
    const lineupGrid = $('#lineupGrid');
    const contentTitle = $('#contentTitle');
    const resultCount = $('#resultCount');
    const searchInput = $('#searchInput');
    const sidebar = $('#sidebar');
    const mobileToggle = $('#mobileFilterToggle');

    // ─── GAME SWITCHING ───────────────────────────────────
    $$('.game-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            $$('.game-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentGame = tab.dataset.game;
            document.body.classList.toggle('cs2-mode', currentGame === 'cs2');

            // Toggle filter panels
            $('#valFilters').classList.toggle('hidden', currentGame !== 'valorant');
            $('#cs2Filters').classList.toggle('hidden', currentGame !== 'cs2');

            // Reset filters
            resetFilters();
            renderCards();
        });
    });

    // ─── FILTER LOGIC ─────────────────────────────────────
    function bindChips(selector, filterKey) {
        $$(selector).forEach(chip => {
            chip.addEventListener('click', () => {
                const val = chip.dataset[filterKey];
                if (chip.classList.contains('active')) {
                    chip.classList.remove('active');
                    activeFilters[filterKey] = null;
                } else {
                    $$(selector).forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    activeFilters[filterKey] = val;
                }
                renderCards();
            });
        });
    }

    bindChips('.agent-chip', 'agent');
    bindChips('.map-chip', 'map');
    bindChips('.ability-chip', 'ability');

    $('#clearFilters').addEventListener('click', () => { resetFilters(); renderCards(); });

    function resetFilters() {
        activeFilters = { agent: null, map: null, ability: null };
        $$('.agent-chip, .map-chip, .ability-chip').forEach(c => c.classList.remove('active'));
        searchInput.value = '';
    }

    // ─── SEARCH ───────────────────────────────────────────
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

    // ─── RENDER CARDS ─────────────────────────────────────
    function getFiltered() {
        let items = LINEUPS[currentGame];
        const q = searchInput.value.toLowerCase().trim();
        if (q) items = items.filter(i =>
            i.title.toLowerCase().includes(q) ||
            i.agent.toLowerCase().includes(q) ||
            i.map.toLowerCase().includes(q) ||
            i.ability.toLowerCase().includes(q)
        );
        if (activeFilters.agent) items = items.filter(i => i.agent === activeFilters.agent);
        if (activeFilters.map) items = items.filter(i => i.map === activeFilters.map);
        if (activeFilters.ability) items = items.filter(i => i.ability === activeFilters.ability);
        return items;
    }

    function renderCards() {
        const items = getFiltered();
        contentTitle.textContent = currentGame === 'valorant' ? 'Valorant Lineups' : 'CS2 Lineups';
        resultCount.textContent = `${items.length} lineup${items.length !== 1 ? 's' : ''} found`;

        lineupGrid.innerHTML = items.map((item, idx) => {
            const colors = MAP_COLORS[item.map] || ['#1f1f3a', '#3a3a5e'];
            const diffClass = item.difficulty === 'Easy' ? 'diff-easy' : item.difficulty === 'Medium' ? 'diff-medium' : 'diff-pro';
            const badgeClass = currentGame === 'valorant' ? 'val-badge' : 'cs2-badge';
            const isFav = favorites.includes(item.id);
            return `
        <div class="lineup-card" data-id="${item.id}" style="animation-delay:${idx * .04}s">
          <div class="card-thumb" style="background:linear-gradient(135deg,${colors[0]},${colors[1]})">
            <div class="card-overlay"></div>
            <span class="card-game-badge ${badgeClass}">${currentGame === 'valorant' ? 'VAL' : 'CS2'}</span>
          </div>
          <div class="card-body">
            <div class="card-top-row">
              <span class="card-agent"><span class="card-agent-icon">${item.agentIcon}</span> ${item.agent}</span>
              <span class="card-difficulty ${diffClass}">${item.difficulty}</span>
            </div>
            <div class="card-title">${item.title}</div>
            <div class="card-desc">${item.desc}</div>
            <div class="card-footer">
              <span class="card-map">${item.map}</span>
              ${item.pro ? '<span class="card-pro-tag">⭐ Pro</span>' : ''}
              <span class="card-rating">★ ${item.rating}</span>
            </div>
          </div>
        </div>`;
        }).join('');

        // Bind card clicks
        $$('.lineup-card').forEach(card => {
            card.addEventListener('click', () => openDetail(card.dataset.id));
        });
    }

    // ─── DETAIL VIEW ──────────────────────────────────────
    function openDetail(id) {
        const pool = LINEUPS[currentGame];
        const item = pool.find(i => i.id === id);
        if (!item) return;

        const colors = MAP_COLORS[item.map] || ['#1f1f3a', '#3a3a5e'];
        const img = $('#detailImage');
        img.src = '';
        img.alt = item.title;
        $('#detailMedia').style.background = `linear-gradient(135deg,${colors[0]},${colors[1]})`;

        $('#detailTitle').textContent = item.title;
        $('#detailDesc').textContent = item.desc;
        $('#detailMap').textContent = item.map;
        $('#detailAgent').textContent = `${item.agentIcon} ${item.agent}`;
        $('#detailTechnique').textContent = item.technique;
        $('#detailSide').textContent = item.side;

        // Badges
        const diffBadge = $('#detailBadges .difficulty-badge');
        diffBadge.textContent = item.difficulty;
        diffBadge.className = 'badge difficulty-badge';
        if (item.difficulty === 'Easy') diffBadge.style.cssText = 'background:rgba(62,207,142,.15);color:#3ecf8e';
        else if (item.difficulty === 'Medium') diffBadge.style.cssText = 'background:rgba(245,197,66,.15);color:#f5c542';
        else diffBadge.style.cssText = 'background:rgba(255,70,85,.15);color:#ff4655';

        $('#detailBadges .pro-badge').classList.toggle('hidden', !item.pro);

        // Rating
        $$('#starRating .star').forEach(star => {
            const v = parseInt(star.dataset.star);
            star.classList.toggle('filled', v <= Math.round(item.rating));
        });
        $('.rating-text').textContent = `${item.rating} / 5 — ${item.votes} votes`;

        // Fav button
        const favBtn = $('#favBtn');
        const isFav = favorites.includes(item.id);
        favBtn.classList.toggle('is-fav', isFav);
        favBtn.querySelector('svg').style.fill = isFav ? 'var(--accent)' : 'none';
        favBtn.onclick = () => toggleFav(item.id);

        // Copy crosshair
        $('#copyCrosshairBtn').onclick = () => {
            navigator.clipboard.writeText(`crosshair:${item.map}:${item.title}`).then(() => showToast('Crosshair position copied!'));
        };

        // Show
        gridView.classList.add('hidden');
        detailView.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function closeDetail() {
        detailView.classList.add('hidden');
        gridView.classList.remove('hidden');
    }
    $('#backBtn').addEventListener('click', closeDetail);

    // ─── FAVORITES ────────────────────────────────────────
    function toggleFav(id) {
        const idx = favorites.indexOf(id);
        if (idx > -1) favorites.splice(idx, 1);
        else favorites.push(id);
        localStorage.setItem('taclineup_favs', JSON.stringify(favorites));
        const favBtn = $('#favBtn');
        const isFav = favorites.includes(id);
        favBtn.classList.toggle('is-fav', isFav);
        favBtn.querySelector('svg').style.fill = isFav ? 'var(--accent)' : 'none';
        showToast(isFav ? 'Added to favorites!' : 'Removed from favorites');
    }

    // ─── TOAST ────────────────────────────────────────────
    function showToast(msg) {
        const toast = $('#toast');
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2000);
    }

    // ─── MOBILE SIDEBAR ──────────────────────────────────
    mobileToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
        if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== mobileToggle && !mobileToggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });

    // ─── STAR HOVER ───────────────────────────────────────
    $$('#starRating .star').forEach(star => {
        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.dataset.star);
            $$('#starRating .star').forEach(s => {
                s.classList.toggle('filled', parseInt(s.dataset.star) <= val);
            });
        });
    });
    $('#starRating').addEventListener('mouseleave', () => {
        // restore original (just keep as-is for demo)
    });

    // ─── INIT ─────────────────────────────────────────────
    renderCards();

})();
