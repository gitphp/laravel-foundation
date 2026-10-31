<?php

declare(strict_types=1);

namespace Sunny\Foundation\Enums;

enum JwtAlgorithm: string
{
    case HS256 = 'HS256';
    case RS256 = 'RS256';
}
