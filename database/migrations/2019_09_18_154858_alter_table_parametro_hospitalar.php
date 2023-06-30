<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableParametroHospitalar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parametro_hospitalar', function ($table) {
            $table->dropColumn('margens');
            $table->dropColumn('mark_up');
            $table->dropColumn('desconto_pagamento_antecipado_0');
            $table->dropColumn('desconto_pagamento_antecipado_30');
            $table->dropColumn('desconto_pagamento_antecipado_60');
            $table->dropColumn('desconto_pagamento_antecipado_61');
            $table->dropColumn('desconto_inscricao_estadual');
            $table->string('estado')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parametro_hospitalar', function ($table) {
            $table->string('margens');
            $table->float('mark_up');
            $table->float('desconto_pagamento_antecipado_0');
            $table->float('desconto_pagamento_antecipado_30');
            $table->float('desconto_pagamento_antecipado_60');
            $table->float('desconto_pagamento_antecipado_61');
            $table->float('desconto_inscricao_estadual');
        });
    }
}
