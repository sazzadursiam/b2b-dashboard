<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function __invoke()
    {
        $status = [
            'database' => 'ok',
            'cache' => 'ok',
            'queue' => 'ok',
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $status['database'] = 'failed';
        }

        try {
            Cache::put('health_check', 'ok', 10);
            Cache::get('health_check');
        } catch (\Throwable $e) {
            $status['cache'] = 'failed';
        }

        try {
            Queue::connection()->getConnectionName();
        } catch (\Throwable $e) {
            $status['queue'] = 'failed';
        }

        $httpStatus = in_array('failed', $status) ? 500 : 200;

        return response()->json($status, $httpStatus);
    }
}
