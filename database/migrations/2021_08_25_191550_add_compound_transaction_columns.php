<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCompoundTransactionColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            config('ifrs.table_prefix') . 'transactions',
            function (Blueprint $table) {
                $table->decimal('main_account_amount', 13, 4)->default(0);
                $table->boolean('compound')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            config('ifrs.table_prefix') . 'transactions',
            function (Blueprint $table) {
                if (config('database.default') == 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF;'); // sqlite needs to drop the entire table to remove a column, which fails because the table is already referenced
                }
                $table->dropColumn('compound');
        });
        Schema::table(
            config('ifrs.table_prefix') . 'transactions',
            function (Blueprint $table) {
                if (config('database.default') == 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF;'); // sqlite needs to drop the entire table to remove a column, which fails because the table is already referenced
                }
                $table->dropColumn('main_account_amount');
        });
    }
}
