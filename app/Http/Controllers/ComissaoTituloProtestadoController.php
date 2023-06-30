<?php

namespace App\Http\Controllers;

use App\ComissaoTituloProtestado;
use App\LancamentoDebCredVendedor;
use App\TitulosEmAbertoNasajonPortal;
use App\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class ComissaoTituloProtestadoController extends Controller
{
    public function importacaoTitulosProtestados(){

        ini_set('memory_limit', '1024M');

        $titulosEmAbertoObj = TitulosEmAbertoNasajonPortal::with('notaDetalhes', 'notaDetalhes.revisao_vendedor_comissao', 'notaDetalhes.revisao_vendedor_comissao.usuario')
            ->select(
                'nota_id',
                'vencimento',
                'saldotitulo',
                'titulo_emissao',
                'parcela'
            )
            ->distinct()
            ->where('vencimento', "<", Carbon::Now()->subDays(15))
            ->where('vencimento', ">=", '2019-11-14')
            ->where('titulo_emissao', ">=", '2019-05-01')
            ->where('nome_cliente', '!=', 'BALCAO')
            ->whereHas('notaDetalhes', function($query){
                $query->whereHas('revisao_vendedor_comissao');
            })
            ->where(DB::Raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), 'not ilike', '06311274%')
            ->where(DB::Raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), 'not ilike', '05075884%')
            ->get();

        $comissaoTituloProtestadoObj = ComissaoTituloProtestado::all();

        $protestos_concat = [];

        $comissaoTituloProtestadoObj->each(function ($protesto) use (&$protestos_concat){
            $protestos_concat[] = $protesto->nota_id . $protesto->parcela;
        });

        $titulosEmAbertoObj->each(function($titulo) use ($protestos_concat){
            if(!in_array($titulo->nota_id . $titulo->parcela, $protestos_concat)){

                $valor = round($titulo->saldotitulo  * ($titulo->notaDetalhes->revisao_vendedor_comissao->percentual_comissao/100), 2);

                if($valor > 0){
                    $novo_protesto = new ComissaoTituloProtestado;
                    $lancamento_debito = new LancamentoDebCredVendedor;
    
                    $novo_protesto->nota_id = $titulo->nota_id;
                    $novo_protesto->vencimento = $titulo->vencimento;
                    $novo_protesto->saldo = $titulo->saldotitulo;
                    $novo_protesto->titulo_emissao = $titulo->titulo_emissao;
                    $novo_protesto->parcela = $titulo->parcela;
    
                    $novo_protesto->save();
                    
                    if(isset($titulo->notaDetalhes->revisao_vendedor_comissao->usuario->id) && !is_null($titulo->notaDetalhes->revisao_vendedor_comissao->usuario->id)){
                        $id = $titulo->notaDetalhes->revisao_vendedor_comissao->usuario->id;
                    }
                    else{
                        $id = 1;
                    }
    
                    $lancamento_debito->data_lancamento = date('Y-m-d');
                    $lancamento_debito->nota_uuid = $titulo->notaDetalhes->id;
                    $lancamento_debito->num_documento = $titulo->notaDetalhes->numero . '-' . $titulo->parcela;
                    $lancamento_debito->codigo_vendedor = $id;
                    $lancamento_debito->codigo_motivo = 8;
                    $lancamento_debito->tipo = "D";
                    $lancamento_debito->valor = round($titulo->saldotitulo * ($titulo->notaDetalhes->revisao_vendedor_comissao->percentual_comissao/100), 2);
                    $lancamento_debito->created_by = 1;
    
                    $lancamento_debito->save();

                }


            }
        });
        
    }

    public function apagaTitulosPagos(){

        ini_set('memory_limit', '1024M');

        $titulosEmAbertoObj = TitulosEmAbertoNasajonPortal::
            where('titulo_emissao', ">=", '2019-05-01')
            ->where('nome_cliente', '!=', 'BALCAO')
            ->has('notaDetalhes.revisao_vendedor_comissao')
            ->where(DB::Raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), 'not ilike', '06311274%')
            ->where(DB::Raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), 'not ilike', '05075884%')
            ->get();

        $comissaoTituloProtestadoObj = ComissaoTituloProtestado::with('notaDetalhes', 'notaDetalhes.revisao_vendedor_comissao', 'notaDetalhes.revisao_vendedor_comissao.usuario')->get();

        $comissaoTituloProtestadoObj->load('debito_comissao');
        
        $titulos_concat = [];

        $titulosEmAbertoObj->each(function ($titulo) use (&$titulos_concat){
            $titulos_concat[] = $titulo->nota_id . $titulo->parcela;
        });

        $comissaoTituloProtestadoObj->each(function ($protesto) use ($titulos_concat){
            if(!in_array($protesto->nota_id . $protesto->parcela, $titulos_concat)){
                $lancamento_credito = new LancamentoDebCredVendedor;

                $id = $protesto->debito_comissao->codigo_vendedor;
                $comissao = $protesto->debito_comissao->valor;
                $lancamento_credito->data_lancamento = date('Y-m-d');
                $lancamento_credito->nota_uuid = $protesto->debito_comissao->nota_uuid;
                $lancamento_credito->num_documento = $protesto->debito_comissao->num_documento;
                $lancamento_credito->codigo_vendedor = $id;
                $lancamento_credito->codigo_motivo = 2;
                $lancamento_credito->tipo = "C";
                $lancamento_credito->valor = $protesto->debito_comissao->valor;
                $lancamento_credito->created_by = 1;

                $lancamento_credito->save();

                $protesto->delete();
            }
        });

    }

    public function primeiraImportacaoTitulosProtestados(){

        ini_set('memory_limit', '1024M');

        $titulosEmAbertoObj = TitulosEmAbertoNasajonPortal::with('notaDetalhes', 'notaDetalhes.revisao_vendedor_comissao', 'notaDetalhes.revisao_vendedor_comissao.usuario')
            ->select(
                'nota_id',
                'vencimento',
                'saldotitulo',
                'titulo_emissao',
                'parcela'
            )
            ->distinct()
            ->where('vencimento', "<", '2019-11-14')
            ->where('titulo_emissao', ">=", '2019-05-01')
            ->where('nome_cliente', '!=', 'BALCAO')
            ->whereHas('notaDetalhes', function($query){
                $query->whereHas('revisao_vendedor_comissao');
            })
            ->where(DB::Raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), 'not ilike', '06311274%')
            ->where(DB::Raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), 'not ilike', '05075884%')
            ->get();

        $lancamentos_pro_vendedor = [];

        $titulosEmAbertoObj->each(function($titulo) use (&$lancamentos_pro_vendedor){

            $valor = round($titulo->saldotitulo  * ($titulo->notaDetalhes->revisao_vendedor_comissao->percentual_comissao/100), 2);

            if($valor > 0){
                $novo_protesto = new ComissaoTituloProtestado;
    
                $novo_protesto->nota_id = $titulo->nota_id;
                $novo_protesto->vencimento = $titulo->vencimento;
                $novo_protesto->saldo = $titulo->saldotitulo;
                $novo_protesto->titulo_emissao = $titulo->titulo_emissao;
                $novo_protesto->parcela = $titulo->parcela;
    
                $novo_protesto->save();
    
                if(isset($titulo->notaDetalhes->revisao_vendedor_comissao->usuario) && !is_null($titulo->notaDetalhes->revisao_vendedor_comissao->usuario)){
                    $id = $titulo->notaDetalhes->revisao_vendedor_comissao->usuario->id;
                }
                else{
                    $id = 1;
                }
    
                $parcelas = floor($valor / 400) + 1;
    
                $data = Carbon::createFromFormat('Y-m-d', '2019-11-30');
    
                if($parcelas > 1){
                    for ($x=1; $x < $parcelas; $x++) {
                        $lancamento_debito = new LancamentoDebCredVendedor;
                        $lancamento_debito->data_lancamento = $data->format('Y-m-t');
                        $lancamento_debito->nota_uuid = $titulo->notaDetalhes->id;
                        $lancamento_debito->num_documento = $titulo->notaDetalhes->numero . '-' . $titulo->parcela;
                        $lancamento_debito->codigo_vendedor = $id;
                        $lancamento_debito->codigo_motivo = 8;
                        $lancamento_debito->tipo = "D";
                        $lancamento_debito->valor = 400;
                        $lancamento_debito->parcela = $x .'/'. $parcelas;
                        $lancamento_debito->created_by = 1;
    
                        $data->addMonthNoOverflow();
        
                        $lancamento_debito->save();
                    }
                }
                else{
                    $x = 1;
                }
    
                $lancamento_debito = new LancamentoDebCredVendedor;
                $lancamento_debito->data_lancamento = $data->format('Y-m-t');
                $lancamento_debito->nota_uuid = $titulo->notaDetalhes->id;
                $lancamento_debito->num_documento = $titulo->notaDetalhes->numero . '-' . $titulo->parcela;
                $lancamento_debito->codigo_vendedor = $id;
                $lancamento_debito->codigo_motivo = 8;
                $lancamento_debito->tipo = "D";
                $lancamento_debito->valor = fmod($valor, 400);

                if($parcelas > 1){
                    $lancamento_debito->parcela = $x .'/'. $parcelas;
                }

                $lancamento_debito->created_by = 1;
    
                $lancamento_debito->save();
            }

        });
        
    }

}
