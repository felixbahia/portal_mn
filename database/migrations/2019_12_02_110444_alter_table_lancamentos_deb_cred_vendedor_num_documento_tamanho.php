<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentosDebCredVendedorNumDocumentoTamanho extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamentos_deb_cred_vendedor', function (Blueprint $table) {
            $table->string('num_documento', 15)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamentos_deb_cred_vendedor', function (Blueprint $table) {
            $table->string('num_documento', 10)->change();
        });
    }
}
