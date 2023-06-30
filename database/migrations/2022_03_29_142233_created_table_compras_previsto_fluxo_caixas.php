<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableComprasPrevistoFluxoCaixas extends Migration
{
        /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compras_previsto_fluxo_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->date('mes_ano');
            $table->float('valor');
            $table->float('mes_atual_valor');
            $table->float('mes_1_valor');
            $table->float('mes_2_valor');
            $table->float('mes_3_valor');
            $table->float('mes_4_valor');
            $table->float('mes_5_valor');
            $table->float('mes_6_valor');
            $table->float('mes_7_valor');
            $table->float('mes_8_valor');
            $table->float('mes_9_valor');
            $table->float('mes_10_valor');
            $table->float('mes_11_valor');
            $table->float('mes_atual_porcetagem');
            $table->float('mes_1_porcetagem');
            $table->float('mes_2_porcetagem');
            $table->float('mes_3_porcetagem');
            $table->float('mes_4_porcetagem');
            $table->float('mes_5_porcetagem');
            $table->float('mes_6_porcetagem');
            $table->float('mes_7_porcetagem');
            $table->float('mes_8_porcetagem');
            $table->float('mes_9_porcetagem');
            $table->float('mes_10_porcetagem');
            $table->float('mes_11_porcetagem');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('compras_previsto_fluxo_caixas');
    }
}
