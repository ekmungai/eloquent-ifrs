<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateClosingRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('ifrs.table_prefix') . 'closing_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // relationships
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('reporting_period_id');
            $table->unsignedBigInteger('exchange_rate_id');

            // constraints
            $table->foreign('entity_id')->references('id')->on(config('ifrs.table_prefix') . 'entities');
            $table->foreign('reporting_period_id')->references('id')->on(config('ifrs.table_prefix') . 'reporting_periods');
            $table->foreign('exchange_rate_id')->references('id')->on(config('ifrs.table_prefix') . 'exchange_rates');

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
        Schema::dropIfExists(config('ifrs.table_prefix') .'closing_rates');
    }
}
