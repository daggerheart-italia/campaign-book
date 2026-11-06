<?php

return [
    'authenticated' => env('THROTTLE_AUTHENTICATED', 120),
    'polling' => env('THROTTLE_POLLING', 120),
    'queue_advance' => env('THROTTLE_QUEUE_ADVANCE', 60),
    'guest' => env('THROTTLE_GUEST', 5),
    'public' => env('THROTTLE_PUBLIC', 60),
];
