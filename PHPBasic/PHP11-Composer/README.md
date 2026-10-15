# PHP11｜Composer 工程化与可复用组件

本专题演示如何把零散 PHP 代码整理成可维护的项目：Composer 依赖管理、PSR-4 自动加载、配置读取、结构化日志、JSON 响应、输入校验与 PHPUnit 测试。

## 环境要求

- PHP 8.2+
- Composer 2

## 安装与测试

在仓库根目录执行：

```bash
cd PHPBasic/PHP11-Composer
composer install
composer test
composer lint
```

## 目录结构

```text
PHP11-Composer/
├── composer.json
├── phpunit.xml
├── src/
│   ├── Config/Environment.php
│   ├── Http/JsonResponse.php
│   ├── Logging/Logger.php
│   └── Validation/InputValidator.php
└── tests/
    ├── EnvironmentTest.php
    ├── JsonResponseTest.php
    └── InputValidatorTest.php
```

## 设计说明

- `Learning\\` 命名空间由 Composer PSR-4 自动加载映射到 `src/`。
- 环境变量优先于默认值；不将秘密写进代码或提交到 Git。
- `JsonResponse` 只负责构造状态码、响应头和 JSON 字符串，不直接终止进程，便于测试与复用。
- `InputValidator` 提供轻量字段校验；复杂业务规则应放在领域层，不要把所有逻辑堆进控制器。
- `Logger` 采用 JSON Lines 格式，日志路径由调用方配置。生产环境还应设置文件权限、轮转策略、保留期限，并避免记录密码、Token 或其他敏感数据。

## 使用示例

```php
require __DIR__ . '/vendor/autoload.php';

use Learning\\Config\\Environment;
use Learning\\Http\\JsonResponse;
use Learning\\Validation\\InputValidator;

$env = new Environment(['APP_ENV' => 'local']);
$validator = new InputValidator();
$errors = $validator->validate(
    ['title' => 'A PHP book', 'page_size' => '20'],
    ['title' => ['required', 'string', 'max:120'], 'page_size' => ['integer', 'min:1', 'max:100']]
);
$response = $errors === []
    ? JsonResponse::success(['message' => 'valid'])
    : JsonResponse::error('validation_error', 'Input is invalid.', 422, ['fields' => $errors]);

// 在 HTTP 入口中发送：
// http_response_code($response->status());
// foreach ($response->headers() as $name => $value) header($name . ': ' . $value);
// echo $response->body();
```

## 练习

1. 为 Logger 增加日志级别过滤和文件轮转。
2. 将验证规则抽象成可扩展的 Rule 接口。
3. 增加 PHPStan 或 Psalm 静态分析。
4. 为组件创建版本号、变更日志与语义化版本发布流程。
