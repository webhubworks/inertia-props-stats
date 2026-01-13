# Inertia Props Stats

A Laravel package that measures the size of InertiaJS props and provides statistics about them. This tool helps you monitor and optimize your Inertia payloads during development.

## What it does

This package:

- **Measures payload size**: Automatically calculates the total size of your Inertia props in KB
- **Warns about large payloads**: Displays console warnings and an expandable stats panel when props exceed the configured threshold
- **Detects duplicate keys**: Identifies when the same key is used in both shared and page props
- **Development-only**: Only collects stats in non-production environments to avoid unnecessary overhead
- **Configurable exceptions**: Control when exceptions are thrown for duplicate keys or size violations
- **Flare integration**: Optionally reports large payloads to Spatie Flare for monitoring
- **Ray integration**: Sends payload statistics to Ray for debugging

The package provides detailed statistics including:
- Total payload size (shared + page props)
- Component-specific props size
- Configured threshold
- Amount exceeded (if applicable)
- Duplicate keys between shared and page props

## Installation

Install the package via Composer:

```bash
composer require webhubworks/inertia-props-stats
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag="inertia-props-stats-config"
```

This will create a `config/inertia-props-stats.php` file where you can customize:

```php
return [
    'enabled' => env('INERTIA_PAYLOAD_SIZE_MEASUREMENT_ENABLED', true),

    'payload_size' => [
        'threshold_in_kb' => (int) env('INERTIA_PAYLOAD_SIZE_THRESHOLD_IN_KB', 500),
    ],

    'throw_exception' => [
        'on_duplicate_keys' => env('INERTIA_PROPS_THROW_EXCEPTION_ON_DUPLICATE_KEYS', true),
        'when_component_props_size_exceed_total_props_size' => env('INERTIA_PROPS_THROW_WHEN_COMPONENT_PROPS_EXCEED_TOTAL_PROPS_SIZE', true),
    ],
];
```

Configuration options:
- `enabled`: Enable/disable stats collection globally (disabled in production by default)
- `payload_size.threshold_in_kb`: Size threshold in KB before warnings are shown
- `throw_exception.on_duplicate_keys`: Throw an exception when duplicate keys are detected in shared and page props
- `throw_exception.when_component_props_size_exceed_total_props_size`: Throw an exception when component props exceed total props size

Recommended thresholds:
- Soft limit: 100-200KB
- Hard limit: 500KB

## Vue.js Component

An example Vue.js component is included in `resources/js/components/InertiaPropsStatsPanel.vue` that you can copy and paste into your main app.

This component:
- Monitors the `_inertiaPayloadTotalSizeInKb` prop
- Displays an expandable stats panel when the payload exceeds the threshold
- Shows detailed payload statistics including size, threshold, and duplicate keys
- Provides a cleaner UI for development environments with more detailed error messages

To use it, copy the component to your Vue.js application and include it in your main layout:

```vue
<script setup>
import InertiaPropsStatsPanel from '@/components/InertiaPropsStatsPanel.vue'
</script>

<template>
  <div>
    <InertiaPropsStatsPanel />
    <!-- Your app content -->
  </div>
</template>
```

Note: The component requires `lucide-vue-next` for icons. Install it with:

```bash
npm install lucide-vue-next
```

## License

The MIT License (MIT). Please see the license file for more information.
