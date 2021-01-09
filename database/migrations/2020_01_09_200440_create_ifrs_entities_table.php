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

class CreateIfrsEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            config('ifrs.table_prefix') . 'entities',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                // relationships
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();

                // constraints
                $table->foreign('parent_id')->references('id')->on(config('ifrs.table_prefix') . 'entities');

                // attributes
                $table->string('name', 300);
                $table->boolean('multi_currency')->default(false);
                $table->boolean('mid_year_balances')->default(false);
                $table->integer('year_start')->default(1);

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
        Schema::dropIfExists(config('ifrs.table_prefix') . 'entities');
    }
}
