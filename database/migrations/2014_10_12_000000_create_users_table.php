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

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
