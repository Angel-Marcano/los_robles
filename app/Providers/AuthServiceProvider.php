<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\PaymentReport::class => \App\Policies\PaymentReportPolicy::class,
        \App\Models\ExpenseItem::class => \App\Policies\ExpenseItemPolicy::class,
    \App\Models\Ownership::class => \App\Policies\OwnershipPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
