<?php

namespace IBuildWebApps\SchemaGenerator\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchemaReader
{
    public function __construct(
        private readonly string $database
    ) {}

    public function getTables(string $pattern = '*'): Collection
    {
        $pattern = str_replace('*', '%', $pattern);

        return collect(DB::select(
            "SELECT TABLE_NAME AS name FROM information_schema.tables
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ?",
            [$this->database, $pattern]
        ));
    }

    public function getColumns(string $table): Collection
    {
        return collect(DB::select(
            "SELECT
                COLUMN_NAME as name,
                COLUMN_DEFAULT as `default`,
                IS_NULLABLE as nullable,
                DATA_TYPE as data_type,
                CHARACTER_MAXIMUM_LENGTH as max_length,
                NUMERIC_PRECISION as `precision`,
                NUMERIC_SCALE as scale,
                COLUMN_TYPE as column_type,
                COLUMN_KEY as column_key,
                EXTRA as extra,
                COLUMN_COMMENT as comment
             FROM information_schema.columns
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION",
            [$this->database, $table]
        ));
    }

    public function getForeignKeys(string $table): Collection
    {
        return collect(DB::select(
            "SELECT
                COLUMN_NAME as column_name,
                REFERENCED_TABLE_SCHEMA as referenced_schema,
                REFERENCED_TABLE_NAME as referenced_table,
                REFERENCED_COLUMN_NAME as referenced_column
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY COLUMN_NAME",
            [$this->database, $table]
        ));
    }

    public function getReferencingTables(string $table): Collection
    {
        return collect(DB::select(
            "SELECT
                TABLE_SCHEMA as schema_name,
                TABLE_NAME as table_name,
                COLUMN_NAME as column_name,
                REFERENCED_COLUMN_NAME as referenced_column
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY COLUMN_NAME",
            [$this->database, $table]
        ));
    }

    public function getDatabase(): string
    {
        return $this->database;
    }
}
