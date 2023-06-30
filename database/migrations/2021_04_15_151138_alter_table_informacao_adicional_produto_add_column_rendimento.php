<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInformacaoAdicionalProdutoAddColumnRendimento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('informacao_adicional_produtos', function (Blueprint $table) {
            $table->string('rendimento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('informacao_adicional_produtos', function (Blueprint $table) {
            $table->dropColumn('rendimento');
        });
    }
}
