<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasTransportadoraHeader extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_transportadora_headers', function (Blueprint $table) {
            $table->string('caminho_arquivo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_transportadora_headers', function (Blueprint $table) {
            $table->dropColumn('caminho_arquivo');
        });
    }
}
