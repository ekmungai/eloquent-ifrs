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

class CreateIfrsAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            config('ifrs.table_prefix').'accounts',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                // relationships
                $table->unsignedBigInteger('entity_id');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('currency_id');

                // constraints
                $table->foreign('entity_id')->references('id')->on(config('ifrs.table_prefix').'entities');
                $table->foreign('category_id')->references('id')->on(config('ifrs.table_prefix').'categories')  ;
                $table->foreign('currency_id')->references('id')->on(config('ifrs.table_prefix').'currencies');

                // attributes
                $table->integer('code');
                $table->string('name', 255);
                $table->string('description', 1000)->nullable();
                $table->enum(
                    'account_type',
                    array_keys(config('ifrs')['accounts'])
                );

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
        Schema::dropIfExists(config('ifrs.table_prefix').'accounts');
    }
}
