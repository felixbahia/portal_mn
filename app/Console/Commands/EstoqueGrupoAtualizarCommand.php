<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\AtualizacaoCron;
use App\Http\Controllers\ProdutoGrupoController;
use App\Http\Controllers\EstoqueDadosAdicionaisController;

class EstoqueGrupoAtualizarCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produto_grupo:estoque';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o grupo na tabela de estoque';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'estoque_grupo')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $produtoGrupoControllerObj = new ProdutoGrupoController();
                $produtoGrupoControllerObj->atualizarGrupoNoEstoque();
                $produtoGrupoControllerObj->atualizarUnidadeNoEstoque();
                $estoqueDadosAdicionaisControllerObj = new EstoqueDadosAdicionaisController();
                $estoqueDadosAdicionaisControllerObj->dadosUltimaVenda();
                $estoqueDadosAdicionaisControllerObj->dadosMediaVenda();
                $AtualizacaoCronObj->atualizacao =  Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            } catch (Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
