<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\{Invoice,PaymentReport};
use App\Observers\{InvoiceObserver,PaymentReportObserver};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        Invoice::observe(InvoiceObserver::class);
        PaymentReport::observe(PaymentReportObserver::class);

        // Share tenant name with all views
        view()->composer('*', function ($view) {
            $condo = app()->bound('currentCondominium') ? app('currentCondominium') : null;
            $view->with('appName', $condo ? $condo->name : config('app.name', 'Los Robles'));
        });
    }
}
