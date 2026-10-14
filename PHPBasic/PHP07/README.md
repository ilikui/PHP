# PHP07｜面向对象、Composer 与测试

本章使用独立的小型项目演示类、接口、命名空间、异常、Composer PSR-4 自动加载和 PHPUnit 单元测试。

## 目录

- `composer.json`：项目依赖与自动加载配置
- `src/Contract/Notifier.php`：接口
- `src/Exception/InvalidUserException.php`：领域异常
- `src/Service/WelcomeService.php`：依赖接口的服务类
- `tests/WelcomeServiceTest.php`：单元测试

## 环境与运行

需要 PHP 8.2+ 和 Composer：

```bash
cd PHPBasic/PHP07
composer install
composer dump-autoload
composer test
```

Composer 会生成 `vendor/`，该目录通常不应提交到版本控制。依赖版本与锁文件应在实际安装依赖后共同管理。

## 核心概念

- 类封装状态与行为；构造函数用于建立对象所需的初始状态。
- 接口定义调用方依赖的契约，实现类提供具体行为。
- 命名空间用于组织符号并减少命名冲突。
- 异常用于表达无法正常完成的操作；在合适的边界捕获并转换错误。
- Composer 的 PSR-4 映射把命名空间前缀映射到目录。
- 单元测试验证可重复的行为，不应依赖真实网络或生产数据库。

## 练习

1. 增加 `EmailNotifier`，实现 `Notifier` 接口。
2. 为服务添加名字为空时的异常。
3. 为异常场景编写 PHPUnit 测试。
4. 为项目添加静态分析或代码风格检查。
