<?php

use IBuildWebApps\SchemaGenerator\Generators\MigrationGenerator;
use IBuildWebApps\SchemaGenerator\Services\SchemaReader;

beforeEach(function () {
    $this->schemaReader = Mockery::mock(SchemaReader::class);
});

afterEach(function () {
    Mockery::close();
});

describe('MigrationGenerator', function () {
    it('generates a basic migration', function () {
        $columns = collect([
            (object) [
                'name' => 'name',
                'data_type' => 'varchar',
                'column_type' => 'varchar(255)',
                'max_length' => 255,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => '',
                'default' => null,
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->toContain('return new class extends Migration')
            ->toContain("Schema::create(\$this->table")
            ->toContain("\$table->id()")
            ->toContain("\$table->string('name', 255)");
    });

    it('maps varchar to string', function () {
        $columns = collect([
            (object) [
                'name' => 'title',
                'data_type' => 'varchar',
                'column_type' => 'varchar(100)',
                'max_length' => 100,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => '',
                'default' => null,
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('post');

        expect($result)->toContain("\$table->string('title', 100)");
    });

    it('maps int to integer', function () {
        $columns = collect([
            (object) [
                'name' => 'count',
                'data_type' => 'int',
                'column_type' => 'int(11)',
                'max_length' => null,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => '',
                'default' => null,
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('stats');

        expect($result)->toContain("\$table->integer('count')");
    });

    it('adds nullable modifier', function () {
        $columns = collect([
            (object) [
                'name' => 'bio',
                'data_type' => 'text',
                'column_type' => 'text',
                'max_length' => null,
                'precision' => null,
                'scale' => null,
                'nullable' => 'YES',
                'column_key' => '',
                'default' => null,
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('profile');

        expect($result)->toContain("->nullable()");
    });

    it('adds unique modifier', function () {
        $columns = collect([
            (object) [
                'name' => 'email',
                'data_type' => 'varchar',
                'column_type' => 'varchar(255)',
                'max_length' => 255,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => 'UNI',
                'default' => null,
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain("->unique()");
    });

    it('adds default value', function () {
        $columns = collect([
            (object) [
                'name' => 'status',
                'data_type' => 'varchar',
                'column_type' => 'varchar(50)',
                'max_length' => 50,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => '',
                'default' => 'pending',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('order');

        expect($result)->toContain("->default('pending')");
    });

    it('generates foreign key constraints', function () {
        $columns = collect([
            (object) [
                'name' => 'user_id',
                'data_type' => 'int',
                'column_type' => 'int(11)',
                'max_length' => null,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => 'MUL',
                'default' => null,
            ],
        ]);

        $foreignKeys = collect([
            (object) [
                'column_name' => 'user_id',
                'referenced_table' => 'user',
                'referenced_column' => 'id',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn($foreignKeys);

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('order');

        expect($result)
            ->toContain("\$table->foreign('user_id')")
            ->toContain("->references('id')")
            ->toContain("->on('user')");
    });

    it('handles enum type', function () {
        $columns = collect([
            (object) [
                'name' => 'status',
                'data_type' => 'enum',
                'column_type' => "enum('draft','published','archived')",
                'max_length' => null,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => '',
                'default' => null,
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('post');

        expect($result)->toContain("enum('status', ['draft','published','archived'])");
    });

    it('generates correct filename', function () {
        $this->schemaReader->shouldReceive('getColumns')->andReturn(collect());
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $filename = $generator->getFilename('user_profile');

        expect($filename)
            ->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_create_user_profile_table\.php$/');
    });

    it('excludes id column', function () {
        $columns = collect([
            (object) [
                'name' => 'id',
                'data_type' => 'int',
                'column_type' => 'int(11)',
                'max_length' => null,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => 'PRI',
                'default' => null,
            ],
            (object) [
                'name' => 'name',
                'data_type' => 'varchar',
                'column_type' => 'varchar(255)',
                'max_length' => 255,
                'precision' => null,
                'scale' => null,
                'nullable' => 'NO',
                'column_key' => '',
                'default' => null,
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new MigrationGenerator($this->schemaReader);
        $result = $generator->generate('user');

        // Should use $table->id() instead of manual id column
        expect($result)
            ->toContain("\$table->id()")
            ->not->toContain("\$table->integer('id')");
    });
});
