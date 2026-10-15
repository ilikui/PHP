# PHP12｜轻量 CMS 内容管理系统

这是一个不依赖框架的 PHP 内容管理实战，整合 PDO、SQLite、会话认证、CSRF、文章 CRUD、草稿/发布、分页与服务端校验。

## 环境

- PHP 8.2+
- PDO SQLite 扩展
- 建议仅通过本地开发服务器运行

## 初始化管理员

在仓库根目录执行（PowerShell）：

```powershell
$env:CMS_ADMIN_USERNAME = "admin"
$env:CMS_ADMIN_PASSWORD = "请替换为至少 16 位的随机密码"
php PHPBasic/PHP12-CMS/bin/create-admin.php
```

创建账号后请清除当前终端中的密码环境变量。数据库默认位于 `PHPBasic/PHP12-CMS/var/cms.sqlite`，可通过 `CMS_DB_PATH` 修改。不要把数据库、密码或 Token 提交到 Git。

## 启动

```powershell
php -S 127.0.0.1:8080 -t PHPBasic/PHP12-CMS/public PHPBasic/PHP12-CMS/public/index.php
```

访问 `http://127.0.0.1:8080/`。管理员登录入口为 `/login`，后台文章管理入口为 `/admin`。

## 功能

- 公开文章列表与文章详情，只展示已发布内容
- 公共文章关键词搜索；后台按标题/正文关键词及草稿/已发布状态筛选
- 管理员登录/退出、会话 ID 轮换
- 文章新增、编辑、删除、发布/撤回
- PDO 预处理、服务端校验、CSRF Token、输出编码
- SQLite 自动建表、关键词搜索（标题与正文）、总数统计与分页
- 搜索仅查询已发布文章，参数使用预处理语句并限制关键词长度
- 基础安全响应头
- CLI 管理员初始化与数据层测试

## 安全说明

这是教学项目，不是开箱即用的生产 CMS。生产环境还需 HTTPS、反向代理和请求限流、审计日志、备份/恢复演练、依赖漏洞检查、严格的部署权限和监控。示例文章内容按纯文本处理，不执行原始 HTML 或 Markdown。管理员密码应使用密码管理器生成的强随机值。

## 测试

```powershell
php PHPBasic/PHP12-CMS/tests.php
```
