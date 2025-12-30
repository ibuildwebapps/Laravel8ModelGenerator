<?php

namespace IBuildWebApps\SchemaGenerator\Services;

class StringHelper
{
    public static function studly(string $value): string
    {
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        $value = trim($value);
        $value = ucwords($value);

        return str_replace(' ', '', $value);
    }

    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }

    public static function stripForeignKeyPrefixSuffix(string $value): string
    {
        $value = preg_replace('/^fk_/', '', $value);

        return preg_replace('/_id$/', '', $value);
    }

    public static function plural(string $value): string
    {
        // Simple pluralization - just append 's'
        return $value . 's';
    }
}
