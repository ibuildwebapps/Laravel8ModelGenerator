<?php

namespace IBuildWebApps\SchemaGenerator\Commands;

use IBuildWebApps\SchemaGenerator\Generators\ModelGenerator;
use IBuildWebApps\SchemaGenerator\Services\SchemaReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateModelCommand extends Command
{
    protected $signature = 'schema:model {table : Table name (supports wildcards like users or user*)}
                                         {--database= : Database name (defaults to DB_DATABASE)}
                                         {--force : Overwrite existing files}';

    protected $description = 'Generate Eloquent models from MySQL database schema';

    public function handle(): int
    {
        $database = $this->option('database') ?? config('database.connections.mysql.database');
        $pattern = $this->argument('table');

        if (!$database) {
            $this->error('No database specified. Use --database or set DB_DATABASE.');
            return self::FAILURE;
        }

        $schema = new SchemaReader($database);
        $tables = $schema->getTables($pattern);

        if ($tables->isEmpty()) {
            $this->error("No tables found matching: {$pattern}");
            return self::FAILURE;
        }

        $this->displayTables($tables);

        if (!$this->option('force') && !$this->confirm('Generate models for these tables?')) {
            return self::SUCCESS;
        }

        $generator = new ModelGenerator($schema, config('schema-generator', []));
        $outputPath = config('schema-generator.model_path', app_path('Models'));

        File::ensureDirectoryExists($outputPath);

        foreach ($tables as $table) {
            $content = $generator->generate($table->name);
            $filename = $outputPath . '/' . \IBuildWebApps\SchemaGenerator\Services\StringHelper::studly($table->name) . '.php';

            if (File::exists($filename) && !$this->option('force')) {
                $this->warn("Skipped: {$filename} (already exists)");
                continue;
            }

            File::put($filename, $content);
            $this->info("Created: {$filename}");
        }

        return self::SUCCESS;
    }

    private function displayTables($tables): void
    {
        $outputPath = config('schema-generator.model_path', app_path('Models'));

        $this->newLine();
        $this->info('Tables to generate:');

        foreach ($tables as $table) {
            $filename = $outputPath . '/' . \IBuildWebApps\SchemaGenerator\Services\StringHelper::studly($table->name) . '.php';
            $exists = File::exists($filename);

            if ($exists) {
                $this->line("  <fg=red>{$table->name}</> (exists)");
            } else {
                $this->line("  <fg=green>{$table->name}</>");
            }
        }

        $this->newLine();
    }
}
