<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

use IFRS\User;

/**
 * Resolves the application's users table for the package migrations.
 *
 * The package extends the application's users table, it does not own it. This
 * helper resolves the table from the configured User model, asserts that the
 * application has created it, and mirrors its primary key definition so that
 * foreign keys pointing at it are always type compatible.
 */
class UsersTable
{
    /**
     * Integer primary key types, keyed by the Blueprint method that matches them.
     *
     * @var array<string, string[]>
     */
    private static $integerTypes = [
        'bigInteger' => ['bigint', 'int8', 'bigserial', 'serial8'],
        'integer' => ['int', 'integer', 'int4', 'serial', 'serial4'],
        'mediumInteger' => ['mediumint'],
        'smallInteger' => ['smallint', 'int2', 'smallserial', 'serial2'],
        'tinyInteger' => ['tinyint'],
    ];

    /**
     * Fixed length string primary key types.
     *
     * @var string[]
     */
    private static $charTypes = ['char', 'bpchar', 'nchar', 'character'];

    /**
     * Variable length string primary key types.
     *
     * @var string[]
     */
    private static $stringTypes = ['varchar', 'varchar2', 'nvarchar', 'string', 'character varying', 'text'];

    /**
     * Native uuid primary key types.
     *
     * @var string[]
     */
    private static $uuidTypes = ['uuid', 'guid', 'uniqueidentifier'];

    /**
     * The User model configured for the application.
     *
     * Handles both the string format ('App\Models\User') and the legacy
     * array format ([7 => App\User::class, 8 => App\Models\User::class]).
     *
     * @return string|null
     */
    public static function model()
    {
        $userModel = config('ifrs.user_model');

        if (is_array($userModel)) {
            $major = (int) App::version();
            $userModel = $userModel[$major] ?? end($userModel);
        }

        return is_string($userModel) && class_exists($userModel) ? $userModel : null;
    }

    /**
     * The name of the users table the package should attach itself to.
     *
     * @return string
     */
    public static function name()
    {
        $userModel = self::model();

        return $userModel ? (new $userModel())->getTable() : 'users';
    }

    /**
     * The primary key column of the users table.
     *
     * @return string
     */
    public static function keyName()
    {
        $userModel = self::model();

        return $userModel ? (new $userModel())->getKeyName() : 'id';
    }

    /**
     * Whether the resolved users table belongs to the package rather than to
     * the application. Only a package owned table may be created by the
     * package migrations.
     *
     * @return bool
     */
    public static function isPackageOwned()
    {
        $userModel = self::model();

        if ($userModel === null || !is_a($userModel, User::class, true)) {
            return false;
        }

        $prefix = (string) config('ifrs.table_prefix');

        return $prefix !== '' && strpos(self::name(), $prefix) === 0;
    }

    /**
     * Assert that the users table exists before a migration references it.
     *
     * @param string $migration Migration requiring the table, used in the error message.
     *
     * @throws \RuntimeException
     *
     * @return string The users table name.
     */
    public static function requireTable($migration)
    {
        $usersTable = self::name();

        if (!Schema::hasTable($usersTable)) {
            throw new \RuntimeException(
                sprintf(
                    'Eloquent IFRS requires the "%s" table to exist before the %s migration runs. '
                    . 'Run your application\'s users migration first (it must be dated before %s), '
                    . 'or point the ifrs.user_model configuration at the model whose table you want IFRS to use.',
                    $usersTable,
                    $migration,
                    '2014_10_12_000000'
                )
            );
        }

        return $usersTable;
    }

    /**
     * Add a column to the blueprint that can hold, and be constrained against,
     * the primary key of the users table.
     *
     * Laravel 11 reports the native database type, so the column type has to be
     * matched exactly: MySQL will reject a foreign key between char(36) and
     * varchar(36), or between signed and unsigned integers.
     *
     * @param Blueprint $table
     * @param string    $column
     * @param string    $usersTable
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public static function matchUserId(Blueprint $table, $column, $usersTable)
    {
        $key = self::keyName();
        $type = strtolower(Schema::getColumnType($usersTable, $key));
        $definition = strtolower(Schema::getColumnType($usersTable, $key, true));

        if (in_array($type, self::$uuidTypes, true)) {
            $table->uuid($column);

            return;
        }

        $length = preg_match('/\((\d+)/', $definition, $matches) === 1 ? (int) $matches[1] : null;

        if (in_array($type, self::$charTypes, true)) {
            $length === null ? $table->uuid($column) : $table->char($column, $length);

            return;
        }

        if (in_array($type, self::$stringTypes, true)) {
            $length === null ? $table->string($column) : $table->string($column, $length);

            return;
        }

        foreach (self::$integerTypes as $method => $types) {
            if (in_array($type, $types, true)) {
                strpos($definition, 'unsigned') === false
                    ? $table->{$method}($column)
                    : $table->{'unsigned' . ucfirst($method)}($column);

                return;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Eloquent IFRS cannot create a foreign key to "%s.%s": the column type "%s" is not supported. '
                . 'Supported primary keys are integer, string (char/varchar) and uuid types.',
                $usersTable,
                $key,
                $definition
            )
        );
    }
}
