# PHP08｜MVC 与 Web 应用架构

本章将图书管理示例拆分为 Model、View、Controller，并使用单一入口处理请求。项目独立于 PHP06，不覆盖已有 CRUD 示例。

## 目录结构

```text
PHP08-MVC/
├── bootstrap.php             # 会话、安全辅助函数、自动加载、视图渲染
├── public/
│   └── index.php             # 前端控制器与路由表
├── src/
│   ├── Controller/
│   │   └── BookController.php
│   └── Model/
│       └── BookRepository.php
├── views/
│   ├── layout/
│   │   ├── header.php
│   │   └── footer.php
│   ├── books/
│   │   ├── index.php
│   │   └── form.php
│   └── errors/
│       └── 404.php
├── tests.php
└── README.md
```

## 环境要求

- PHP 8.2+
- pdo_sqlite、mbstring

## 启动

从仓库根目录执行：

```bash
php -S 127.0.0.1:8000 -t PHPBasic/PHP08-MVC/public PHPBasic/PHP08-MVC/public/index.php
```

打开 http://127.0.0.1:8000。应用会在 PHP08-MVC/var/books.sqlite 创建数据库；可通过环境变量 PHP08_DATA_DIR 指定数据目录。不要将数据目录放在 public 目录中。

## 路由

| 方法 | 路径 | 作用 |
| --- | --- | --- |
| GET | / 或 /books | 图书列表与搜索 |
| GET | /books/create | 新建表单 |
| POST | /books | 保存新图书 |
| GET | /books/{id}/edit | 编辑表单 |
| POST | /books/{id}/update | 保存修改 |
| POST | /books/{id}/delete | 删除记录 |

## MVC 职责

- **Model（BookRepository）**：数据库访问、预处理语句与字段校验。
- **View（views）**：HTML 呈现；所有用户数据输出使用 e() 转义。
- **Controller（BookController）**：读取请求、调用模型、选择视图或重定向。
- **Front Controller（public/index.php）**：统一入口、路由分发、通用异常边界。
- **Bootstrap**：自动加载、会话与 CSRF 辅助函数、通用视图渲染。

## 测试

```bash
php PHPBasic/PHP08-MVC/tests.php
```

测试使用内存 SQLite，不会修改 Web 应用的数据文件。

## 安全基线与边界

- 所有写请求校验 CSRF Token；删除只接受 POST。
- SQL 使用 PDO 预处理语句；HTML 输出通过 htmlspecialchars 编码。
- 会话 Cookie 设置 HttpOnly、SameSite=Lax；HTTPS 环境启用 Secure。
- 响应不展示异常堆栈，详细异常写入服务器日志。
- 本项目仍是教学示例：未实现登录、权限、速率限制、完整审计和正式部署配置，不应直接公开部署。
- 生产环境还应配置 Web 服务器只将 public 作为网站根目录，并限制数据库文件权限。

## 练习

1. 把路由匹配抽取为 Router 类，并为不支持的方法返回 405。
2. 增加分页和排序白名单。
3. 把表单验证抽取为专用 Validator。
4. 增加登录、退出登录和基于角色的访问控制。
5. 将数据库连接参数迁移到环境配置，并添加环境变量校验。
6. 为 Controller 的路由行为增加集成测试。
