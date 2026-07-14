<?php

namespace App\Util\Php;

class PhpUploadLimit
{
    public static function maxKilobytes(): int
    {
        $mb = (int) env('APP_UPLOAD_MAX_MB', 12);
        if ($mb <= 0) {
            $mb = 12;
        }

        return $mb * 1024;
    }
}
