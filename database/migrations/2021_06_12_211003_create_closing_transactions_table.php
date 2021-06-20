<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateClosingTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('ifrs.table_prefix') . 'closing_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relationships
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('reporting_period_id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('currency_id');

            // constraints
            $table->foreign('entity_id')->references('id')->on(config('ifrs.table_prefix') . 'entities');
            $table->foreign('reporting_period_id')->references('id')->on(config('ifrs.table_prefix') . 'reporting_periods');
            $table->foreign('transaction_id')->references('id')->on(config('ifrs.table_prefix') . 'transactions');
            $table->foreign('currency_id')->references('id')->on(config('ifrs.table_prefix') . 'currencies');

            // *permanent* deletion
            $table->dateTime('destroyed_at')->nullable();

            //soft deletion
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('ifrs.table_prefix') .'closing_transactions');
    }
}
