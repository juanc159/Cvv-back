<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');

// Limpia tokens de Passport revocados/expirados (la tabla oauth_access_tokens
// crece sin límite: cada login crea un token y no se purgaban).
Schedule::command('passport:purge')->daily()->at('02:00');
