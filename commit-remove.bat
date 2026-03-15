@echo off
cd /d "c:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified"
git add create-repo.ps1
git -c user.email="deploy@taichu.chat" -c user.name="Deploy" commit -m "Remove sensitive token"
