<?php

declare(strict_types=1);

/**
 * 活动日志测试
 *
 * @package     Sunny\Foundation\Tests\Logging
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Logging;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Sunny\Foundation\Logging\ActivityLogger;
use Sunny\Foundation\Tests\TestCase;

/**
 * 活动日志测试
 */
class ActivityLoggerTest extends TestCase
{
    /**
     * 密码和令牌字段应被打码，普通字段保留。
     */
    public function test_it_redacts_sensitive_context(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $log) use (&$logs): void {
            $logs[] = $log;
        });

        $this->app->make(ActivityLogger::class)->info('user.created', [
            'password' => 'secret',
            'email' => 'a@b.c',
            'nested' => ['access_token' => 'abc'],
        ]);

        $log = $this->findLog($logs, 'user.created');

        $this->assertSame('[redacted]', $log->context['password'] ?? null);
        $this->assertSame('a@b.c', $log->context['email'] ?? null);
        $this->assertSame('[redacted]', $log->context['nested']['access_token'] ?? null);
    }

    /**
     * 默认只记异常类名和位置，不记堆栈。
     */
    public function test_it_records_exception_class_without_a_trace_by_default(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $log) use (&$logs): void {
            $logs[] = $log;
        });

        $this->app->make(ActivityLogger::class)->exception(new RuntimeException('broken'), 'failed');

        $log = $this->findLog($logs, 'failed');

        $this->assertSame('error', $log->level);
        $this->assertSame(RuntimeException::class, $log->context['exception']['class'] ?? null);
        $this->assertArrayNotHasKey('trace', $log->context['exception'] ?? []);
    }

    /**
     * 从已捕获的日志里找出指定消息。
     *
     * @param  array<int, MessageLogged>  $logs
     */
    private function findLog(array $logs, string $message): MessageLogged
    {
        foreach ($logs as $log) {
            if ($log->message === $message) {
                return $log;
            }
        }

        $this->fail('Log ['.$message.'] was not written.');
    }
}

