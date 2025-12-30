<?php

namespace IBuildWebApps\SchemaGenerator\Generators;

use IBuildWebApps\SchemaGenerator\Services\SchemaReader;
use IBuildWebApps\SchemaGenerator\Services\StringHelper;
use Illuminate\Support\Collection;

class ModelGenerator
{
    public function __construct(
        private readonly SchemaReader $schema,
        private readonly array $config = []
    ) {}

    public function generate(string $table): string
    {
        $columns = $this->schema->getColumns($table);
        $foreignKeys = $this->schema->getForeignKeys($table);
        $referencingTables = $this->schema->getReferencingTables($table);

        $className = StringHelper::studly($table);
        $namespace = $this->config['model_namespace'] ?? 'App\\Models';
        $useSoftDeletes = $columns->contains('name', 'deleted_at');
        $useTimestamps = $columns->contains('name', 'created_at');

        return $this->buildTemplate([
            'namespace' => $namespace,
            'className' => $className,
            'tableName' => $table,
            'useSoftDeletes' => $useSoftDeletes,
            'useTimestamps' => $useTimestamps,
            'fillable' => $this->buildFillable($columns),
            'belongsToRelations' => $this->buildBelongsToRelations($foreignKeys),
            'hasManyRelations' => $this->buildHasManyRelations($referencingTables),
        ]);
    }

    private function buildFillable(Collection $columns): string
    {
        $lines = $columns->map(function ($column) {
            $commented = in_array($column->name, ['id', 'deleted_at']);
            $prefix = $commented ? '//' : '';
            $comment = $column->comment ? " /* {$column->comment} */" : '';

            return "        {$prefix}'{$column->name}', // ({$column->column_type}){$comment}";
        })->implode("\n");

        return "[\n{$lines}\n    ]";
    }

    private function buildBelongsToRelations(Collection $foreignKeys): string
    {
        if ($foreignKeys->isEmpty()) {
            return '';
        }

        $counts = $foreignKeys->countBy('referenced_table');

        return $foreignKeys->map(function ($fk) use ($counts) {
            $methodName = $counts[$fk->referenced_table] > 1
                ? StringHelper::camel(StringHelper::stripForeignKeyPrefixSuffix($fk->column_name))
                : StringHelper::camel($fk->referenced_table);

            $relatedClass = StringHelper::studly($fk->referenced_table);

            return <<<PHP
    public function {$methodName}()
    {
        return \$this->belongsTo({$relatedClass}::class, '{$fk->column_name}', '{$fk->referenced_column}');
    }
PHP;
        })->implode("\n\n");
    }

    private function buildHasManyRelations(Collection $referencingTables): string
    {
        if ($referencingTables->isEmpty()) {
            return '';
        }

        $counts = $referencingTables->countBy('table_name');

        return $referencingTables->map(function ($ref) use ($counts) {
            $baseName = $counts[$ref->table_name] > 1
                ? StringHelper::camel(StringHelper::stripForeignKeyPrefixSuffix($ref->column_name)) . StringHelper::studly($ref->table_name)
                : StringHelper::camel($ref->table_name);

            $methodName = StringHelper::plural($baseName);
            $relatedClass = StringHelper::studly($ref->table_name);

            return <<<PHP
    public function {$methodName}()
    {
        return \$this->hasMany({$relatedClass}::class, '{$ref->column_name}', '{$ref->referenced_column}');
    }
PHP;
        })->implode("\n\n");
    }

    private function buildTemplate(array $data): string
    {
        $softDeleteImport = $data['useSoftDeletes']
            ? "\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;"
            : '';

        $softDeleteTrait = $data['useSoftDeletes']
            ? "\n    use SoftDeletes;\n"
            : '';

        $timestamps = $data['useTimestamps'] ? 'true' : 'false';

        $relations = collect([
            $data['belongsToRelations'],
            $data['hasManyRelations'],
        ])->filter()->implode("\n\n");

        if ($relations) {
            $relations = "\n\n" . $relations;
        }

        return <<<PHP
<?php

namespace {$data['namespace']};

use Illuminate\Database\Eloquent\Model;{$softDeleteImport}

class {$data['className']} extends Model
{{$softDeleteTrait}
    protected \$table = '{$data['tableName']}';

    public \$timestamps = {$timestamps};

    protected \$fillable = {$data['fillable']};{$relations}
}

PHP;
    }
}
