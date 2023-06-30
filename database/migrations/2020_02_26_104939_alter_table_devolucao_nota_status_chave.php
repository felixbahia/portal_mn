<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Spatie\Permission\Models\Permission;

use App\DevolucaoNotaStatus;

class AlterTableDevolucaoNotaStatusChave extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_nota_status', function ($table) {
            $table->renameColumn('status', 'descricao');        
            $table->string('chave')->nullable();
        });

        DevolucaoNotaStatus::all()->each(function ($devolucao){
            $devolucao->chave = str_replace(' ', '_', strtolower($devolucao->descricao));
            $devolucao->save();


            if($devolucao->selecionavel === true){
                $permissao = new Permission;
    
                $permissao->name = 'action App\DevolucaoNotaAprovacao ' . $devolucao->chave;
                $permissao->guard_name = 'web';
                $permissao->save();
            }
        });

        Schema::table('devolucao_nota_status', function ($table) {
            $table->string('chave')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devolucao_nota_status', function ($table) {
            $table->renameColumn('descricao', 'status');        
            $table->dropColumn('chave');
        });
    }
}
