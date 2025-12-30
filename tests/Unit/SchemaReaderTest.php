<?php

use IBuildWebApps\SchemaGenerator\Services\SchemaReader;

describe('SchemaReader', function () {
    it('returns database name', function () {
        $reader = new SchemaReader('test_database');

        expect($reader->getDatabase())->toBe('test_database');
    });
});
