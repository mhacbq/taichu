@echo off
cd /d "c:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified"
git add .
git -c user.email="deploy@taichu.chat" -c user.name="Deploy" commit -m "Add: stats API, daily checkin, rate limit, vite config"
