# PHP06｜PDO 数据库实战

本章介绍 PDO 连接 SQLite/MySQL、预处理语句、CRUD、事务与分页，并提供完整图书管理应用。

## 示例导航

| 文件 | 说明 |
| --- | --- |
| [sqlite_crud.php](./sqlite_crud.php) | 创建 SQLite 数据库并演示增删改查 |
| [mysql_connection.php](./mysql_connection.php) | 使用环境变量连接 MySQL |
| [transaction.php](./transaction.php) | 使用事务保证一组写操作一起成功或回滚 |
| [pagination.php](./pagination.php) | 使用预处理语句实现分页 |
| [book_manager](./book_manager/) | 完整 Web CRUD：增删改查、搜索、分页、CSRF 防护和测试 |

## 环境要求

- PHP 8.2+
- SQLite 示例与图书管理项目需要 pdo_sqlite
- 图书管理项目还需要 mbstring
- MySQL 连接示例需要 pdo_mysql

检查已启用驱动：

    php -r "print_r(PDO::getAvailableDrivers());"

## 运行独立示例

    php PHPBasic/PHP06/sqlite_crud.php

脚本会在当前章节目录创建 learning.sqlite。请勿把含有个人数据的数据库文件提交到仓库。

## 运行图书管理项目

在仓库根目录执行：

    php -S 127.0.0.1:8000 -t PHPBasic/PHP06/book_manager

然后访问 http://127.0.0.1:8000。项目会自动创建本地数据库。详细说明见 [图书管理 README](./book_manager/README.md)。

运行项目数据层测试：

    php PHPBasic/PHP06/book_manager/tests.php

测试使用内存 SQLite，不修改实际数据库文件。

## 安全与正确性

- 用户输入必须通过 prepare() 与 execute() 绑定；禁止拼接到 SQL。
- 参数占位符只能代表数据值，不能绑定表名、列名或排序方向；动态标识符必须使用固定白名单。
- 分页参数转换为整数并限制范围。
- 生产环境数据库凭据从环境变量或秘密管理系统读取，不提交到仓库。
- 事务中的异常要触发回滚；数据库表引擎也必须支持事务。
- 错误日志可以包含诊断信息，HTTP 响应不要暴露 SQL、密码或堆栈。

## 练习

1. 为书籍表增加分类字段，并实现分类筛选。
2. 为分页添加总记录数和总页数。
3. 模拟事务中途异常，确认数据是否回滚。
4. 分别用 SQLite 与 MySQL 驱动运行 CRUD，记录 SQL 方言差异。
5. 阅读图书管理项目，尝试补充 ISBN 唯一性校验和登录权限。
