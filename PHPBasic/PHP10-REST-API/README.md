# PHP10｜REST API 工程实战

一个基于 PHP 8.2+、PDO SQLite 的 JSON REST API 教学项目，包含资源 CRUD、分页搜索、参数校验、Bearer Token 认证和自动化测试。

## 启动

先设置一个仅用于本地开发的随机 API Token。不要将真实 Token 提交到 Git：

PowerShell：

```powershell
$env:API_BEARER_TOKEN = "replace-with-a-long-random-secret"
php -S 127.0.0.1:8000 -t PHPBasic/PHP10-REST-API/public PHPBasic/PHP10-REST-API/public/index.php
```

Bash：

```bash
export API_BEARER_TOKEN='replace-with-a-long-random-secret'
php -S 127.0.0.1:8000 -t PHPBasic/PHP10-REST-API/public PHPBasic/PHP10-REST-API/public/index.php
```

数据库自动创建在 `PHPBasic/PHP10-REST-API/var/books.sqlite`。仅使用本地开发服务器，不要直接用于生产部署。

## API 端点

所有端点都要求请求头 `Authorization: Bearer <API_BEARER_TOKEN>`。

| 方法 | 路径 | 说明 | 成功状态 |
| --- | --- | --- | --- |
| GET | `/api/books` | 列表、搜索、分页 | 200 |
| GET | `/api/books/{id}` | 获取单本图书 | 200 |
| POST | `/api/books` | 创建图书 | 201 |
| PUT | `/api/books/{id}` | 更新图书 | 200 |
| PATCH | `/api/books/{id}` | 教学版按完整资源更新 | 200 |
| DELETE | `/api/books/{id}` | 删除图书 | 204 |

列表参数：`page`（默认 1）、`per_page`（默认 10，最大 50）、`q`（标题/作者/ISBN 搜索）、`sort`（`id`、`title`、`author`、`created_at`）、`direction`（`asc` 或 `desc`）。

## curl 示例

```bash
curl -H "Authorization: Bearer $API_BEARER_TOKEN" \
  "http://127.0.0.1:8000/api/books?page=1&per_page=10&q=clean"

curl -X POST http://127.0.0.1:8000/api/books \
  -H "Authorization: Bearer $API_BEARER_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"title":"Clean Code","author":"Robert C. Martin","isbn":"9780132350884"}'
```

## 响应约定

成功数据统一放在 `data` 字段。错误统一为 `error.code` 与 `error.message`。常见状态码：400 JSON 无效、401 未认证、404 资源不存在、405 方法不支持、413 请求体过大、415 内容类型错误、422 参数校验失败、500 未预期服务错误。服务端日志保留异常详情，客户端不返回堆栈。

## 安全与设计说明

- PDO 预处理语句；排序列使用白名单，避免把用户输入直接拼入 SQL。
- 请求体限制为 64 KiB；只接受 JSON 写入请求。
- 所有端点都验证 Bearer Token，缺少 Token 时默认拒绝。
- Token 是单一共享凭据，适合本地学习，不是完整用户认证系统；生产系统应采用安全的密钥管理、轮换、速率限制、审计和按用户授权。
- 不启用 CORS；不要把 Token 暴露给不可信浏览器脚本。
- 本例的 PATCH 为教学简化，要求提交完整的 title/author 资源字段；生产 API 应明确实现 RFC 语义和版本策略。

## 测试

```bash
php PHPBasic/PHP10-REST-API/tests.php
```

测试覆盖 CRUD、搜索分页、缺失资源和输入校验。HTTP 状态码与认证流程可在本地使用 curl/Postman 继续验证。
