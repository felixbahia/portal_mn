<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AltecaoNomeTabelaInventario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('inventario_historiocos', 'inventario_historicos');
        Schema::rename('inventario_historioco_produtos', 'inventario_historico_produtos');
        Schema::rename('inventario_historioco_produto_pecas', 'inventario_historico_produto_pecas');

        Schema::table('inventario_historico_produtos', function ($table) {
            $table->renameColumn('inventario_historiocos_id', 'inventario_historicos_id');
        });
        Schema::table('inventario_historico_produto_pecas', function ($table) {
            $table->renameColumn('inventario_historioco_produtos_id', 'inventario_historico_produtos_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('inventario_historicos', 'inventario_historiocos');
        Schema::rename('inventario_historico_produtos', 'inventario_historioco_produtos');
        Schema::rename('inventario_historico_produto_pecas', 'inventario_historioco_produto_pecas');


        Schema::table('inventario_historioco_produtos', function ($table) {
            $table->renameColumn('inventario_historicos_id', 'inventario_historiocos_id');
        });
        Schema::table('inventario_historioco_produto_pecas', function ($table) {
            $table->renameColumn('inventario_historico_produtos_id', 'inventario_historioco_produtos_id');
        });
    }
}
