<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLogLogColetorRomaneios extends Migration
{
    public function up()
    {
        Schema::table('log_coletor_romaneios', function (Blueprint $table) {
            $table->dropColumn('cnpj_cpf');
            $table->dropColumn('produto_grupo');
         $table->uuid('peca_id')->nullable();
         $table->string('peca_codigo',60)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_coletor_romaneios', function (Blueprint $table) {
   
            $table->string('cnpj_cpf',60)->nullable();
            $table->string('produto_grupo',100)->nullable();
            $table->dropColumn('peca_codigo');
            $table->dropColumn('peca_id');

        });
    }
}
