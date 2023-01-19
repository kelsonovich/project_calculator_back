<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisionLogField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('revision_logs', function (Blueprint $table) {
            $table->string('model_id')->after('revision_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('revision_logs', function (Blueprint $table) {
            $table->dropColumn('model_id')->after('revision_id')->nullable();
        });
    }
}
