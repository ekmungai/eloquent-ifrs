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

class CreateAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ifrs_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relationships
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('forex_account_id')->nullable();

            // constraints
            $table->foreign('entity_id')->references('id')->on('entities');
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->foreign('forex_account_id')->references('id')->on('accounts');

            // attributes
            $table->unsignedBigInteger('cleared_id');
            $table->string('cleared_type', 300);
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
        Schema::dropIfExists('ifrs_assignments');
    }
}
