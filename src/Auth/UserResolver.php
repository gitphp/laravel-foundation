<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserResolver
{
    public function resolve(JwtToken $token): ?Authenticatable;
}
