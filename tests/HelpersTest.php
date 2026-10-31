<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests;

use Sunny\Foundation\SunnyManager;

class HelpersTest extends TestCase
{
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
