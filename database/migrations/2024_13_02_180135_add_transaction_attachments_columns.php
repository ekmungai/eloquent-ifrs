<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTransactionAttachmentsColumns extends Migration
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
                $table->unsignedBigInteger('attachment_id')->nullable();
                $table->string('attachment_type')->nullable();
            }
        );
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
                $table->dropColumn('attachment_id');
            }
        );

        Schema::table(
            config('ifrs.table_prefix') . 'transactions',
            function (Blueprint $table) {
                $table->dropColumn('attachment_type');
            }
        );
    }
}
