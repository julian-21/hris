<?php
// app/Helpers/NotificationHelper.php

namespace App\Helpers;

use App\Models\Notification;

class NotificationHelper
{
    public static function create($userId, $type, $title, $message, $data = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function createForMultiple($userIds, $type, $title, $message, $data = null)
    {
        foreach ($userIds as $userId) {
            self::create($userId, $type, $title, $message, $data);
        }
    }
}