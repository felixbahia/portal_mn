<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CorrecaoMargemPrazo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('margem_prazos', function ($table) {
            $table->dropColumn('prazo_vista');
            $table->dropColumn('prazo_15');
            $table->dropColumn('prazo_30');
            $table->dropColumn('prazo_45');
            $table->dropColumn('prazo_60');
            $table->dropColumn('prazo_90');

            $table->float('fator_diario')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('margem_prazos', function ($table) {
            $table->float('prazo_vista')->nullable();
            $table->float('prazo_15')->nullable();
            $table->float('prazo_30')->nullable();
            $table->float('prazo_45')->nullable();
            $table->float('prazo_60')->nullable();
            $table->float('prazo_90')->nullable();

            $table->dropColumn('fator_diario');

        });
    }
}
