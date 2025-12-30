<?php

namespace IBuildWebApps\SchemaGenerator\Commands;

use IBuildWebApps\SchemaGenerator\Generators\RequestGenerator;
use IBuildWebApps\SchemaGenerator\Services\SchemaReader;
use IBuildWebApps\SchemaGenerator\Services\StringHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateRequestCommand extends Command
{
    protected $signature = 'schema:request {table : Table name (supports wildcards like users or user*)}
                                           {--database= : Database name (defaults to DB_DATABASE)}
                                           {--force : Overwrite existing files}';

    protected $description = 'Generate Form Request classes from MySQL database schema';

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

        if (!$this->option('force') && !$this->confirm('Generate request classes for these tables?')) {
            return self::SUCCESS;
        }

        $generator = new RequestGenerator($schema, config('schema-generator', []));
        $outputPath = config('schema-generator.request_path', app_path('Http/Requests'));

        File::ensureDirectoryExists($outputPath);

        foreach ($tables as $table) {
            $content = $generator->generate($table->name);
            $filename = $outputPath . '/' . StringHelper::studly($table->name) . 'Request.php';

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
        $outputPath = config('schema-generator.request_path', app_path('Http/Requests'));

        $this->newLine();
        $this->info('Tables to generate requests for:');

        foreach ($tables as $table) {
            $filename = $outputPath . '/' . StringHelper::studly($table->name) . 'Request.php';
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
