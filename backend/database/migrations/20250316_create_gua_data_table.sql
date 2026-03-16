-- 六十四卦数据表（卦辞、彖辞、象辞）
CREATE TABLE IF NOT EXISTS `gua_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `gua_name` varchar(20) NOT NULL DEFAULT '' COMMENT '卦名',
  `gua_code` varchar(6) NOT NULL DEFAULT '' COMMENT '卦象代码（6位：0阴1阳，从下到上）',
  `gua_xuhao` tinyint(4) NOT NULL DEFAULT '0' COMMENT '卦序号（1-64）',
  `shang_gua` varchar(20) NOT NULL DEFAULT '' COMMENT '上卦（八卦名）',
  `xia_gua` varchar(20) NOT NULL DEFAULT '' COMMENT '下卦（八卦名）',
  
  -- 卦辞
  `gua_ci` text COMMENT '卦辞',
  `gua_ci_meaning` text COMMENT '卦辞解释',
  
  -- 彖辞
  `tuan_ci` text COMMENT '彖辞',
  `tuan_ci_meaning` text COMMENT '彖辞解释',
  
  -- 大象辞
  `da_xiang` text COMMENT '大象辞',
  `da_xiang_meaning` text COMMENT '大象辞解释',
  
  -- 卦象特征
  `wuxing` varchar(10) NOT NULL DEFAULT '' COMMENT '卦五行属性',
  `fangwei` varchar(20) NOT NULL DEFAULT '' COMMENT '方位',
  `jijie` varchar(50) NOT NULL DEFAULT '' COMMENT '季节',
  `caise` varchar(20) NOT NULL DEFAULT '' COMMENT '卦色',
  
  -- 总体解卦
  `general_meaning` text COMMENT '总体卦义',
  `fortune_overview` varchar(500) NOT NULL DEFAULT '' COMMENT '运势概述',
  
  -- 分类解读（JSON格式）
  `career` text COMMENT '事业财运解读',
  `love` text COMMENT '感情婚姻解读',
  `health` text COMMENT '健康解读',
  `study` text COMMENT '学业考试解读',
  `travel` text COMMENT '出行解读',
  `lawsuit` text COMMENT '官司诉讼解读',
  `lost` text COMMENT '失物寻找解读',
  
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gua_code` (`gua_code`),
  UNIQUE KEY `uk_gua_name` (`gua_name`),
  KEY `idx_xuhao` (`gua_xuhao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='六十四卦数据表';

-- 六爻爻辞表（每卦6爻）
CREATE TABLE IF NOT EXISTS `gua_yao_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gua_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '卦ID',
  `yao_position` tinyint(1) NOT NULL DEFAULT '1' COMMENT '爻位（1-6，从下到上）',
  `yao_type` varchar(10) NOT NULL DEFAULT '' COMMENT '爻类型：0老阴 1少阳 2少阴 3老阳',
  
  -- 爻辞
  `yao_ci` text COMMENT '爻辞',
  `yao_ci_meaning` text COMMENT '爻辞解释',
  
  -- 小象辞
  `xiao_xiang` text COMMENT '小象辞',
  `xiao_xiang_meaning` text COMMENT '小象辞解释',
  
  -- 断语
  `duan_yu` varchar(500) NOT NULL DEFAULT '' COMMENT '断语',
  `xiang_yue` varchar(500) NOT NULL DEFAULT '' COMMENT '象曰',
  
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gua_yao` (`gua_id`, `yao_position`),
  KEY `idx_gua_id` (`gua_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='六爻爻辞表';
