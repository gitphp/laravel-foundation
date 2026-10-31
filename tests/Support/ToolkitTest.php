<?php

declare(strict_types=1);

/**
 * 通用工具测试
 *
 * @package     Sunny\Foundation\Tests\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Support;

use RuntimeException;
use Sunny\Foundation\Support\Toolkit;
use Sunny\Foundation\Tests\TestCase;

/**
 * 通用工具测试
 */
class ToolkitTest extends TestCase
{
    /**
     * UUID 版本 4 和字节格式化应符合约定。
     */
    public function test_it_builds_a_uuid_and_formats_bytes(): void
    {
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            Toolkit::uuid(),
        );
        $this->assertSame('1.50 KB', Toolkit::formatBytes(1536));
        $this->assertTrue(Toolkit::blank(''));
        $this->assertTrue(Toolkit::filled('晴'));
    }

    /**
     * 重试应在成功的那一次返回结果。
     */
    public function test_it_retries_until_the_callback_succeeds(): void
    {
        $attempts = 0;

        $value = Toolkit::retry(3, function () use (&$attempts): string {
            $attempts++;

            if ($attempts < 2) {
                throw new RuntimeException('not yet');
            }

            return 'ok';
        });

        $this->assertSame('ok', $value);
        $this->assertSame(2, $attempts);
    }
}

