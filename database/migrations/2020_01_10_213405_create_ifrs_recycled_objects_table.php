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
use Illuminate\Support\Facades\App;
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
        // check for already existing user id column type before relating it via bigInteger or integer
        // old laravel apps used integer for increments.

        $versionString = App::version();
        $version = strpos($versionString, "Components") > 0 ? substr($versionString, 7, 1) : $versionString;
        $userModel = is_array(config('ifrs.user_model')) ? config('ifrs.user_model')[intval($version)] : config('ifrs.user_model');
        $type = Schema::getColumnType((new $userModel())->getTable(),'id');

        Schema::create(
            config('ifrs.table_prefix').'recycled_objects',
            function (Blueprint $table) use($type) {
                $table->bigIncrements('id');

                // relationships
                $table->unsignedBigInteger('entity_id');
                if($type == "integer"){
                    $table->unsignedInteger('user_id');
                }else{
                    $table->unsignedBigInteger('user_id');
                }

                // constraints
                $versionString = App::version();
                $version = strpos($versionString, "Components") > 0 ? substr($versionString, 7, 1) : $versionString;
                $userModel = is_array(config('ifrs.user_model')) ? config('ifrs.user_model')[intval($version)] : config('ifrs.user_model');
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
        Schema::dropIfExists(config('ifrs.table_prefix').'recycled_objects');
    }
}
