<?php

namespace App\Util\Php;

class PhpUploadLimit
{
    public static function maxKilobytes(): int
    {
        $mb = (int) config('app.upload_max_mb', 12);
        if ($mb <= 0) {
            $mb = 12;
        }

        return $mb * 1024;
    }
}
