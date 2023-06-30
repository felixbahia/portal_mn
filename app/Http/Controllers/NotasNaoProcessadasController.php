<?php

namespace App\Http\Controllers;

use App\NotasNaoProcessadasNasajon;

use App\Http\Controllers\EmailController;

use Illuminate\Http\Request;
use Carbon\Carbon;

class NotasNaoProcessadasController extends Controller
{
    public function enviaEmail(){

        $inicio =  Carbon::now();

		$EmailObj = new EmailController();

        $notasNaoProcessadasObj = NotasNaoProcessadasNasajon::with('notaDetalhes')->get();

        if($notasNaoProcessadasObj->isNotEmpty()){

            $estabelecimentos = returnEmpresasNasajonView();

            $variaveis = [
                'data' => Carbon::now()->format('d/m/Y'),
                'notas' => ''
            ];
            
            $notasNaoProcessadasObj->each(function($nota) use (&$variaveis, $estabelecimentos, $inicio){

                if(isset($nota->notaDetalhes) && !is_null($nota->notaDetalhes)){
                    $cliente = $nota->notaDetalhes->cliente_nome . ' - ' . $nota->notaDetalhes->cliente_documento;
                    $operacao = $nota->notaDetalhes->operacao_descricao;
                }
                else{
                    $cliente = '';
                    $operacao = '';
                }

                $variaveis['notas'] .= '<p>
                    <b>Estabelecimento:</b> ' . $estabelecimentos[intval($nota->estabelecimento_codigo)] . '
                    <b>Nota:</b> ' . $nota->nota_numero . '
                    <b>Cliente:</b> ' . $cliente . '
                    <b>Emissão:</b> ' . parserData($nota->nota_emissao) . '
                    <b>Valor:</b> ' . parserValor($nota->nota_valor) . '
                    <b>Operação:</b> ' . $operacao . '
                </p>';
            });

            $returnEmail = $EmailObj->sendEmailToken('00', "email_notas_nao_processadas", [], $variaveis);

            echo "Notas não processadas enviadas: " . $notasNaoProcessadasObj->count() . PHP_EOL . 'Demorou ' . $inicio->diffForHumans() . PHP_EOL;

        }

    }
}
