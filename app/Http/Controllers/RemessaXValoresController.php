<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;

use App\Movimentacao;
use App\NotasEntradasNasajon;
use App\NotasNasajon;
use App\NasajonEstabelecimento;

class RemessaXValoresController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\RemessaXValores") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RemessaXValores');

		$estabelecimentos_temp = returnEmpresasNasajonView();
		$estabelecimentos = [];
		$estabelecimentos[3] = $estabelecimentos_temp[3];
		$estabelecimentos[4] = $estabelecimentos_temp[4];
	
		$data_inicial = Carbon::parse('2021-03-01')->format('m/Y');

        return view('programs.remessa_x_valores.index')->with(['estabelecimentos' => $estabelecimentos, 'data_inicial' => $data_inicial]);
    }

	public function filtro(Request $request){
		$filtro = $request->only(['estabelecimento', 'data']);

		$MovimentacaoObj = Movimentacao::with(['produto']);
		$estabelecimentos = returnEmpresasNasajonView();


		$MovimentacaoObj->whereNotIn('cfop', [0]);
		$MovimentacaoObj->whereIn('estabelecimento', ['03', '04']);
		$MovimentacaoObj->where('documento', '!=', '');
		if(!empty($filtro['data'])){
			$data = Carbon::createFromFormat('m/Y', $filtro['data']);
			$MovimentacaoObj->whereBetween('data_movimentacao', [$data->format('Y-m-01'), $data->format('Y-m-t')]);
		}else{
			$MovimentacaoObj->whereBetween('data_movimentacao', ['2021-03-01', '2021-03-31']);
		}
		$MovimentacaoObj->orderBy('estabelecimento');
		$MovimentacaoObj->orderBy('produto_codigo');
		$MovimentacaoObj->orderBy('data_movimentacao');
		$MovimentacaoObj = $MovimentacaoObj->get();
		
		$produtos = [];
		$retorno = [];
		$MovimentacaoObj->each(function($movimento) use (&$produtos){
			$key = $movimento->estabelecimento.Carbon::parse($movimento->data_movimentacao)->format('Ym').$movimento->produto_codigo;
			if(!isset($produtos[$key])){
				$produtos[$key] = [
					'estabelecimento' => $movimento->estabelecimento,
					'produto_codigo' => $movimento->produto_codigo,
					'produto_descricao' => $movimento->produto->descricao,
					'movimentos' => []
				];
			}
			$produtos[$key]['movimentos'][] = $movimento;
		});
		$produtos = collect($produtos);
		$produtos->each(function($produto, $chave) use (&$retorno, $estabelecimentos){
			$produto = collect($produto);
			$total = ['remessa' => 0, 'venda' => 0, 'saldo' => 0, 'valor_remessa' => 0, 'valor_venda' => 0, 'valor_saldo' => 0];
			$movimentos = collect($produto['movimentos']);
			$movimentos->each(function($movimento) use (&$total){
				if($movimento->sinal == 'SAIDA'){
					if(in_array($movimento->cfop, ['6905'])){
						$total['remessa'] += $movimento->quantidade;
						$total['saldo'] += $movimento->quantidade;
						$total['valor_remessa'] += ($movimento->quantidade * $movimento->preco);
						$total['valor_saldo'] += ($movimento->quantidade * $movimento->preco);
					}else{
						$total['venda'] += $movimento->quantidade;
						$total['saldo'] -= $movimento->quantidade;
						$total['valor_venda'] += ($movimento->quantidade * $movimento->preco);
						$total['valor_saldo'] -= ($movimento->quantidade * $movimento->preco);
					}
				}
			});
			$retorno[] = [
				'estabelecimento' => $estabelecimentos[(int) $produto['estabelecimento']],
				'data' => Carbon::parse($movimentos->first()->data_movimentacao)->format('m/Y'),
				'produto_codigo' => $produto['produto_codigo'],
				'produto_descricao' => $produto['produto_descricao'],
				'venda' => $total['venda'],
				'remessa' => $total['remessa'],
				'saldo' => $total['saldo'],
				'saldo_inicial' => 0,
				'valor_remessa' => $total['valor_remessa'],
				'valor_venda' => $total['valor_venda'],
				'valor_saldo' => $total['valor_saldo'],
				'valor_nota_complementar' => 0,
				'filtro' => encrypt([
					'estabelecimento' => $produto['estabelecimento'],
					'produto' =>  $produto['produto_codigo'],
					'data' =>  Carbon::parse($movimentos->first()->data_movimentacao)
				])
			];
		});
		return response()->json([
			'status' => 'success',
			'message' => '',
			'error' => [],
			'response' => $retorno
		], 200);
	}

	public function modalHistorico(Request $request){
		$filtro = $request->only(['filtro'])['filtro'];
		$filtro = decrypt($filtro);
		
		$MovimentacaoObj = Movimentacao::with(['produto'])
			->whereNotIn('cfop', [0])
			->where('estabelecimento', $filtro['estabelecimento'])
			->where('produto_codigo', $filtro['produto'])
			->where('documento', '!=', '')
			->whereBetween('data_movimentacao', [$filtro['data']->format('Y-m-01'), $filtro['data']->format('Y-m-t')])
			->orderBy('data_movimentacao');
		$MovimentacaoObj = $MovimentacaoObj->get();
		$retorno = [];
		$total = ['saldo' => 0, 'valor' => 0];
		$MovimentacaoObj->each(function($movimento) use(&$retorno, &$total){

			$documento = $movimento->documento;
            if(Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07') && !empty($documento)){
                if($movimento->sinal == 'ENTRADA'){
                    $nota = NotasEntradasNasajon::query()->
                        where('Estabelecimento', $movimento->estabelecimento)->
                        where('Número do Documento', $movimento->documento)->
                        first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $nota['Identificador Documento'] . "'>" . $movimento->documento . "</a>";
                    }else{
                        $nota = NotasEntradasNasajon::query()->
							where('Estabelecimento', '20')->
							where('Número do Documento', $movimento->documento)->
                            first();
                        if(!empty($nota)){
                            $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $nota['Identificador Documento'] . "'>" . $movimento->documento . "</a>";
                        }
                    }
                }else{
                    $nota = NotasNasajon::query()->
                        where('estabelecimento_codigo', $movimento->estabelecimento)->
                        where('numero', $movimento->documento)->first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota' href='#' data-id='" . $nota->id . "'>" . $movimento->documento . "</a>";
                    }
                }
            }
			if($movimento->sinal == 'SAIDA'){
				if(in_array($movimento->cfop, ['6905'])){
					$retorno[] = [
						'data' => Carbon::parse($movimento->data_movimentacao)->format('d/m/Y'),
						'documento' => $documento,
						'aliquota' => $movimento->aliquota,
						'cfop' => $movimento->cfop,
						'unidade' => $movimento->unidade,
						'quantidade_nota' => parserValor($movimento->quantidade),
						'preco_nota' => parserValor($movimento->preco),
						'total' => parserValor($movimento->quantidade * $movimento->preco),
						'tipo' => "Remessa"
					];
					$total['saldo'] += $movimento->quantidade;
					$total['valor'] += ($movimento->quantidade * $movimento->preco);
				}else{
					$retorno[] = [
						'data' => Carbon::parse($movimento->data_movimentacao)->format('d/m/Y'),
						'documento' => $documento,
						'aliquota' => $movimento->aliquota,
						'cfop' => $movimento->cfop,
						'unidade' => $movimento->unidade,
						'quantidade_nota' => parserValor($movimento->quantidade),
						'preco_nota' => parserValor($movimento->preco),
						'total' => parserValor($movimento->quantidade * $movimento->preco),
						'tipo' => "venda"
					];
					$total['saldo'] -= $movimento->quantidade;
					$total['valor'] -= ($movimento->quantidade * $movimento->preco);
				}
			}
		});
		return view('programs.remessa_x_valores.modal.abertura')->with(['movimentos' => $retorno]);
    }
}

