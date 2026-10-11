<?php

namespace App\Providers;

use App\Repositories\Contracts\ImportRepositoryInterface;
use App\Repositories\Contracts\ProductAttributeRepositoryInterface;
use App\Repositories\Contracts\ProductImageRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\ImportRepository;
use App\Repositories\Eloquent\ProductAttributeRepository;
use App\Repositories\Eloquent\ProductImageRepository;
use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ProductRepositoryInterface::class => ProductRepository::class,
        ProductAttributeRepositoryInterface::class => ProductAttributeRepository::class,
        ProductImageRepositoryInterface::class => ProductImageRepository::class,
        ImportRepositoryInterface::class => ImportRepository::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        RateLimiter::for('imports', function (Request $request) {
            return Limit::perMinute((int) config('import.rate_limit_per_minute'))
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()))
                ->response(fn () => response()->json([
                    'message' => 'Слишком много импортов. Попробуйте через минуту.',
                ], 429));
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->ip());
        });
    }
}
