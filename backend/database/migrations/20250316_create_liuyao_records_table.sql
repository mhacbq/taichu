-- 六爻起卦记录表
CREATE TABLE IF NOT EXISTS `tc_liuyao_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `question` varchar(500) NOT NULL DEFAULT '' COMMENT '所问事项',
  `question_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '问事类型：1求财 2感情 3事业 4健康 5学业 6出行 7其他',
  
  -- 起卦信息
  `method` tinyint(1) NOT NULL DEFAULT '1' COMMENT '起卦方式：1时间起卦 2数字起卦 3手动摇卦',
  `input_number` varchar(20) NOT NULL DEFAULT '' COMMENT '数字起卦时输入的数字',
  `yao_result` varchar(20) NOT NULL DEFAULT '' COMMENT '六爻结果（6个数字：6老阴 7少阳 8少阴 9老阳）',
  `yao_code` varchar(6) NOT NULL DEFAULT '' COMMENT '爻码（0老阴 1少阳 2少阴 3老阳）',
  
  -- 卦象信息
  `main_gua_name` varchar(20) NOT NULL DEFAULT '' COMMENT '本卦名称',
  `main_gua_code` varchar(6) NOT NULL DEFAULT '' COMMENT '本卦卦象（6位：0阴1阳）',
  `bian_gua_name` varchar(20) NOT NULL DEFAULT '' COMMENT '变卦名称',
  `bian_gua_code` varchar(6) NOT NULL DEFAULT '' COMMENT '变卦卦象',
  `hu_gua_name` varchar(20) NOT NULL DEFAULT '' COMMENT '互卦名称',
  `hu_gua_code` varchar(6) NOT NULL DEFAULT '' COMMENT '互卦卦象',
  
  -- 六亲六神
  `liuqin` text COMMENT '六亲信息（JSON格式）',
  `liushen` text COMMENT '六神信息（JSON格式）',
  `shiying` varchar(50) NOT NULL DEFAULT '' COMMENT '世应位置',
  `yongshen` varchar(20) NOT NULL DEFAULT '' COMMENT '用神',
  
  -- 时间信息
  `liunian` varchar(20) NOT NULL DEFAULT '' COMMENT '流年干支',
  `yuejian` varchar(20) NOT NULL DEFAULT '' COMMENT '月建',
  `rigan` varchar(10) NOT NULL DEFAULT '' COMMENT '日干支',
  
  -- 解读信息
  `interpretation` text COMMENT '解读内容',
  `ai_interpretation` text COMMENT 'AI解读内容',
  
  -- 状态
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否收藏：0否 1是',
  `is_shared` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否分享：0否 1是',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0删除 1正常',
  
  -- 时间戳
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_question_type` (`question_type`),
  KEY `idx_main_gua` (`main_gua_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='六爻起卦记录表';
