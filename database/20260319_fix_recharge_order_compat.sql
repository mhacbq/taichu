-- 充值订单新旧字段兼容补丁
-- 用途：为 phpstudy / Navicat 已导入的旧 tc_recharge_order 表补齐后台统一口径所需字段，
-- 并把历史数据里的 pay_status / pay_type / transaction_id 回填到 status / payment_type / pay_order_no。

SET @schema_name := DATABASE();
SET @has_table := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
);

-- 1) 缺列时补齐新口径字段
SET @sql := IF(
    @has_table > 0 AND NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'tc_recharge_order'
          AND COLUMN_NAME = 'status'
    ),
    'ALTER TABLE `tc_recharge_order` ADD COLUMN `status` VARCHAR(20) DEFAULT ''pending'' COMMENT ''状态: pending/paid/cancelled/refunded/processing'' AFTER `points`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_table > 0 AND NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'tc_recharge_order'
          AND COLUMN_NAME = 'payment_type'
    ),
    'ALTER TABLE `tc_recharge_order` ADD COLUMN `payment_type` VARCHAR(30) DEFAULT ''wechat_jsapi'' COMMENT ''支付方式'' AFTER `status`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_table > 0 AND NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'tc_recharge_order'
          AND COLUMN_NAME = 'pay_order_no'
    ),
    'ALTER TABLE `tc_recharge_order` ADD COLUMN `pay_order_no` VARCHAR(100) DEFAULT '''' COMMENT ''支付平台订单号'' AFTER `payment_type`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) 重新探测字段存在性
SET @has_status := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'status'
);
SET @has_payment_type := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'payment_type'
);
SET @has_pay_order_no := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'pay_order_no'
);
SET @has_pay_status := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'pay_status'
);
SET @has_pay_type := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'pay_type'
);
SET @has_transaction_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'transaction_id'
);
SET @has_pay_time := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'pay_time'
);
SET @has_refund_time := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'refund_time'
);
SET @has_refund_no := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'refund_no'
);
SET @has_refund_amount := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tc_recharge_order'
      AND COLUMN_NAME = 'refund_amount'
);

-- 3) 先规范已有文本口径
SET @sql := IF(
    @has_table > 0 AND @has_status > 0,
    'UPDATE `tc_recharge_order` SET `status` = LOWER(TRIM(`status`)) WHERE `status` IS NOT NULL AND CHAR_LENGTH(TRIM(`status`)) > 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_table > 0 AND @has_payment_type > 0,
    'UPDATE `tc_recharge_order` SET `payment_type` = LOWER(TRIM(`payment_type`)) WHERE `payment_type` IS NOT NULL AND CHAR_LENGTH(TRIM(`payment_type`)) > 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) pay_status -> status
SET @sql := IF(
    @has_table > 0 AND @has_status > 0 AND @has_pay_status > 0,
    'UPDATE `tc_recharge_order` SET `status` = CASE `pay_status` WHEN 3 THEN ''refunded'' WHEN 2 THEN ''cancelled'' WHEN 1 THEN ''paid'' ELSE ''pending'' END WHERE CHAR_LENGTH(TRIM(COALESCE(`status`, ''''))) = 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5) 若仍为空，再按退款信号补状态
SET @refund_condition := '';
SET @refund_condition := CONCAT(
    @refund_condition,
    IF(@has_refund_time > 0, '`refund_time` IS NOT NULL', '')
);
SET @refund_condition := CONCAT(
    @refund_condition,
    IF(@has_refund_no > 0, CONCAT(IF(@refund_condition <> '', ' OR ', ''), 'CHAR_LENGTH(TRIM(COALESCE(`refund_no`, ''''))) > 0'), '')
);
SET @refund_condition := CONCAT(
    @refund_condition,
    IF(@has_refund_amount > 0, CONCAT(IF(@refund_condition <> '', ' OR ', ''), '`refund_amount` > 0'), '')
);
SET @sql := IF(
    @has_table > 0 AND @has_status > 0 AND @refund_condition <> '',
    CONCAT(
        'UPDATE `tc_recharge_order` SET `status` = ''refunded'' WHERE CHAR_LENGTH(TRIM(COALESCE(`status`, ''''))) = 0 AND (',
        @refund_condition,
        ')'
    ),
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6) 若仍为空，再按支付完成信号补状态
SET @pay_condition := '';
SET @pay_condition := CONCAT(
    @pay_condition,
    IF(@has_pay_time > 0, '`pay_time` IS NOT NULL', '')
);
SET @pay_condition := CONCAT(
    @pay_condition,
    IF(@has_pay_order_no > 0, CONCAT(IF(@pay_condition <> '', ' OR ', ''), 'CHAR_LENGTH(TRIM(COALESCE(`pay_order_no`, ''''))) > 0'), '')
);
SET @pay_condition := CONCAT(
    @pay_condition,
    IF(@has_transaction_id > 0, CONCAT(IF(@pay_condition <> '', ' OR ', ''), 'CHAR_LENGTH(TRIM(COALESCE(`transaction_id`, ''''))) > 0'), '')
);
SET @sql := IF(
    @has_table > 0 AND @has_status > 0 AND @pay_condition <> '',
    CONCAT(
        'UPDATE `tc_recharge_order` SET `status` = ''paid'' WHERE CHAR_LENGTH(TRIM(COALESCE(`status`, ''''))) = 0 AND (',
        @pay_condition,
        ')'
    ),
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7) 最后把仍为空的状态兜底为 pending
SET @sql := IF(
    @has_table > 0 AND @has_status > 0,
    'UPDATE `tc_recharge_order` SET `status` = ''pending'' WHERE CHAR_LENGTH(TRIM(COALESCE(`status`, ''''))) = 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8) pay_type -> payment_type
SET @sql := IF(
    @has_table > 0 AND @has_payment_type > 0 AND @has_pay_type > 0,
    'UPDATE `tc_recharge_order` SET `payment_type` = CASE WHEN LOWER(TRIM(COALESCE(`pay_type`, ''''))) = ''alipay'' THEN ''alipay'' WHEN LOWER(TRIM(COALESCE(`pay_type`, ''''))) = ''wechat_jsapi'' THEN ''wechat_jsapi'' WHEN LOWER(TRIM(COALESCE(`pay_type`, ''''))) IN (''wechat'', ''weixin'', ''wxpay'') THEN ''wechat'' ELSE `payment_type` END WHERE CHAR_LENGTH(TRIM(COALESCE(`payment_type`, ''''))) = 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 9) transaction_id -> pay_order_no
SET @sql := IF(
    @has_table > 0 AND @has_pay_order_no > 0 AND @has_transaction_id > 0,
    'UPDATE `tc_recharge_order` SET `pay_order_no` = TRIM(COALESCE(`transaction_id`, '''')) WHERE CHAR_LENGTH(TRIM(COALESCE(`pay_order_no`, ''''))) = 0 AND CHAR_LENGTH(TRIM(COALESCE(`transaction_id`, ''''))) > 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
