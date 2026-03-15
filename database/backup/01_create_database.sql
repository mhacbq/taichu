-- =====================================================
-- 太初命理系统 - 数据库创建脚本
-- 执行此脚本创建数据库
-- =====================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS taichu 
    DEFAULT CHARACTER SET utf8mb4 
    DEFAULT COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE taichu;

-- 设置时区
SET time_zone = '+08:00';
