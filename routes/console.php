<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas Programadas Automáticas del Sistema
|--------------------------------------------------------------------------
| Actualización automática de la tasa oficial del BCV una vez al día:
| A las 8:00 PM (20:00) hora de Venezuela (America/Caracas)
*/
Schedule::command('bcv:actualizar --sync-all')
    ->dailyAt('20:00')
    ->timezone('America/Caracas')
    ->name('actualizar-tasa-bcv-noche-08pm')
    ->withoutOverlapping();
