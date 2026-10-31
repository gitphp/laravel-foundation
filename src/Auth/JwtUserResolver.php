<?php

declare(strict_types=1);

/**
 * JWT 用户解析
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * JWT 用户解析
 */
final class JwtUserResolver implements UserResolver
{
    /**
     * 未配置模型时返回 JwtIdentity，配置了模型时按主键查询。
     */
    public function resolve(JwtToken $token): ?Authenticatable
    {
        $subject = $token->subject();

        if ($subject === '') {
            return null;
        }

        $model = config('sunny.jwt.user_model');

        // 没配置用户模型时，用令牌里的 subject 作为临时身份，不查数据库。
        if (! is_string($model) || $model === '') {
            return new JwtIdentity($subject, $token->claims());
        }

        if (! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            throw JwtException::misconfigured('sunny.jwt.user_model must be an Eloquent model class.');
        }

        $identifier = config('sunny.jwt.identifier', 'id');
        $column = is_string($identifier) && $identifier !== '' ? $identifier : 'id';

        /** @var Model $model */
        return $model::query()->where($column, $subject)->first();
    }
}

