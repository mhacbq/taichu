@echo off
cd /d "c:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified"
git add .
git -c user.email="deploy@taichu.chat" -c user.name="Deploy" commit -m "Fix: login page, route guard, mobile nav, 404 page, user guide, time format, feedback contact"
