<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class JwtUserResolver implements UserResolver
{
    public function resolve(JwtToken $token): ?Authenticatable
    {
        $subject = $token->subject();

        if ($subject === '') {
            return null;
        }

        $model = config('sunny.jwt.user_model');

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
