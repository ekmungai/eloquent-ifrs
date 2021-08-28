<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLineItemEntryColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            config('ifrs.table_prefix') . 'line_items',
            function (Blueprint $table) {
                $table->boolean('credited')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( config('ifrs.table_prefix') . 'line_items',
        function (Blueprint $table) {
            if (config('database.default') == 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;'); // sqlite needs to drop the entire table to remove a column, which fails because the table is already referenced
            }
            $table->dropColumn('credited');
    });
    }
}
