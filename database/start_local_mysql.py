#!/usr/bin/env python3
"""
太初命理系统 - 本地 MySQL 服务（用于开发测试）
基于 mysql-mimic 实现 MySQL 协议兼容服务，数据存储在 SQLite

依赖安装:
    pip3 install mysql-mimic sqlparse

启动方式:
    python3 database/start_local_mysql.py          # 交互式（询问是否重建）
    python3 database/start_local_mysql.py --reinit  # 重新初始化数据库
    python3 database/start_local_mysql.py --no-reinit > /tmp/mysql.log 2>&1 &  # 后台启动

连接方式:
    mysql -h 127.0.0.1 -P 3306 -u root --password=
    或修改 backend/.env:
        DB_HOST = 127.0.0.1
        DB_PORT = 3306
        DB_USER = root
        DB_PASSWORD =
        DB_NAME = taichu
"""

import asyncio
import sqlite3
import os
import re
import sys
import logging
import argparse

try:
    import sqlparse
except ImportError:
    print("请先安装: pip3 install mysql-mimic sqlparse")
    sys.exit(1)

# 日志配置
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(levelname)s %(message)s',
    datefmt='%H:%M:%S'
)
log = logging.getLogger('taichu-mysql')

DB_FILE = '/tmp/taichu_dev.db'
DATABASE_DIR = os.path.dirname(os.path.abspath(__file__))

# SQL 导入顺序
SQL_FILES = [
    'backup/01_create_database.sql',
    'backup/02_create_tables.sql',
    'backup/03_insert_basic_data.sql',
    'backup/04_insert_test_data.sql',
    'backup/05_mingli_tables.sql',
    'backup/06_seo_tables.sql',
]


