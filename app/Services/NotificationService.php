<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function notify(int $userId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'url'     => $url,
            'read'    => false,
        ]);
    }

    public static function notifyOwners(int $apartmentId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        $ownerships = \App\Models\Ownership::where('apartment_id', $apartmentId)->where('active', true)->get();
        foreach ($ownerships as $own) {
            self::notify($own->user_id, $type, $title, $body, $url);
        }
    }
}