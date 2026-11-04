<?php

return [

    'dev_pages' => (bool) env('POINTS_DEV_PAGES', false),

    'idle_minutes' => [
        'playing' => (int) env('POINTS_IDLE_PLAYING_MINUTES', 180),
        'lobby'   => (int) env('POINTS_IDLE_LOBBY_MINUTES', 45),
    ],

];
