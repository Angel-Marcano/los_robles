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
        // Recalcular morosidad y enviar recordatorios semanalmente los lunes a las 08:00
        $schedule->command('invoices:notify-overdue')->weeklyOn(1, '08:00');

        // Recordatorio mensual de facturas pendientes el día 1 de cada mes a las 09:00
        $schedule->command('invoices:notify-pending')->monthlyOn(1, '09:00');

        // Backup automático de BD master y tenant a las 00:00, retención 7 días
        $schedule->command('db:backup --retention=7')->dailyAt('00:00');
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
