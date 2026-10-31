<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Logging;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Sunny\Foundation\Logging\ActivityLogger;
use Sunny\Foundation\Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
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
