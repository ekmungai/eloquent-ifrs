<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveVatIdColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('ifrs.table_prefix') . 'line_items', function (Blueprint $table) {
            // Drop the foreign key before the column on every driver. Modern SQLite
            // (and Laravel 11+, which drops columns via a table rebuild) rejects a
            // table that still references vat_id in a foreign key definition.
            $table->dropForeign(['vat_id']);
            $table->dropColumn('vat_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('ifrs.table_prefix') . 'line_items', function (Blueprint $table) {
            $table->unsignedBigInteger('vat_id')->nullable();  
            $table->foreign('vat_id')->references('id')->on(config('ifrs.table_prefix') . 'vats');

        });
    }
}
