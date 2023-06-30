<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBooksVirtualsAddIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books_virtuals', function (Blueprint $table){
            $table->index(['nome', 'deleted_at']);
            $table->index(['artigo', 'deleted_at']);
            $table->index(['segmentos_id', 'deleted_at']);
            $table->index(['tipo_material', 'deleted_at']);
            $table->index(['nome', 'artigo', 'deleted_at']);
            $table->index(['nome', 'segmentos_id', 'deleted_at']);
            $table->index(['nome', 'tipo_material', 'deleted_at']);
            $table->index(['nome', 'artigo', 'segmentos_id', 'deleted_at']);
            $table->index(['nome', 'artigo', 'tipo_material', 'deleted_at']);
            $table->index(['artigo', 'segmentos_id', 'deleted_at']);
            $table->index(['artigo', 'tipo_material', 'deleted_at']);
            $table->index(['artigo', 'segmentos_id', 'tipo_material', 'deleted_at']);
            $table->index(['segmentos_id', 'tipo_material', 'deleted_at']);
            

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('books_virtuals', function (Blueprint $table){
            $table->dropIndex(['nome', 'deleted_at']);
            $table->dropIndex(['artigo', 'deleted_at']);
            $table->dropIndex(['segmentos_id', 'deleted_at']);
            $table->dropIndex(['tipo_material', 'deleted_at']);
            $table->dropIndex(['nome', 'artigo', 'deleted_at']);
            $table->dropIndex(['nome', 'segmentos_id', 'deleted_at']);
            $table->dropIndex(['nome', 'tipo_material', 'deleted_at']);
            $table->dropIndex(['nome', 'artigo', 'segmentos_id', 'deleted_at']);
            $table->dropIndex(['nome', 'artigo', 'tipo_material', 'deleted_at']);
            $table->dropIndex(['artigo', 'segmentos_id', 'deleted_at']);
            $table->dropIndex(['artigo', 'tipo_material', 'deleted_at']);
            $table->dropIndex(['artigo', 'segmentos_id', 'tipo_material', 'deleted_at']);
            $table->dropIndex(['segmentos_id', 'tipo_material', 'deleted_at']);
        });
    }
}