<?php

return [
    'image_optimization' => [
        'max_width' => (int) env('MEDIA_IMAGE_MAX_WIDTH', 512),
        'max_height' => (int) env('MEDIA_IMAGE_MAX_HEIGHT', 512),
        'webp_quality' => (int) env('MEDIA_IMAGE_WEBP_QUALITY', 82),
    ],
];
