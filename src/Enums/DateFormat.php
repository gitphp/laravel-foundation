<?php

declare(strict_types=1);

namespace Sunny\Foundation\Enums;

enum DateFormat: string
{
    case Date = 'Y-m-d';
    case DateTime = 'Y-m-d H:i:s';
    case Time = 'H:i:s';
    case Iso8601 = 'c';
}
