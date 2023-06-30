<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Margem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('margem', function (Blueprint $table) {

            $table->increments("id");

            $table->string('empresa', 2);
            $table->string('grupo', 15)->nullable();
            $table->string('produto', 18)->nullable();
            $table->string('marca', 10)->nullable();
            $table->string('linha', 15)->nullable();

            $table->float('margem_a');

            $table->softDeletes();

            $table->timestamps();

            $table->integer('created_by');
            $table->integer('modified_by')->nullable();

            $table->unique(['empresa', 'grupo', 'produto', 'marca', 'linha', 'deleted_at']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('margem');
    }
}
