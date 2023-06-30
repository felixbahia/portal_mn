<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ObservacaoEmHistoricoFinanceiroCliente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico_financeiro_clientes', function ($table) {
            $table->string('observacao', 100)->default('')->nullable();
            $table->string('email_enviado', 250)->default('')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('historico_financeiro_clientes', function ($table) {
            $table->dropColumn('observacao');
            $table->dropColumn('email_enviado');
        });
    }
}
