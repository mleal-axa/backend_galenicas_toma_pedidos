<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('sanctum:prune-expired --hours=24')->daily();

        $schedule->command('extraccion:clientes')->hourly();
        $schedule->command('extraccion:direcciones-clientes')->hourly();
        $schedule->command('extraccion:listas-precios')->daily();
        $schedule->command('extraccion:ubicaciones')->daily();
        $schedule->command('extraccion:inventario')->everyTenMinutes();
        $schedule->command('extraccion:inventario-kits')->everyTenMinutes();
        $schedule->command('extraccion:catalogo')->hourly();
        $schedule->command('extraccion:kits')->everyTwoHours();
        $schedule->command('extraccion:promociones')->hourly();
        $schedule->command('extraccion:usuarios')->everyFourHours();
        $schedule->command('extraccion:medicos')->daily();
        $schedule->command('extraccion:detalles-inventarios')->everyTenMinutes();
        $schedule->command('extraccion:catalogo-abbott')->daily();

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
