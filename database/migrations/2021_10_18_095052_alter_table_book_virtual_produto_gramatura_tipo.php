<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBookVirtualProdutoGramaturaTipo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_especificacaos', function (Blueprint $table) {
            $table->renameColumn('gramatura_top', 'gramatura_tipo');
        });

        Schema::table('books_virtuals', function (Blueprint $table) {
            $table->renameColumn('gramatura_top', 'gramatura_tipo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produto_especificacaos', function (Blueprint $table) {
            $table->renameColumn('gramatura_tipo', 'gramatura_top');
        });
        
        Schema::table('books_virtuals', function (Blueprint $table) {
            $table->renameColumn('gramatura_tipo', 'gramatura_top');
        });
    }
}