def transform_stmt(sql):
    """将单条 MySQL 语句转为 SQLite 兼容语法"""
    sql = sql.replace('`', '')
    # ON UPDATE CURRENT_TIMESTAMP
    sql = re.sub(r'\bON\s+UPDATE\s+\w+(?:\s*\(\s*\))?', '', sql, flags=re.IGNORECASE)
    # 列注释
    sql = re.sub(r"\bCOMMENT\s+'[^']*'", '', sql, flags=re.IGNORECASE)
    # 先去 UNSIGNED/ZEROFILL/AUTO_INCREMENT（加空格避免后续类型粘连）
    sql = re.sub(r'\s*\bUNSIGNED\b\s*', ' ', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\s*\bZEROFILL\b\s*', ' ', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\s*\bAUTO_INCREMENT\b\s*', ' ', sql, flags=re.IGNORECASE)
    # 整数类型（替换后补空格，防止粘连）
    for t in ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT']:
        sql = re.sub(
            rf'\b{t}\b(\s*(?:\(\d+\))?)',
            lambda m: 'INTEGER' + (' ' if not (m.group(1) or '').strip() else m.group(1)),
            sql, flags=re.IGNORECASE
        )
    sql = re.sub(
        r'\bINT\b(\s*(?:\(\d+\))?)',
        lambda m: 'INTEGER' + (' ' if not (m.group(1) or '').strip() else m.group(1)),
        sql, flags=re.IGNORECASE
    )
    # 浮点类型
    sql = re.sub(r'\bFLOAT\b\s*(?:\(\d+,\d+\))?', 'REAL ', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bDOUBLE\b\s*(?:\(\d+,\d+\))?', 'REAL ', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bDECIMAL\s*\(\d+,\d+\)', 'REAL', sql, flags=re.IGNORECASE)
    # 字符串类型
    sql = re.sub(r'\bVARCHAR\s*\(\d+\)', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bCHAR\s*\(\d+\)', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bLONGTEXT\b', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bMEDIUMTEXT\b', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bTINYTEXT\b', 'TEXT', sql, flags=re.IGNORECASE)
    # 时间类型
    sql = re.sub(r'\bDATETIME\b', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bTIMESTAMP\b', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bDATE\b', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bTIME\b', 'TEXT', sql, flags=re.IGNORECASE)
    # 其他
    sql = re.sub(r'\bJSON\b', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bENUM\s*\([^)]+\)', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bSET\s*\([^)]+\)', 'TEXT', sql, flags=re.IGNORECASE)
    # 清理多余空格（保留换行）
    sql = re.sub(r'[ \t]{2,}', ' ', sql)
    # 逐行移除 KEY/INDEX 定义（在 CREATE TABLE 内部）
    lines = sql.split('\n')
    out = []
    for line in lines:
        s = line.strip()
        if re.match(r'(UNIQUE\s+)?(KEY|INDEX)\s+\w+\s*\(', s, re.IGNORECASE):
            # 前一非空行去掉尾部逗号
            for j in range(len(out) - 1, -1, -1):
                if out[j].strip():
                    out[j] = out[j].rstrip().rstrip(',')
                    break
            continue
        out.append(line)
    sql = '\n'.join(out)
    # 表级选项（在括号外）
    sql = re.sub(r'\bENGINE\s*=\s*\w+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bDEFAULT\s+CHARSET\s*=\s*[\w_]+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bCHARSET\s*=\s*[\w_]+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bCOLLATE\s*(?:=\s*)?[\w_]+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bAUTO_INCREMENT\s*=\s*\d+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bROW_FORMAT\s*=\s*\w+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r"\bCOMMENT\s*=\s*'[^']*'", '', sql, flags=re.IGNORECASE)
    # 修复 CREATE TABLE 括号前多余逗号
    sql = re.sub(r',(\s*\n\s*\))', r'\1', sql)
    return sql.strip()


def replace_mysql_funcs(sql):
    """替换 MySQL 专有函数为 SQLite 等价"""
    sql = re.sub(
        r"DATE_ADD\s*\(\s*NOW\s*\(\s*\)\s*,\s*INTERVAL\s+(\d+)\s+(\w+)\s*\)",
        lambda m: f"datetime('now', '+{m.group(1)} {m.group(2).lower()}')",
        sql, flags=re.IGNORECASE
    )
    sql = re.sub(r'NOW\s*\(\s*\)', "datetime('now')", sql, flags=re.IGNORECASE)
    sql = re.sub(r'CURDATE\s*\(\s*\)', "date('now')", sql, flags=re.IGNORECASE)
    return sql


def should_skip(sql_upper):
    """判断是否跳过该语句"""
    if not sql_upper: return True
    if sql_upper.startswith('USE '): return True
    if re.match(r'SET\s+(NAMES|FOREIGN_KEY|UNIQUE_CHECKS|SQL_MODE|TIME_ZONE|CHARACTER|@)', sql_upper): return True
    if re.match(r'CREATE\s+(DATABASE|SCHEMA)\b', sql_upper): return True
    if re.match(r'CREATE\s+(PROCEDURE|FUNCTION|TRIGGER|EVENT)\b', sql_upper): return True
    return False


def load_sql_to_sqlite(db_path, sql_files, db_dir):
    """将 MySQL SQL 文件导入 SQLite"""
    conn = sqlite3.connect(db_path)
    conn.execute("PRAGMA foreign_keys=OFF")
    conn.execute("PRAGMA journal_mode=WAL")

    total_ok = total_err = 0

    for sql_file in sql_files:
        full_path = os.path.join(db_dir, sql_file)
        if not os.path.exists(full_path):
            log.warning(f"SQL 文件不存在，跳过: {sql_file}")
            continue

        log.info(f"导入: {sql_file}")
        content = open(full_path, encoding='utf-8').read()
        # 移除 DELIMITER 块（存储过程）
        content = re.sub(r'DELIMITER\s+//.*?DELIMITER\s+;', '', content, flags=re.DOTALL | re.IGNORECASE)

        stmts = sqlparse.split(content)
        file_ok = file_err = 0
        for raw_stmt in stmts:
            raw_stmt = raw_stmt.strip()
            if not raw_stmt: continue
            # 去注释行
            actual = '\n'.join(
                l for l in raw_stmt.split('\n') if not l.strip().startswith('--')
            ).strip()
            if not actual: continue
            au = actual.upper().lstrip()
            if should_skip(au): continue

            transformed = transform_stmt(actual)
            transformed = replace_mysql_funcs(transformed)
            if not transformed: continue

            try:
                conn.execute(transformed)
                file_ok += 1
                total_ok += 1
            except sqlite3.Error as e:
                file_err += 1
                total_err += 1
                log.debug(f"跳过: {e} | {transformed[:60]}")

        conn.commit()
        log.info(f"  OK={file_ok}, 跳过={file_err}")

    tables = conn.execute(
        "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
    ).fetchall()
    log.info(f"数据库就绪，共 {len(tables)} 张表")
    conn.close()
    return len(tables)


async def start_server():
    """启动 mysql-mimic 服务（MySQL 协议兼容）"""
    try:
        from mysql_mimic import MysqlServer, Session
    except ImportError:
        log.error("请先安装: pip3 install mysql-mimic")
        sys.exit(1)

    import sqlite3 as _sqlite3

    class SqliteSession(Session):
        """将 mysql-mimic 的查询转发到 SQLite"""

        def __init__(self):
            super().__init__()
            self._conn = _sqlite3.connect(DB_FILE, check_same_thread=False)
            self._conn.row_factory = _sqlite3.Row

        async def query(self, expression, sql, attrs):
            sql_upper = sql.strip().upper()

            # MySQL 特有命令处理
            if sql_upper.startswith('SET '):
                return [], []
            if sql_upper.startswith('SHOW DATABASES'):
                return [('taichu',)], [('Database', 'varchar(64)')]
            if sql_upper.startswith('SHOW FULL TABLES'):
                rows = self._conn.execute(
                    "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
                ).fetchall()
                return [(r[0], 'BASE TABLE') for r in rows], [
                    ('Tables_in_taichu', 'varchar(64)'), ('Table_type', 'varchar(64)')
                ]
            if sql_upper.startswith('SHOW TABLES'):
                rows = self._conn.execute(
                    "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
                ).fetchall()
                return [(r[0],) for r in rows], [('Tables_in_taichu', 'varchar(64)')]
            if 'SELECT DATABASE()' in sql_upper:
                return [('taichu',)], [('DATABASE()', 'varchar(64)')]
            if sql_upper.startswith('USE '):
                return [], []
            if 'VERSION()' in sql_upper:
                return [('8.0.35-sqlite',)], [('VERSION()', 'varchar(64)')]
            if sql_upper.startswith('SHOW VARIABLES'):
                return [], [('Variable_name', 'varchar(64)'), ('Value', 'varchar(256)')]
            if sql_upper.startswith('SHOW STATUS'):
                return [], [('Variable_name', 'varchar(64)'), ('Value', 'varchar(256)')]
            if sql_upper.startswith('SHOW CREATE TABLE'):
                table = sql.split()[-1].strip('`;')
                schema = self._conn.execute(
                    "SELECT sql FROM sqlite_master WHERE type='table' AND name=?", (table,)
                ).fetchone()
                if schema:
                    return [(table, schema[0])], [('Table', 'varchar(64)'), ('Create Table', 'longtext')]
                return [], [('Table', 'varchar(64)'), ('Create Table', 'longtext')]
            if 'INFORMATION_SCHEMA' in sql_upper:
                return [], []

            # 普通 SQL 预处理
            sql_proc = sql.replace('`', '')
            sql_proc = replace_mysql_funcs(sql_proc)

            try:
                cursor = self._conn.execute(sql_proc)
                self._conn.commit()
                rows = cursor.fetchall()
                if cursor.description:
                    cols = [(d[0], 'varchar(255)') for d in cursor.description]
                    return [tuple(r) for r in rows], cols
                return [], []
            except _sqlite3.Error as e:
                raise Exception(f"SQL Error: {e}")

    server = MysqlServer(session_factory=SqliteSession)
    await server.start_server(port=3306, host='0.0.0.0')

    log.info("=" * 55)
    log.info("  太初命理系统 本地 MySQL 服务已启动")
    log.info("  地址: 127.0.0.1:3306")
    log.info("  用户: root  密码: (空)")
    log.info("  数据库: taichu")
    log.info("")
    log.info("  连接命令:")
    log.info("  mysql -h 127.0.0.1 -P 3306 -u root --password=")
    log.info("=" * 55)

    await server.serve_forever()


def main():
    parser = argparse.ArgumentParser(description='太初命理本地MySQL服务')
    parser.add_argument('--reinit', action='store_true', help='重新初始化数据库')
    parser.add_argument('--no-reinit', action='store_true', help='不重新初始化（非交互模式）')
    args = parser.parse_args()

    log.info("初始化数据库...")

    if os.path.exists(DB_FILE):
        if args.reinit:
            os.remove(DB_FILE)
            log.info("已删除旧数据库，重新初始化")
        elif args.no_reinit:
            log.info(f"使用已有数据库: {DB_FILE}")
        else:
            log.info(f"发现已有数据库: {DB_FILE}")
            try:
                ans = input("是否重新初始化数据库? [y/N]: ").strip().lower()
                if ans == 'y':
                    os.remove(DB_FILE)
                    log.info("已删除旧数据库")
            except EOFError:
                log.info("非交互模式，使用已有数据库")

    if not os.path.exists(DB_FILE):
        table_count = load_sql_to_sqlite(DB_FILE, SQL_FILES, DATABASE_DIR)
        if table_count == 0:
            log.warning("没有成功创建任何表，请检查 SQL 文件")

    log.info("启动 MySQL 协议服务...")
    try:
        asyncio.run(start_server())
    except KeyboardInterrupt:
        log.info("\n服务已停止")


if __name__ == '__main__':
    main()
