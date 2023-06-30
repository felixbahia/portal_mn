<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableVideoPerfil extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      
        Schema::create('video_perfils', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('videos_id')->nullable();
            $table->integer('perfil_id')->nullable();
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
			$table->foreign('perfil_id')
				->references('id')
				->on('roles')
				->onDelete('NO ACTION');

                $table->foreign('videos_id')
				->references('id')
				->on('videos')
				->onDelete('NO ACTION');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign('videos_politicas_id_foreign');
            $table->dropColumn('politicas_id');
            $table->dropColumn('grupo');
            $table->renameColumn('codigo', 'arquivo');
       
        });

  
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('video_perfils');

        Schema::table('videos', function (Blueprint $table) {
  
            $table->integer('politicas_id')->nullable();
            $table->renameColumn('arquivo', 'codigo');
         
            $table->foreign('politicas_id')
                ->references('id')
                ->on('politicas')
                ->onDelete('NO ACTION');
       
        });
    }
}
