<?php

declare(strict_types=1);

/**
 * JWT 签名算法
 *
 * 只允许 HS256 和 RS256。none 以及其他算法会在签发和解析时被拒绝。
 *
 * @package     Sunny\Foundation\Enums
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Enums;

/**
 * 允许的签名算法，只有 HS256 和 RS256。
 */
enum JwtAlgorithm: string
{
    /** 对称签名，使用 SUNNY_JWT_SECRET */
    case HS256 = 'HS256';

    /** 非对称签名，私钥签发、公钥校验 */
    case RS256 = 'RS256';
}
