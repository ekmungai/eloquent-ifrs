<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Balance;
use App\Models\Transaction;

class CreateBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('balances', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relationships
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('exchange_rate_id');

            // constraints
            $table->foreign('entity_id')->references('id')->on('entities');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('exchange_rate_id')->references('id')->on('exchange_rates');
            $table->foreign('account_id')->references('id')->on('accounts');

            // attributes
            $table->year('year');
            $table->string('reference', 255)->nullable();
            $table->string('transaction_no', 255);
            $table->enum('transaction_type', [
                Transaction::IN,
                Transaction::BL,
                Transaction::JN
            ])->default(Transaction::JN);
            $table->enum('balance_type', [Balance::D, Balance::C])->default(Balance::D);
            $table->decimal('amount', 13, 4);

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
        Schema::dropIfExists('balances');
    }
}
