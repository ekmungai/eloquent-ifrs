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

class CreateIfrsLineItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('ifrs.table_prefix') . 'line_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relationships
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('vat_id');

            // constraints
            $table->foreign('entity_id')->references('id')->on(config('ifrs.table_prefix') . 'entities');
            $table->foreign('account_id')->references('id')->on(config('ifrs.table_prefix') . 'accounts');
            $table->foreign('transaction_id')->references('id')->on(config('ifrs.table_prefix') . 'transactions');
            $table->foreign('vat_id')->references('id')->on(config('ifrs.table_prefix') . 'vats');

            // attributes
            $table->string('narration', 500)->nullable();;
            $table->decimal('amount', 13, 4);
            $table->decimal('quantity', 13, 4)->default(1);

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
        Schema::dropIfExists(config('ifrs.table_prefix') . 'line_items');
    }
}
