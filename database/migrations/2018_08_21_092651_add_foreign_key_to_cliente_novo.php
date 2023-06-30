<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyToClienteNovo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_novos', function (Blueprint $table)
        {
            $table->foreign('created_by', 'fk_cliente_novos_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('updated_by', 'fk_cliente_novos_updated_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('deleted_by', 'fk_cliente_novos_deleted_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
        Schema::table('cliente_novo_socios', function (Blueprint $table)
        {
            $table->foreign('cliente_novo_id', 'fk_cliente_novo_socios_cliente_novo_id')->references('id')->on('cliente_novos')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('created_by', 'fk_cliente_novo_socios_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('updated_by', 'fk_cliente_novo_socios_updated_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('deleted_by', 'fk_cliente_novo_socios_deleted_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
        Schema::table('cliente_novo_referencias', function (Blueprint $table)
        {
            $table->foreign('cliente_novo_id', 'fk_cliente_novo_referencias_cliente_novo_id')->references('id')->on('cliente_novos')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('created_by', 'fk_cliente_novo_referencias_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('updated_by', 'fk_cliente_novo_referencias_updated_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('deleted_by', 'fk_cliente_novo_referencias_deleted_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cliente_novos', function (Blueprint $table){
            $table->dropForeign('fk_cliente_novos_created_by');
            $table->dropForeign('fk_cliente_novos_updated_by');
            $table->dropForeign('fk_cliente_novos_deleted_by');
        });
        Schema::table('cliente_novo_socios', function (Blueprint $table){
            $table->dropForeign('fk_cliente_novo_socios_cliente_novo_id');
            $table->dropForeign('fk_cliente_novo_socios_created_by');
            $table->dropForeign('fk_cliente_novo_socios_updated_by');
            $table->dropForeign('fk_cliente_novo_socios_deleted_by');
        });
        Schema::table('cliente_novo_referencias', function (Blueprint $table){
            $table->dropForeign('fk_cliente_novo_referencias_cliente_novo_id');
            $table->dropForeign('fk_cliente_novo_referencias_created_by');
            $table->dropForeign('fk_cliente_novo_referencias_updated_by');
            $table->dropForeign('fk_cliente_novo_referencias_deleted_by');
        });
    }
}
