<?php

return [
    'payload_size' => [
        'enabled' => env('INERTIA_PAYLOAD_SIZE_MEASUREMENT_ENABLED', true),

        /**
         * Recommendations:
         * - Soft limit: 100-200KB
         * - Hard limit: 500KB
         */
        'threshold_in_kb' => (int) env('INERTIA_PAYLOAD_SIZE_THRESHOLD_IN_KB', 500),
    ],
];
