<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
        Invoice::observe(InvoiceObserver::class);
        PaymentReport::observe(PaymentReportObserver::class);
    }
}
