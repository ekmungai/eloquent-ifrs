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

class CreateLineItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('line_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relationships
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('vat_account_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('vat_id');

            // constraints
            $table->foreign('entity_id')->references('id')->on('entities');
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('vat_account_id')->references('id')->on('accounts');
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->foreign('vat_id')->references('id')->on('vats');

            // attributes
            $table->string('description', 500)->nullable();
            ;
            $table->decimal('amount', 13, 4);
            $table->double('quantity', 5, 2)->default(1);

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
        Schema::dropIfExists('line_items');
    }
}
