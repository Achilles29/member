SET @db_name = DATABASE();

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE mst_product ADD COLUMN online_food_price DECIMAL(18,2) NULL DEFAULT NULL AFTER selling_price',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'mst_product'
    AND COLUMN_NAME = 'online_food_price'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE mst_product ADD COLUMN show_online_food TINYINT(1) NOT NULL DEFAULT 0 AFTER show_member',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'mst_product'
    AND COLUMN_NAME = 'show_online_food'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE mst_product ADD INDEX idx_mst_product_online_food (show_online_food)',
    'SELECT 1'
  )
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'mst_product'
    AND INDEX_NAME = 'idx_mst_product_online_food'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE mst_product
SET online_food_price = selling_price
WHERE online_food_price IS NULL;

UPDATE mst_product
SET show_online_food = show_pos
WHERE show_online_food = 0;
