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

class IfrsUpdateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
<<<<<<< HEAD:database/migrations/2014_10_12_000000_create_users_table.php
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                //entity
                $table->unsignedBigInteger('entity_id')->nullable();

            });
        }else{
            Schema::create(
                'users',
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
                }
            );
        }
=======
        Schema::table(
            $this->getTableName(),
            function (Blueprint $table) {
                //entity
                $table->unsignedBigInteger('entity_id')->nullable();
                // *permanent* deletion
                $table->dateTime('destroyed_at')->nullable();
            }
        );
>>>>>>> bae3f1a12eba46975d6341beb455b2e29ba34cc9:database/migrations/2014_10_12_000000_ifrs_update_users_table.php
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            $this->getTableName(),
            function (Blueprint $table) {
                $table->dropColumn('entity_id');
                $table->dropColumn('destroyed_at');
            }
        );
    }

    private function getTableName()
    {
        $userModel = config('ifrs.user_model');
        return (new $userModel())->getTable();
    }
}
