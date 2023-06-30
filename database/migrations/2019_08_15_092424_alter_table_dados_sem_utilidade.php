<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDadosSemUtilidade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->dropColumn('desconto_inscricao_estadual');
            $table->dropColumn('desconto_pagamento_antecipado');
            $table->dropColumn('frete_porcetagem');
            $table->dropColumn('custo_total_desconto_inscricao_estadual');
            $table->dropColumn('custo_total_desconto_pagamento_antecipado');
            $table->dropColumn('custo_total_frete');
            $table->dropColumn('margem');
            $table->dropColumn('custo_total_margem');
            $table->dropColumn('acima_tabela');
            $table->dropColumn('mark_up_real');
            $table->float('desconto')->nullable();
            $table->float('frete')->nullable();
            $table->float('subtotal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->float('desconto_inscricao_estadual')->nullable();
            $table->float('desconto_pagamento_antecipado')->nullable();
            $table->float('frete_porcetagem')->nullable();
            $table->float('custo_total_desconto_inscricao_estadual')->nullable();
            $table->float('custo_total_desconto_pagamento_antecipado')->nullable();
            $table->float('custo_total_frete')->nullable();
            $table->float('margem')->nullable();
            $table->float('custo_total_margem')->nullable();
            $table->float('acima_tabela')->nullable();
            $table->float('mark_up_real')->nullable();
            $table->dropColumn('desconto');
            $table->dropColumn('frete');
            $table->dropColumn('subtotal');
        });
    }
}
