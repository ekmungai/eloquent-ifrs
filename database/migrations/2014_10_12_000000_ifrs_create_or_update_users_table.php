<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use IFRS\Support\UsersTable;

class IfrsCreateOrUpdateUsersTable extends Migration
{
    /**
     * Columns the package adds to the application's users table.
     *
     * @var string[]
     */
    private $columns = ['entity_id', 'destroyed_at'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $usersTable = UsersTable::name();

        if (Schema::hasTable($usersTable)) {
            $this->extend($usersTable);

            return;
        }

        // The package never creates the application's users table, only its own.
        if (!UsersTable::isPackageOwned()) {
            UsersTable::requireTable(basename(__FILE__, '.php'));
        }

        $this->create($usersTable);
    }

    /**
     * Add the package's columns to an existing users table, skipping any that
     * are already there so that the migration is safe to re-run.
     *
     * @param string $usersTable
     *
     * @return void
     */
    private function extend($usersTable)
    {
        $missing = array_filter(
            $this->columns,
            function ($column) use ($usersTable) {
                return !Schema::hasColumn($usersTable, $column);
            }
        );

        if (empty($missing)) {
            return;
        }

        Schema::table(
            $usersTable,
            function (Blueprint $table) use ($missing) {
                if (in_array('entity_id', $missing, true)) {
                    //entity
                    $table->unsignedBigInteger('entity_id')->nullable();
                }

                if (in_array('destroyed_at', $missing, true)) {
                    // *permanent* deletion
                    $table->dateTime('destroyed_at')->nullable();
                }
            }
        );
    }

    /**
     * Create the package's own users table.
     *
     * @param string $usersTable
     *
     * @return void
     */
    private function create($usersTable)
    {
        Schema::create(
            $usersTable,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                //entity
                $table->unsignedBigInteger('entity_id')->nullable();

                // attributes
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();

                // *permanent* deletion
                $table->dateTime('destroyed_at')->nullable();

                //soft deletion
                $table->softDeletes();

                $table->timestamps();

                // flag for created table
                $table->boolean('created')->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $usersTable = UsersTable::name();

        if (!Schema::hasTable($usersTable)) {
            return;
        }

        // the flag marks a table created by the package, which it may drop
        if (Schema::hasColumn($usersTable, 'created')) {
            Schema::dropIfExists($usersTable);

            return;
        }

        $present = array_filter(
            $this->columns,
            function ($column) use ($usersTable) {
                return Schema::hasColumn($usersTable, $column);
            }
        );

        if (empty($present)) {
            return;
        }

        Schema::table(
            $usersTable,
            function (Blueprint $table) use ($present) {
                $table->dropColumn(array_values($present));
            }
        );
    }
}
