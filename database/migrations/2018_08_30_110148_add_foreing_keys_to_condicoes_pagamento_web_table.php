<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeingKeysToCondicoesPagamentoWebTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('condicoes_pagamento_web', function (Blueprint $table) {
            $table->foreign('created_by', 'fk_condicoes_pagamento_web_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('modified_by', 'fk_condicoes_pagamento_web_modified_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('condicoes_pagamento_web', function (Blueprint $table) {
            $table->dropForeign('fk_condicoes_pagamento_web_created_by');
            $table->dropForeign('fk_condicoes_pagamento_web_modified_by');
        });
    }
}
