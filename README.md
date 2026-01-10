# Inertia Props Stats

A Laravel package that measures the size of InertiaJS props and provides statistics about them. This tool helps you monitor and optimize your Inertia payloads during development.

## What it does

This package:

- **Measures payload size**: Automatically calculates the total size of your Inertia props in KB
- **Warns about large payloads**: Displays console warnings and toast notifications when props exceed the configured threshold
- **Detects duplicate keys**: Identifies when the same key is used in both shared and page props
- **Development-only**: Only shows warnings in non-production environments
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
    'payload_size' => [
        'enabled' => env('INERTIA_PAYLOAD_SIZE_MEASUREMENT_ENABLED', true),
        'threshold_in_kb' => (int) env('INERTIA_PAYLOAD_SIZE_THRESHOLD_IN_KB', 500),
    ],
];
```

Recommended thresholds:
- Soft limit: 100-200KB
- Hard limit: 500KB

## Vue.js Component

An example Vue.js component is included in `resources/js/components/InertiaPropsStats.vue` that you can copy and paste into your main app.

This component:
- Monitors the `_inertiaPayloadTotalSizeInKb` prop
- Displays toast notifications when the payload exceeds the threshold
- Shows a console table with detailed payload statistics
- Warns about duplicate keys in shared and page props

To use it, copy the component to your Vue.js application and include it in your main layout:

```vue
<script setup>
import InertiaPropsStats from '@/components/InertiaPropsStats.vue'
</script>

<template>
  <div>
    <InertiaPropsStats />
    <!-- Your app content -->
  </div>
</template>
```

Note: The component requires `vue-sonner` for toast notifications. Install it with:

```bash
npm install vue-sonner
```

## License

The MIT License (MIT). Please see the license file for more information.