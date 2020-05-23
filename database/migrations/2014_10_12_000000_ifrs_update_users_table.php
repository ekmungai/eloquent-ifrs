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
        Schema::table(
            $this->getTableName(),
            function (Blueprint $table) {
                //entity
                $table->unsignedBigInteger('entity_id')->nullable();
                // *permanent* deletion
                $table->dateTime('destroyed_at')->nullable();
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
