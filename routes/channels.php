<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', function ($user, $userId) {
    return $user !== null && (int) $user->getKey() === (int) $userId;
});
