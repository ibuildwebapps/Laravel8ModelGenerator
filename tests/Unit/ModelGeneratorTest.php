<?php

use IBuildWebApps\SchemaGenerator\Generators\ModelGenerator;
use IBuildWebApps\SchemaGenerator\Services\SchemaReader;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->schemaReader = Mockery::mock(SchemaReader::class);
});

afterEach(function () {
    Mockery::close();
});

describe('ModelGenerator', function () {
    it('generates a basic model', function () {
        $columns = collect([
            (object) ['name' => 'id', 'column_type' => 'int(11)', 'comment' => ''],
            (object) ['name' => 'name', 'column_type' => 'varchar(255)', 'comment' => ''],
            (object) ['name' => 'email', 'column_type' => 'varchar(255)', 'comment' => ''],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->with('user')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->with('user')->andReturn(collect());
        $this->schemaReader->shouldReceive('getReferencingTables')->with('user')->andReturn(collect());

        $generator = new ModelGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->toContain('class User extends Model')
            ->toContain("protected \$table = 'user'")
            ->toContain("'name'")
            ->toContain("'email'")
            ->toContain('$timestamps = false');
    });

    it('enables timestamps when created_at exists', function () {
        $columns = collect([
            (object) ['name' => 'id', 'column_type' => 'int(11)', 'comment' => ''],
            (object) ['name' => 'name', 'column_type' => 'varchar(255)', 'comment' => ''],
            (object) ['name' => 'created_at', 'column_type' => 'timestamp', 'comment' => ''],
            (object) ['name' => 'updated_at', 'column_type' => 'timestamp', 'comment' => ''],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());
        $this->schemaReader->shouldReceive('getReferencingTables')->andReturn(collect());

        $generator = new ModelGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain('$timestamps = true');
    });

    it('adds SoftDeletes trait when deleted_at exists', function () {
        $columns = collect([
            (object) ['name' => 'id', 'column_type' => 'int(11)', 'comment' => ''],
            (object) ['name' => 'deleted_at', 'column_type' => 'timestamp', 'comment' => ''],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());
        $this->schemaReader->shouldReceive('getReferencingTables')->andReturn(collect());

        $generator = new ModelGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->toContain('use Illuminate\Database\Eloquent\SoftDeletes')
            ->toContain('use SoftDeletes;');
    });

    it('generates belongsTo relationships from foreign keys', function () {
        $columns = collect([
            (object) ['name' => 'id', 'column_type' => 'int(11)', 'comment' => ''],
            (object) ['name' => 'company_id', 'column_type' => 'int(11)', 'comment' => ''],
        ]);

        $foreignKeys = collect([
            (object) [
                'column_name' => 'company_id',
                'referenced_table' => 'company',
                'referenced_column' => 'id',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn($foreignKeys);
        $this->schemaReader->shouldReceive('getReferencingTables')->andReturn(collect());

        $generator = new ModelGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->toContain('public function company()')
            ->toContain('belongsTo(Company::class');
    });

    it('generates hasMany relationships from referencing tables', function () {
        $columns = collect([
            (object) ['name' => 'id', 'column_type' => 'int(11)', 'comment' => ''],
        ]);

        $referencingTables = collect([
            (object) [
                'table_name' => 'order',
                'column_name' => 'user_id',
                'referenced_column' => 'id',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());
        $this->schemaReader->shouldReceive('getReferencingTables')->andReturn($referencingTables);

        $generator = new ModelGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->toContain('public function orders()')
            ->toContain('hasMany(Order::class');
    });

    it('comments out id and deleted_at in fillable', function () {
        $columns = collect([
            (object) ['name' => 'id', 'column_type' => 'int(11)', 'comment' => ''],
            (object) ['name' => 'name', 'column_type' => 'varchar(255)', 'comment' => ''],
            (object) ['name' => 'deleted_at', 'column_type' => 'timestamp', 'comment' => ''],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());
        $this->schemaReader->shouldReceive('getReferencingTables')->andReturn(collect());

        $generator = new ModelGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->toContain("//'id'")
            ->toContain("//'deleted_at'")
            ->toContain("'name'");
    });

    it('uses custom namespace from config', function () {
        $columns = collect([
            (object) ['name' => 'id', 'column_type' => 'int(11)', 'comment' => ''],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());
        $this->schemaReader->shouldReceive('getReferencingTables')->andReturn(collect());

        $generator = new ModelGenerator($this->schemaReader, ['model_namespace' => 'Custom\\Models']);
        $result = $generator->generate('user');

        expect($result)->toContain('namespace Custom\\Models;');
    });
});
