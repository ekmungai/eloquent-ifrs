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
use IFRS\Models\ReportingPeriod;

class CreateReportingPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'ifrs_reporting_periods',
            function (Blueprint $table) {
                $table->bigIncrements('id');

                // relationships
                $table->unsignedBigInteger('entity_id');

                // constraints
                $table->foreign('entity_id')->references('id')->on('entities');

                // attributes
                $table->integer('period_count');
                $table->enum('status', [
                    ReportingPeriod::OPEN,
                    ReportingPeriod::CLOSED,
                    ReportingPeriod::ADJUSTING
                ])->default(ReportingPeriod::OPEN);
                $table->year('year');

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
        Schema::dropIfExists('ifrs_reporting_periods');
    }
}
