<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBooksVirtualsRenameTipoConfeccao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books_virtuals', function (Blueprint $table) {
            $table->renameColumn('tipo_confeccao', 'tipo_material');
        });      
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('books_virtuals', function (Blueprint $table) {
            $table->renameColumn('tipo_material', 'tipo_confeccao');
        });    
    }
}
