<?php

declare(strict_types=1);

/**
 * 常用日期格式
 *
 * @package     Sunny\Foundation\Enums
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Enums;

/**
 * 常用日期格式。
 */
enum DateFormat: string
{
    /** 日期，例如 2026-10-31 */
    case Date = 'Y-m-d';

    /** 日期时间，例如 2026-10-31 09:30:00 */
    case DateTime = 'Y-m-d H:i:s';

    /** 时间，例如 09:30:00 */
    case Time = 'H:i:s';

    /** ISO 8601 */
    case Iso8601 = 'c';
}
