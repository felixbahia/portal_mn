<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRemessaTecido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projeto_tecidos', function ($table) {
            $table->float('quantidade_enviada')->nullable();
            $table->boolean('enviado_total')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projeto_tecidos', function ($table) {
            $table->dropColumn('quantidade_enviada');
            $table->dropColumn('enviado_total');
        });
    }
}
