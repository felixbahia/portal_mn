<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableClienteBlackList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_black_lists', function (Blueprint $table) {
            $table->integer('status_cliente_black_lists_id')->nullable();

            $table->foreign('status_cliente_black_lists_id')
                ->references('id')
                ->on('status_cliente_black_lists')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cliente_black_lists', function (Blueprint $table) {
            $table->dropColumn('status_cliente_black_lists_id');
        });
    }
}
