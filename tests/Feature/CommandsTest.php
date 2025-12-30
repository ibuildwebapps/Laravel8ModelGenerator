<?php

describe('Commands Registration', function () {
    it('registers schema:model command', function () {
        $this->artisan('list')
            ->assertSuccessful()
            ->expectsOutputToContain('schema:model');
    });

    it('registers schema:migration command', function () {
        $this->artisan('list')
            ->assertSuccessful()
            ->expectsOutputToContain('schema:migration');
    });

    it('registers schema:request command', function () {
        $this->artisan('list')
            ->assertSuccessful()
            ->expectsOutputToContain('schema:request');
    });

    it('registers schema:all command', function () {
        $this->artisan('list')
            ->assertSuccessful()
            ->expectsOutputToContain('schema:all');
    });
});

describe('GenerateModelCommand', function () {
    it('requires database to be specified', function () {
        config(['database.connections.mysql.database' => null]);

        $this->artisan('schema:model', ['table' => 'users'])
            ->assertFailed()
            ->expectsOutputToContain('No database specified');
    });
});

describe('GenerateMigrationCommand', function () {
    it('requires database to be specified', function () {
        config(['database.connections.mysql.database' => null]);

        $this->artisan('schema:migration', ['table' => 'users'])
            ->assertFailed()
            ->expectsOutputToContain('No database specified');
    });
});

describe('GenerateRequestCommand', function () {
    it('requires database to be specified', function () {
        config(['database.connections.mysql.database' => null]);

        $this->artisan('schema:request', ['table' => 'users'])
            ->assertFailed()
            ->expectsOutputToContain('No database specified');
    });
});
