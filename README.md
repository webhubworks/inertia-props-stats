# Weclapp API for Laravel

## Configuration
Make sure to have set the following environment variables in your `.env` file:
```dotenv
WECLAPP_API_BASE_URL=
WECLAPP_AUTH_TOKEN=
```

## Usage
Example usage:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webhub\InertiaPropsStats\Facades\Weclapp;

class WeclappTest extends Command
{
    protected $signature = 'weclapp:test';

    protected $description = 'Tests the connection to weclapp';

    public function handle()
    {
        $weclappClient = Weclapp::create();

        $result = $weclappClient->getArticleCount();

        dd($result);
    }
}

```

## Logging
By default, logging the requests and responses is enabled. You may set the `WECLAPP_ENABLE_LOGGING` environment variable to `false` to disable logging.
Logs will be written to a daily file in the `storage/logs` directory. The log file will be named `weclapp-api-YYYY-MM-DD.log`.
