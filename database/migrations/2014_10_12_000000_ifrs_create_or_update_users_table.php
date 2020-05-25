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

class IfrsCreateOrUpdateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->getTableName())) {
            Schema::table(
                $this->getTableName(),
                function (Blueprint $table) {
                    //entity
                    $table->unsignedBigInteger('entity_id')->nullable();
                    // *permanent* deletion
                    $table->dateTime('destroyed_at')->nullable();
            });
        }else{
            Schema::create(
                $this->getTableName(),
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
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn($this->getTableName(), 'created'))
        {
            Schema::dropIfExists($this->getTableName());
        }else{
            Schema::table(
                $this->getTableName(),
                function (Blueprint $table) {
                    $table->dropColumn('entity_id');
                    $table->dropColumn('destroyed_at');
                }
            );
        }
    }

    private function getTableName()
    {
        $userModel = config('ifrs.user_model');
        return (new $userModel())->getTable();
    }
}
