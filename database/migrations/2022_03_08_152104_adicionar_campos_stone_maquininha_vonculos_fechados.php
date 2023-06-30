<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCamposStoneMaquininhaVonculosFechados extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_maquininha_vinculos_fechados', function (Blueprint $table) {
            $table->string('cashier_number');
            $table->string('pdv_number');
            $table->string('pos_link_label');
            $table->string('pos_reference_id');
            $table->integer('stone_configuracao_maquininha_id');
            
            $table->foreign('stone_configuracao_maquininha_id')
                ->references('id')
                ->on('stone_configuracao_maquininhas')
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
        Schema::table('stone_maquininha_vinculos_fechados', function (Blueprint $table) {
            $table->dropColumn('cashier_number');
            $table->dropColumn('pdv_number');
            $table->dropColumn('pos_link_label');
            $table->dropColumn('pos_reference_id');
            $table->dropColumn('stone_configuracao_maquininha_id');
        });
    }
}
