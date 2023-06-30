<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMargemPrazosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('margem_prazos', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('origem', 2);

            $table->float('prazo_vista')->nullable();
            $table->float('prazo_15')->nullable();
            $table->float('prazo_30')->nullable();
            $table->float('prazo_45')->nullable();
            $table->float('prazo_60')->nullable();
            $table->float('preco_a')->nullable();
            $table->float('preco_b')->nullable();
            $table->float('preco_c')->nullable();
            $table->float('comissao_a')->nullable();
            $table->float('comissao_b')->nullable();
            $table->float('comissao_c')->nullable();

            $table->softDeletes();

            $table->integer('created_by');
            $table->integer('modified_by')->nullable();
 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('margem_prazos');
    }
}
