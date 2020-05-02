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
use IFRS\Models\Balance;

class CreateLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relationships
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('vat_id');
            $table->unsignedBigInteger('post_account');
            $table->unsignedBigInteger('folio_account');
            $table->unsignedBigInteger('line_item_id');

            // constraints
            $table->foreign('entity_id')->references('id')->on('entities');
            $table->foreign('vat_id')->references('id')->on('vats');
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->foreign('post_account')->references('id')->on('accounts');
            $table->foreign('folio_account')->references('id')->on('accounts');
            $table->foreign('line_item_id')->references('id')->on('line_items');

            // attributes
            $table->dateTime('date', 0);
            $table->enum('entry_type', [Balance::DEBIT,Balance::CREDIT]);
            $table->decimal('amount', 13, 4);
            $table->string('hash', 500)->nullable();

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
        Schema::dropIfExists('ledgers');
    }
}
