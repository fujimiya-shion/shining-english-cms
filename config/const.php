<?php

return [
    'pagination' => [
        'default_per_page' => 15,
    ],

    'star' => [
        'init' => (int) env('REGISTER_STAR_INIT', 15),
        'daily_checkin' => (int) env('DAILY_CHECKIN_STAR', 1),
        'review_rating_only' => (int) env('REVIEW_STAR_RATING_ONLY', 1),
        'review_full_content' => (int) env('REVIEW_STAR_FULL_CONTENT', 4),
        'course_complete' => (int) env('COURSE_COMPLETE_STAR', 10),
    ],
];
