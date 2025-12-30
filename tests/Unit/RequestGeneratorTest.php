<?php

use IBuildWebApps\SchemaGenerator\Generators\RequestGenerator;
use IBuildWebApps\SchemaGenerator\Services\SchemaReader;

beforeEach(function () {
    $this->schemaReader = Mockery::mock(SchemaReader::class);
});

afterEach(function () {
    Mockery::close();
});

describe('RequestGenerator', function () {
    it('generates a basic request class', function () {
        $columns = collect([
            (object) [
                'name' => 'name',
                'data_type' => 'varchar',
                'max_length' => 255,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->toContain('class UserRequest extends FormRequest')
            ->toContain('public function rules(): array')
            ->toContain("'name' => 'required|max:255'");
    });

    it('adds required rule for non-nullable columns', function () {
        $columns = collect([
            (object) [
                'name' => 'title',
                'data_type' => 'varchar',
                'max_length' => 100,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('post');

        expect($result)->toContain("'title' => 'required");
    });

    it('adds nullable rule for nullable columns', function () {
        $columns = collect([
            (object) [
                'name' => 'bio',
                'data_type' => 'text',
                'max_length' => null,
                'nullable' => 'YES',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('profile');

        expect($result)->toContain("'bio' => 'nullable");
    });

    it('adds email rule for email fields', function () {
        $columns = collect([
            (object) [
                'name' => 'email',
                'data_type' => 'varchar',
                'max_length' => 255,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain('email');
    });

    it('adds max rule for string columns with max_length', function () {
        $columns = collect([
            (object) [
                'name' => 'username',
                'data_type' => 'varchar',
                'max_length' => 50,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain('max:50');
    });

    it('adds integer rule for integer columns', function () {
        $columns = collect([
            (object) [
                'name' => 'age',
                'data_type' => 'int',
                'max_length' => null,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain('integer');
    });

    it('adds numeric rule for decimal columns', function () {
        $columns = collect([
            (object) [
                'name' => 'price',
                'data_type' => 'decimal',
                'max_length' => null,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('product');

        expect($result)->toContain('numeric');
    });

    it('adds date rule for date columns', function () {
        $columns = collect([
            (object) [
                'name' => 'birth_date',
                'data_type' => 'date',
                'max_length' => null,
                'nullable' => 'YES',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain('date');
    });

    it('adds exists rule for foreign key columns', function () {
        $columns = collect([
            (object) [
                'name' => 'company_id',
                'data_type' => 'int',
                'max_length' => null,
                'nullable' => 'NO',
            ],
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

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain('exists:company,id');
    });

    it('excludes id column from rules', function () {
        $columns = collect([
            (object) [
                'name' => 'id',
                'data_type' => 'int',
                'max_length' => null,
                'nullable' => 'NO',
            ],
            (object) [
                'name' => 'name',
                'data_type' => 'varchar',
                'max_length' => 255,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)
            ->not->toContain("'id' =>")
            ->toContain("'name' =>");
    });

    it('uses custom namespace from config', function () {
        $columns = collect([
            (object) [
                'name' => 'name',
                'data_type' => 'varchar',
                'max_length' => 255,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader, ['request_namespace' => 'Custom\\Requests']);
        $result = $generator->generate('user');

        expect($result)->toContain('namespace Custom\\Requests;');
    });

    it('adds boolean rule for boolean columns', function () {
        $columns = collect([
            (object) [
                'name' => 'is_active',
                'data_type' => 'boolean',
                'max_length' => null,
                'nullable' => 'NO',
            ],
        ]);

        $this->schemaReader->shouldReceive('getColumns')->andReturn($columns);
        $this->schemaReader->shouldReceive('getForeignKeys')->andReturn(collect());

        $generator = new RequestGenerator($this->schemaReader);
        $result = $generator->generate('user');

        expect($result)->toContain('boolean');
    });
});
