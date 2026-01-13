<?php

return [
    'enabled' => env('INERTIA_PAYLOAD_SIZE_MEASUREMENT_ENABLED', true),

    'payload_size' => [
        /**
         * Recommendations:
         * - Soft limit: 100-200KB
         * - Hard limit: 500KB
         */
        'threshold_in_kb' => (int) env('INERTIA_PAYLOAD_SIZE_THRESHOLD_IN_KB', 500),
    ],

    'throw_exception' => [
        'on_duplicate_keys' => env('INERTIA_PROPS_THROW_EXCEPTION_ON_DUPLICATE_KEYS', true),
        'when_component_props_size_exceed_total_props_size' => env('INERTIA_PROPS_THROW_WHEN_COMPONENT_PROPS_EXCEED_TOTAL_PROPS_SIZE', true),
    ],
];
