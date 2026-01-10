<?php

namespace Webhub\InertiaPropsStats;

use Inertia\ResponseFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class InertiaPropsStatsServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(ResponseFactory::class, InertiaResponseFactory::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('inertia-props-stats')
            ->hasConfigFile('inertia-props-stats');
    }
}
