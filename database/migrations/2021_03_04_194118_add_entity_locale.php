<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEntityLocale extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            config('ifrs.table_prefix') . 'entities',
            function (Blueprint $table) {
                $table->string('locale', 20)->default(config('ifrs.locales')[0]);
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
            config('ifrs.table_prefix') . 'entities', function(BLueprint $table)
            {
                $table->dropColumn('locale');
            });
    }
}
