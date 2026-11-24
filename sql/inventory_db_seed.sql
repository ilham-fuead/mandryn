-- ============================================================================
-- inventory_db : seeding script
-- Part of the mandryn examples (mandryn/mandryn)
--
-- Seeds 6 categories, 4 warehouses and 40 sample items (SKU-10001 .. SKU-10040)
-- covering every status: ACTIVE, LOW_STOCK, OUT_OF_STOCK, DISCONTINUED.
--
-- Idempotent: re-running updates existing rows instead of duplicating them
-- (ON DUPLICATE KEY UPDATE).
--
-- Usage (run the generate script first):
--   mysql -h 127.0.0.1 -u root inventory_db < sql/inventory_db_seed.sql
-- ============================================================================

USE `inventory_db`;

-- ----------------------------------------------------------------------------
-- categories
-- ----------------------------------------------------------------------------
INSERT INTO `categories` (`category_id`, `category_code`, `category_name`) VALUES
  (1, 'ELEC', 'Electronics'),
  (2, 'OFFC', 'Office Supplies'),
  (3, 'HARD', 'Hardware & Tools'),
  (4, 'PACK', 'Packaging & Shipping'),
  (5, 'SAFT', 'Safety & PPE'),
  (6, 'APPL', 'Appliances & Facilities')
ON DUPLICATE KEY UPDATE
  `category_name` = VALUES(`category_name`);

-- ----------------------------------------------------------------------------
-- warehouses
-- ----------------------------------------------------------------------------
INSERT INTO `warehouses` (`warehouse_id`, `warehouse_code`, `warehouse_name`, `city`, `country`) VALUES
  (1, 'WH-CEN-01', 'Central Hub',               'Putrajaya',    'Malaysia'),
  (2, 'WH-KLG-02', 'Port Klang Distribution',   'Klang',        'Malaysia'),
  (3, 'WH-PNG-03', 'Northern Logistics Center', 'George Town',  'Malaysia'),
  (4, 'WH-JHR-04', 'Southern Fulfillment Center', 'Johor Bahru','Malaysia')
ON DUPLICATE KEY UPDATE
  `warehouse_name` = VALUES(`warehouse_name`),
  `city`           = VALUES(`city`),
  `country`        = VALUES(`country`);

-- ----------------------------------------------------------------------------
-- inventory_items (sku, name, category, warehouse, qty, reorder, cost, price, status, last restock)
-- ----------------------------------------------------------------------------
INSERT INTO `inventory_items`
  (`sku`, `item_name`, `category_id`, `warehouse_id`, `quantity_on_hand`, `reorder_level`, `unit_cost`, `unit_price`, `status`, `last_restocked_at`)
