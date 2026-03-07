<?php

use Illuminate\Support\Facades\Broadcast;
 
Broadcast::channel('test-channel', function ($user) {
    return true; // O tu lógica de autenticación
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});
