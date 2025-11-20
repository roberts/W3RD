<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\GameEngine\GameEngineFactory;

class SystemHealthService
{
    /**
     * Check health status of all critical services.
     *
     * @return array
     */
    public function checkHealth(): array
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'game_engine' => $this->checkGameEngine(),
        ];

        $allHealthy = collect($services)->every(fn($service) => $service['status'] === 'healthy');

        return [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ];
    }

    /**
     * Check database connectivity.
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $latency = $this->measureLatency(fn() => DB::select('SELECT 1'));
            
            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => 'Database connection failed',
            ];
        }
    }

    /**
     * Check cache (Redis) connectivity.
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            
            $latency = $this->measureLatency(fn() => Cache::get('test_key'));
            
            return [
                'status' => $value === 'test' ? 'healthy' : 'degraded',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => 'Cache connection failed',
            ];
        }
    }

    /**
     * Check queue connectivity.
     */
    private function checkQueue(): array
    {
        try {
            $size = Queue::size();
            
            return [
                'status' => 'healthy',
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => 'Queue connection failed',
            ];
        }
    }

    /**
     * Check game engine availability.
     */
    private function checkGameEngine(): array
    {
        try {
            // Test that game engine factory can instantiate a simple game
            $engine = GameEngineFactory::create('connect-four');
            
            return [
                'status' => 'healthy',
                'engines_available' => count(config('protocol.games', [])),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'error' => 'Game engine initialization failed',
            ];
        }
    }

    /**
     * Measure operation latency in milliseconds.
     */
    private function measureLatency(callable $operation): float
    {
        $start = microtime(true);
        $operation();
        $end = microtime(true);
        
        return round(($end - $start) * 1000, 2);
    }
}
