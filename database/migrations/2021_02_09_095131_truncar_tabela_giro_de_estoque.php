<?php

use App\GiroDeEstoque;
use Illuminate\Database\Migrations\Migration;

class TruncarTabelaGiroDeEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        GiroDeEstoque::truncate();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        GiroDeEstoque::truncate();
    }
}
