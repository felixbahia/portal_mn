<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCenprotLogsAddStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cenprot_logs', function (Blueprint $table) {
            $table->string('cenprot_status')->nullable();
            $table->timestamp('cenprot_status_data_hora')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cenprot_titulos', function (Blueprint $table) {
            $table->dropColumn('cenprot_status');
            $table->dropColumn('cenprot_status_data_hora');
        });
    }
}
