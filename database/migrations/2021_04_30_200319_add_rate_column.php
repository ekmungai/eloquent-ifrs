<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRateColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            config('ifrs.table_prefix') . 'ledgers',
            function (Blueprint $table) {
                $table->decimal('rate', 13, 4)->default(1);
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
        config('ifrs.table_prefix') . 'ledgers', function(BLueprint $table){
            $table->dropColumn('rate');
        });
    }
}
