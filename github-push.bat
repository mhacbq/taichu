@echo off
chcp 65001 >nul
echo ==========================================
echo  太初项目 GitHub 推送脚本
echo ==========================================
echo.

if "%1"=="" (
    echo 用法: github-push.bat ^<GitHub仓库URL^>
    echo.
    echo 示例:
    echo   github-push.bat https://github.com/username/taichu.git
    echo   github-push.bat https://github.com/username/taichu-unified.git
    echo.
    pause
    exit /b 1
)

cd /d "c:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified"

echo [1/3] 添加远程仓库...
git remote add origin %1
if errorlevel 1 (
    echo 远程仓库已存在，尝试更新...
    git remote set-url origin %1
)

echo.
echo [2/3] 推送到 GitHub...
git push -u origin master
if errorlevel 1 (
    echo.
    echo 推送到 master 失败，尝试 main 分支...
    git branch -m main
    git push -u origin main
)

echo.
echo ==========================================
if errorlevel 1 (
    echo  推送失败，请检查:
    echo  1. GitHub 仓库 URL 是否正确
    echo  2. 是否已配置 GitHub 登录凭证
    echo  3. 网络连接是否正常
) else (
    echo  推送成功！
)
echo ==========================================
pause
