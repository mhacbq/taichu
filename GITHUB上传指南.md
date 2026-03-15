# GitHub 上传指南

## 方法一：使用脚本推送（推荐）

### 1. 在 GitHub 创建新仓库

1. 访问 https://github.com/new
2. 输入仓库名称（如 `taichu-unified`）
3. 选择 **Public** 或 **Private**
4. **不要** 勾选 "Add a README file"
5. 点击 **Create repository**

### 2. 复制仓库地址

创建后会看到类似以下的地址：
- HTTPS: `https://github.com/你的用户名/taichu-unified.git`
- SSH: `git@github.com:你的用户名/taichu-unified.git`

### 3. 执行推送脚本

双击运行 `github-push.bat`，或者在命令行执行：

```bash
github-push.bat https://github.com/你的用户名/taichu-unified.git
```

## 方法二：手动推送

### 1. 添加远程仓库

```bash
cd c:/Users/v_boqchen/WorkBuddy/Claw/taichu-unified
git remote add origin https://github.com/你的用户名/taichu-unified.git
```

### 2. 推送到 GitHub

```bash
git push -u origin master
```

如果失败，尝试：
```bash
git branch -m main
git push -u origin main
```

## 方法三：直接上传 ZIP

### 1. 打包项目

```bash
cd c:/Users/v_boqchen/WorkBuddy/Claw
tar -czvf taichu-unified.tar.gz taichu-unified/
```

或在 Windows 右键点击 `taichu-unified` 文件夹 → **发送到** → **压缩文件夹**

### 2. 上传到 GitHub

1. 在 GitHub 创建新仓库
2. 点击 **uploading an existing file** 链接
3. 将打包的文件拖放到上传区域
4. 点击 **Commit changes**

## 常见问题

### Q: 提示 "fatal: Authentication failed"

需要在 GitHub 生成 Personal Access Token：
1. 访问 https://github.com/settings/tokens
2. 点击 **Generate new token (classic)**
3. 勾选 **repo** 权限
4. 生成后复制 token，用它代替密码

### Q: 提示 "remote: Permission denied"

检查：
1. GitHub 仓库是否属于你
2. 是否有写入权限
3. Token 是否有足够的权限

### Q: 分支名问题

GitHub 默认使用 `main` 分支，本地可能是 `master`：
```bash
git branch -m main
git push -u origin main
```

## 推送后验证

推送成功后，在浏览器访问：
```
https://github.com/你的用户名/taichu-unified
```

确认以下文件已上传：
- `frontend/` 目录
- `backend/` 目录
- `docker-compose.yml`
- `README.md`
- `nginx.conf`
