#!/usr/bin/env python3
import re
import sys
from pathlib import Path

def clean_console_logs(file_path):
    """清理文件中的console.log和console.debug，保留error和warn"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # 删除 console.log 和 console.debug（包括分号结尾或不分号）
        # 匹配 console.log(...) 或 console.debug(...) 后面跟着可选的分号和换行
        content = re.sub(r'\bconsole\.(log|debug)\([^)]*\)\s*;?\s*\n?', '', content)
        
        if content != original_content:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        return False
    except Exception as e:
        print(f"Error processing {file_path}: {e}", file=sys.stderr)
        return False

def main():
    base_dir = Path('/data/workspace/taichu/frontend/src')
    
    # 找到所有.vue和.js文件
    files_to_clean = []
    for pattern in ['*.vue', '*.js']:
        files_to_clean.extend(base_dir.rglob(pattern))
    
    cleaned_count = 0
    for file_path in files_to_clean:
        if clean_console_logs(file_path):
            print(f"✓ Cleaned: {file_path}")
            cleaned_count += 1
    
    print(f"\n总共清理了 {cleaned_count} 个文件")
    return 0

if __name__ == '__main__':
    sys.exit(main())