VALUES
  ('SKU-10001', 'Thermal Paper Roll 80x80 (50 pack)',    4, 1, 220,  60,  12.50,  24.90, 'ACTIVE',       '2026-11-20 09:00:00'),
  ('SKU-10002', 'Barcode Label Roll 40x25',              4, 2,  45,  50,   8.00,  15.50, 'LOW_STOCK',    '2026-11-10 09:30:00'),
  ('SKU-10003', 'Packing Tape 48mm (36 rolls)',          4, 3,   0,  20,  18.00,  32.00, 'OUT_OF_STOCK', NULL),
  ('SKU-10004', 'Bubble Wrap 50cm x 50m',                4, 4,  18,  15,  22.00,  45.00, 'ACTIVE',       '2026-11-18 10:00:00'),
  ('SKU-10005', 'Wireless Barcode Scanner',              1, 2,  12,  10,  85.00, 165.00, 'ACTIVE',       '2026-11-15 11:00:00'),
  ('SKU-10006', 'Label Printer ZT230',                   1, 1,   4,   5, 420.00, 750.00, 'LOW_STOCK',    '2026-11-05 14:20:00'),
  ('SKU-10007', 'POS Touch Terminal 15 inch',            1, 3,   0,   3, 950.00, 1650.00,'OUT_OF_STOCK', NULL),
  ('SKU-10008', 'Cash Drawer Metal',                     1, 4,   9,   8, 120.00, 220.00, 'ACTIVE',       '2026-11-12 09:45:00'),
  ('SKU-10009', 'USB-C Hub 7-in-1',                      1, 1,  60,  20,  35.00,  79.90, 'ACTIVE',       '2026-11-19 08:30:00'),
  ('SKU-10010', 'A4 Paper 80gsm (5 reams)',              2, 2, 300,  80,  52.00,  89.00, 'ACTIVE',       '2026-11-21 10:15:00'),
  ('SKU-10011', 'Stapler Heavy Duty',                    2, 1,   7,   8,  14.50,  29.90, 'LOW_STOCK',    '2026-11-08 16:00:00'),
  ('SKU-10012', 'Permanent Marker Set (12)',             2, 3, 150,  40,   9.80,  19.90, 'ACTIVE',       '2026-11-16 11:30:00'),
  ('SKU-10013', 'Whiteboard 4x6 ft',                     2, 4,   0,   4,  95.00, 189.00, 'OUT_OF_STOCK', NULL),
  ('SKU-10014', 'Filing Cabinet 4-Drawer',               2, 2,   6,   5, 210.00, 420.00, 'ACTIVE',       '2026-11-09 13:10:00'),
  ('SKU-10015', 'Cordless Drill 18V',                    3, 3,  22,  15, 180.00, 340.00, 'ACTIVE',       '2026-11-14 09:20:00'),
  ('SKU-10016', 'Impact Driver Kit',                     3, 1,   5,  10, 220.00, 420.00, 'LOW_STOCK',    '2026-11-11 15:40:00'),
  ('SKU-10017', 'Safety Helmet Yellow',                  5, 2, 120,  40,  15.00,  28.00, 'ACTIVE',       '2026-11-17 08:50:00'),
  ('SKU-10018', 'Nitrile Gloves L (100 pack)',           5, 3,   0,  50,  25.00,  45.00, 'OUT_OF_STOCK', NULL),
  ('SKU-10019', 'Safety Vest Class 2',                   5, 4,  30,  20,  12.00,  22.50, 'ACTIVE',       '2026-11-13 10:05:00'),
  ('SKU-10020', 'Fire Extinguisher 2kg ABC',             5, 1,   8,   6,  55.00,  98.00, 'ACTIVE',       '2026-11-07 14:00:00'),
  ('SKU-10021', 'Air Compressor 50L',                    3, 4,   0,   2, 480.00, 890.00, 'OUT_OF_STOCK', NULL),
  ('SKU-10022', 'Tool Cabinet 7-Drawer',                 3, 2,   4,   3, 560.00, 980.00, 'ACTIVE',       '2026-11-06 09:10:00'),
  ('SKU-10023', 'Aircon Unit 1.5HP',                     6, 1,   3,   5, 780.00, 1250.00,'LOW_STOCK',    '2026-11-04 12:30:00'),
  ('SKU-10024', 'Standing Fan 16 inch',                  6, 3,  40,  15,  65.00, 119.00, 'ACTIVE',       '2026-11-18 15:20:00'),
  ('SKU-10025', 'Microwave 20L',                         6, 2,   0,   4, 150.00, 280.00, 'OUT_OF_STOCK', NULL),
  ('SKU-10026', 'Paper Shredder 6-Sheet',                2, 1,  14,  12,  95.00, 189.00, 'ACTIVE',       '2026-11-15 08:40:00'),
  ('SKU-10027', 'Keyboard Wired 104-Key',                1, 4,  80,  25,  18.00,  39.90, 'ACTIVE',       '2026-11-20 11:50:00'),
  ('SKU-10028', 'Mouse Wireless 2.4G',                   1, 2,   3,  20,  12.00,  29.90, 'LOW_STOCK',    '2026-11-09 10:25:00'),
  ('SKU-10029', 'HDMI Cable 2m',                         1, 3, 200,  60,   6.50,  14.90, 'ACTIVE',       '2026-11-22 09:35:00'),
  ('SKU-10030', 'Corrugated Box 30x20x20 (25 pack)',     4, 1,  60,  40,  28.00,  55.00, 'ACTIVE',       '2026-11-19 14:45:00'),
  ('SKU-10031', 'Stretch Film 500m',                     4, 2,  25,  20,  19.00,  38.00, 'ACTIVE',       '2026-11-10 08:15:00'),
  ('SKU-10032', 'Steel Rack 5-Level',                    3, 3,   0,   2, 380.00, 720.00, 'OUT_OF_STOCK', NULL),
  ('SKU-10033', 'Ear Plugs (200 pack)',                  5, 4,  90,  30,  20.00,  42.00, 'ACTIVE',       '2026-11-21 13:05:00'),
  ('SKU-10034', 'Safety Boots S3 Size 9',                5, 1,  12,  15,  65.00, 130.00, 'LOW_STOCK',    '2026-11-08 11:20:00'),
  ('SKU-10035', 'First Aid Kit Wall Mount',              5, 2,   8,   6,  45.00,  85.00, 'ACTIVE',       '2026-11-13 16:30:00'),
  ('SKU-10036', 'Vacuum Cleaner Industrial',             6, 3,   5,   4, 340.00, 620.00, 'ACTIVE',       '2026-11-06 10:50:00'),
  ('SKU-10037', 'Kettle 1.8L',                           6, 4,   0,   6,  35.00,  69.00, 'OUT_OF_STOCK', NULL),
  ('SKU-10038', 'Envelope A4 (100 pack)',                4, 1, 250,  80,  14.00,  26.00, 'ACTIVE',       '2026-11-23 09:55:00'),
  ('SKU-10039', 'Ink Cartridge 664 Legacy',              2, 2,   2,   0,   0.00,   0.00, 'DISCONTINUED', '2026-01-05 10:00:00'),
  ('SKU-10040', 'Thermal Printer TTP-244 Legacy',        1, 3,   1,   0,   0.00,   0.00, 'DISCONTINUED', '2026-01-12 10:00:00')
ON DUPLICATE KEY UPDATE
  `item_name`         = VALUES(`item_name`),
  `category_id`       = VALUES(`category_id`),
  `warehouse_id`      = VALUES(`warehouse_id`),
  `quantity_on_hand`  = VALUES(`quantity_on_hand`),
  `reorder_level`     = VALUES(`reorder_level`),
  `unit_cost`         = VALUES(`unit_cost`),
  `unit_price`        = VALUES(`unit_price`),
  `status`            = VALUES(`status`),
  `last_restocked_at` = VALUES(`last_restocked_at`),
  `updated_at`        = CURRENT_TIMESTAMP();
