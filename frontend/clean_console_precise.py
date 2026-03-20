#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
精准清理前端代码中的console语句
保留代码结构完整性，避免破坏多行代码
"""

import os
import re
import sys
from pathlib import Path

# 需要排除的文件和目录
EXCLUDED_FILES = {'ErrorBoundary.vue'}
EXCLUDED_DIRS = {'tests', 'scripts', 'node_modules', 'dist', 'build'}

# Console语句的正则模式
CONSOLE_PATTERNS = [
    r'\bconsole\.(log|warn|error|info|debug|trace)\([^)]*\)',
    r'\bconsole\.(log|warn|error|info|debug|trace)\([^)]*\)[;]?',
]

def should_skip_file(file_path):
    """检查文件是否应该被跳过"""
    file_name = os.path.basename(file_path)
    if file_name in EXCLUDED_FILES:
        return True
    
    # 检查是否在排除的目录中
    parts = Path(file_path).parts
    for excluded_dir in EXCLUDED_DIRS:
        if excluded_dir in parts:
            return True
    
    return False

def find_console_in_line(line):
    """
    在行中查找console语句的位置
    返回：(start_pos, end_pos, matched_text)
    """
    for pattern in CONSOLE_PATTERNS:
        for match in re.finditer(pattern, line):
            # 检查是否在注释中（简单检查）
            before_match = line[:match.start()]
            # 如果前面有//，且同一行没有换行，可能是注释
            if '//' in before_match and '\n' not in before_match:
                last_comment_pos = before_match.rfind('//')
                if last_comment_pos > before_match.rfind('\n') if '\n' in before_match else -1:
                    continue
            return (match.start(), match.end(), match.group())
    return None

def clean_file(file_path):
    """
    清理单个文件中的console语句
    返回：(是否修改, 删除数量)
    """
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            lines = f.readlines()
    except Exception as e:
        print(f"读取文件失败: {file_path} - {e}")
        return (False, 0)
    
    new_lines = []
    deleted_count = 0
    modified = False
    
    i = 0
    while i < len(lines):
        line = lines[i]
        console_match = find_console_in_line(line)
        
        if console_match:
            # 找到console语句
            start, end, matched = console_match
            
            # 检查console语句是否占据整行（忽略前后空白）
            line_stripped = line.strip()
            matched_stripped = matched.strip()
            
            # 如果console语句是整行的主要部分
            if line_stripped == matched_stripped or line_stripped.startswith(matched_stripped):
                # 检查这行是否有其他有意义的代码
                remaining = line[:start] + line[end:]
                remaining_stripped = remaining.strip()
                
                # 如果剩余部分只有空白或简单的标点，删除整行
                if not remaining_stripped or remaining_stripped in [';', ',', '};', '},', '];', ']']:
                    deleted_count += 1
                    modified = True
                    i += 1
                    continue
                else:
                    # 保留其他代码，只删除console部分
                    new_line = line[:start] + line[end:]
                    # 清理多余的空格和标点
                    new_line = re.sub(r'\s*;?\s*$', '', new_line)
                    if new_line.strip():
                        new_lines.append(new_line + '\n')
                    else:
                        new_lines.append('\n')
                    deleted_count += 1
                    modified = True
                    i += 1
                    continue
            else:
                # console是行的一部分，只删除console部分
                new_line = line[:start] + line[end:]
                # 清理多余的空格
                new_line = re.sub(r'\s+', ' ', new_line)
                new_line = new_line.replace(' ; ', '; ')
                new_line = new_line.replace(' ;', ';')
                new_lines.append(new_line)
                deleted_count += 1
                modified = True
        else:
            new_lines.append(line)
        
        i += 1
    
    if modified:
        try:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.writelines(new_lines)
            return (True, deleted_count)
        except Exception as e:
            print(f"写入文件失败: {file_path} - {e}")
            return (False, deleted_count)
    
    return (False, 0)

def scan_and_clean(src_dir):
    """
    扫描并清理所有文件
    返回：(文件总数, 修改文件数, 总删除数量)
    """
    total_files = 0
    modified_files = 0
    total_deleted = 0
    results = []
    
    # 查找所有.vue和.js文件
    for root, dirs, files in os.walk(src_dir):
        # 过滤排除的目录
        dirs[:] = [d for d in dirs if d not in EXCLUDED_DIRS]
        
        for file in files:
            if file.endswith('.vue') or file.endswith('.js'):
                file_path = os.path.join(root, file)
                total_files += 1
                
                if should_skip_file(file_path):
                    continue
                
                modified, deleted = clean_file(file_path)
                if modified:
                    modified_files += 1
                    total_deleted += deleted
                    results.append({
                        'file': file_path,
                        'deleted': deleted
                    })
    
    return total_files, modified_files, total_deleted, results

def main():
    src_dir = '/data/workspace/taichu/frontend/src'
    
    print("=" * 60)
    print("精准console清理任务")
    print("=" * 60)
    print(f"扫描目录: {src_dir}")
    print(f"排除文件: {', '.join(EXCLUDED_FILES)}")
    print(f"排除目录: {', '.join(EXCLUDED_DIRS)}")
    print("=" * 60)
    print()
    
    total_files, modified_files, total_deleted, results = scan_and_clean(src_dir)
    
    print(f"扫描文件总数: {total_files}")
    print(f"修改文件数量: {modified_files}")
    print(f"删除console语句总数: {total_deleted}")
    print()
    
    if results:
        print("清理详情:")
        print("-" * 60)
        for result in results:
            rel_path = os.path.relpath(result['file'], '/data/workspace/taichu/frontend')
            print(f"  {rel_path}: 删除 {result['deleted']} 个console语句")
    else:
        print("没有找到需要清理的console语句")
    
    print("=" * 60)

if __name__ == '__main__':
    main()
