<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Registrar comandos explícitos si se requiere.
     * TenantsMigrate se carga automáticamente desde app/Console/Commands por $this->load(),
     * pero se puede forzar aquí si en el futuro se necesita autoload distinto.
     */
    protected $commands = [
        \App\Console\Commands\TenantsMigrate::class,
        \App\Console\Commands\CreateTenant::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
