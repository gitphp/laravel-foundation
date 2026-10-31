<?php

declare(strict_types=1);

/**
 * 数组处理测试
 *
 * @package     Sunny\Foundation\Tests\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Support;

use InvalidArgumentException;
use Sunny\Foundation\Support\Arr;
use Sunny\Foundation\Tests\TestCase;

/**
 * 数组处理测试
 */
class ArrTest extends TestCase
{
    /**
     * 树结构和点号读写应保持父子关系。
     */
    public function test_it_builds_a_tree_and_reads_dot_keys(): void
    {
        $tree = Arr::tree([
            ['id' => 1, 'parent_id' => null, 'name' => 'root'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'child'],
            ['id' => 3, 'parent_id' => 2, 'name' => 'leaf'],
        ]);

        $this->assertSame('root', $tree[0]['name']);
        $this->assertSame('child', $tree[0]['children'][0]['name']);
        $this->assertSame('leaf', $tree[0]['children'][0]['children'][0]['name']);

        $profile = [];
        Arr::set($profile, 'user.name', '晴');
        $this->assertSame('晴', Arr::get($profile, 'user.name'));
    }

    /**
     * 重命名、分组和去重应保留第一次出现的数据。
     */
    public function test_it_renames_groups_and_drops_duplicates(): void
    {
        $items = [
            ['id' => 1, 'type' => 'a'],
            ['id' => 2, 'type' => 'a'],
            ['id' => 1, 'type' => 'b'],
        ];

        $this->assertSame(['title' => 'a'], Arr::rename(['name' => 'a'], ['name' => 'title']));
        $this->assertCount(2, Arr::groupBy($items, 'type')['a']);
        $this->assertCount(2, Arr::uniqueBy($items, 'id'));
        $this->assertFalse(Arr::isAssoc([1, 2]));
        $this->assertTrue(Arr::isAssoc(['name' => '晴']));
    }

    /**
     * 树节点编号重复时应拒绝。
     */
    public function test_it_rejects_duplicate_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Arr::tree([
            ['id' => 1, 'parent_id' => null],
            ['id' => 1, 'parent_id' => null],
        ]);
    }
}

