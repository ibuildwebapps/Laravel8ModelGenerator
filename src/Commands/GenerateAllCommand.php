<?php

namespace IBuildWebApps\SchemaGenerator\Commands;

use Illuminate\Console\Command;

class GenerateAllCommand extends Command
{
    protected $signature = 'schema:all {table : Table name (supports wildcards like users or user*)}
                                       {--database= : Database name (defaults to DB_DATABASE)}
                                       {--force : Overwrite existing files}';

    protected $description = 'Generate models, migrations, and requests from MySQL database schema';

    public function handle(): int
    {
        $table = $this->argument('table');
        $database = $this->option('database');
        $force = $this->option('force');

        $options = ['table' => $table];
        if ($database) {
            $options['--database'] = $database;
        }
        if ($force) {
            $options['--force'] = true;
        }

        $this->info('Generating Models...');
        $this->call('schema:model', $options);

        $this->newLine();
        $this->info('Generating Migrations...');
        $this->call('schema:migration', $options);

        $this->newLine();
        $this->info('Generating Requests...');
        $this->call('schema:request', $options);

        $this->newLine();
        $this->info('All artifacts generated successfully!');

        return self::SUCCESS;
    }
}
