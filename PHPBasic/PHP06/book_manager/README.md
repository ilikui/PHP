# PHP06 实战项目｜图书管理 CRUD

本目录使用 PDO + SQLite 实现可运行的图书管理应用。它与 PHP06 原有的独立示例并存，不覆盖原有内容。

## 功能

- 添加、查看、编辑、删除图书。
- 按书名、作者或 ISBN 搜索。
- 分页展示，默认每页 10 条，数据访问层限制最大每页 50 条。
- 服务端校验书名、作者和 ISBN 长度。
- SQL 预处理语句、HTML 输出转义。
- 删除通过 POST 执行；所有写操作校验 CSRF Token。
- 数据库异常不向页面泄露 SQL 或堆栈，详细信息写入服务器日志。
- 数据访问层与 Web 页面分离，可使用内存 SQLite 独立测试。

## 环境要求

- PHP 8.2+
- pdo_sqlite、mbstring 扩展

检查版本与驱动：

    php -v
    php -m
    php -r "print_r(PDO::getAvailableDrivers());"

## 启动

在仓库根目录执行：

    php -S 127.0.0.1:8000 -t PHPBasic/PHP06/book_manager

浏览器打开 http://127.0.0.1:8000。首次启动会自动创建 books.sqlite。可通过环境变量 BOOK_MANAGER_DB 指定数据库文件路径。

Windows PowerShell 示例：

    $env:BOOK_MANAGER_DB = "$PWD\books.sqlite"
    php -S 127.0.0.1:8000 -t PHPBasic/PHP06/book_manager

本项目生成的 SQLite 文件只用于本地运行，不应提交含有个人数据的数据库。

## 自动化测试

    php PHPBasic/PHP06/book_manager/tests.php

测试使用 sqlite::memory:，不依赖真实文件或外部数据库，不会修改实际图书数据。

## 关键设计

1. 数据访问层 BookRepository 只处理数据库读写与字段校验，不负责 HTML。
2. 搜索条件和分页参数通过预处理语句绑定；分页大小由代码限制。
3. 页面输出统一经过 htmlspecialchars()，减少 XSS 风险。
4. 新增、修改、删除都校验会话 CSRF Token；删除不通过 GET 请求执行。
5. 向用户展示通用错误，详细异常写入服务器日志。
6. 写操作成功后使用 303 重定向，避免刷新页面重复提交。

## 练习

- 为图书增加分类，并加入分类筛选。
- 为 ISBN 添加格式校验与唯一索引。
- 增加每页条数选择，但仅允许 10、20、50。
- 将 SQLite 数据访问切换为 MySQL，比较建表语法与驱动配置。
- 增加登录和角色权限，再考虑部署到可访问的服务器。

> 安全提示：这是教学项目，不是已经完成安全审计的生产系统。公开部署前还需要身份认证、授权、HTTPS、会话 Cookie 安全配置、安全测试及备份恢复演练。
