<?php

namespace IBuildWebApps\SchemaGenerator;

use IBuildWebApps\SchemaGenerator\Commands\GenerateAllCommand;
use IBuildWebApps\SchemaGenerator\Commands\GenerateMigrationCommand;
use IBuildWebApps\SchemaGenerator\Commands\GenerateModelCommand;
use IBuildWebApps\SchemaGenerator\Commands\GenerateRequestCommand;
use Illuminate\Support\ServiceProvider;

class SchemaGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/schema-generator.php', 'schema-generator');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/schema-generator.php' => config_path('schema-generator.php'),
            ], 'schema-generator-config');

            $this->commands([
                GenerateModelCommand::class,
                GenerateMigrationCommand::class,
                GenerateRequestCommand::class,
                GenerateAllCommand::class,
            ]);
        }
    }
}
