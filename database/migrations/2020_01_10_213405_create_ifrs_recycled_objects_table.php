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

class CreateIfrsRecycledObjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            config('ifrs.table_prefix').'recycled_objects',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                // relationships
                $table->unsignedBigInteger('entity_id');
                $table->unsignedBigInteger('user_id');

                // constraints
                $userModel = config('ifrs.user_model');
                $table->foreign('entity_id')->references('id')->on(config('ifrs.table_prefix').'entities');
                $table->foreign('user_id')->references('id')->on((new $userModel())->getTable());

                // attributes
                $table->bigInteger('recyclable_id');
                $table->string('recyclable_type', 300);

                // *permanent* deletion
                $table->dateTime('destroyed_at')->nullable();

                //soft deletion
                $table->softDeletes();

                $table->timestamps();
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
        Schema::dropIfExists('recycled_objects');
    }
}
