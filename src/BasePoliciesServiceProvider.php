<?php

namespace EduardoRibeiroDev\BasePolicies;

use EduardoRibeiroDev\BasePolicies\Console\Commands\GeneratePermissionsCommand;
use Illuminate\Support\ServiceProvider;
use EduardoRibeiroDev\BasePolicies\Console\Commands\MakeBasePolicyCommand;
use EduardoRibeiroDev\BasePolicies\Console\Commands\GeneratePoliciesCommand;
use EduardoRibeiroDev\BasePolicies\Services\BasePoliciesService;
use Spatie\Permission\Traits\HasRoles;

class BasePoliciesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'base-policies');

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/base-policies.php' => config_path('base-policies.php'),
            ], 'base-policies-config');

            // Publish stubs
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/vendor/base-policies'),
            ], 'base-policies-stubs');

            // Register commands
            $this->commands([
                MakeBasePolicyCommand::class,
                GeneratePoliciesCommand::class,
                GeneratePermissionsCommand::class,
            ]);
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/base-policies.php',
            'base-policies'
        );

        $this->app->singleton('base-policies', fn() => new BasePoliciesService);
    }
}
