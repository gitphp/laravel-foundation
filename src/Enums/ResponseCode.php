<?php

declare(strict_types=1);

namespace Sunny\Foundation\Enums;

enum ResponseCode: int
{
    case Ok = 0;
    case Fail = 1;
    case Unauthenticated = 40100;
    case Forbidden = 40300;
    case NotFound = 40400;
    case ValidationFailed = 42200;
    case TooManyRequests = 42900;
    case ServerError = 50000;

    public function httpStatus(): int
    {
        return match ($this) {
            self::Ok => 200,
            self::Fail => 400,
            self::Unauthenticated => 401,
            self::Forbidden => 403,
            self::NotFound => 404,
            self::ValidationFailed => 422,
            self::TooManyRequests => 429,
            self::ServerError => 500,
        };
    }

    public function message(): string
    {
        $key = 'sunny::messages.'.$this->translationKey();
        $line = __($key);

        return $line === $key ? $this->defaultMessage() : $line;
    }

    private function translationKey(): string
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $this->name);

        return strtolower(is_string($name) ? $name : $this->name);
    }

    private function defaultMessage(): string
    {
        return match ($this) {
            self::Ok => 'ok',
            self::Fail => 'The request could not be completed.',
            self::Unauthenticated => 'Unauthenticated.',
            self::Forbidden => 'This action is unauthorized.',
            self::NotFound => 'The resource was not found.',
            self::ValidationFailed => 'The given data was invalid.',
            self::TooManyRequests => 'Too many requests.',
            self::ServerError => 'Server error.',
        };
    }
}
