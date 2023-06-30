<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteracaoEmPoliticasParaPerfil extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('politica_perfils', function (Blueprint $table) {
            $table->dropForeign('politica_perfils_tipo_usuarios_id_foreign');
            $table->dropColumn('tipo_usuarios_id');

            $table->integer('perfil_id')->nullable();
			$table->foreign('perfil_id')
				->references('id')
				->on('roles')
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
        Schema::table('politica_perfils', function (Blueprint $table) {
            $table->dropForeign('politica_perfils_perfil_id_foreign');
            $table->dropColumn('perfil_id');

            $table->integer('tipo_usuarios_id')->nullable();
			$table->foreign('tipo_usuarios_id')
				->references('id')
				->on('tipo_usuarios')
				->onDelete('NO ACTION');
        });
    }
}
