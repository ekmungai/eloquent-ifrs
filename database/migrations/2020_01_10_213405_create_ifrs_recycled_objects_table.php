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

class CreateIfrsRecycledObjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $usersTable = UsersTable::requireTable(basename(__FILE__, '.php'));

        Schema::create(
            config('ifrs.table_prefix').'recycled_objects',
            function (Blueprint $table) use ($usersTable) {
                $table->bigIncrements('id');

                // relationships
                $table->unsignedBigInteger('entity_id');

                // the user id column has to match the primary key of the users table
                UsersTable::matchUserId($table, 'user_id', $usersTable);

                // constraints
                $table->foreign('entity_id')->references('id')->on(config('ifrs.table_prefix').'entities');
                $table->foreign('user_id')->references(UsersTable::keyName())->on($usersTable);

                // attributes
                // recyclable models are IFRS models, whose keys are always big integers
                $table->unsignedBigInteger('recyclable_id');
                $table->string('recyclable_type', 300);

                // *permanent* deletion
                $table->dateTime('destroyed_at')->nullable();

                //soft deletion
                $table->softDeletes();

                $table->timestamps();
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
        Schema::dropIfExists(config('ifrs.table_prefix').'recycled_objects');
    }
}
