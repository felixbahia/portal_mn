<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableClienteBlackListObservacaoMotivo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_black_lists', function (Blueprint $table) {
            $table->integer('motivo_cliente_black_lists_id')->nullable();
            $table->string('observacao', 40)->nullable();

            $table->foreign('motivo_cliente_black_lists_id')
                ->references('id')
                ->on('motivo_cliente_black_lists')
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
            $table->dropColumn('motivo_cliente_black_lists_id');
            $table->dropColumn('observacao');
        });
    }
}
