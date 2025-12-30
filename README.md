# Laravel Schema Generator

Generate Laravel models, migrations, and form requests from existing MySQL database schema.

## Installation

```bash
composer require ibuildwebapps/laravel-schema-generator --dev
```

The package auto-registers via Laravel's package discovery.

## Usage

### Generate Models

```bash
# Single table
php artisan schema:model users

# Wildcard pattern
php artisan schema:model user*

# All tables
php artisan schema:model '*'

# Specify database
php artisan schema:model users --database=mydb

# Overwrite existing
php artisan schema:model users --force
```

### Generate Migrations

```bash
php artisan schema:migration users
php artisan schema:migration '*' --force
```

### Generate Form Requests

```bash
php artisan schema:request users
php artisan schema:request '*' --force
```

### Generate All (Models + Migrations + Requests)

```bash
php artisan schema:all users
php artisan schema:all '*' --database=mydb --force
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=schema-generator-config
```

Config options in `config/schema-generator.php`:

```php
return [
    'model_namespace' => 'App\\Models',
    'model_path' => app_path('Models'),
    'request_namespace' => 'App\\Http\\Requests',
    'request_path' => app_path('Http/Requests'),
];
```

## Features

- **Models**: Generates Eloquent models with:
  - `$fillable` array with column types as comments
  - `$timestamps` based on `created_at` column presence
  - `SoftDeletes` trait if `deleted_at` column exists
  - `belongsTo()` relationships from foreign keys
  - `hasMany()` relationships from referencing tables

- **Migrations**: Generates migrations with:
  - All column types mapped to Laravel schema builder methods
  - Nullable, unique, and default modifiers
  - Foreign key constraints
  - Modern anonymous class syntax

- **Requests**: Generates Form Request classes with:
  - `required`/`nullable` based on column nullability
  - Type-based rules (`integer`, `numeric`, `date`, `boolean`, `json`)
  - `email` rule for email fields
  - `max` rule for string columns
  - `exists` rule for foreign key columns

## Testing

```bash
composer test
# or
vendor/bin/pest
```

## License

MIT
