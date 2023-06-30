<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CondicaoDePagamentoParaNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('condicoes_pagamento_web', function ($table) {
            $table->boolean('nasajon')->default(false)->nullable();
            $table->uuid('nasajon_forma_pagamento')->nullable();
            $table->uuid('nasajon_parcela')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('condicoes_pagamento_web', function ($table) {
            $table->dropColumn('nasajon');
            $table->dropColumn('nasajon_forma_pagamento');
            $table->dropColumn('nasajon_parcela');
        });
    }
}
