<?php

use IBuildWebApps\SchemaGenerator\Services\StringHelper;

describe('StringHelper', function () {
    describe('studly', function () {
        it('converts snake_case to StudlyCase', function () {
            expect(StringHelper::studly('user_profile'))->toBe('UserProfile');
        });

        it('converts single word', function () {
            expect(StringHelper::studly('user'))->toBe('User');
        });

        it('handles multiple underscores', function () {
            expect(StringHelper::studly('user__profile__setting'))->toBe('UserProfileSetting');
        });

        it('handles hyphens', function () {
            expect(StringHelper::studly('user-profile'))->toBe('UserProfile');
        });

        it('handles mixed delimiters', function () {
            expect(StringHelper::studly('user_profile-setting'))->toBe('UserProfileSetting');
        });
    });

    describe('camel', function () {
        it('converts snake_case to camelCase', function () {
            expect(StringHelper::camel('user_profile'))->toBe('userProfile');
        });

        it('converts single word to lowercase first char', function () {
            expect(StringHelper::camel('User'))->toBe('user');
        });
    });

    describe('stripForeignKeyPrefixSuffix', function () {
        it('removes fk_ prefix', function () {
            expect(StringHelper::stripForeignKeyPrefixSuffix('fk_user'))->toBe('user');
        });

        it('removes _id suffix', function () {
            expect(StringHelper::stripForeignKeyPrefixSuffix('user_id'))->toBe('user');
        });

        it('removes both fk_ prefix and _id suffix', function () {
            expect(StringHelper::stripForeignKeyPrefixSuffix('fk_user_id'))->toBe('user');
        });

        it('leaves other strings unchanged', function () {
            expect(StringHelper::stripForeignKeyPrefixSuffix('username'))->toBe('username');
        });
    });

    describe('plural', function () {
        it('appends s to word', function () {
            expect(StringHelper::plural('user'))->toBe('users');
            expect(StringHelper::plural('order'))->toBe('orders');
        });
    });
});
