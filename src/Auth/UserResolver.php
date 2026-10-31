<?php

declare(strict_types=1);

/**
 * JWT 用户解析接口
 *
 * 默认实现按配置里的 Eloquent 模型查询。需要自定义时，在应用服务提供者里重新绑定这个接口。
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * 把令牌解析成可登录的用户。
 */
interface UserResolver
{
    /**
     * 根据令牌中的 sub 找到当前用户。找不到时返回 null。
     */
    public function resolve(JwtToken $token): ?Authenticatable;
}
