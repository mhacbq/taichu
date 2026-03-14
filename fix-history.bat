@echo off
cd /d "c:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified"
git filter-branch --force --index-filter "git rm --cached --ignore-unmatch create-repo.ps1" --prune-empty --tag-name-filter cat -- --all
