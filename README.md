# Sunny Foundation

面向 Laravel 13 的基础扩展包。安装后即可使用助手函数、全局中间件、表单验证基类、统一 API 响应、JWT 认证、访问日志、枚举，以及配置、缓存、字符串、数字、数组、日期和通用工具。

要求：PHP 8.3+、Laravel 13、`bcmath`、`openssl`、`mbstring`、`json`。

## 安装

```bash
composer require gitphp/laravel-foundation
php artisan vendor:publish --tag=sunny-config
php artisan vendor:publish --tag=sunny-lang
```

在 `.env` 中设置至少 32 位的随机密钥：

```dotenv
SUNNY_JWT_SECRET=请替换成随机字符串且不少于32位
SUNNY_JWT_ALGO=HS256
```

生成密钥：

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

服务提供者会自动发现，门面别名是 `Sunny`。

## 发布到 Packagist

1. 把 `composer.json` 里的 `name` 改成你在 Packagist 上拥有的供应商名，例如 `acme/laravel-foundation`。PHP 命名空间可以保持 `Sunny\Foundation`。
2. 将本目录推送到 Git 仓库。
3. 打上版本标签，例如 `v1.0.0`。Packagist 使用 Git 标签作为版本，不要在 `composer.json` 里写 `version`。
4. 登录 [packagist.org](https://packagist.org) 提交仓库地址，并配置 GitHub / Gitea webhook，之后推送标签会自动更新。

## 助手函数

| 函数 | 作用 |
| --- | --- |
| `sunny()` | 入口，返回配置、缓存、JWT 和日志 |
| `sunny_config($key, $default)` | 读取 `config/sunny.php` |
| `sunny_cache()` | 带前缀的缓存 |
| `sunny_request_id()` | 当前请求 ID |
| `sunny_mask()` | 脱敏 |
| `sunny_tree()` | 扁平数组转树 |
| `sunny_yuan()` / `sunny_fen()` | 分与元互转 |
| `sunny_date()` | 格式化日期 |
| `sunny_success()` / `sunny_error()` / `sunny_paginate()` | API 响应 |
| `sunny_log()` | 写包日志 |

```php
sunny_success(['id' => 1]);
sunny_log('order.created', ['order_id' => 10]);
```

## 全局中间件

应用启动后会注册这些全局中间件：

- `AssignRequestId`：为每个响应写入 `X-Request-Id`。传入的 ID 只接受 8 到 128 位字母、数字、下划线和连字符，避免换行注入。
- `ForceJsonResponse`：只对 `api/*` 强制 `Accept: application/json`，页面请求不受影响。
- `LogApiRequest`：记录 `api/*` 的方法、路径、状态码、耗时和 IP，不记录请求体和凭证。

路由别名：

- `sunny.request-id`
- `sunny.json`
- `sunny.log`
- `sunny.auth`

关闭全局注册时，发布配置并把 `middleware.enabled` 设为 `false`。这个值在应用启动时读取。

## 基础验证请求

继承 `Sunny\Foundation\Http\Requests\FormRequest`。验证失败返回 HTTP 422，未授权返回 HTTP 403，响应体与 API 响应类一致。

```php
use Sunny\Foundation\Http\Requests\FormRequest;
use Sunny\Foundation\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'mobile' => ['required', ...Rules::mobile()],
            'id_card' => ['nullable', ...Rules::chineseIdCard()],
            'amount' => ['required', ...Rules::decimal(scale: 2)],
        ];
    }
}
```

需要权限时重写 `authorize()`。

## API 响应

```json
{
    "code": 0,
    "message": "ok",
    "data": {},
    "request_id": "..."
}
```

```php
use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Http\Responses\ApiResponse;

return ApiResponse::success($user);
return ApiResponse::error('保存失败', ResponseCode::ValidationFailed, $errors);
return ApiResponse::paginate($users);
```

业务码使用枚举，HTTP 状态由枚举决定。中文文案在语言为 `zh_CN` 时生效。

## JWT 认证

默认算法是 HS256。只允许 HS256 和 RS256，`none` 会被拒绝。访问令牌和刷新令牌分开，刷新后旧的刷新令牌进入缓存黑名单。令牌只从 `Authorization: Bearer` 读取。

```php
$tokens = Sunny::jwt()->issue($user->getAuthIdentifier(), ['role' => 'admin']);

return sunny_success($tokens);
```

保护路由：

```php
Route::middleware('sunny.auth')->get('/api/me', function () {
    return sunny_success(['id' => auth()->id()]);
});
```

中间件会把默认 guard 切换为 `sunny`，因此这次请求里的 `auth()->user()` 来自 JWT。未配置 `jwt.user_model` 时，用户是仅含 `sub` 的身份对象；配置 Eloquent 模型后按主键查询：

```php
'user_model' => App\Models\User::class,
'identifier' => 'id',
```

自定义解析时，在 `AppServiceProvider::register()` 绑定 `Sunny\Foundation\Auth\UserResolver`。

退出登录：

```php
Sunny::jwt()->logout($accessToken, $refreshToken);
```

刷新：

```php
$tokens = Sunny::jwt()->refresh($refreshToken);
```

RS256 时设置 PEM 私钥 `SUNNY_JWT_PRIVATE_KEY` 和公钥 `SUNNY_JWT_PUBLIC_KEY`，算法改为 `RS256`。`.env` 里的换行可以写成 `\n`。

## 日志

日志通道名默认是 `sunny`，按天写入 `storage/logs/sunny.log`。如果应用已经定义了同名通道，包不会覆盖它。

```php
Sunny::logger()->info('order.paid', ['order_id' => 10]);
Sunny::logger()->exception($exception, 'order.failed');
```

`password`、`token`、`authorization`、`secret` 等字段会记成 `[redacted]`。堆栈默认不写，需要时把 `log.trace` 设为 `true`。

## 枚举

- `ResponseCode`：业务码、HTTP 状态和文案
- `DateFormat`：常用日期格式
- `JwtAlgorithm`：`HS256`、`RS256`

## 配置、缓存、字符串、数字、数组、日期

```php
sunny_config('jwt.ttl');

sunny_cache()->remember('cities', fn () => City::query()->get());

Sunny\Foundation\Support\Str::maskMobile('13800138000');
Sunny\Foundation\Support\Number::yuan(1234); // 12.34
Sunny\Foundation\Support\Arr::tree($categories);
Sunny\Foundation\Support\DateTime::eachDay('2026-10-01', '2026-10-07');
Sunny\Foundation\Support\Toolkit::uuid();
```

金额计算使用 BCMath，保留指定小数位，四舍五入采用远离零的方式。分和元的换算不要用浮点数直接相乘。

## 测试

```bash
composer test
```
