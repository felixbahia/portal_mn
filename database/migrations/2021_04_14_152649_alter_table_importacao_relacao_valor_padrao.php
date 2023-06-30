<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoRelacaoValorPadrao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacaos', function (Blueprint $table) {
            $table->integer('importacao_valor_padraos_id')->nullable();
            $table->foreign('importacao_valor_padraos_id')
                ->references('id')
                ->on('importacao_valor_padraos')
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
        Schema::table('importacaos', function (Blueprint $table) {
            $table->dropForeign('importacaos_importacao_valor_padraos_id_foreign');
            $table->dropColumn('importacao_valor_padraos_id');
        });
    }
}
