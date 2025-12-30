<?php

namespace IBuildWebApps\SchemaGenerator\Commands;

use IBuildWebApps\SchemaGenerator\Generators\MigrationGenerator;
use IBuildWebApps\SchemaGenerator\Services\SchemaReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateMigrationCommand extends Command
{
    protected $signature = 'schema:migration {table : Table name (supports wildcards like users or user*)}
                                             {--database= : Database name (defaults to DB_DATABASE)}
                                             {--force : Overwrite existing files}';

    protected $description = 'Generate migrations from MySQL database schema';

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

        $this->info('Tables to generate migrations for:');
        foreach ($tables as $table) {
            $this->line("  - {$table->name}");
        }
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Generate migrations for these tables?')) {
            return self::SUCCESS;
        }

        $generator = new MigrationGenerator($schema);
        $outputPath = database_path('migrations');

        File::ensureDirectoryExists($outputPath);

        foreach ($tables as $table) {
            $content = $generator->generate($table->name);
            $filename = $outputPath . '/' . $generator->getFilename($table->name);

            File::put($filename, $content);
            $this->info("Created: {$filename}");

            // Small delay to ensure unique timestamps
            usleep(100000);
        }

        return self::SUCCESS;
    }
}
