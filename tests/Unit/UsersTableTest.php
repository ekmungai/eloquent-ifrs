<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

use IFRS\Tests\TestCase;
use IFRS\Tests\Fixtures\ApplicationUser;
use IFRS\Tests\Fixtures\ApplicationUserWithUuidKey;

use IFRS\Support\UsersTable;
use IFRS\User;

class UsersTableTest extends TestCase
{
    /**
     * Users table resolution test.
     *
     * @return void
     */
    public function testUsersTableResolution()
    {
        $this->assertEquals(User::class, UsersTable::model());
        $this->assertEquals(config('ifrs.table_prefix') . 'users', UsersTable::name());
        $this->assertEquals('id', UsersTable::keyName());
        $this->assertTrue(UsersTable::isPackageOwned());

        Config::set('ifrs.user_model', ApplicationUser::class);

        $this->assertEquals('application_users', UsersTable::name());
        $this->assertFalse(UsersTable::isPackageOwned());
    }

    /**
     * Missing application users table test.
     *
     * @return void
     */
    public function testMissingUsersTableIsNotCreated()
    {
        Config::set('ifrs.user_model', ApplicationUser::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires the "application_users" table to exist');

        try {
            (new \IfrsCreateOrUpdateUsersTable())->up();
        } finally {
            $this->assertFalse(Schema::hasTable('application_users'));
        }
    }

    /**
     * Users table extension idempotence test.
     *
     * @return void
     */
    public function testUsersTableExtensionIsIdempotent()
    {
        $usersTable = UsersTable::name();

        // the table already carries the package's columns after the migrations ran
        $this->assertTrue(Schema::hasColumn($usersTable, 'entity_id'));
        $this->assertTrue(Schema::hasColumn($usersTable, 'destroyed_at'));

        (new \IfrsCreateOrUpdateUsersTable())->up();

        $this->assertTrue(Schema::hasColumn($usersTable, 'entity_id'));
        $this->assertTrue(Schema::hasColumn($usersTable, 'destroyed_at'));
    }

    /**
     * Application users table extension test.
     *
     * @return void
     */
    public function testApplicationUsersTableIsExtendedAdditively()
    {
        Schema::create(
            'application_users',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
            }
        );

        Config::set('ifrs.user_model', ApplicationUser::class);

        (new \IfrsCreateOrUpdateUsersTable())->up();

        $this->assertTrue(Schema::hasColumn('application_users', 'entity_id'));
        $this->assertTrue(Schema::hasColumn('application_users', 'destroyed_at'));
        $this->assertFalse(Schema::hasColumn('application_users', 'created'));

        // rerunning the migration must not fail on the columns it already added
        (new \IfrsCreateOrUpdateUsersTable())->up();

        (new \IfrsCreateOrUpdateUsersTable())->down();

        // the package drops its own columns, but never the application's table
        $this->assertTrue(Schema::hasTable('application_users'));
        $this->assertFalse(Schema::hasColumn('application_users', 'entity_id'));
        $this->assertFalse(Schema::hasColumn('application_users', 'destroyed_at'));
        $this->assertTrue(Schema::hasColumn('application_users', 'name'));
    }

    /**
     * String primary key matching test.
     *
     * @return void
     */
    public function testStringUserIdIsMatched()
    {
        Schema::create(
            'application_users',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
            }
        );

        $this->assertEquals(
            Schema::getColumnType('application_users', 'id'),
            $this->userIdColumnType('application_users')
        );
    }

    /**
     * Integer primary key matching test.
     *
     * @return void
     */
    public function testIntegerUserIdIsMatched()
    {
        Schema::create(
            'application_users',
            function (Blueprint $table) {
                $table->bigIncrements('id');
            }
        );

        $this->assertEquals(
            Schema::getColumnType('application_users', 'id'),
            $this->userIdColumnType('application_users')
        );
    }

    /**
     * Custom primary key name test.
     *
     * @return void
     */
    public function testCustomUserKeyNameIsMatched()
    {
        Schema::create(
            'application_users',
            function (Blueprint $table) {
                $table->uuid('uuid')->primary();
            }
        );

        Config::set('ifrs.user_model', ApplicationUserWithUuidKey::class);

        $this->assertEquals('uuid', UsersTable::keyName());
        $this->assertEquals(
            Schema::getColumnType('application_users', 'uuid'),
            $this->userIdColumnType('application_users')
        );
    }

    /**
     * Unsupported primary key type test.
     *
     * @return void
     */
    public function testUnsupportedUserIdTypeIsRejected()
    {
        Schema::create(
            'application_users',
            function (Blueprint $table) {
                $table->binary('id');
            }
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not supported');

        $this->userIdColumnType('application_users');
    }

    /**
     * The type of a user id column created for the given users table.
     *
     * @param string $usersTable
     *
     * @return string
     */
    private function userIdColumnType($usersTable)
    {
        Schema::create(
            'user_id_probe',
            function (Blueprint $table) use ($usersTable) {
                UsersTable::matchUserId($table, 'user_id', $usersTable);
            }
        );

        return Schema::getColumnType('user_id_probe', 'user_id');
    }
}
