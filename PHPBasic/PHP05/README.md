# PHP05｜文件、JSON、正则与日期时间

## 本章目标

学习读取和写入文件、JSON 编解码、正则表达式与日期时间 API。示例优先使用 CLI 运行。

| 示例 | 内容 | 运行方式 |
| --- | --- | --- |
| [file_io.php](./file_io.php) | 安全地读写本地示例文件 | `php file_io.php` |
| [json.php](./json.php) | JSON 编码、解码与错误处理 | `php json.php` |
| [regex.php](./regex.php) | 正则匹配与替换 | `php regex.php` |
| [datetime.php](./datetime.php) | 时区、日期解析、时间间隔 | `php datetime.php` |

## 重要注意

- 文件路径必须来自可信配置或经过严格约束，不能把用户输入直接拼成路径。
- 写文件要检查返回值；并发写入时可评估使用 `LOCK_EX`。
- JSON 使用 `JSON_THROW_ON_ERROR`，避免静默失败。
- 正则表达式的分隔符、边界与 Unicode 模式要经过测试。
- 时间展示应明确时区；保存时间戳时应采用一致的时区策略。

## 练习

1. 将一个关联数组写入 JSON 文件，再读回并验证结构。
2. 从文本中提取所有邮箱候选项，并使用 `filter_var` 做进一步校验。
3. 计算两个日期之间的天数，并解释时区对日期边界的影响。
