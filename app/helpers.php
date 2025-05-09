<?php

use Illuminate\Support\Str;

if (! function_exists('image_tag')) {
    function image_tag($id, $type)
    {
        return md5($id.'-'.$type);
    }
}

if (! function_exists('flexible_cache_time')) {
    function flexible_cache_time(array $times)
    {
        return random_int($times[0], $times[1]);
    }
}

if (! function_exists('human_filesize')) {
    function human_filesize($bytes, $decimals = 2)
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
    }
}

if (! function_exists('human_bytes')) {
    function human_bytes($bytes, $decimals = 2)
    {
        $size = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1000, $factor)).@$size[$factor].'ps';
    }
}

if (! function_exists('video_resolution')) {
    /**
     * Convert resolution dimensions to common video format names
     *
     * @param  int  $length  Width or height in pixels
     * @param  int  $width  The other dimension in pixels
     * @return string Common resolution format (4K/2K/1080p/etc)
     */
    function video_resolution(int $length, int $width): string
    {
        $max = max($length, $width);

        $formats = [
            '8K' => 7000,    // Approximate threshold for 7680px
            '4K' => 3500,    // Covers 3840-4096px
            '2K' => 2000,    // Covers 2048-2560px
            '1080p' => 1800, // Full HD
            '720p' => 1200,  // HD
            '480p' => 700,   // SD
            '360p' => 500,
            '240p' => 400,
        ];

        foreach ($formats as $format => $threshold) {
            if ($max >= $threshold) {
                return $format;
            }
        }

        return 'Other';
    }
}

if (! function_exists('static_base')) {
    function static_base()
    {
        return config('app.static_file_base');
    }
}

if (! function_exists('get_gravatar_url')) {
    function get_gravatar_url($email)
    {
        $address = strtolower(trim($email));
        $hash = hash('sha256', $address);

        return 'https://gravatar.com/avatar/'.$hash;
    }
}

if (! function_exists('routeIsMe')) {
    function routeIsMe($me)
    {
        return Str::contains(Illuminate\Support\Facades\Route::currentRouteAction(), $me);
    }
}

if (! function_exists('telegramEscape')) {
    function telegramEscape($text)
    {
        $escapeChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

        return str_replace(
            $escapeChars,
            array_map(fn ($c) => "\\$c", $escapeChars),
            $text
        );
    }
}
