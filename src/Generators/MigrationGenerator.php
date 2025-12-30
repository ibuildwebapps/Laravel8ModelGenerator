<?php

namespace IBuildWebApps\SchemaGenerator\Generators;

use IBuildWebApps\SchemaGenerator\Services\SchemaReader;
use IBuildWebApps\SchemaGenerator\Services\StringHelper;
use Illuminate\Support\Collection;

class MigrationGenerator
{
    private const TYPE_MAP = [
        'bigint' => 'bigInteger',
        'blob' => 'binary',
        'boolean' => 'boolean',
        'char' => 'char',
        'date' => 'date',
        'datetime' => 'dateTime',
        'decimal' => 'decimal',
        'double' => 'double',
        'enum' => 'enum',
        'float' => 'float',
        'int' => 'integer',
        'integer' => 'integer',
        'json' => 'json',
        'jsonb' => 'jsonb',
        'longtext' => 'longText',
        'mediumint' => 'mediumInteger',
        'mediumtext' => 'mediumText',
        'smallint' => 'smallInteger',
        'text' => 'text',
        'time' => 'time',
        'timestamp' => 'timestamp',
        'tinyint' => 'tinyInteger',
        'varchar' => 'string',
    ];

    public function __construct(
        private readonly SchemaReader $schema
    ) {}

    public function generate(string $table): string
    {
        $columns = $this->schema->getColumns($table)->filter(fn($col) => $col->name !== 'id');
        $foreignKeys = $this->schema->getForeignKeys($table);

        $className = 'Create' . StringHelper::studly($table) . 'Table';

        return $this->buildTemplate([
            'className' => $className,
            'tableName' => $table,
            'columns' => $this->buildColumnDefinitions($columns),
            'foreignKeys' => $this->buildForeignKeyDefinitions($foreignKeys),
        ]);
    }

    public function getFilename(string $table): string
    {
        return date('Y_m_d_His') . '_create_' . strtolower($table) . '_table.php';
    }

    private function buildColumnDefinitions(Collection $columns): string
    {
        $statements = [];
        $rawStatements = [];

        foreach ($columns as $column) {
            $type = strtolower($column->data_type);

            // Handle unsupported blob types with raw SQL
            if (in_array($type, ['longblob', 'mediumblob'])) {
                $blobType = strtoupper($type);
                $rawStatements[] = "        DB::statement('ALTER TABLE ' . \$this->table . ' ADD {$column->name} {$blobType}');";
                continue;
            }

            $method = self::TYPE_MAP[$type] ?? null;
            if (!$method) {
                continue;
            }

            $definition = $this->buildColumnDefinition($column, $method);
            $statements[] = "            {$definition};";
        }

        $result = implode("\n", $statements);

        if ($rawStatements) {
            $result .= "\n        });\n\n" . implode("\n", $rawStatements);
            return $result;
        }

        return $result;
    }

    private function buildColumnDefinition(object $column, string $method): string
    {
        $args = ["'{$column->name}'"];

        // Add size/precision arguments based on type
        switch ($method) {
            case 'char':
            case 'string':
                if ($column->max_length) {
                    $args[] = $column->max_length;
                }
                break;
            case 'decimal':
            case 'double':
                if ($column->precision && $column->scale !== null) {
                    $args[] = $column->precision;
                    $args[] = $column->scale;
                }
                break;
            case 'enum':
                $values = preg_replace("/^enum\((.+)\)$/", '$1', $column->column_type);
                $args[] = "[{$values}]";
                break;
        }

        $definition = "\$table->{$method}(" . implode(', ', $args) . ")";

        // Add modifiers
        if ($column->nullable === 'YES') {
            $definition .= '->nullable()';
        }

        if ($column->column_key === 'UNI') {
            $definition .= '->unique()';
        }

        if ($column->default !== null && $column->default !== '') {
            $default = is_numeric($column->default) ? $column->default : "'{$column->default}'";
            $definition .= "->default({$default})";
        }

        return $definition;
    }

    private function buildForeignKeyDefinitions(Collection $foreignKeys): string
    {
        if ($foreignKeys->isEmpty()) {
            return '';
        }

        $lines = $foreignKeys->map(function ($fk) {
            return "        \$table->foreign('{$fk->column_name}')"
                . "->references('{$fk->referenced_column}')"
                . "->on('{$fk->referenced_table}');";
        })->implode("\n");

        return "\n\n{$lines}";
    }

    private function buildTemplate(array $data): string
    {
        $foreignKeys = $data['foreignKeys'];

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected \$table = '{$data['tableName']}';

    public function up(): void
    {
        Schema::create(\$this->table, function (Blueprint \$table) {
            \$table->id();
{$data['columns']}{$foreignKeys}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(\$this->table);
    }
};

PHP;
    }
}
