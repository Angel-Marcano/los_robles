<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ApiRateLimit
{
    protected int $windowSeconds;
    protected int $defaultLimit;
    protected int $elevatedLimit;

    public function __construct()
    {
        $this->windowSeconds = (int) env('RATE_LIMIT_WINDOW', 60);
        $this->defaultLimit = (int) env('RATE_LIMIT_BASE', 60);
        $this->elevatedLimit = (int) env('RATE_LIMIT_SUPER', 120);
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $key = 'rate:' . ($user ? $user->id : $request->ip());
        $limit = $this->defaultLimit;
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            $limit = $this->elevatedLimit;
        }

        $data = Cache::get($key, [
            'count' => 0,
            'expires' => now()->addSeconds($this->windowSeconds),
        ]);

        if (now()->gt($data['expires'])) {
            $data = [
                'count' => 0,
                'expires' => now()->addSeconds($this->windowSeconds),
            ];
        }

        $data['count']++;
        Cache::put($key, $data, $data['expires']);
        $remaining = max(0, $limit - $data['count']);

        $policy = sprintf('window=%ds; base=%d; super_admin=%d', $this->windowSeconds, $this->defaultLimit, $this->elevatedLimit);

        if ($data['count'] > $limit) {
            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => __('errors.RATE_LIMIT_EXCEEDED'),
                ],
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $data['expires']->getTimestamp(),
                'X-RateLimit-Policy' => $policy,
                'Retry-After' => $data['expires']->diffInSeconds(now()),
            ]);
        }

        // Pasar datos al request para que el controlador pueda incluirlos en meta
        $request->attributes->set('ratelimit_limit', $limit);
        $request->attributes->set('ratelimit_remaining', $remaining);
        $request->attributes->set('ratelimit_reset', $data['expires']->getTimestamp());
        $request->attributes->set('ratelimit_policy', $policy);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $data['expires']->getTimestamp(),
            'X-RateLimit-Policy' => $policy,
        ]);
    }
}
