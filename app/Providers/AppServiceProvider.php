<?php

namespace App\Providers;

use App\Repositories\MasterDataRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Octane 等の長寿命プロセスでもリクエスト間にマスターを持ち越さない。
        $this->app->scoped(MasterDataRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
