<?php

declare(strict_types=1);

/**
 * 助手函数测试
 *
 * @package     Sunny\Foundation\Tests
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests;

use Sunny\Foundation\SunnyManager;

/**
 * 助手函数测试
 */
class HelpersTest extends TestCase
{
    /**
     * 助手函数应转到对应的服务和工具类。
     */
    public function test_helpers_delegate_to_the_package_services(): void
    {
        $this->assertInstanceOf(SunnyManager::class, sunny());
        $this->assertSame('12.00', sunny_yuan(1200));
        $this->assertSame('1200', sunny_fen('12'));
        $this->assertSame('138****8000', sunny_mask('13800138000'));
        $this->assertSame('2024-01-02', sunny_date('2024-01-02 03:04:05', 'Y-m-d', 'UTC'));

        $tree = sunny_tree([
            ['id' => 1, 'parent_id' => null, 'name' => 'root'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'child'],
        ]);

        $this->assertSame('child', $tree[0]['children'][0]['name']);
        $this->assertSame(0, sunny_success(['ok' => true])->getData(true)['code']);
    }
}

