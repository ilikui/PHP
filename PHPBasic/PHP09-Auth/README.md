# PHP09｜登录认证与权限控制

本专题演示密码哈希、登录会话、CSRF、防会话固定与角色标记。

## 环境要求

- PHP 8.2+
- PDO SQLite 扩展

## 初始化管理员账号

请在仓库根目录通过环境变量创建管理员账号，切勿将真实密码写进仓库：

```bash
AUTH_ADMIN_USERNAME=site_admin AUTH_ADMIN_PASSWORD='替换为至少12位的强密码' php PHPBasic/PHP09-Auth/setup.php
```

PowerShell：

```powershell
$env:AUTH_ADMIN_USERNAME = "site_admin"
$env:AUTH_ADMIN_PASSWORD = "替换为至少12位的强密码"
php PHPBasic/PHP09-Auth/setup.php
Remove-Item Env:AUTH_ADMIN_PASSWORD
```

## 启动

```bash
php -S 127.0.0.1:8000 -t PHPBasic/PHP09-Auth/public PHPBasic/PHP09-Auth/public/index.php
```

访问 `http://127.0.0.1:8000/login`。

## 安全要点

- 使用 `password_hash()` / `password_verify()`，不保存明文密码。
- 登录成功后调用 `session_regenerate_id(true)`。
- 登录与退出表单使用 CSRF Token；SQL 使用 PDO 预处理语句；HTML 输出转义。
- Cookie 设置 HttpOnly、SameSite=Lax；HTTPS 下启用 Secure。
- 示例的失败登录限制仅按当前会话计数，生产环境必须使用服务端共享的账号/IP 限流、审计告警、MFA 和密码重置流程。
- 教学项目未经安全评审不应直接用于生产环境。

## 测试

```bash
php PHPBasic/PHP09-Auth/tests.php
```

## 练习

1. 增加服务端共享的登录失败限流和审计日志。
2. 实现用户注册、邮箱验证、密码重置和 MFA。
3. 增加管理员路由守卫与权限矩阵。
4. 为会话增加闲置超时和绝对超时。
