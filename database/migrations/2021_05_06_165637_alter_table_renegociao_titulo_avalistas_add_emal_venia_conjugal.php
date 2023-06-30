<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociaoTituloAvalistasAddEmalVeniaConjugal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_avalistas', function (Blueprint $table) {
            $table->string('email_venia_conjugal')->nullable();
            $table->string('ip_venia_conjugal')->nullable();
            $table->date('data_assinatura_venia_conjugal')->nullable();
            $table->boolean('aceito_venia_conjugal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_avalistas', function (Blueprint $table) {
            $table->dropColumn('email_venia_conjugal');
            $table->dropColumn('ip_venia_conjugal');
            $table->dropColumn('data_assinatura_venia_conjugal');
            $table->dropColumn('aceito_venia_conjugal');
        });
    }
}
