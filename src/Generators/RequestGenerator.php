<?php

namespace IBuildWebApps\SchemaGenerator\Generators;

use IBuildWebApps\SchemaGenerator\Services\SchemaReader;
use IBuildWebApps\SchemaGenerator\Services\StringHelper;
use Illuminate\Support\Collection;

class RequestGenerator
{
    public function __construct(
        private readonly SchemaReader $schema,
        private readonly array $config = []
    ) {}

    public function generate(string $table): string
    {
        $columns = $this->schema->getColumns($table)->filter(fn($col) => $col->name !== 'id');
        $foreignKeys = $this->schema->getForeignKeys($table);

        $className = StringHelper::studly($table) . 'Request';
        $namespace = $this->config['request_namespace'] ?? 'App\\Http\\Requests';

        return $this->buildTemplate([
            'namespace' => $namespace,
            'className' => $className,
            'rules' => $this->buildValidationRules($columns, $foreignKeys),
        ]);
    }

    private function buildValidationRules(Collection $columns, Collection $foreignKeys): string
    {
        $fkColumns = $foreignKeys->pluck('referenced_table', 'column_name');

        $rules = $columns->map(function ($column) use ($fkColumns) {
            $rules = [];

            if ($column->nullable !== 'YES') {
                $rules[] = 'required';
            } else {
                $rules[] = 'nullable';
            }

            // Type-based rules
            switch (strtolower($column->data_type)) {
                case 'int':
                case 'integer':
                case 'bigint':
                case 'smallint':
                case 'mediumint':
                case 'tinyint':
                    $rules[] = 'integer';
                    break;
                case 'decimal':
                case 'double':
                case 'float':
                    $rules[] = 'numeric';
                    break;
                case 'date':
                    $rules[] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                    $rules[] = 'date';
                    break;
                case 'boolean':
                    $rules[] = 'boolean';
                    break;
                case 'json':
                case 'jsonb':
                    $rules[] = 'json';
                    break;
            }

            // Email field detection
            if (strtolower($column->name) === 'email') {
                $rules[] = 'email';
            }

            // Max length for strings
            if ($column->max_length) {
                $rules[] = "max:{$column->max_length}";
            }

            // Foreign key exists rule
            if ($fkColumns->has($column->name)) {
                $referencedTable = $fkColumns->get($column->name);
                $rules[] = "exists:{$referencedTable},id";
            }

            return "            '{$column->name}' => '" . implode('|', $rules) . "'";
        });

        return $rules->implode(",\n");
    }

    private function buildTemplate(array $data): string
    {
        return <<<PHP
<?php

namespace {$data['namespace']};

use Illuminate\Foundation\Http\FormRequest;

class {$data['className']} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
{$data['rules']},
        ];
    }
}

PHP;
    }
}
