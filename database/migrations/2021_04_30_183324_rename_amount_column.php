<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameAmountColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('ifrs.table_prefix').'balances', function (Blueprint $table) {
            $table->renameColumn('amount', 'balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('ifrs.table_prefix').'balances', function (Blueprint $table) {
            $table->renameColumn('balance', 'amount');
        });
    }
}
