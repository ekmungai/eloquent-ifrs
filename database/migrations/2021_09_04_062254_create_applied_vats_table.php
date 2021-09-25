<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppliedVatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('ifrs.table_prefix') . 'applied_vats', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relationships
            $table->unsignedBigInteger('vat_id');
            $table->unsignedBigInteger('line_item_id');

            // constraints
            $table->foreign('vat_id')->references('id')->on(config('ifrs.table_prefix').'vats');
            $table->foreign('line_item_id')->references('id')->on(config('ifrs.table_prefix').'line_items');

            // attributes 
            $table->decimal('amount', 13, 4);
            
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
        Schema::dropIfExists(config('ifrs.table_prefix') . 'applied_vats');
    }
}
