<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckService
{
    /**
     * @return array{status: 'ok'|'fail', checks: array<string, array{status: string, message?: string}>}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->database(),
            'queue' => $this->queue(),
            'storage' => $this->storage(),
        ];

        $failed = array_filter($checks, fn (array $check) => $check['status'] === 'fail');

        return [
            'status' => $failed === [] ? 'ok' : 'fail',
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function database(): array
    {
        try {
            DB::select('select 1');

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function queue(): array
    {
        $connection = (string) config('queue.default');
        $driver = (string) config("queue.connections.{$connection}.driver");

        if ($driver !== 'rabbitmq') {
            return ['status' => 'ok', 'message' => "driver: {$driver}"];
        }

        /** @var array{host: string, port: int} $host */
        $host = config("queue.connections.{$connection}.hosts.0");
        $socket = @fsockopen($host['host'], (int) $host['port'], $errno, $error, 2.0);

        if ($socket === false) {
            return ['status' => 'fail', 'message' => "RabbitMQ is unreachable: {$error}"];
        }

        fclose($socket);

        return ['status' => 'ok', 'message' => 'driver: rabbitmq'];
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function storage(): array
    {
        $root = Storage::disk((string) config('import.images.disk'))->path('');

        return is_dir($root) && is_writable($root)
            ? ['status' => 'ok']
            : ['status' => 'fail', 'message' => 'Images storage is not writable.'];
    }
}
