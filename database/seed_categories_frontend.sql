-- Frontend navbar (üstte 2 sekme) ve game_content (sidebar: Agent, Map, Ability Type)
-- 2. ve 3. fotoğraftaki yapı için örnek kategoriler. İstediğiniz gibi düzenleyebilirsiniz.

SET NAMES utf8mb4;

-- Navbar: Sadece ilk 2 kayıt üstte "Valorant | CS2" sekmeleri olarak görünür
INSERT INTO `categories` (`parent_id`, `name`, `type`, `order_index`, `icon`, `slug`, `is_active`) VALUES
(NULL, 'Valorant', 'navbar', 0, NULL, 'valorant', 1),
(NULL, 'CS2', 'navbar', 1, NULL, 'cs2', 1);

-- Game content üst kategorileri (sidebar başlıkları: AGENT, MAP, ABILITY TYPE)
INSERT INTO `categories` (`parent_id`, `name`, `type`, `order_index`, `icon`, `slug`, `is_active`) VALUES
(NULL, 'Agent', 'game_content', 0, NULL, 'agent', 1),
(NULL, 'Map', 'game_content', 1, NULL, 'map', 1),
(NULL, 'Ability Type', 'game_content', 2, NULL, 'ability-type', 1);

-- Agent alt kategorileri (sidebar'da ikonlu chip olarak)
SET @agent_id = (SELECT id FROM categories WHERE slug = 'agent' AND type = 'game_content' LIMIT 1);
INSERT INTO `categories` (`parent_id`, `name`, `type`, `order_index`, `icon`, `slug`, `is_active`) VALUES
(@agent_id, 'Jett', 'game_content', 0, '🌪️', 'jett', 1),
(@agent_id, 'Sova', 'game_content', 1, '🏹', 'sova', 1),
(@agent_id, 'Viper', 'game_content', 2, '🧪', 'viper', 1),
(@agent_id, 'Brimstone', 'game_content', 3, '🎯', 'brimstone', 1),
(@agent_id, 'Omen', 'game_content', 4, '👤', 'omen', 1),
(@agent_id, 'KAY/O', 'game_content', 5, '🤖', 'kayo', 1),
(@agent_id, 'Killjoy', 'game_content', 6, '⚙️', 'killjoy', 1),
(@agent_id, 'Cypher', 'game_content', 7, '🎩', 'cypher', 1),
(@agent_id, 'Phoenix', 'game_content', 8, '🔥', 'phoenix', 1),
(@agent_id, 'Raze', 'game_content', 9, '💣', 'raze', 1);

-- Map alt kategorileri (sidebar'da map-chip olarak)
SET @map_id = (SELECT id FROM categories WHERE slug = 'map' AND type = 'game_content' LIMIT 1);
INSERT INTO `categories` (`parent_id`, `name`, `type`, `order_index`, `icon`, `slug`, `is_active`) VALUES
(@map_id, 'Bind', 'game_content', 0, NULL, 'bind', 1),
(@map_id, 'Ascent', 'game_content', 1, NULL, 'ascent', 1),
(@map_id, 'Haven', 'game_content', 2, NULL, 'haven', 1),
(@map_id, 'Split', 'game_content', 3, NULL, 'split', 1),
(@map_id, 'Icebox', 'game_content', 4, NULL, 'icebox', 1),
(@map_id, 'Breeze', 'game_content', 5, NULL, 'breeze', 1),
(@map_id, 'Fracture', 'game_content', 6, NULL, 'fracture', 1),
(@map_id, 'Lotus', 'game_content', 7, NULL, 'lotus', 1),
(@map_id, 'Sunset', 'game_content', 8, NULL, 'sunset', 1);

-- Ability Type alt kategorileri (sidebar'da ability-chip olarak)
SET @ability_id = (SELECT id FROM categories WHERE slug = 'ability-type' AND type = 'game_content' LIMIT 1);
INSERT INTO `categories` (`parent_id`, `name`, `type`, `order_index`, `icon`, `slug`, `is_active`) VALUES
(@ability_id, 'Smoke', 'game_content', 0, '💨', 'smoke', 1),
(@ability_id, 'Molly', 'game_content', 1, '🔥', 'molly', 1),
(@ability_id, 'Flash', 'game_content', 2, '⚡', 'flash', 1),
(@ability_id, 'Recon', 'game_content', 3, '📡', 'recon', 1);
