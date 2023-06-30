<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasImportadasEntradasAlterarCampoCliente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->renameColumn('cliente_id', 'remetente_id');
            $table->renameColumn('cliente_cpf_cnpj', 'remetente_cnpj');
            $table->renameColumn('cliente_nome', 'remetente_nome');
            $table->renameColumn('destinatario_cnpj', 'destinatario_cpf_cnpj');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->renameColumn('remetente_id', 'cliente_id');
            $table->renameColumn('remetente_cnpj', 'cliente_cpf_cnpj');
            $table->renameColumn('remetente_nome', 'cliente_nome');
            $table->renameColumn('destinatario_cpf_cnpj', 'destinatario_cnpj');
        });
    }
}
