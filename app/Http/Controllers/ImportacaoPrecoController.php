<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;

use App\ComprasNasajon;
use App\CompraProdutoImportacao;
use App\ItensNotasCompraNasajon;
use App\NasajonEstabelecimento;
use App\Preco;
use App\PrecosLog;
use App\Produto;
use App\ProdutoEspecificacao;
use App\ProdutosEstoque;
use App\ProdutoNasajon;
use App\ProformaEncerrado;
use App\ProformaProduto;
use App\ValorCustoNota;
use App\ValorCustoNotaProduto;
use App\NotasVenda;
use App\NotasEntradasItensNasajon;
use App\PedidoComprasAssociacaoNotaNasajon;
use App\UnidadeConversaoProdutoNasajon;
use App\PrecoMargem;
use App\HistoricoAtualizacaoAutomaticaMargem;
use App\ProdutoLinha;
use App\AjusteEstoqueNasajon;
use App\PrecoAlteracaoPorDePara;

use App\Http\Controllers\EmailController;

use App\Http\Requests\EspecificacoesRequest;
use App\Http\Requests\PrecoAtualizacaEmMassaRequest;

use App\Exports\ProdutosSemGerencialExport;
use App\Exports\ProdutosGerencialMenorContabilExport;
use App\Exports\ProdutoSemCustoContabilExport;
use App\Exports\ProdutosCustoContabilMaiorCustoGerencialExport;
use App\Exports\ProdutosSemBookExport;
use Carbon\Carbon;

use Auth;

class ImportacaoPrecoController extends Controller
{
    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\ImportacaoPreco") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\ImportacaoPreco');

        $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\ImportacaoPreco');
        $campos_salvos = $UserCamposSalvoControllerObj->returnCamposSalvos();

        return view('programs.atualizacao_preco.index')->with('campos_salvos', $campos_salvos);
    }

    public function filtro(Request $request){

        $fields = $request->only('grupo', 'subgrupo', 'grupo_exato', 'subgrupo_exato', 'descricao', 'codigo_produto', 'marca', 'linha', 'preco_dolar', 'preco_real', 'margem_abaixo', 'margem_acima', 'status');

        if(
            (!isset($fields['codigo_produto']) ||
            is_null($fields['codigo_produto'])) && (
            !isset($fields['descricao']) || 
            is_null($fields['descricao'])
            )
         ){
            $precosQuery = ProdutoEspecificacao::with('produtoGrupo')
            ->select('produto_grupos_id', 'marca', 'subgrupo', 'linha', DB::Raw('max(preco_real) as preco_real, max(preco_dolar) as preco_dolar, max(precos.codigo_produto) as codigo_produto, max(compra_real) as compra_real, max(compra_dolar) as compra_dolar, COUNT(*) as total_itens'))
            ->leftJoin('precos', 'produto_especificacaos.codigo_produto', '=', 'precos.codigo_produto');
         }else{
            $precosQuery = ProdutoEspecificacao::with('produtoGrupo')
            ->select('produto_grupos_id', 'marca', 'subgrupo', 'linha', 'produto_especificacaos.codigo_produto', 'descricao', 'preco_real', 'preco_dolar', 'compra_real', 'compra_dolar', DB::Raw('1 as total_itens'))
            ->leftJoin('precos', 'produto_especificacaos.codigo_produto', '=', 'precos.codigo_produto');
        }

        $precosQuery->where('ativo', '=', $fields['status']);    

        if (isset($fields['grupo']) && !is_null($fields['grupo'])){

            if(isset($fields['grupo_exato']) && $fields['grupo_exato'] == 'true' ){
                $precosQuery->whereHas('produtoGrupo', function ($query) use($fields){
                    $query->where('descricao', $fields['grupo_exato']);
                    
                });
        }else{
                $precosQuery->whereHas('produtoGrupo', function ($query) use($fields){
                    $query->where('descricao', 'ilike', '%' . $fields['grupo'] . '%');
                });
            }
        }

        if (isset($fields['subgrupo']) && !is_null($fields['subgrupo'])){

            if(isset($fields['subgrupo_exato']) && $fields['subgrupo_exato'] == 'true' ){
                $precosQuery->where('subgrupo', $fields['subgrupo']);
            }

            $precosQuery->where('subgrupo', 'ilike', '%' . $fields['subgrupo'] . '%');
        }

        if (isset($fields['descricao']) && !is_null($fields['descricao'])){
            $precosQuery->where('descricao', $fields['descricao']);
        }

        if (isset($fields['marca']) && !is_null($fields['marca'])){
            $precosQuery->where('marca', 'ilike', '%' . $fields['marca'] . '%');
        }

        if (isset($fields['linha']) && !is_null($fields['linha'])){
            $precosQuery->where('linha', 'ilike', '%' . $fields['linha'] . '%');
        }

        if (isset($fields['codigo_produto']) && !is_null($fields['codigo_produto'])){
            $precosQuery->whereRaw("lower(produto_especificacaos.codigo_produto) = '" . strtolower($fields['codigo_produto']) . "'" );
        }

        if (isset($fields['margem_abaixo']) && !is_null($fields['margem_abaixo'])){
            $marge = str_replace(',', '.', str_replace('.', '', $fields['margem_abaixo']));
            $precosQuery->where(function($query) use($marge){
                $query->orWhere(function($query) use($marge){
                    $query->where('compra_real', '>', 0);
                    $query->whereRaw(
                        "( ( (preco_real / compra_real) - 1 ) * 100 ) <= {$marge}"
                    );
                });
                $query->orWhere(function($query) use($marge){
                    $query->where('compra_dolar', '>', 0);
                    $query->whereRaw(
                        "( ( (preco_dolar / compra_dolar) - 1 ) * 100 ) <= {$marge}"
                    );
                });
            });
        }

        if (isset($fields['margem_acima']) && !is_null($fields['margem_acima'])){
            $marge = str_replace(',', '.', str_replace('.', '', $fields['margem_acima']));
            $precosQuery->where(function($query) use($marge){
                $query->orWhere(function($query) use($marge){
                    $query->where('compra_real', '>', 0);
                    $query->whereRaw(
                        "( ( (preco_real / compra_real) - 1 ) * 100 ) >= {$marge}"
                    );
                });
                $query->orWhere(function($query) use($marge){
                    $query->where('compra_dolar', '>', 0);
                    $query->whereRaw(
                        "( ( (preco_dolar / compra_dolar) - 1 ) * 100 ) >= {$marge}"
                    );
                });
            });
        }
        $precosQuery->whereNull('precos.deleted_at');

        if ((!isset($fields['codigo_produto']) || is_null($fields['codigo_produto'])) &&
            (!isset($fields['descricao']) || is_null($fields['descricao'])) ) {
            $precosQuery->groupBy('produto_grupos_id', 'marca', 'subgrupo', 'linha', 'preco_real', 'preco_dolar', 'compra_real');
        }

        $precosObj = $precosQuery
        ->get();

        $return = ['linhas' => [], 'mostrar_codigo' => false];

        foreach ($precosObj as $preco){

            $linha = [
                'grupo' => ($preco->produtoGrupo->descricao),
                'subgrupo' => ($preco->subgrupo),
                'marca' => ($preco->marca),
                'linha' => ($preco->linha),
                'ativo' => $fields['status'],
            ];            

            if ((isset($fields['codigo_produto']) && !is_null($fields['codigo_produto'])) ||
                (isset($fields['descricao']) && !is_null($fields['descricao']))
            ){
                $linha['codigo_produto'] = $preco->codigo_produto;
                $linha['descricao'] = $preco->descricao;
            }

            $linha_com_precos = array_merge($linha, [
                'preco_real' => $preco->preco_real,
                'preco_dolar' => $preco->preco_dolar,
                'custo_gerencial' => $preco->compra_real,
            ]);


            $hash = Crypt::encrypt($linha);
            $hash_precos = Crypt::encrypt($linha_com_precos);

            $return['linhas'][] = array_merge(
                $linha,
                [
                    'total_itens' => $preco->total_itens??'',
                    'preco_dolar' => $preco->preco_dolar > 0 ? parserValor($preco->preco_dolar) : '',
                    'preco_real' => $preco->preco_real > 0 ? parserValor($preco->preco_real) : '',
                    'compra_real' => $preco->compra_real > 0 ? parserValor($preco->compra_real) : '',
                    'compra_dolar' => $preco->compra_dolar > 0 ? parserValor($preco->compra_dolar) : '',
                    'margem_real' => $preco->compra_real > 0 ? (parserValor((($preco->preco_real / $preco->compra_real)-1)*100) . '%'):'',
                    'margem_dolar' => $preco->compra_dolar > 0 ? (parserValor((($preco->preco_dolar / $preco->compra_dolar)-1)*100) . '%'):'',
                    'hash' => $hash_precos,
                    'hash_precos' => $hash_precos
                ]
            );            
        }

        if (((isset($fields['codigo_produto']) && !is_null($fields['codigo_produto'])) || 
            (isset($fields['descricao']) && !is_null($fields['descricao']))) && count($return['linhas']) > 0){
            $return['mostrar_codigo'] = true;
        }

        return response()->json($return, 200);
    }

    public function modal(Request $request){

        $fields = $request->only('hash');

        $ficha_tecnica_criacao = '';
        $ficha_tecnica_update = '';

        $linha = Crypt::decrypt($fields['hash']);

        $ProdutoEspecificacao = new ProdutoEspecificacao;

        $produtosQuery = ProdutoEspecificacao::with('estoque', 'custos', 'ficha_tecnica', 'ficha_tecnica.tecidosTodos','ficha_tecnica.insumosTodos','ficha_tecnica.servicosTodos');
        if(!empty($linha['ativo'])){
            $produtosQuery->where('ativo', '=', $linha['ativo']);
        }
		if(isset($linha['estoque'])){
			$produtosQuery->has('estoque');
		}

        if(isset($linha['compra_real']) || isset($linha['custo'])){

            $compra_real = $linha['compra_real'];
            $custo = $linha['custo'];
    
            unset($linha['compra_real'], $linha['custo'], $linha['estoque']);

            $produtosQuery->whereHas('produtoGrupo', function($query) use($ProdutoEspecificacao, $linha) {
                $query->where('descricao', $linha['grupo']);
            });
            unset($linha['grupo']);
    
            $produtosQuery->where($linha);

            if(empty($compra_real)){
                $produtosQuery->where(function ($q){
                    $q->whereHas('preco', function($query){
                        $query->where('compra_real', 0)
                        ->orWhereNull('compra_real');
                    })
                    ->orWhereDoesntHave('preco');
                });
            }
            else{
                $produtosQuery->whereHas('preco', function($query) use($compra_real){
                    $query->where('compra_real', $compra_real);
                });
            }

            if(empty($custo)){
                $produtosQuery->where(function($q){
                    $q->whereHas('estoque', function($query){
                        $query->where('custo', 0)
                        ->orWhereNull('custo');
                    })
                    ->orWhereDoesntHave('estoque');                
                });
            }
            else{
                $produtosQuery->whereHas('estoque', function($query) use ($custo){
                    $query->where('custo', $custo);
                });
            }
        }
        else{
            
            $preco_real = $linha['preco_real'];
            $preco_dolar = $linha['preco_dolar'];
            $custo_gerencial = $linha['custo_gerencial'];
            
            unset($linha['preco_real'], $linha['preco_dolar'], $linha['ativo'], $linha['custo_gerencial'], $linha['estoque']);
            
            $produtosQuery->whereHas('produtoGrupo', function($query) use($linha) {
                $query->where('descricao', $linha['grupo']);
            });
            unset($linha['grupo']);
            
            $produtosQuery->where($linha);

            if(!empty((float) $preco_real) || !empty((float) $preco_dolar) || !empty((float) $custo_gerencial)){
                $produtosQuery->whereHas('preco', function($query) use($preco_real, $preco_dolar, $custo_gerencial){
                   $query->where('preco_real', $preco_real)
                   ->where('preco_dolar', $preco_dolar)
                   ->where('compra_real', $custo_gerencial);
                });
            }
            else{
                $produtosQuery->where(function($query){
                    $query->whereDoesntHave('preco')
                    ->orWhereHas('preco', function($q){
                        $q->where(function($real){
                            $real->where('preco_real', 0)
                            ->orWhereNull('preco_real');
                        })
                        ->where(function($dolar){
                            $dolar->where('preco_dolar', 0)
                            ->orWhereNull('preco_dolar');
                        })
                        ->where(function($compra){
                            $compra->where('compra_real', 0)
                            ->orWhereNull('compra_real');
                        });
                    });
                });
            }
        }

        $produtosObj = $produtosQuery->get();

        $produtoObj = $produtosObj->sortBy('preco.preco_real')->first();

        $empresas = returnEmpresasNasajonView();

        $movimentacoes = [];
        if ($produtosObj->isNotEmpty()){
            $movimentacoes = $produtosObj->pluck('codigo_produto');
            $empresas = returnEmpresasNasajonView();
            $estoque_total = 0;
            $produtosObj->each(function ($produto) use (&$estoque_total){
                $produto->estoque->each(function ($estoque) use (&$estoque_total){
                    $estoque_total += $estoque->estoque;
                });
            });
            
            $compra_real = $produtosObj->pluck('preco')->max('compra_real')??0;
            $compra_dolar = $produtosObj->pluck('preco')->max('compra_dolar')??0;

            $custo_dolar = $compra_dolar > 0 ? $compra_dolar : 0;
            if(!empty($custo_dolar)){
                $margem_dolar = parserValor(round(((($produtoObj->preco_dolar - $custo_dolar) * 100) / $custo_dolar)*100)/100);
            } else {
                $margem_dolar = '';
            }

            $custo_real =  $compra_real > 0 ? $compra_real : 0;
            if(!empty($custo_real)){
                $margem_real = parserValor(round(((($produtoObj->preco_real - $custo_real) * 100) / $custo_real)*100)/100);
            } else {
                $margem_real = '';
            }

            $ultima_compra_real = is_null($produtosObj->pluck('preco')->max('ultima_compra_real')) ? '' : parserData($produtosObj->pluck('preco')->max('ultima_compra_real'));
            $ultima_compra_dolar = is_null($produtosObj->pluck('preco')->max('ultima_compra_dolar')) ? '' : parserData($produtosObj->pluck('preco')->max('ultima_compra_dolar'));

            foreach($linha as $key => $value){

                if($key == 'codigo_produto'){
                    $pesquisa['produto_especificacaos.' . $key] = $value;
                }
                else{
                    $pesquisa[$key] = $value;
                }

            }
        
            $custo_medio_dolar = Preco::select(DB::Raw('SUM(estoque * compra_dolar) as estoque_vezes_dolar, SUM(estoque) as custo_medio_dolar'))
            ->join('produtos_estoques', 'produtos_estoques.codigo_produto', '=', 'precos.codigo_produto')
            ->join('produto_especificacaos', 'produtos_estoques.codigo_produto', '=', 'produto_especificacaos.codigo_produto')
            ->where($pesquisa)
            ->groupBy(array_keys($pesquisa))
            ->first();

            $preco_real = $produtoObj->preco->preco_real??null;
            $preco_dolar = $produtoObj->preco->preco_dolar??null;

            $agora = Carbon::Now();
            $seisMeses = Carbon::Now();
            $seisMeses->day = 1;
            $seisMeses->addMonths(-5);

            $notas = NotasVenda::query()
            ->whereRaw("cast( concat(ano_data_entrada,'-', lpad(mes_data_entrada,2,'0'),'-01') as date ) >= '{$seisMeses->format('Y-m-d')}'")
            ->whereIn('codigo_produto', $produtosObj->pluck('codigo_produto'))
            ->get();

            $venda_ultimos_meses = $notas->sum('quantidade');

            $venda_media = ($venda_ultimos_meses / 6);
            $estoque_meses = $venda_media > 0 ? parserValor($estoque_total / $venda_media). ' meses' : '-';

            $produto = [
                'marca' => $produtoObj->marca,            
                'linha' => $produtoObj->linha,
                'grupo' => $produtoObj->produtoGrupo->descricao,
                'subgrupo' => $produtoObj->subgrupo,
                'compra_real' => parserValor($compra_real),
                'preco_real' => parserValor($preco_real),
                'preco_dolar' => parserValor($preco_dolar),
                'hash' => $fields['hash'],
                'venda_ultimos_meses' => parserValor($venda_ultimos_meses),
                'venda_media' => parserValor($venda_media),
                'estoque_meses' => $estoque_meses,
                'produtos' => $produtosObj->count(),
                'estoque' => parserValor($estoque_total),
                'unidade' => $produtoObj->unidade,
                'ultimas_notas' => '',
                'custo_portal' => [
                    'custo_contabil' => '',
                    'popover_custo_contabil' => '',
                    'custo_gerencial' => '',
                    'popover_custo_gerencial' => '',
                ]
            ];
    

            $custo_contabil = 0;
            $custo_gerencial = 0;
            
            $contador_produto = 0;
            $contador_produto_custo = 0;

            $popover_custo_contabil = '';
            $popover_custo_gerencial = '';

            $custo_contabil_estabelecimento = [];
            $custo_gerencial_estabelecimento = [];

            $custo_contabil_produtos = 0;
            $custo_gerencial_produtos = 0;

			$estoque_total_estabelecimento = [];
			$estoque_total = 0;

            $produtosObj->each(function ($produto) use (&$custo_contabil_estabelecimento, &$custo_gerencial_estabelecimento, &$custo_contabil_produtos, &$custo_gerencial_produtos, &$estoque_total, &$estoque_total_estabelecimento){
				$estoque = [];
				$produto->estoque->each(function($est) use (&$estoque, &$estoque_total, &$estoque_total_estabelecimento){
					if(!isset($estoque[$est->estabelecimento])){
						$estoque[$est->estabelecimento] = 0;
					}
					$estoque[$est->estabelecimento] += $est->estoque;

					if(!isset($estoque_total_estabelecimento[$est->estabelecimento])){
						$estoque_total_estabelecimento[$est->estabelecimento] = 0;
					}
					$estoque_total_estabelecimento[$est->estabelecimento] += $est->estoque;
					$estoque_total += $est->estoque;
				});
                $produto->custos->each(function ($custo) use (&$custo_contabil_estabelecimento, &$custo_gerencial_estabelecimento, &$custo_contabil_produtos, &$custo_gerencial_produtos, $estoque){
					$estoque_estabelecimento = 0;
					if(isset($estoque[$custo->estabelecimento])){
						$estoque_estabelecimento = $estoque[$custo->estabelecimento];
					}
					if($estoque_estabelecimento > 0){
						$custo_contabil_produtos += $custo->custo_medio_contabil * $estoque_estabelecimento;
						$custo_gerencial_produtos += $custo->custo_medio_gerencial * $estoque_estabelecimento;
						if (!isset($custo_contabil_estabelecimento[$custo->estabelecimento])) {
							$custo_contabil_estabelecimento[$custo->estabelecimento] = 0;
						}
						if (!isset($custo_gerencial_estabelecimento[$custo->estabelecimento])) {
							$custo_gerencial_estabelecimento[$custo->estabelecimento] = 0;
						}
						$custo_contabil_estabelecimento[$custo->estabelecimento] += $custo->custo_medio_contabil * $estoque_estabelecimento;
						$custo_gerencial_estabelecimento[$custo->estabelecimento] += $custo->custo_medio_gerencial * $estoque_estabelecimento;
					}
                });
            });
            foreach($custo_contabil_estabelecimento as $estabelecimento => $custo){
				$custo = $custo;
				if(isset($estoque_total_estabelecimento[$estabelecimento]) && $estoque_total_estabelecimento[$estabelecimento] > 0){
					$custo = $custo / $estoque_total_estabelecimento[$estabelecimento];
					$popover_custo_contabil .= '<p style="float: left;width: 100%;padding: 0;margin: 0;font-size: 14px;line-height: 18px;">'.$empresas[intval($estabelecimento)].' - ' .parserValor($custo).'</p>';
				}
			}
            foreach($custo_gerencial_estabelecimento as $estabelecimento => $custo){
				$custo = $custo;
				if(isset($estoque_total_estabelecimento[$estabelecimento]) && $estoque_total_estabelecimento[$estabelecimento] > 0){
					$custo = $custo / $estoque_total_estabelecimento[$estabelecimento];
                	$popover_custo_gerencial .= '<p style="float: left;width: 100%;padding: 0;margin: 0;font-size: 14px;line-height: 18px;">'.$empresas[intval($estabelecimento)].' - ' .parserValor($custo).'</p>';
				}
            }
			if($estoque_total > 0){
				$custo_contabil_produtos = $custo_contabil_produtos / $estoque_total;
				$custo_gerencial_produtos = $custo_gerencial_produtos / $estoque_total;
			}else{
				$custo_contabil_produtos = 0;
				$custo_gerencial_produtos = 0;
			}
			$produto['custo_portal'] = [
				'custo_contabil' => parserValor($custo_contabil_produtos),
				'custo_gerencial' => parserValor($custo_gerencial_produtos),
				'popover_custo_contabil' => $popover_custo_contabil,
				'popover_custo_gerencial' => $popover_custo_gerencial,
			];

            if(isset($linha['CODPRD']) && !is_null($linha['CODPRD'])){
                $produto['codigo_produto'] = $produtoObj->codigo_produto;
                $produto['descricao'] = $produtoObj->descricao;
            }
            
            $custo_total = 0;
            $estoque_total = 0;

            $estoqueObj = ProdutosEstoque::where('codigo_produto', $produtoObj->codigo_produto)->get();

            $estoqueObj->each(function ($linha) use (&$custo_total, &$estoque_total){
                $custo_total += $linha->estoque * $linha->custo;
                $estoque_total += $linha->estoque;
            });

            if($estoque_total > 0){
                $custo_medio = $custo_total / $estoque_total;
            }
            else{
                $custo_medio = 0;
            }

            if(!empty($custo_medio_dolar->estoque_vezes_dolar)){
                $custo_medio_dolar  = $custo_medio_dolar->custo_medio_dolar / $custo_medio_dolar->estoque_vezes_dolar;
            }else{
                $custo_medio_dolar = 0;
            }

            $ficha_tecnica_criacao = is_null($produtosObj->pluck('ficha_tecnica')->min('created_at')) ? '' : parserData($produtosObj->pluck('ficha_tecnica')->min('created_at'));
            if(!empty($ficha_tecnica_criacao)){
                foreach($produtosObj as $produto_ficha){
                    if(empty($ficha_tecnica_tecido_update)){
                        $ficha_tecnica_tecido_update = is_null($produto_ficha->ficha_tecnica->tecidosTodos->max('updated_at')) ? '' : $produto_ficha->ficha_tecnica->tecidosTodos->max('updated_at');
                    }else{
                        if(isset($produto_ficha->ficha_tecnica->tecidosTodos) && count($produto_ficha->ficha_tecnica->tecidosTodos) > 0){
                            if($ficha_tecnica_tecido_update->lt($produto_ficha->ficha_tecnica->tecidosTodos->max('updated_at'))){
                                $ficha_tecnica_tecido_update = $produto_ficha->ficha_tecnica->tecidosTodos->max('updated_at');
                            }
                        }
                    }
                    if(empty($ficha_tecnica_insumo_update)){
                        $ficha_tecnica_insumo_update = isset($produto_ficha->ficha_tecnica->insumosTodos) &&  count($produto_ficha->ficha_tecnica->tecidosTodos) > 0 ? $produto_ficha->ficha_tecnica->insumosTodos->max('updated_at') : '';
                    }else{
                        if(isset($produto_ficha->ficha_tecnica->insumosTodos)){
                            if($ficha_tecnica_insumo_update->lt($produto_ficha->ficha_tecnica->insumosTodos->max('updated_at'))){
                                $ficha_tecnica_insumo_update = $produto_ficha->ficha_tecnica->insumosTodos->max('updated_at');
                            }
                        }
                    }
                    if(empty($ficha_tecnica_servico_update)){
                        $ficha_tecnica_servico_update = !isset($produto_ficha->ficha_tecnica->servicosTodos) ? '' : $produto_ficha->ficha_tecnica->servicosTodos->max('updated_at');
                    }else{
                        if(isset($produto_ficha->ficha_tecnica->servicosTodos)){
                            if($ficha_tecnica_servico_update->lt($produto_ficha->ficha_tecnica->servicosTodos->max('updated_at'))){
                                $ficha_tecnica_servico_update = $produto_ficha->ficha_tecnica->servicosTodos->max('updated_at');
                            }
                        }
                    }
                }

                if(!empty($ficha_tecnica_insumo_update)){
                    if($ficha_tecnica_tecido_update->gt($ficha_tecnica_insumo_update)){
                        if($ficha_tecnica_tecido_update->gt($ficha_tecnica_servico_update)){
                            $ficha_tecnica_update = parserData($ficha_tecnica_tecido_update);
                        }else{
                            $ficha_tecnica_update = parserData($ficha_tecnica_servico_update);
                        }
                    }else if($ficha_tecnica_insumo_update->gt($ficha_tecnica_servico_update)){
                        $ficha_tecnica_update = parserData($ficha_tecnica_insumo_update);
                    }else{
                        $ficha_tecnica_update = parserData($ficha_tecnica_servico_update);
                    }
                }else{
                    $ficha_tecnica_update = parserData($ficha_tecnica_servico_update);
                }
            }

            $produto['ficha_tecnica_criacao'] = $ficha_tecnica_criacao;
            $produto['ficha_tecnica_update'] = $ficha_tecnica_update;

            $margens = [
                'custo_real' => !empty($custo_real) ? parserValor($custo_real) : '',
                'custo_dolar' => !empty($custo_dolar) ? parserValor($custo_dolar) : '',
                'margem_dolar' => $margem_dolar . '%',
                'ultima_compra_real' => $ultima_compra_real,
                'ultima_compra_dolar' => $ultima_compra_dolar,
                'custo_medio' => parserValor($custo_medio),
                'entrega' => !empty($produtoObj->previsao_entrega) ? parserData($produtoObj->previsao_entrega) : '',
                'proforma' => $produtoObj->numero_proforma,
                'valor_compra' => $produtoObj->valor_compra,
                'custo_medio_dolar' => parserValor($custo_medio_dolar),
                'preco_venda_x_custo_medio' => intval($custo_medio) > 0 ? parserValor($preco_real / $custo_medio) : '',
                'preco_venda_x_preco_compra' => intval($custo_real) > 0 ? parserValor($preco_real / $custo_real) : '',
                'preco_venda_x_preco_proforma_fob' => isset($custo_medio_dolar->custo_medio_dolar) && intval($custo_medio_dolar->custo_medio_dolar) > 0 ? parserValor($preco_dolar / $custo_medio_dolar->custo_medio_dolar) : '',
            ];

        }
        else{
            unset($linha['preco_real'], $linha['preco_dolar'], $linha['compra_real'], $linha['compra_dolar']);

            $produtoEspecificacoes = ProdutoEspecificacao::with('custos')->where($linha)->first();

            $estoque_total = ProdutosEstoque::select(DB::Raw('sum("estoque") as estoque, max(unidade) as unidade'))->where('codigo_produto', $produtoEspecificacoes->codigo_produto)->first();

            $agora = Carbon::Now();
            $seisMeses = Carbon::Now();
            $seisMeses->day = 1;
            $seisMeses->addMonths(-5);

            $notas = NotasVenda::query()
            ->whereRaw("cast( concat(ano_data_entrada,'-', lpad(mes_data_entrada,2,'0'),'-01') as date ) >= '{$seisMeses->format('Y-m-d')}'")
            ->where('codigo_produto', $produtoEspecificacoes->codigo_produto)
            ->get();

            $venda_ultimos_meses = $notas->sum('quantidade');
            
            $venda_media = ($venda_ultimos_meses / 6);
            $estoque_meses = $venda_media > 0 ? parserValor($estoque_total->estoque / $venda_media). ' meses' : '-';

            $produto = [
                'marca' => $produtoEspecificacoes->marca,            
                'linha' => $produtoEspecificacoes->linha,
                'grupo' => $produtoEspecificacoes->produtoGrupo->descricao,
                'subgrupo' => $produtoEspecificacoes->subgrupo,
                'preco_real' => '',
                'preco_dolar' => '',
                'compra_real' => '',
                'hash' => $fields['hash'],
                'venda_ultimos_meses' => $venda_ultimos_meses,
                'venda_media' => $venda_media,
                'estoque_meses' => $estoque_meses,
                'produtos' => $produtosObj->count(),
                'estoque' => parserValor($estoque_total->estoque),
                'unidade' => $estoque_total->unidade,
                'ultimas_notas' => '',
                'custo_portal' => [
                    'custo_contabil' => '',
                    'popover_custo_contabil' => '',
                    'custo_gerencial' => '',
                    'popover_custo_gerencial' => '',
                ]
            ];

            $margens = [
                'custo_real' => '',
                'custo_dolar' => '',
                'margem_dolar' => '',
                'ultima_compra_real' => '',
                'ultima_compra_dolar' => '',
                'custo_medio' => '',
                'entrega' => '',
                'proforma' => '',
                'valor_compra' => '',
                'custo_medio_dolar' => '',
                'preco_venda_x_custo_medio' => '',
                'preco_venda_x_preco_compra' => '',
                'preco_venda_x_preco_proforma_fob' => '',
    
            ];

        }

        return view('programs.atualizacao_preco.modal')->with(['produto' => $produto, 'margens' => $margens, 'movimentacoes' => $movimentacoes]);
    }

    public function modalProdutos(Request $request){

        $fields = $request->only('hash');

        $linha = Crypt::decrypt($fields['hash']);

        $produtosQuery = ProdutoEspecificacao::with('estoque', 'custos', 'preco');
        if(!empty($linha['ativo'])){
            $produtosQuery->where('ativo', '=', $linha['ativo']);
        }else{
            $produtosQuery->where('ativo', '=', 'true');
        }
        if(!empty($linha['estoque'])){
			$produtosQuery->has('estoque');
            unset($linha['estoque']);
		}

        if(isset($linha['compra_real']) || isset($linha['custo'])){

            $compra_real = $linha['compra_real'];
            $custo = $linha['custo'];
    
            unset($linha['compra_real'], $linha['custo']);
    
            $produtosQuery->where($linha);

            if(empty($compra_real)){
                $produtosQuery->where(function ($q){
                    $q->whereHas('preco', function($query){
                        $query->where('compra_real', 0)
                        ->orWhereNull('compra_real');
                    })
                    ->orWhereDoesntHave('preco');
                });
            }
            else{
                $produtosQuery->whereHas('preco', function($query) use($compra_real){
                    $query->where('compra_real', $compra_real);
                });
            }

            if(empty($custo)){
                $produtosQuery->where(function($q){
                    $q->whereHas('estoque', function($query){
                        $query->where('custo', 0)
                        ->orWhereNull('custo');
                    })
                    ->orWhereDoesntHave('estoque');                
                });
            }
            else{
                $produtosQuery->whereHas('estoque', function($query) use ($custo){
                    $query->where('custo', $custo);
                });
            }
        }
        else{

            $preco_real = $linha['preco_real'];
            $preco_dolar = $linha['preco_dolar'];
            $compra_real = $linha['custo_gerencial'];
            
            unset($linha['preco_real'], 
            $linha['preco_dolar'],
            $linha['ativo'],
            $linha['compra_real'],
            $linha['compra_dolar'],
            $linha['custo_gerencial']); 

            $produtosQuery->where($linha);
            
            if(!empty((float) $preco_real) || !empty((float) $preco_dolar) || !empty((float) $compra_real)){
                $produtosQuery->whereHas('preco', function($query) use($preco_real, $preco_dolar, $compra_real){
                    $query
                        ->where('preco_real', $preco_real)
                        ->where('preco_dolar', $preco_dolar)
                        ->where('compra_real', $compra_real);
                });
            }
            else{
                $produtosQuery->where(function($query){
                    $query->whereDoesntHave('preco')
                        ->orWhereHas('preco', function($q){
                            $q->where(function($real){
                                $real->where('preco_real', 0)
                                ->orWhereNull('preco_real');
                            })
                            ->where(function($dolar){
                                $dolar->where('preco_dolar', 0)
                                ->orWhereNull('preco_dolar');
                            })
                            ->where(function($compra){
                                $compra->where('compra_real', 0)
                                    ->orWhereNull('compra_real');
                            });
                        });
                });
            }
        }
        $produtos = $produtosQuery->get();
        
        $retorno = [];

        $empresas = returnEmpresasNasajonView();
		$total = [
			'custo_contabil' => 0,
			'custo_medio_contabil' => 0,
			'custo_medio_gerencial' => 0,
			'custo_gerencial' => 0,
			'estoque' => 0
		];
        foreach($produtos as $produto){
            $estoques = [];
            foreach($produto->estoque as $estoque){
				$custo_gerencial = 0;
				if(!empty($produto->preco)){
					$custo_gerencial = $produto->preco->compra_real;
				}
                $estoques[intval($estoque->estabelecimento)] = [
                    'estabelecimento' => $empresas[intval($estoque->estabelecimento)],
                    'estoque' => $estoque->estoque,
                    'custo_contabil' => $estoque->custo,
                    'custo_contabil_total' => 0,
                    'custo_medio_contabil' => 0,
                    'custo_medio_contabil_total' => 0,
                    'custo_medio_gerencial' => 0,
                    'custo_medio_gerencial_total' => 0,
                    'custo_gerencial' => $custo_gerencial,
                    'custo_gerencial_total' => 0
                ];

            }

            foreach($produto->custos as $custo){
                if(isset($estoques[intval($custo->estabelecimento)])){
                    $estoques[intval($custo->estabelecimento)]['custo_medio_contabil'] = $custo->custo_medio_contabil;
                    $estoques[intval($custo->estabelecimento)]['custo_medio_gerencial'] = $custo->custo_medio_gerencial;
                }
            }
            if(empty($estoques)){
                $estoques[] = [
                    'estabelecimento' => 0,
                    'estoque' => 0,
                    'custo_contabil' => 0,
                    'custo_contabil_total' => 0,
                    'custo_medio_contabil' => 0,
                    'custo_medio_contabil_total' => 0,
                    'custo_medio_gerencial' => 0,
                    'custo_medio_gerencial_total' => 0,
                    'custo_gerencial' => 0,
                    'custo_gerencial_total' => 0
                ];
            }
			foreach($estoques as $estabelecimento => $valores){
				foreach($valores as $key => $valor){
					if(substr($key, -6) == '_total'){
						$key2 = str_replace('_total', '', $key);
						$valor = $estoques[$estabelecimento][$key2] * $estoques[$estabelecimento]['estoque'];
						$total[$key2] += $valor;
						$estoques[$estabelecimento][$key] = $valor;
					}elseif($key == 'estoque'){
						$total[$key] += $valor;
					}
				}
			}
			foreach($estoques as $estabelecimento => $valores){
				foreach($valores as $key => $valor){
					if($key != 'estabelecimento'){
						$estoques[$estabelecimento][$key] = parserValor($valor);
					}
				}
			}

            $retorno[] = [
                'codigo_produto' => $produto->codigo_produto,
                'descricao' => $produto->descricao,
                'estoque' => $estoques,
            ];

            unset($estoque);
        }
		foreach($total as $key => $valor){
			if($valor > 0){
				$total[$key] = parserValor($valor);
			}else{
				$total[$key] = '';
			}
		}


        return view('programs.atualizacao_preco.modal_produtos')->with(['produtos' => $retorno, 'total' => $total ]);

    }

    public function modalUltimasCompras(Request $request){
        $fields = $request->only('hash');

        $linha = Crypt::decrypt($fields['hash']);

        $produtosQuery = ProdutoEspecificacao::with('estoque');
        if(!empty($linha['ativo'])){
            $produtosQuery->where('ativo', '=', $linha['ativo']);
        }else{
            $produtosQuery->where('ativo', '=', 'true');
        }
        if(!empty($linha['estoque'])){
			$produtosQuery->has('estoque');
            unset($linha['estoque']);
		}

        if(isset($linha['compra_real']) || isset($linha['custo'])){

            $compra_real = $linha['compra_real'];
            $custo = $linha['custo'];
    
            unset($linha['compra_real'], $linha['custo']);
            $produtosQuery->where($linha);

            if(empty($compra_real)){
                $produtosQuery->where(function ($q){
                    $q->whereHas('preco', function($query){
                        $query->where('compra_real', 0)
                        ->orWhereNull('compra_real');
                    })
                    ->orWhereDoesntHave('preco');
                });
            }
            else{
                $produtosQuery->whereHas('preco', function($query) use($compra_real){
                    $query->where('compra_real', $compra_real);
                });
            }

            if(empty($custo)){
                $produtosQuery->where(function($q){
                    $q->whereHas('estoque', function($query){
                        $query->where('custo', 0)
                        ->orWhereNull('custo');
                    })
                    ->orWhereDoesntHave('estoque');                
                });
            }
            else{
                $produtosQuery->whereHas('estoque', function($query) use ($custo){
                    $query->where('custo', $custo);
                });
            }
        }
        else{

            $preco_real = $linha['preco_real'];
            $preco_dolar = $linha['preco_dolar'];
            $custo_gerencial = $linha['custo_gerencial'];
            
            unset($linha['preco_real'], 
            $linha['preco_dolar'], 
            $linha['compra_real'],
            $linha['compra_dolar'],
            $linha['custo_gerencial']); 

            $produtosQuery->where($linha);

            if(!empty((float) $preco_real) || !empty((float) $preco_dolar) || !empty((float) $custo_gerencial)){
                $produtosQuery->whereHas('preco', function($query) use($preco_real, $preco_dolar, $custo_gerencial){
                $query->where('preco_real', $preco_real)
                    ->where('preco_dolar', $preco_dolar)
                    ->where('compra_real', $custo_gerencial);
                });
            }
            else{
                $produtosQuery->where(function($query){
                    $query->whereDoesntHave('preco')
                    ->orWhereHas('preco', function($q){
                        $q->where(function($real){
                            $real->where('preco_real', 0)
                            ->orWhereNull('preco_real');
                        })
                        ->where(function($dolar){
                            $dolar->where('preco_dolar', 0)
                            ->orWhereNull('preco_dolar');
                        })
                        ->where(function($compra){
                            $compra->where('compra_real', 0)
                            ->orWhereNull('compra_real');
                        });
                    });
                });
            }
        }

        $produtosObj = $produtosQuery->get();

        $compras = ValorCustoNotaProduto::select(
            'valor_custo_notas_id',
            'custo',
            'custo_gerencial',
            'valor_custo_nota_produtos.proforma',
            'valor_dolar',
            'quantidade',
            'fornecedor_codigo',
            'valor_custo_nota_produtos.updated_at',
            'valor_custo_nota_produtos.created_by',
            'valor_custo_nota_produtos.updated_by',
            'valor_custo_nota_produtos.created_at',
            'data_compra'
        )->with('nota.fornecedor', 'nota', 'createdby', 'updatedby')
        ->whereIn('codigo_produto', $produtosObj->pluck('codigo_produto'))
        ->join('valor_custo_notas', 'valor_custo_notas.id', 'valor_custo_notas_id')
        ->groupBy(
            'valor_custo_notas_id',
            'custo',
            'custo_gerencial',
            'valor_custo_nota_produtos.proforma',
            'valor_dolar',
            'quantidade',
            'fornecedor_codigo',
            'valor_custo_nota_produtos.updated_at',
            'valor_custo_nota_produtos.created_by',
            'valor_custo_nota_produtos.updated_by',
            'valor_custo_nota_produtos.created_at',
            'data_compra')
        ->orderBy('data_compra', 'desc')
        ->take(10)
        ->get();
        
        $retorno = [];

        foreach($compras as $compra){
            if(!is_null($compra->nota)){
                $compra_linha['pcmn'] = $compra->nota->numero_pedido;
                if(isset($compra->nota->fornecedor)){
                    $compra_linha['fornecedor'] = $compra->nota->fornecedor->nome;
                }
                else{
                    $compra_linha['fornecedor'] = '';
                }
            }
            else{
                $compra_linha['pcmn'] = '';
                $compra_linha['fornecedor'] = '';
            }

            $compra_linha['proforma'] = $compra->proforma;
            $compra_linha['qtde'] = parserQtd($compra->quantidade);
            $compra_linha['dolar'] = (!empty($compra->valor_dolar)) ? parserValor($compra->valor_dolar) : '';
            $compra_linha['real'] = parserValor($compra->custo);
            $compra_linha['usuario'] = (!is_null($compra->updatedby))?$compra->updatedby->name: $compra->createdby->name;
            $compra_linha['ultima_modificação'] = parserData($compra->data_compra);
            $compra_linha['id_pedido'] = '';
            $compra_linha['custo_gerencial'] = (!empty($compra->custo_gerencial)) ? parserValor($compra->custo_gerencial) : parserValor($compra->custo);

            $retorno[] = $compra_linha;
        }
        $PrecosLogObj = PrecosLog::with('criadoPor')
            ->whereIn('codigo_produto', $produtosObj->pluck('codigo_produto'))
            ->whereColumn('compra_real_antigo', '!=', 'compra_real_novo')
            ->selectRaw('max(created_at) as created_at, created_by, compra_real_novo')
            ->groupBy('created_by', 'compra_real_novo')
            ->get();

        foreach($PrecosLogObj as $log){
            $retorno[] = [
                'pcmn' => '',
                'fornecedor' => '',
                'proforma' => '',
                'qtde' => '',
                'dolar' => '',
                'real' => '',
                'usuario' => $log->criadoPor->name,
                'ultima_modificação' => parserData($log->created_at),
                'id_pedido' => '',
                'custo_gerencial' => parserValor($log->compra_real_novo)
            ];
        }
        
        return view('programs.atualizacao_preco.modal_compras')->with(['compras' => $retorno]);

    }

    public function atualizaPreco(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('hash', 'preco_real', 'preco_dolar', 'compra_real', 'atualiza_grupo_subgrupo', 'campo', 'origem', 'produto_de');

        if(isset($fields['origem'])){
            if($fields['origem'] === "projeto"){
                $usuario_id = 1;
                $envia_email = false;
            }else if($fields['origem'] === 'atualizacao_margem'){
                $usuario_id = 1;
                $envia_email = false;
            }else if($fields['origem'] === 'de_para'){
                $usuario_id = 1;
                $envia_email = true;
            }
        }else{
            $usuario_id = Auth::id();
            $envia_email = true;
        }

        $linha = Crypt::decrypt($fields['hash']);

        $pesquisa = [];

        $count = 0;

        $precoProdutoQuery = ProdutoEspecificacao::with('preco');
        if(!empty($linha['ativo'])){
            $precoProdutoQuery->where('ativo', '=', $linha['ativo']);
        }else{
            $precoProdutoQuery->where('ativo', '=', 'true');
        }

        $grupo = $linha['grupo'];
        unset($linha['grupo']);

        if($fields['atualiza_grupo_subgrupo'] == 'agrupado') {
            if(isset($linha['compra_real']) || isset($linha['custo'])){

                $compra_real = $linha['compra_real'];
                $custo = $linha['custo'];
        
                unset($linha['compra_real'], $linha['custo']);

                $precoProdutoQuery->where($linha);
                
                if(empty($compra_real)){
                    $precoProdutoQuery->where(function ($q){
                        $q->whereHas('preco', function($query){
                            $query->where('compra_real', 0)
                            ->orWhereNull('compra_real');
                        })
                        ->orWhereDoesntHave('preco');
                    });
                }
                else{
                    $precoProdutoQuery->whereHas('preco', function($query) use($compra_real){
                        $query->where('compra_real', $compra_real);
                    });
                }

                if(empty($custo)){
                    $precoProdutoQuery->where(function($q){
                        $q->whereHas('estoque', function($query){
                            $query->where('custo', 0)
                            ->orWhereNull('custo');
                        })
                        ->orWhereDoesntHave('estoque');                
                    });
                }
                else{
                    $precoProdutoQuery->whereHas('estoque', function($query) use ($custo){
                        $query->where('custo', $custo);
                    });
                }
            }
            else{
                if(!isset($linha["preco_real"]) && !isset($linha["preco_dolar"])){
                    unset($linha['preco_real'], $linha['preco_dolar']);
                }
        
                $preco_real = $linha['preco_real']??null;
                $preco_dolar = $linha['preco_dolar']??null;
                $custo_gerencial = $linha['custo_gerencial'];
                unset(
                    $linha['preco_real'], 
                    $linha['preco_dolar'],
                    $linha['custo_gerencial']
                ); 
        
                $precoProdutoQuery->where($linha);
        
                if(!empty((float) $preco_real) || !empty((float) $preco_dolar) || !empty((float) $custo_gerencial)){
                    $precoProdutoQuery->whereHas('preco', function($query) use($preco_real, $preco_dolar, $custo_gerencial){
                        $query->where('preco_real', $preco_real)
                            ->where('preco_dolar', $preco_dolar)
                            ->where('compra_real', $custo_gerencial);
                    });
                }
                else{
                    $precoProdutoQuery->where(function($query){
                        $query->whereDoesntHave('preco')
                        ->orWhereHas('preco', function($q){
                            $q->where(function($real){
                                $real->where('preco_real', 0)
                                ->orWhereNull('preco_real');
                            })
                            ->where(function($dolar){
                                $dolar->where('preco_dolar', 0)
                                ->orWhereNull('preco_dolar');
                            })
                            ->where(function($compra){
                                $compra->where('compra_real', 0)
                                ->orWhereNull('compra_real');
                            });
                        });
                    });
                }
            }
        }else{
            $linhaProduto = $linha['linha'];
            $subgrupo = $linha['subgrupo'];
            if($fields['atualiza_grupo_subgrupo'] == 'grupo'){
                $precoProdutoQuery->whereHas('produtoGrupo', function($query) use($grupo) {
                    $query->where('descricao',  $grupo);
                });
            }else if($fields['atualiza_grupo_subgrupo'] == 'grupo_subgrupo'){
                $precoProdutoQuery->where('subgrupo', $subgrupo);
                $precoProdutoQuery->whereHas('produtoGrupo', function($query) use($grupo) {
                    $query->where('descricao',  $grupo);
                });
            }elseif($fields['atualiza_grupo_subgrupo'] == 'linha'){
                $precoProdutoQuery->where('linha', $linhaProduto);
                $precoProdutoQuery->where('subgrupo', $subgrupo);
                $precoProdutoQuery->whereHas('produtoGrupo', function($query) use($grupo) {
                    $query->where('descricao',  $grupo);
                });
            }
        }

        $precoProdutoObj = $precoProdutoQuery->get();
        if($fields['campo'] == 'real'){
            $preco_real_novo = parserNumber($fields['preco_real']);
            $preco_dolar_novo = null;
            $compra_real_novo = null;
        } else if($fields['campo'] == 'dolar'){
            $preco_dolar_novo = parserNumber($fields['preco_dolar']);
            $preco_real_novo = null;
            $compra_real_novo = null;
        } else if($fields['campo'] == 'compra'){
            $compra_real_novo = parserNumber($fields['compra_real']);
            $preco_real_novo = null;
            $preco_dolar_novo = null;
        }
        foreach($precoProdutoObj as $preco){

            if (is_null($preco->preco)){

                Preco::create([
                    'codigo_produto' => $preco->codigo_produto,
                    'preco_real' => $preco_real_novo??null,
                    'preco_dolar' => $preco_dolar_novo??null,
                    'created_by' => $usuario_id,
                ]);
                
                $log_preco = [
                    'codigo_produto' => $preco->codigo_produto,
                    'preco_real_antigo' => null,
                    'preco_real_novo' => $preco_real_novo,
                    'preco_dolar_antigo' => null,
                    'preco_dolar_novo' => $preco_dolar_novo,
                    'compra_real_antigo' => null,
                    'compra_real_novo' => $compra_real_novo,
                    'created_by' => $usuario_id
                ];

                PrecosLog::create($log_preco);

                $count++;

            }
            else{
                
                if($preco->preco->preco_real != $preco_real_novo || $preco->preco->preco_dolar != $preco_dolar_novo || $preco->preco->compra_real != $compra_real_novo){
    
                    $log_preco = [
                        'codigo_produto' => $preco->codigo_produto,
                        'created_by' => $usuario_id
                    ];
    
                    if($fields['campo'] == 'real' && $preco->preco->preco_real != $preco_real_novo){
                        $log_preco['preco_real_antigo'] = (float) $preco->preco->preco_real;
                        $log_preco['preco_real_novo'] = (float) $preco_real_novo;
                        $preco->preco->preco_real = $preco_real_novo;
                    }
    
                    if($fields['campo'] == 'dolar' && $preco->preco->preco_dolar != $preco_dolar_novo){
                        $log_preco['preco_dolar_antigo'] = (float) $preco->preco->preco_dolar;
                        $log_preco['preco_dolar_novo'] = (float) $preco_dolar_novo;
                        $preco->preco->preco_dolar = $preco_dolar_novo;    
                    }

                    if($fields['campo'] == 'compra' && $preco->preco->compra_real != $compra_real_novo){
                        $log_preco['compra_real_antigo'] = (float) $preco->preco->compra_real;
                        $log_preco['compra_real_novo'] = (float) $compra_real_novo;
                        $preco->preco->compra_real = $compra_real_novo;
                    }
    
                    PrecosLog::create($log_preco);
            
                    $preco->preco->updated_by = $usuario_id;
                    
                    $preco->push();
    
                    $count++;
                }
            }
        }

        if($fields['campo'] == 'real'){
            try {
                $result = DB::connection('nasajon')->select('select integracoes.api_alteracaoproduto (?, ?)', ['{'. $precoProdutoObj->implode('codigo_produto', ',') .'}', $preco_real_novo]);
            } catch (\Exception $e) {
                return response()->json(['erro' => 'Erro na atualização do Nasajon. Contate o setor de TI'], 422);
            }
            
            $nasajon_result = json_decode(str_replace("\")", '', (str_replace("(\"", '', str_replace("\"\"", "\"", $result[0]->api_alteracaoproduto)))));
        }
        $mail_result = '';

        if($count > 0 && $envia_email){

            if(isset($fields['origem'])){
                if($fields['origem'] === 'de_para'){
                    $body = '<p>Os preços dos seguintes produtos foram alterados pro causa De/Para: <br>
                    <strong>Grupo:</strong> '. $grupo .' - <strong>Subgrupo:</strong> '. $linha['subgrupo'] .'<br>';
                }else{
                    $body = '<p>Os preços dos seguintes produtos foram alterados: <br>
                    <strong>Grupo:</strong> '. $grupo .' - <strong>Subgrupo:</strong> '. $linha['subgrupo'] .'<br>';
                }
            }else{
                $body = '<p>Os preços dos seguintes produtos foram alterados: <br>
                <strong>Grupo:</strong> '. $grupo .' - <strong>Subgrupo:</strong> '. $linha['subgrupo'] .'<br>';
            }
            

            if($precoProdutoObj->count() > 1){

                $body .= '<strong>Produto:</strong> Todos <br>';
            }
            else{
                if(isset($fields['origem'])){
                    if($fields['origem'] === 'de_para'){
                        $body .= '<strong>Produto De:</strong> ' . $fields['produto_de']. '  Produto Para: '. $precoProdutoObj[0]->codigo_produto . ' - ' . $precoProdutoObj[0]->descricao .'<br>';
                    }else{
                        $body .= '<strong>Produto:</strong> ' . $precoProdutoObj[0]->codigo_produto . ' - ' . $precoProdutoObj[0]->descricao . '  <br>';
                    }  
                }else{
                    $body .= '<strong>Produto:</strong> ' . $precoProdutoObj[0]->codigo_produto . ' - ' . $precoProdutoObj[0]->descricao . '  <br>';
                }              
            }

            if($fields['atualiza_grupo_subgrupo'] !== 'agrupado'){
                $body .= '<strong>Marca:</strong> '. $linha['marca'] .' - <strong>Linha:</strong> '. $linha['linha'] .' <br>';
            }

            if(empty(Auth::user()->name)){
                $body .= '<strong>Alterado por:</strong> Sistema - <strong>Data e hora:</strong>  '. date('d/m/Y H:i:s') .' <br></p>'; 
            }else{
                $body .= '<strong>Alterado por:</strong> '. Auth::user()->name .' - <strong>Data e hora:</strong>  '. date('d/m/Y H:i:s') .' <br></p>';
            }

            if($fields['campo'] == 'real'){
                if(isset($preco_real) && isset($preco_real_novo) && $preco_real != $preco_real_novo){
                    $body .= '<p><strong>Preço em real anterior:</strong> '. parserValor($preco_real) .' - <strong>Preço em real atualizado:</strong> '. parserValor($preco_real_novo) .'</p>';
                } else if(!isset($preco_real) && isset($preco_real_novo)){
                    $body .= '<p><strong>Preço em real atualizado:</strong> '. $preco_real_novo .'</p>';
                }
            }

            if($fields['campo'] == 'dolar'){
                if(isset($preco_dolar) && isset($preco_dolar_novo) && $preco_dolar != $preco_dolar_novo){
                    $body .= '<p><strong>Preço em dólar anterior:</strong> '. parserValor($preco_dolar) .' - <strong>Preço em dólar atualizado:</strong> '. parserValor($preco_dolar_novo) .'</p>';
                } else if(!isset($preco_dolar) && isset($preco_dolar_novo)){
                    $body .= '<p><strong>Preço em dólar atualizado:</strong> '. parserValor($preco_dolar_novo) .'</p>';
                }
            }

            if($fields['campo'] == 'compra'){
                if(isset($compra_real) && isset($compra_real_novo) && $compra_real != $compra_real_novo){
                    $body .= '<p><strong>Custo gerencial anterior:</strong> '. parserValor($compra_real) . ' - <strong>Custo gerencial atualizado:</strong> '. parserValor($compra_real_novo) .'</p>';
                } else if(!isset($compra_real) && isset($compra_real_novo)){
                    $body .= '<p><strong>Custo gerencial atualizado:</strong> '. parserValor($compra_real_novo) .'</p>';
                }
            }

            $body .= '<p><strong>Obs.:</strong> Preço FOB à vista 12% para Nacional e 4% para Importado.</p>'; 

            $emailControllerObj = new EmailController;

            $mail_result = $emailControllerObj->sendEmailToken('01', 'alteracao_preco_portal', [], ['body' => $body]);
        
        }

        $linha['preco_real'] = $preco_real_novo;
        $linha['preco_dolar'] = $preco_dolar_novo;

        $hash = Crypt::encrypt($linha);

        return response()->json(['total' => $count, 'email' => $mail_result, 'hash' => $hash], 200);

    }

    public function edicaoEspecificacaoMassa(Request $request){

        $fields = $request->only('hash');

        $linha = Crypt::decrypt($fields['hash']);

        $pesquisa = [];

        $count = 0;

        $preco_real = $linha['preco_real']??null;
        $preco_dolar = $linha['preco_dolar']??null;
        $custo_gerencial = $linha['custo_gerencial'];

        unset($linha['preco_real'], 
        $linha['preco_dolar'], $linha['custo_gerencial']); 

        $precoProdutoQuery = ProdutoEspecificacao::where($linha);
        if(!empty($linha['ativo'])){
            $precoProdutoQuery->where('ativo', '=', $linha['ativo']);
        }else{
            $precoProdutoQuery->where('ativo', '=', 'true');
        }
        

        if(!empty((float) $preco_real) || !empty((float) $preco_dolar) || !empty((float) $custo_gerencial)){
            $precoProdutoQuery->whereHas('preco', function($query) use($preco_real, $preco_dolar, $custo_gerencial){
                $query->where('preco_real', $preco_real)
                ->where('preco_dolar', $preco_dolar)
                ->where('compra_real', $custo_gerencial);
            });
        }
        else{
            $precoProdutoQuery->where(function($query){
                $query->whereDoesntHave('preco')
                ->orWhereHas('preco', function($q){
                    $q->where(function($real){
                        $real->where('preco_real', 0)
                        ->orWhereNull('preco_real');
                    })
                    ->where(function($dolar){
                        $dolar->where('preco_dolar', 0)
                        ->orWhereNull('preco_dolar');
                    })
                    ->where(function($compra){
                        $compra->where('compra_real', 0)
                        ->orWhereNull('compra_real');
                    });
                });
            });
        }
        
        $precoProdutoObj = $precoProdutoQuery->get();

        $produto = [
            'marca' => $precoProdutoObj[0]->marca,
            'grupo' => $precoProdutoObj[0]->produtoGrupo->descricao,
            'subgrupo' => $precoProdutoObj[0]->subgrupo,
            'linha' => $precoProdutoObj[0]->linha,
            'unidade' => $precoProdutoObj[0]->unidade,
            'hash' => $fields['hash'],
            'produtos' => $precoProdutoObj->count()
        ];

        foreach($precoProdutoObj as $produtoObj){

            $produto['itens'][] = [
                'codigo_produto' => $produtoObj->codigo_produto,
                'descricao' => $produtoObj->descricao
            ];
        }

        return view('programs.atualizacao_preco.modal_especificacoes')->with(['produto' => $produto]);
    }

    public function atualizaEspecificacoes(EspecificacoesRequest $request){

        $fields = $request->only('hash', 'marca', 'linha', 'grupo', 'subgrupo', 'itens');

        $linha = Crypt::decrypt($fields['hash']);
        
        $pesquisa = [];

        $count = 0;

        $preco_real = $linha['preco_real']??null;
        $preco_dolar = $linha['preco_dolar']??null;
        $custo_gerencial = $linha['custo_gerencial'];

        unset($linha['preco_real'], 
        $linha['preco_dolar'],
        $linha['custo_gerencial']);


        if(isset($fields['itens']) && !is_null($fields['itens'])){
            $precoProdutoQuery = ProdutoEspecificacao::whereIn('codigo_produto', $fields['itens'])
            ->where('ativo', '=', $linha['ativo']);
        }
        else{
            $precoProdutoQuery = ProdutoEspecificacao::where($linha)
            ->where('ativo', '=', $linha['ativo']);
    
            if(!empty((float) $preco_real) || !empty((float) $preco_dolar) || !empty((float) $custo_gerencial)){
                $precoProdutoQuery->whereHas('preco', function($query) use($preco_real, $preco_dolar, $custo_gerencial){
                    $query->where('preco_real', $preco_real)
                        ->where('preco_dolar', $preco_dolar)
                        ->where('compra_real', $custo_gerencial);
                });
            }
            else{
                $precoProdutoQuery->where(function($query){
                    $query->whereDoesntHave('preco')
                    ->orWhereHas('preco', function($q){
                        $q->where(function($real){
                            $real->where('preco_real', 0)
                            ->orWhereNull('preco_real');
                        })
                        ->where(function($dolar){
                            $dolar->where('preco_dolar', 0)
                            ->orWhereNull('preco_dolar');
                        })
                        ->where(function($compra){
                            $compra->where('compra_real', 0)
                            ->orWhereNull('compra_real');
                        });
                    });
                });
            }
        }

        $subgrupo_novo = [
            'marca' => $fields['marca'],
            'linha' => $fields['linha'],
            'grupo' => $fields['grupo'],
            'subgrupo' => $fields['subgrupo']
        ];

        try {
            $produtosAtualizados = $precoProdutoQuery->update($subgrupo_novo);
        }
        catch (Exception $e) {
            return response()->json(['erro' => 'Erro na atualização dos produtos. Contate o setor de TI'], 422);
        }

        return response()->json(['total' => $produtosAtualizados], 220);

    }

    public function importaPrecosPrologos(){

        ini_set('memory_limit', '99999M');

        echo 'Importação de preços da prologos: ' . PHP_EOL;

        $agora = Carbon::now();

        $ProformaObj = ProformaProduto::
        select('CODPRD as codigo_produto', 'TBPFM1.DT_RECEBIMENTO as previsao_entrega', 'TBPFP1.NUM_PROFORMA as numero_proforma', 'TBPFA1.PU_VND_US as PU_VND_US', 'TBPFA1.PU_FOB_ART as PU_FOB_ART', 'TBPFA1.PU_REAL_ART as PU_REAL_ART', 'TBPFM1.DATA_PROFORMA as DATA_PROFORMA')
        ->whereHas('proforma_artigo.proforma.compra_ativa')
        ->whereHas('proforma_artigo')
        ->join('TBPFA1', 'TBPFA1.NUM_PROFORMA', '=', 'TBPFP1.NUM_PROFORMA')
        ->join('TBPFM1', 'TBPFM1.NUM_PROFORMA', '=', 'TBPFP1.NUM_PROFORMA')
        ->orderBy('TBPFM1.DATA_PROFORMA', 'asc')
        ->get();

        $produtos = Produto::select(
            'CODPRD',
            'PRCVND_PREFIX_A', 
            'PRCVND_PREFIX_V'
        )
        ->where(function($query){
            $query->orWhere('PRCVND_PREFIX_A', '>', 0)
            ->orWhere('PRCVND_PREFIX_V', '>', 0);
        })->get();

        $produtosNasajon = ProdutoNasajon::select(
            'codigo as CODPRD', 
            'precovenda as PRCVND_PREFIX_A', 
            'precovenda_dolar as PRCVND_PREFIX_V'
        )
        ->where(function ($query){
            $query->orWhere('precovenda', '>', 0)
            ->orWhere('precovenda_dolar', '>', 0);
        })
        ->get();

        $produtos = $produtos
        ->concat($produtosNasajon
            ->whereNotIn('CODPRD', $produtos->pluck('CODPRD'))
        );

        $produtosComEstoque = collect([]);
        
        $inseridos = 0;
        $atualizados = 0;

        $logs = [];
        $novos = [];

        foreach($produtos as $item){

            $novos[$item->CODPRD] = [
                'codigo_produto' => utf8_encode($item->CODPRD),
                'preco_real' => (float) $item->PRCVND_PREFIX_A,
                'preco_dolar' => (float) $item->PRCVND_PREFIX_V,
                'created_by' => 1,
                'ultima_compra_real' => null,
                'ultima_compra_dolar' => null,
                'compra_real' => null,
                'compra_dolar' => null,
                'valor_compra' => null,
                'previsao_entrega' => null,
                'numero_proforma' => null,
            ];

            system('clear');
            echo 'Produtos processados: ' . ++$inseridos;

        }

        $compras = CompraProdutoImportacao::orderBy('data_faturamento', 'asc')->get();

        $update_preco = [];

        foreach($compras as $produto_compra){

            if(isset($novos[$produto_compra->codigo_produto])){

                if (strtotime($produto_compra->data_faturamento) > strtotime($novos[$produto_compra->codigo_produto]['ultima_compra_real'])){

                    $novos[$produto_compra->codigo_produto]['ultima_compra_real'] = $produto_compra->data_faturamento;
                    $novos[$produto_compra->codigo_produto]['compra_real'] = $produto_compra->preco_unitario;

                }
        
            }
            
        }

        foreach ($ProformaObj as $proforma){

            if(isset($novos[$proforma->codigo_produto])){

                $novos[$proforma->codigo_produto]['valor_compra'] = $proforma->PU_FOB_ART;
                $novos[$proforma->codigo_produto]['previsao_entrega'] = $proforma->previsao_entrega;
                $novos[$proforma->codigo_produto]['numero_proforma'] = $proforma->numero_proforma;
                
            }

        }

        $proformaEncerradoObj = ProformaEncerrado::all();

        foreach ($proformaEncerradoObj as $compra_dolar){
            if(isset($novos[$compra_dolar->codigo_produto])){
                
                $novos[$proforma->codigo_produto]['compra_dolar'] = $compra_dolar->compra_dolar;
                $novos[$proforma->codigo_produto]['ultima_compra_dolar'] = $compra_dolar->ultima_compra_dolar;
            }
        }

        if(!empty($novos)){
            $tempo_processamento = DB::transaction(function () use($novos) {

                $inicio = Carbon::now();

                Preco::query()->truncate();

                $novos_quebrados = array_chunk($novos, 1000);

                foreach($novos_quebrados as $linhas){
                    Preco::insert($linhas);
                }

                echo PHP_EOL . $inicio->diff(Carbon::now())->format('A transação demoromou %i minutos e %s segundos') . PHP_EOL;
            });

            DB::commit();

        }

        echo "Produtos importados: " . $inseridos . PHP_EOL . 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function importaValorCompraProduto(){

        ini_set('memory_limit', '999999M');

        echo 'Importação de todas as compras de produtos: ' . PHP_EOL;

        $agora = Carbon::now();

        $base_tabelas = [];
        $dum = [];
        $dui = [];

        $meses = ['01','02','03','04','05','06','07','08','09','10','11','12'];
        $estabelecimentos = ['00','01','02','03','04'];

        foreach($estabelecimentos as $estabelecimento){
            for($ano = 5; $ano <= date('y'); $ano++){
                foreach($meses as $mes){
                    $base_tabelas[] = [
                        'base_tabela' => $estabelecimento . '_' . str_pad($ano, 2, '0', STR_PAD_LEFT) . $mes . '2',
                        'estabelecimento' => $estabelecimento
                    ];
                }
            }
        }

        $codcad_excluidos = ['0050758840002', '0063112740002', '00507758840001','0063112740003'];
        $tipoperExcluidos = ['EC+', 'EC/', 'ECI', 'ECJ', 'ECO', 'ECS', 'ECT'];

        $x = 0;

        foreach($base_tabelas as $base){

            if (DB::connection('srv_prologos')->getSchemaBuilder()->hasTable('DUM'. $base['base_tabela'])){

                $consulta = DB::connection('srv_prologos')
                ->table('DUM'. $base['base_tabela'])
                ->select(
                    'DUM'.$base['base_tabela'].'.NUMDOC',
                    'DUM'.$base['base_tabela'].'.CODCAD',
                    'CODPRD',
                    'DTEMIS',
                    'QTDE',
                    'PRECOTOT'
                )
                ->join('DUI' . $base['base_tabela'], 'DUM' . $base['base_tabela'] . '.NUMDOC', '=', 'DUI' . $base['base_tabela'] . '.NUMDOC')
                ->where('STATDOC', '!=', 'C')
                ->where(DB::Raw('lower(TIPOPER)'), 'like', 'ec%')
                ->whereRaw('len(DUM'.$base['base_tabela'].'.NUMDOC) > 0')
                ->whereRaw('len(DUM'.$base['base_tabela'].'.CODCAD) > 0')
                ->whereRaw('len(CODPRD) > 0')
                ->whereRaw('len(QTDE) > 0')
                ->whereRaw('len(PRECOTOT) > 0')
                ->whereNotIn('TIPOPER', $tipoperExcluidos)
                ->whereNotIn('DUM'.$base['base_tabela'].'.CODCAD', $codcad_excluidos)
                ->get();

                if(isset($consulta[0])){
                    $compras[] = $consulta;
                }

                echo 'Tabela processada: DUM'. $base['base_tabela'] . PHP_EOL;

            }

        }

        $update_preco = [];
        $compras_linhas = [];

        foreach ($compras as $compra){

            foreach($compra as $produto_compra){

                $preco_total = (float) round($produto_compra->PRECOTOT * 100) / 100;
                $quantidade = (float) round($produto_compra->QTDE * 100) / 100;
                $preco_unitario = (float) round(($produto_compra->PRECOTOT / $produto_compra->QTDE) * 100) / 100;
                
                $compras_linhas[] = [
                    'estabelecimento' => $base['estabelecimento'],
                    'numero_documento' => utf8_encode($produto_compra->NUMDOC),
                    'codigo_produto' => utf8_encode($produto_compra->CODPRD),
                    'data_faturamento' => $produto_compra->DTEMIS,
                    'preco_total' => $preco_total,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $preco_unitario,
                    'origem' => 'prologos'
                ];

                if(isset($update_preco[$produto_compra->CODPRD])){
                    if (strtotime($produto_compra->DTEMIS) > strtotime($update_preco[$produto_compra->CODPRD]['ultima_compra_real'])){

                        $update_preco[$produto_compra->CODPRD] = [
                            'ultima_compra_real' => $produto_compra->DTEMIS,
                            'compra_real' => $preco_unitario
                        ];

                    }

                }
                else{

                    $update_preco[$produto_compra->CODPRD] = [
                        'ultima_compra_real' => $produto_compra->DTEMIS,
                        'compra_real' => $preco_unitario
                    ];

                }

                unset($preco_total, $quantidade, $preco_unitario);

            }    

        }

        DB::transaction(function () use($compras_linhas) {

            CompraProdutoImportacao::query()->truncate();

            $compras_quebradas = array_chunk($compras_linhas, 1000);
            
            foreach($compras_quebradas as $linhas){
                CompraProdutoImportacao::insert($linhas);
            }

        });

        DB::commit();

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function importaTodosValoresCompraProdutoNasajon(){

        ini_set('memory_limit', '999999M');

        echo 'Importação de todas as compras de produtos do Nasajon: ' . PHP_EOL;

        $cnpjs_intercompany = NasajonEstabelecimento::selectRaw('concat(raizcnpj, ordemcnpj) as cnpj')->get()->pluck('cnpj');

        $agora = Carbon::now();

        $comprasProdutoNasajonObj = ItensNotasCompraNasajon::select(
            'estabelecimento_codigo as estabelecimento',
            'numero as numero_documento',
            'produto_codigo as codigo_produto',
            'emissao as data_faturamento',
            'valortotal as preco_total',
            'quantidade',
            'valorunitario as preco_unitario',
            DB::Raw('\'nasajon\' as origem')

        )
        ->whereNotIn('fornecedor_cnpj', $cnpjs_intercompany)
        ->get();

        echo 'Linhas encontradas: ' . $comprasProdutoNasajonObj->count() . PHP_EOL;

        DB::transaction(function () use($comprasProdutoNasajonObj) {

            $comprasProdutoNasajonObj->chunk(1000)->each(function($linhas){

                try {
                    CompraProdutoImportacao::insert($linhas->toArray());
                } catch (\QueryException $e) {
                    echo 'Erro na importação: ' . $e->message . ' ' . printf($e->getSql(), $e->getBindings()) . PHP_EOL;
                    DB::rollback();
                    return null;
                }

            });

            DB::commit();

        });


        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;
        
    }

    public function importaValoresCompraProdutoNasajon(){

        ini_set('memory_limit', '999999M');

        $cnpjs_intercompany = NasajonEstabelecimento::selectRaw('concat(raizcnpj, ordemcnpj) as cnpj, codigo')
            ->whereNotIn('codigo', ['TREINAMENTO', '99'])
            ->get()->pluck('cnpj');
        echo 'Importação de compras recentes de produtos do Nasajon: ' . PHP_EOL;

        $agora = Carbon::now();

        $jaImportadas = CompraProdutoImportacao::select('numero_documento', 'codigo_produto')
        ->where('data_faturamento', '>=', '2021-01-01')
        ->get();

		$UnidadeConversaoProdutoNasajonTable = new UnidadeConversaoProdutoNasajon();
		$UnidadeConversaoProdutoNasajonTable = $UnidadeConversaoProdutoNasajonTable->getTable();

		$NotasEntradasItensNasajonTable = new NotasEntradasItensNasajon();
		$NotasEntradasItensNasajonTable = $NotasEntradasItensNasajonTable->getTable();

        $comprasProdutoNasajonQuery = NotasEntradasItensNasajon::select(
            'Estabelecimento as estabelecimento',
            'Número do Documento as numero_documento',
            'Item - Código as codigo_produto',
            'Data de Entrada as data_faturamento',
            'Valor do Documento as preco_total',
			DB::raw('"Item - Valor Unitário" / (case when '.$UnidadeConversaoProdutoNasajonTable.'.razao is not null then '.$UnidadeConversaoProdutoNasajonTable.'.razao else 1 end) as preco_unitario'),
			DB::raw('"Item - Quantidade" * (case when '.$UnidadeConversaoProdutoNasajonTable.'.razao is not null then '.$UnidadeConversaoProdutoNasajonTable.'.razao else 1 end) as quantidade')
		)
		->leftJoin($UnidadeConversaoProdutoNasajonTable, function($query) use($NotasEntradasItensNasajonTable, $UnidadeConversaoProdutoNasajonTable){
			$query->on($UnidadeConversaoProdutoNasajonTable.'.codigo_produto', DB::raw($NotasEntradasItensNasajonTable .'."Item - Código"'));
			$query->on($UnidadeConversaoProdutoNasajonTable.'.codigo_unidadeconversao', DB::raw($NotasEntradasItensNasajonTable .'."Item - Unidade"'));
		})
        ->where('Estabelecimento', '!=', '03')
        ->where('Data de Entrada', '>=', '2021-01-01')
        ->whereIn('Código da Operação', ['COMPRA'])
        ->where(function($query) use ($cnpjs_intercompany){
            $query->orWhereNotIn(DB::Raw('ns.api_sonumero("CNPJ/CPF do Fornecedor"::text)'), $cnpjs_intercompany);
            $query->orWhereNull('CNPJ/CPF do Fornecedor');
        });
        $jaImportadas->each(function ($linha) use (&$comprasProdutoNasajonQuery){
            $comprasProdutoNasajonQuery->where( function($query) use ($linha){
                $query
                    ->where(DB::Raw('CONCAT("Número do Documento","Item - Código")'), '!=', $linha->numero_documento.''.$linha->codigo_produto);
            });
        });
        $comprasProdutoNasajonObj = $comprasProdutoNasajonQuery->get();

        $comprasProdutoNotasNasajonQuery = NotasEntradasItensNasajon::select(
            'Número do Documento as numero_nota',
            'Estabelecimento as estabelecimento',
            'Fornecedor as fornecedor_codigo',
            'CNPJ/CPF do Fornecedor as fornecedor_cnpj',
            'Data de Entrada as emissao'
        )
        ->where('Estabelecimento', '!=', '03')
        ->where('Data de Entrada', '>=', '2021-01-01')
        ->whereIn('Código da Operação', ['COMPRA'])
        ->where(function($query) use ($cnpjs_intercompany){
            $query->orWhereNotIn(DB::Raw('ns.api_sonumero("CNPJ/CPF do Fornecedor"::text)'), $cnpjs_intercompany);
            $query->orWhereNull('CNPJ/CPF do Fornecedor');
        });
        $jaImportadas->each(function ($linha) use (&$comprasProdutoNotasNasajonQuery){
            $comprasProdutoNotasNasajonQuery->where( function($query) use ($linha){
                $query->where(DB::Raw('CONCAT("Número do Documento","Item - Código")'), '!=', $linha->numero_documento.''.$linha->codigo_produto);
            });
        });
        $comprasProdutoNotasNasajonQuery->groupBy('Número do Documento', 'Estabelecimento', 'Fornecedor', 'CNPJ/CPF do Fornecedor', 'Data de Entrada');
        
        $comprasProdutoNotasNasajonObj = $comprasProdutoNotasNasajonQuery->get();

        echo 'Linhas encontradas: ' . $comprasProdutoNasajonObj->count() . PHP_EOL;
        DB::transaction(function () use($comprasProdutoNasajonObj, $comprasProdutoNotasNasajonObj, $UnidadeConversaoProdutoNasajonTable, $NotasEntradasItensNasajonTable) {
            $comprasProdutoNasajonObj->chunk(1000)->each(function($linhas){
				$linhas->each(function($linha){
					try {
						$CompraProdutoImportacaoObj = new CompraProdutoImportacao();
						$CompraProdutoImportacaoObj->estabelecimento = $linha->estabelecimento;
						$CompraProdutoImportacaoObj->numero_documento = $linha->numero_documento;
						$CompraProdutoImportacaoObj->codigo_produto = $linha->codigo_produto;
						$CompraProdutoImportacaoObj->data_faturamento = $linha->data_faturamento;
						$CompraProdutoImportacaoObj->preco_total = $linha->preco_total;
						$CompraProdutoImportacaoObj->quantidade = $linha->quantidade;
						$CompraProdutoImportacaoObj->preco_unitario = $linha->preco_unitario;
						$CompraProdutoImportacaoObj->origem = $linha->origem;
						$CompraProdutoImportacaoObj->save();
					} catch (\QueryException $e) {
						echo 'Erro na importação: ' . $e->message . ' ' . printf($e->getSql(), $e->getBindings()) . PHP_EOL;
						DB::rollback();
						return null;
					}
				});
            });
            $comprasProdutoNotasNasajonObj->each(function($linhas) use($UnidadeConversaoProdutoNasajonTable, $NotasEntradasItensNasajonTable){
                try {
                    $ValorCustoNotaObj = new ValorCustoNota;
                    $ValorCustoNotaObj->estabelecimento = $linhas['estabelecimento'];
                    $ValorCustoNotaObj->data_compra = $linhas['emissao'];
                    $ValorCustoNotaObj->fornecedor_cnpj = $linhas['fornecedor_cnpj'];
                    $ValorCustoNotaObj->fornecedor_codigo = $linhas['fornecedor_codigo'];
                    $ValorCustoNotaObj->numero_pedido = $linhas['numero_nota'];
                    $ValorCustoNotaObj->created_by = 1;
                    $ValorCustoNotaObj->created_at = date('Y-m-d H:i:s');
                    $ValorCustoNotaObj->save();

                    $ItensNotasCompraNasajonObj = NotasEntradasItensNasajon::query()->
                        select(
                            'Número do Documento as numero_nota',
                            'Estabelecimento as estabelecimento',
                            'Fornecedor as fornecedor_codigo',
                            'CNPJ/CPF do Fornecedor as fornecedor_cnpj',
                            'Data de Entrada as emissao',
                            DB::raw('"Item - Valor Unitário" /(case when '.$UnidadeConversaoProdutoNasajonTable.'.razao is not null then '.$UnidadeConversaoProdutoNasajonTable.'.razao else 1 end) as valorunitario'),
                            DB::raw('"Item - Quantidade" * (case when '.$UnidadeConversaoProdutoNasajonTable.'.razao is not null then '.$UnidadeConversaoProdutoNasajonTable.'.razao else 1 end) as quantidade'),
                            "Item - Código as produto_codigo",
                            "Item - Unidade as unidade"
                        )->
						leftJoin($UnidadeConversaoProdutoNasajonTable, function($query) use($NotasEntradasItensNasajonTable, $UnidadeConversaoProdutoNasajonTable){
							$query->on($UnidadeConversaoProdutoNasajonTable.'.codigo_produto', DB::raw($NotasEntradasItensNasajonTable .'."Item - Código"'));
							$query->on($UnidadeConversaoProdutoNasajonTable.'.codigo_unidadeconversao', DB::raw($NotasEntradasItensNasajonTable .'."Item - Unidade"'));
						})->
                        where('Estabelecimento', $linhas['estabelecimento'])->
                        where('CNPJ/CPF do Fornecedor', $linhas['fornecedor_cnpj'])->
                        where('Número do Documento', $linhas['numero_nota'])->
                        get();

                    $ItensNotasCompraNasajonObj->each(function($item) use ($ValorCustoNotaObj){
                        $ValorCustoNotaProdutoObj = new ValorCustoNotaProduto;
                        $ValorCustoNotaProdutoObj->valor_custo_notas_id = $ValorCustoNotaObj->id;
                        $ValorCustoNotaProdutoObj->codigo_produto = $item['produto_codigo'];
                        $ValorCustoNotaProdutoObj->custo = $item['valorunitario'];
                        $ValorCustoNotaProdutoObj->numero_pedido = $item['numero_nota'];
                        $ValorCustoNotaProdutoObj->proforma = '';
                        $ValorCustoNotaProdutoObj->quantidade = $item['quantidade'];
                        $ValorCustoNotaProdutoObj->valor_dolar = 0;
                        $ValorCustoNotaProdutoObj->created_by = 1;
                        $ValorCustoNotaProdutoObj->created_at = date('Y-m-d H:i:s');
                        $ValorCustoNotaProdutoObj->save();
                    });
                } catch (\QueryException $e) {
                    echo 'Erro na importação: ' . $e->message . ' ' . printf($e->getSql(), $e->getBindings()) . PHP_EOL;
                    DB::rollback();
                    return null;
                }
            });
        });
        DB::commit();


        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;
    }

    public function atualizaValorCompraProduto(){
        
        ini_set('memory_limit', '999999M');

        $agora = Carbon::now();

        $x = 0;

        foreach(DB::select(
            'select
                a.codigo_produto as codigo_produto,
                a.data_faturamento as data_faturamento,
                preco_unitario as preco_unitario
            from
                compra_produto_importacaos a
            join (
                    select codigo_produto,
                    max(data_faturamento) as data_faturamento
                from
                    compra_produto_importacaos c
                    WHERE estabelecimento != \'03\'
                group by
                    codigo_produto) b on
                a.codigo_produto = b.codigo_produto
                and a.data_faturamento = b.data_faturamento
                and a.estabelecimento != \'03\''
        ) as $ultimaCompra){

            $ultimaCompra = (array) $ultimaCompra;

            $PrecoObj = Preco::where('codigo_produto', $ultimaCompra['codigo_produto'])
            ->get();
            
            if($PrecoObj->isEmpty()){
                $PrecoObj = new Preco();
                $PrecoObj->codigo_produto = $ultimaCompra['codigo_produto'];
                $PrecoObj->compra_real = $ultimaCompra['preco_unitario'];
                $PrecoObj->ultima_compra_real = $ultimaCompra['data_faturamento'];
                $PrecoObj->created_by = 1;
                $PrecoObj->save();

                $log_precos = new PrecosLog;
                $log_precos->codigo_produto = $ultimaCompra['codigo_produto'];
                $log_precos->compra_real_novo = $ultimaCompra['preco_unitario'];
                $log_precos->created_by = 1;
                $log_precos->processo = 'Cria ou atualiza todos os valores de compra das linhas da tabela de preço, segundo o que está na tabela de importação';
                $log_precos->save();
            }else{
                $PrecoObj = $PrecoObj->where('ultima_compra_real', '<', $ultimaCompra['data_faturamento'])->first();
                if(!empty($PrecoObj)){
                    $log_precos = new PrecosLog;
                    $log_precos->codigo_produto = $ultimaCompra['codigo_produto'];
                    $log_precos->compra_real_antigo = $PrecoObj->ultima_compra_real;
                    $log_precos->compra_real_novo = $ultimaCompra['preco_unitario'];
                    $log_precos->created_by = 1;
                    $log_precos->processo = 'Cria ou atualiza todos os valores de compra das linhas da tabela de preço, segundo o que está na tabela de importação';
                    $log_precos->save();

                    $PrecoObj->compra_real = $ultimaCompra['preco_unitario'];
                    $PrecoObj->ultima_compra_real = $ultimaCompra['data_faturamento'];
                    $PrecoObj->save();

                }
            }

        }
    }

    public function atualizarCompras(){

        ini_set('memory_limit', '999999M');
        
        echo 'Importa valores da Prologos e coloca na tabela de importação e nos produtos caso ache: ' . PHP_EOL;

        $agora = Carbon::now();

        $ProformaObj = ProformaProduto::
        select('CODPRD as codigo_produto', 'TBPFM1.DT_RECEBIMENTO as previsao_entrega', 'TBPFP1.NUM_PROFORMA as numero_proforma', 'TBPFA1.PU_VND_US as PU_VND_US', 'TBPFA1.PU_FOB_ART as PU_FOB_ART', 'TBPFA1.PU_REAL_ART as PU_REAL_ART', 'TBPFM1.DATA_PROFORMA as DATA_PROFORMA')
        ->whereHas('proforma_artigo')
        ->whereHas('proforma_artigo.proforma.compra_ativa')
        ->join('TBPFA1', 'TBPFA1.NUM_PROFORMA', '=', 'TBPFP1.NUM_PROFORMA')
        ->join('TBPFM1', 'TBPFM1.NUM_PROFORMA', '=', 'TBPFP1.NUM_PROFORMA')
        ->orderBy('TBPFM1.DATA_PROFORMA', 'asc')
        ->get();

        $compras = CompraProdutoImportacao::orderBy('data_faturamento', 'asc')->get();
        
        $proformaEncerradoObj = ProformaEncerrado::orderBy('data_proforma', 'asc')->get();
        
        $atualizar = [];

        foreach($compras as $produto_compra){

            $atualizar[$produto_compra->codigo_produto]['ultima_compra_real'] = $produto_compra->data_faturamento;
            $atualizar[$produto_compra->codigo_produto]['compra_real'] = $produto_compra->preco_unitario;

        }

        foreach ($ProformaObj as $proforma){
            
            $atualizar[$proforma->codigo_produto]['valor_compra'] = $proforma->PU_FOB_ART;
            $atualizar[$proforma->codigo_produto]['previsao_entrega'] = $proforma->previsao_entrega;
            $atualizar[$proforma->codigo_produto]['numero_proforma'] = $proforma->numero_proforma;
            $atualizar[$proforma->codigo_produto]['preco_dolar'] = $proforma->PU_VND_US;           

        }

        foreach ($proformaEncerradoObj as $compra_dolar){

            $atualizar[$compra_dolar->codigo_produto]['compra_dolar'] = $compra_dolar->valor_unitario_fob;
            $atualizar[$compra_dolar->codigo_produto]['ultima_compra_dolar'] = $compra_dolar->data_proforma;

        }

        DB::transaction(function () use($atualizar) {

            echo "Processando produtos..." . PHP_EOL;

            foreach($atualizar as $key => $value){

                $preco = Preco::firstOrNew(['codigo_produto' => $key]);

                if(isset($value['ultima_compra_real']) && !is_null($value['ultima_compra_real'])){
                    $preco->ultima_compra_real = $value['ultima_compra_real'];
                }

                if(isset($value['compra_real']) && !is_null($value['compra_real'])){
                    $preco->compra_real = $value['compra_real'];
                }

                if(isset($value['valor_compra']) && !is_null($value['valor_compra'])){
                    $preco->valor_compra = $value['valor_compra'];
                }
                
                if(isset($value['ultima_compra_dolar']) && !is_null($value['ultima_compra_dolar'])){
                    $preco->ultima_compra_dolar = $value['ultima_compra_dolar'];
                }

                if(isset($value['compra_dolar']) && !is_null($value['compra_dolar'])){
                    $preco->compra_dolar = $value['compra_dolar'];
                }

                if(isset($value['preco_dolar']) && !is_null($value['preco_dolar'])){
                    $preco->preco_dolar = $value['preco_dolar'];
                }

                if(isset($value['numero_proforma']) && !is_null($value['numero_proforma'])){
                    $preco->numero_proforma = $value['numero_proforma'];
                }

                if(isset($value['previsao_entrega']) && !is_null($value['previsao_entrega'])){
                    $preco->previsao_entrega = $value['previsao_entrega'];
                }

                if(
                    isset($value['ultima_compra_real']) && !is_null($value['ultima_compra_real']) ||
                    isset($value['compra_real']) && !is_null($value['compra_real']) ||
                    isset($value['valor_compra']) && !is_null($value['valor_compra']) ||
                    isset($value['ultima_compra_dolar']) && !is_null($value['ultima_compra_dolar']) ||
                    isset($value['compra_dolar']) && !is_null($value['compra_dolar']) ||
                    isset($value['preco_dolar']) && !is_null($value['preco_dolar']) ||
                    isset($value['numero_proforma']) && !is_null($value['numero_proforma']) ||
                    isset($value['previsao_entrega']) && !is_null($value['previsao_entrega']))
                {
                    $preco->created_by = empty($preco->created_by) ? 1: $preco->created_by;
                    $preco->save();
                }

            }

        });

        DB::commit();

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function importaValorCompraProdutoNovos(){

        ini_set('memory_limit', '999999M');

        echo 'Importação de novas compras de produtos: ' . PHP_EOL;

        $agora = Carbon::now();

        $compras = [];

        $base_tabelas = [];
        $dum = [];
        $dui = [];

        $estabelecimentos = ['00','01','02','03','04'];

        if(date('m') == 1 && date('d') == 1){
            $ano_inicial = date('y') - 1;
            $meses = ['12', '1'];
        }
        else if(date('d') == 1 && date('m') > 1){
            $ano_inicial = date('y');
            $meses = [date('m') - 1, date('m')];

        }
        else{
            $ano_inicial = date('y');
            $meses = [date('m')];
        }

        $data = [date('y-m-d', strtotime('yesterday')), date('y-m-d')];
        $ano_final = date('y');

        $comprasJaImportadas = CompraProdutoImportacao::whereBetween('data_faturamento', $data)->get();


        foreach($estabelecimentos as $estabelecimento){
            for($ano = $ano_inicial; $ano <= $ano_final; $ano++){
                foreach($meses as $mes){
                    $base_tabelas[] = [
                        'base_tabela' => $estabelecimento . '_' . str_pad($ano, 2, '0', STR_PAD_LEFT) . $mes . '2',
                        'estabelecimento' => $estabelecimento
                    ];
                }
            }
        }

        $codcad_excluidos = ['0050758840002', '0063112740002', '00507758840001','0063112740003'];
        $tipoperExcluidos = ['EC+', 'EC/', 'ECI', 'ECJ', 'ECO', 'ECS', 'ECT'];

        $x = 0;

        foreach($base_tabelas as $base){

            if (DB::connection('srv_prologos')->getSchemaBuilder()->hasTable('DUM'. $base['base_tabela'])){

                $consulta = DB::connection('srv_prologos')
                ->table('DUM'. $base['base_tabela'])
                ->select(
                    'DUM'.$base['base_tabela'].'.NUMDOC',
                    'DUM'.$base['base_tabela'].'.CODCAD',
                    'CODPRD',
                    'DTEMIS',
                    'QTDE',
                    'PRECOTOT'
                )
                ->join('DUI' . $base['base_tabela'], 'DUM' . $base['base_tabela'] . '.NUMDOC', '=', 'DUI' . $base['base_tabela'] . '.NUMDOC')
                ->where('STATDOC', '!=', 'C')
                ->where(DB::Raw('lower(TIPOPER)'), 'like', 'ec%')
                ->whereRaw('len(DUM'.$base['base_tabela'].'.NUMDOC) > 0')
                ->whereRaw('len(DUM'.$base['base_tabela'].'.CODCAD) > 0')
                ->whereRaw('len(CODPRD) > 0')
                ->whereRaw('len(QTDE) > 0')
                ->whereRaw('len(PRECOTOT) > 0')
                ->whereNotIn('TIPOPER', $tipoperExcluidos)
                ->whereNotIn('DUM'.$base['base_tabela'].'.CODCAD', $codcad_excluidos)
                ->whereNotIn('DUM'.$base['base_tabela'].'.NUMDOC', $comprasJaImportadas->pluck('numero_documento'))
                ->get();

                if(isset($consulta[0])){
                    $compras[] = $consulta;
                }

            }

        }

        $update_preco = [];
        $compras_linhas = [];

        foreach ($compras as $compra){

            foreach($compra as $produto_compra){

                $preco_total = (float) round($produto_compra->PRECOTOT * 100) / 100;
                $quantidade = (float) round($produto_compra->QTDE * 100) / 100;
                $preco_unitario = (float) round(($produto_compra->PRECOTOT / $produto_compra->QTDE) * 100) / 100;
                
                $compras_linhas[] = [
                    'estabelecimento' => $base['estabelecimento'],
                    'numero_documento' => utf8_encode($produto_compra->NUMDOC),
                    'codigo_produto' => utf8_encode($produto_compra->CODPRD),
                    'data_faturamento' => $produto_compra->DTEMIS,
                    'preco_total' => $preco_total,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $preco_unitario,
                ];

                if(isset($update_preco[$produto_compra->CODPRD])){
                    if (strtotime($produto_compra->DTEMIS) > strtotime($update_preco[$produto_compra->CODPRD]['ultima_compra_real'])){

                        $update_preco[$produto_compra->CODPRD] = [
                            'ultima_compra_real' => $produto_compra->DTEMIS,
                            'compra_real' => $preco_unitario
                        ];

                    }

                }
                else{

                    $update_preco[$produto_compra->CODPRD] = [
                        'ultima_compra_real' => $produto_compra->DTEMIS,
                        'compra_real' => $preco_unitario
                    ];

                }

                unset($preco_total, $quantidade, $preco_unitario);

            }    

        }

        DB::transaction(function () use($compras_linhas) {

            $compras_quebradas = array_chunk($compras_linhas, 1000);
            
            foreach($compras_quebradas as $linhas){
                CompraProdutoImportacao::insert($linhas);
            }

        });

        DB::commit();

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL . 'Importadas ' . count($compras_linhas) . ' linhas.' . PHP_EOL;

    }

    public function importaEspecificacoesProduto(){

        ini_set('memory_limit', '999999M');

        echo 'Importação de especificações de produtos: ' . PHP_EOL;

        $agora = Carbon::now();

        $produtosNasajon = ProdutoNasajon::select(
            'codigo as codigo_produto',
            DB::Raw("max(marca) as marca,
            max(linha) as linha,
            max(grupo) as grupo,
            max(subgrupo) as subgrupo,
            max(especificacao) as descricao,
            max(unidade) as unidade,
            CASE WHEN max(procedencia)='Estrangeira - Importação Direta' THEN '1' ELSE '0' END as procedencia")
        )
        ->whereNotNull('marca')
        ->whereNotNull('linha')
        ->whereNotNull('grupo')
        ->whereNotNull('subgrupo')
        ->groupBy('codigo_produto')
        ->get();

        $produtosPrologos = Produto::select(
            'CODPRD as codigo_produto',
            'MARCA as marca',
            'LINHA as linha',
            'GRUPO as grupo',
            'SUBGRUPO as subgrupo',
            'DESCR as descricao',
            'UNIDADE_VND as unidade',
            'PROCEDENCIA as procedencia'
        )->get();

        $produtosPrologos->each(function($item){
            $item->codigo_produto = utf8_encode($item->codigo_produto);
            $item->marca = utf8_encode($item->marca);
            $item->linha = utf8_encode($item->linha);
            $item->grupo= utf8_encode($item->grupo);
            $item->subgrupo = utf8_encode($item->subgrupo);
            $item->descricao = utf8_encode($item->descricao);
            $item->unidade = utf8_encode($item->unidade);
        });

        $produtosNasajon = $produtosNasajon->whereNotIn('codigo_produto', $produtosPrologos->pluck('codigo_produto'));

        $produtos = array_merge($produtosPrologos->toArray(), $produtosNasajon->toArray());

        $produtos_chunks = array_chunk($produtos, 2000);

        DB::transaction(function () use($produtos_chunks) {

            ProdutoEspecificacao::query()->truncate();

            foreach ($produtos_chunks as $value){
                ProdutoEspecificacao::insert($value);
            }
        });

        DB::commit();

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function importarProformasEncerrados(){

        ini_set('memory_limit', '999999M');
        
        echo 'Importação de proformas encerrados: ' . PHP_EOL;

        $agora = Carbon::now();

        $ProformaObj = ProformaProduto::
        select(
            'CODPRD as codigo_produto',
            'TBPFM1.DT_RECEBIMENTO as previsao_entrega', 
            'TBPFP1.NUM_PROFORMA as numero_proforma', 
            'TBPFA1.PU_VND_US as valor_venda_us', 
            'TBPFA1.PU_FOB_ART as valor_unitario_fob', 
            'TBPFA1.PU_REAL_ART as valor_unitario_real', 
            'TBPFM1.DATA_PROFORMA as data_proforma'
        )
        ->whereHas('proforma_artigo')
        ->whereDoesntHave('proforma_artigo.proforma.compra_ativa')
        ->whereNotIn('SITATUAL', ['A', 'B'])
        ->join('TBPFA1', 'TBPFA1.NUM_PROFORMA', '=', 'TBPFP1.NUM_PROFORMA')
        ->join('TBPFM1', 'TBPFM1.NUM_PROFORMA', '=', 'TBPFP1.NUM_PROFORMA')
        ->orderBy('TBPFM1.DATA_PROFORMA', 'asc')
        ->get()->toArray();
        
        $proforma_chunks = array_chunk($ProformaObj, 1000);

        DB::transaction(function () use($proforma_chunks) {

            ProformaEncerrado::query()->truncate();

            foreach($proforma_chunks as $proforma_chunk){

                ProformaEncerrado::insert($proforma_chunk);

            }
        
        });

        DB::commit();

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function agruparPrecos(){

        ini_set('memory_limit', '999999M');

        echo 'Função de agrupar preços: ' .PHP_EOL;
        
        $agora = Carbon::now();
        
        $precoObj = ProdutoEspecificacao::select('marca', 'linha', 'grupo', 'subgrupo', DB::Raw('max(preco_real) preco_real, max(preco_dolar) preco_dolar'))
        ->join('precos', 'precos.codigo_produto', 'produto_especificacaos.codigo_produto' )
        ->groupBy('marca','linha','grupo','subgrupo')
        ->get();

        foreach($precoObj as $preco){
            $subgrupoObj = ProdutoEspecificacao::where(['marca' => $preco->marca, 'linha' => $preco->linha, 'grupo' => $preco->grupo, 'subgrupo' => $preco->subgrupo])->get();

            $subgrupoObj->each(function($subgrupo) use ($preco){
                $precoObj = Preco::firstOrNew(['codigo_produto' => $subgrupo->codigo_produto]);

                $precoObj->preco_real = $preco->preco_real;
                $precoObj->preco_dolar = $preco->preco_dolar;

                if(empty($precoObj->created_at)){
                    $precoObj->created_by = 1;
                }
                else{
                    $precoObj->updated_by = 1;
                }

                $precoObj->save();
            });

            Produto::where(['marca' => $preco->marca, 'linha' => $preco->linha, 'grupo' => $preco->grupo, 'subgrupo' => $preco->subgrupo])->update(['PRCVND_PREFIX_A' => $preco->preco_real??0, 'PRCVND_PREFIX_V' => $preco->preco_dolar??0]);

            $result = DB::connection('nasajon')->select('select integracoes.api_alteracaoproduto (?, ?)', ['{'. $subgrupoObj->implode('codigo_produto', ',') .'}', $preco->preco_real]);

        }

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function modalHistoricoAltecoes(Request $request){
        $fields = $request->only(['hash', 'preco']);

        $linha = Crypt::decrypt($fields['hash']);

        $produtosQuery = ProdutoEspecificacao::with('estoque');

        if(isset($linha['compra_real']) || isset($linha['custo'])){

            $compra_real = $linha['compra_real'];
            $custo = $linha['custo'];
    
            unset($linha['compra_real'], $linha['custo']);

            if(empty($compra_real)){
                $produtosQuery->where(function ($q){
                    $q->whereHas('preco', function($query){
                        $query->where('compra_real', 0)
                        ->orWhereNull('compra_real');
                    })
                    ->orWhereDoesntHave('preco');
                });
            }
            else{
                $produtosQuery->whereHas('preco', function($query) use($compra_real){
                    $query->where('compra_real', $compra_real);
                });
            }

            if(empty($custo)){
                $produtosQuery->where(function($q){
                    $q->whereHas('estoque', function($query){
                        $query->where('custo', 0)
                        ->orWhereNull('custo');
                    })
                    ->orWhereDoesntHave('estoque');                
                });
            }
            else{
                $produtosQuery->whereHas('estoque', function($query) use ($custo){
                    $query->where('custo', $custo);
                });
            }
        }
        else{

            $preco_real = $linha['preco_real'];
            $preco_dolar = $linha['preco_dolar'];
            $custo_gerencial = $linha['custo_gerencial'];
            
            unset($linha['preco_real'], 
            $linha['preco_dolar'], 
            $linha['compra_real'],
            $linha['compra_dolar'],
            $linha['custo_gerencial']); 

            $produtosQuery->where($linha);

            if(!empty((float) $preco_real) || !empty((float) $preco_dolar) || !empty((float) $custo_gerencial)){
                $produtosQuery->whereHas('preco', function($query) use($preco_real, $preco_dolar, $custo_gerencial){
                $query->where('preco_real', $preco_real)
                    ->where('preco_dolar', $preco_dolar)
                    ->where('compra_real', $custo_gerencial);
                });
            }
            else{
                $produtosQuery->where(function($query){
                    $query->whereDoesntHave('preco')
                    ->orWhereHas('preco', function($q){
                        $q->where(function($real){
                            $real->where('preco_real', 0)
                            ->orWhereNull('preco_real');
                        })
                        ->where(function($dolar){
                            $dolar->where('preco_dolar', 0)
                            ->orWhereNull('preco_dolar');
                        })
                        ->where(function($compra){
                            $compra->where('compra_real', 0)
                            ->orWhereNull('compra_real');
                        });
                    });
                });
            }
        }

        $produtosObj = $produtosQuery->get();
        $PrecosLogObj = PrecosLog::with('criadoPor');
        $PrecosLogObj->whereIn('codigo_produto', $produtosObj->pluck('codigo_produto')->toArray());
        if($fields['preco'] === 'real'){
            $PrecosLogObj->whereColumn('preco_real_antigo', '!=', 'preco_real_novo')
                ->select([DB::raw("to_char(created_at, 'YYYY-MM-DD') as created_at") , 'created_by', 'preco_real_antigo', 'preco_real_novo'])
                ->groupby([DB::raw("to_char(created_at, 'YYYY-MM-DD')") , 'created_by', 'preco_real_antigo', 'preco_real_novo']);
        }
        if($fields['preco'] === 'dolar'){
            $PrecosLogObj->whereColumn('preco_dolar_antigo', '!=', 'preco_dolar_novo')
                ->select([DB::raw("to_char(created_at, 'YYYY-MM-DD') as created_at") , 'created_by', 'preco_dolar_antigo', 'preco_dolar_novo'])
                ->groupby([DB::raw("to_char(created_at, 'YYYY-MM-DD')") , 'created_by', 'preco_dolar_antigo', 'preco_dolar_novo']);
        }
        $retorno = [];
        foreach($PrecosLogObj->get() as $log){
            $valor_antigo = '';
            $valor_novo = '';
            if($fields['preco'] === 'real'){
                $valor_antigo = $log['preco_real_antigo'];
                $valor_novo = $log['preco_real_novo'];
            }
            if($fields['preco'] === 'dolar'){
                $valor_antigo = $log['preco_dolar_antigo'];
                $valor_novo = $log['preco_dolar_novo'];
            }
            $retorno[] = [
                'data_alteracao' => parserData($log->created_at),
                'usuario' => $log->criadoPor->name,
                'valor_antigo' => parserValor($valor_antigo),
                'valor_novo' => parserValor($valor_novo)
            ];
        }
        
        return view('programs.atualizacao_preco.modal.historico_alteracoes')->with(['retorno' => $retorno]);
    }

    public function importacaoUltimaCompraDolar(){

        $PedidoComprasAssociacaoNotaNasajonObj = new PedidoComprasAssociacaoNotaNasajon();
        $ComprasNasajonObj = new ComprasNasajon();
        $NotasEntradasItensNasajonObj = new NotasEntradasItensNasajon();

        $sqlBusca = "	
            with notas as (
                select
                    {$NotasEntradasItensNasajonObj->getTable()}.\"Data de Entrada\" as data_entrada,
                    {$NotasEntradasItensNasajonObj->getTable()}.\"Item - Código\",
                    {$PedidoComprasAssociacaoNotaNasajonObj->getTable()}.id_pedido
                from
                    {$NotasEntradasItensNasajonObj->getTable()}
                    left join {$PedidoComprasAssociacaoNotaNasajonObj->getTable()} on ({$PedidoComprasAssociacaoNotaNasajonObj->getTable()}.id_nota = {$NotasEntradasItensNasajonObj->getTable()}.\"Identificador Documento\")
                where
                    \"Estabelecimento\" = '03'
                    and {$PedidoComprasAssociacaoNotaNasajonObj->getTable()}.id_pedido is not null
                group by 
                    {$NotasEntradasItensNasajonObj->getTable()}.\"Data de Entrada\",
                    {$NotasEntradasItensNasajonObj->getTable()}.\"Item - Código\",
                    {$PedidoComprasAssociacaoNotaNasajonObj->getTable()}.id_pedido
            )
            select
                {$ComprasNasajonObj->getTable()}.cod_produto as produto_codigo,
                {$ComprasNasajonObj->getTable()}.preco_compra_unitario as preco,
                notas.data_entrada as data_entrada
            from
                notas
                inner join {$ComprasNasajonObj->getTable()} on ({$ComprasNasajonObj->getTable()}.id_nota = notas.id_pedido and {$ComprasNasajonObj->getTable()}.cod_produto = notas.\"Item - Código\")
            order by 
                {$ComprasNasajonObj->getTable()}.cod_produto,
                notas.data_entrada asc
        ";
        $produtos = [];
        $buscaProdutos = collect(DB::connection('nasajon')->select($sqlBusca));
        $buscaProdutos->each(function($compra) use (&$produtos){
            $data = Carbon::parse($compra->data_entrada);
            if(!isset($produtos[$compra->produto_codigo])){
                $produtos[$compra->produto_codigo] = [
                    'data' => $data,
                    'valor' => $compra->preco
                ];
            }elseif($produtos[$compra->produto_codigo]['data']->lte($data)){
                $produtos[$compra->produto_codigo] = [
                    'data' => $data,
                    'valor' => $compra->preco
                ];
            }
        });
        unset($buscaProdutos);
        foreach($produtos as $produto => $dados){
            $PrecoObj = Preco::where('codigo_produto', $produto)->first();
            $PrecoObj->compra_dolar  = $dados['valor'];
            $PrecoObj->ultima_compra_dolar  = $dados['data']->format('Y-m-d');
            $PrecoObj->save();
        }
    }

    public function modalAtualizacaoEmMassa(){
        return view('programs.atualizacao_preco.modal.atualizacao_massa');
    }

    public function buscaAtualizacaoEmMassa(Request $request){
        $fields = $request->only('hash', 'preco_real', 'preco_dolar', 'compra_real', 'atualiza_grupo_subgrupo', 'campo', 'origem');

    }

    public function salvarAtualizacaoEmMassa(PrecoAtualizacaEmMassaRequest $request){
        $fields = $request->only('grupo', 'subgrupo', 'marca', 'linha', 'preco_novo', 'porcentagem');


        $ProdutoEspecificacaoObj = ProdutoEspecificacao::with(['preco'])
        ->where('ativo', true);
        if(strtolower($fields['grupo']) != 'todos'){
            $ProdutoEspecificacaoObj->where('grupo', 'ilike', $fields['grupo']);
        }
        if(strtolower($fields['subgrupo']) != 'todos'){
            $ProdutoEspecificacaoObj->where('subgrupo', 'ilike', $fields['subgrupo']);
        }
        if(strtolower($fields['marca']) != 'todos'){
            $ProdutoEspecificacaoObj->where('marca', 'ilike', $fields['marca']);
        }
        if(strtolower($fields['linha']) != 'todos'){
            $ProdutoEspecificacaoObj->where('linha', 'ilike', $fields['linha']);
        }
        $produtos = $ProdutoEspecificacaoObj->get();

        $contador = 0;
        $codigos_produtos = [];
        
        $produtos->each(function($produto) use (&$contador, $fields, &$codigos_produtos){
            $PrecosLogObj = new PrecosLog();
            $PrecosLogObj->codigo_produto = $produto->codigo_produto;
            $PrecosLogObj->created_by = Auth::id();
            $preco_real_novo = $produto->preco->preco_real;
            if(!empty($fields['preco_novo']) && !empty(parserNumber($fields['preco_novo']))){
                $preco_real_novo = parserNumber($fields['preco_novo']);
            }else{
                $porcentagem = parserNumber($fields['porcentagem']);
                $porcentagem = $porcentagem / 100;
                $porcentagem = (1 + $porcentagem);
                $preco_real_novo = $produto->preco->preco_real * $porcentagem;
            }

            if($produto->preco->preco_real != $preco_real_novo){
                $PrecosLogObj->preco_real_antigo = (float) $produto->preco->preco_real;
                $PrecosLogObj->preco_real_novo = (float) $preco_real_novo;
                $PrecosLogObj->save();

                $produto->preco->preco_real = $preco_real_novo;
    
                $produto->preco->updated_by = Auth::id();
                $produto->push();
    
                $contador++;

                try {
                    $result = DB::connection('nasajon')->select('select integracoes.api_alteracaoproduto (?, ?)', ['{'. $produto->codigo_produto .'}', $preco_real_novo]);
                } catch (\Exception $e) {
                    $error = [
                        'status' => 'error',
                        'message' => 'Erro na atualização do Nasajon. Contate o setor de TI',
                        'error' => $e->getMessage(),
                        'response' => []
                    ];
                    return response()->json($error, 422);
                }
            }

        });
        
        $mail_result = '';
        if($contador > 0){
            $body = '<p>Os preços dos seguintes produtos foram alterados:  <br>
            <strong>Grupo:</strong> '. $fields['grupo'] .' - <strong>Subgrupo:</strong> '. $fields['subgrupo'] .'<br>
            <strong>Marca:</strong> '. $fields['marca'] .' - <strong>Linha:</strong> '. $fields['linha'] .'<br>';
            if(empty( Auth::user()->name )){
                $body .= '<strong>Alterado por:</strong> Sistema - <strong>Data e hora:</strong>  '. date('d/m/Y H:i:s') .' <br></p>';
            }else{
                $body .= '<strong>Alterado por:</strong> '. Auth::user()->name .' - <strong>Data e hora:</strong>  '. date('d/m/Y H:i:s') .' <br></p>';
            }
            

            if(!empty($fields['preco_novo']) && !empty(parserNumber($fields['preco_novo']))){
                $body .= '<p><strong>Preço em real atualizado:</strong> '.$fields['preco_novo'].'</p>';
            }else{
                $body .= '<p><strong>Porcentagem atualizada:</strong> '.$fields['porcentagem'].'</p>';
            }

            $body .= '<p><strong>Obs.:</strong> Preço FOB à vista 12% para Nacional e 4% para Importado.</p>'; 

            $emailControllerObj = new EmailController;
            $mail_result = $emailControllerObj->sendEmailToken('01', 'alteracao_preco_portal', [], ['body' => $body]);
        }

        return response()->json([
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ]);
    }

	public function produtosSemGerencial(){
		Excel::store(new ProdutosSemGerencialExport, 'produtos_sem_gerencial.xls');
		$EmailObj = new EmailController();
		$returnEmail = $EmailObj->sendEmailToken('00', "produtos_sem_gerencial", [], [], ['produtos_sem_gerencial.xls' => ['as' => 'produtos_sem_gerencial.xls', 'mime' => 'application/vnd.ms-excel']], []);
        Storage::delete('produtos_sem_gerencial.xls');
	}

	public function produtosGerencialManorContabil(){
        ini_set('memory_limit','2024M');
		Excel::store(new ProdutosGerencialMenorContabilExport, 'produtos_gerencial_menor_contabil.xls');
		$EmailObj = new EmailController();
		$returnEmail = $EmailObj->sendEmailToken('00', "produtos_gerencial_menor_contabil", [], [], ['produtos_gerencial_menor_contabil.xls' => ['as' => 'produtos_gerencial_menor_contabil.xls', 'mime' => 'application/vnd.ms-excel']], []);
        Storage::delete('produtos_gerencial_menor_contabil.xls');
	}

    public function produtosSemContabil(){
        ini_set('memory_limit','2024M');
		Excel::store(new ProdutoSemCustoContabilExport, 'produtos_sem_contabil.xls');
		$EmailObj = new EmailController();
		$returnEmail = $EmailObj->sendEmailToken('00', "produtos_sem_contabil", [], [], ['produtos_sem_contabil.xls' => ['as' => 'produtos_sem_contabil.xls', 'mime' => 'application/vnd.ms-excel']], []);
        Storage::delete('produtos_sem_contabil.xls');
	}

    public function produtosCustoContabilMenorCustoGerencial(){
        ini_set('memory_limit','2024M');
		Excel::store(new ProdutosCustoContabilMaiorCustoGerencialExport, 'produtos_contabil_menor_gerencial.xls');
		$EmailObj = new EmailController();
		$returnEmail = $EmailObj->sendEmailToken('00', "produtos_contabil_maior_gerencial", [], [], ['produtos_contabil_menor_gerencial.xls' => ['as' => 'produtos_contabil_menor_gerencial.xls', 'mime' => 'application/vnd.ms-excel']], []);
        Storage::delete('produtos_contabil_menor_gerencial.xls');
	}

    public function produtosSemBook(){
        ini_set('memory_limit','2024M');
        Excel::store(new ProdutosSemBookExport, 'produtos_sem_book.xls');
        $EmailObj = new EmailController();
        $returnEmail = $EmailObj->sendEmailToken('00', "produtos_sem_book", [], [], ['produtos_sem_book.xls' => ['as' => 'produtos_sem_book.xls', 'mime' => 'application/vnd.ms-excel']], []);
        Storage::delete('produtos_sem_book.xls');
	}

    public function atualizacaoPrecoMargem(){
        $query_linha = ProdutoLinha::select();
        $query_linha->whereIn('id', [117, 127, 132, 129]);
        $result_linha = $query_linha->get();

        $query = Preco::select();
        $query->where('preco_real', '<', DB::Raw('((compra_real * 1.43) - 0.01)'));
        $query->whereHas('especificacoes', function($query) use($result_linha){
            $query->whereNotIn('linha', $result_linha->pluck('descricao'));           
            $query->where('ativo', true);
        });
        $query->with(['especificacoes' => function($query) use($result_linha){
            $query->whereNotIn('linha', $result_linha->pluck('descricao'));           
            $query->where('ativo', true);
        }]);
        /*$query->whereHas('estoque', function($query){
            $query->where('estoque', '>', 0);
        });*/
        $query->whereColumn('ultima_compra_real', '>', 'updated_at');
        $query->where('ultima_compra_real', '>', '2022-10-01');
        $result = $query->get();

        $margem = PrecoMargem::select()->first()->valor;
        $margem = 1 + ($margem / 100);

        $body = '<p>Os preços dos seguintes produtos foram alterados: <br><br>';
        
        foreach($result as $preco){
            
            $arr = [];
            $arr["hash"] = encrypt([
                "grupo" => $preco->especificacoes->grupo,
                "subgrupo" => $preco->especificacoes->subgrupo,
                "marca" => $preco->especificacoes->marca,
                "linha" => $preco->especificacoes->linha,
                "ativo" => $preco->especificacoes->ativo,
                "codigo_produto" => $preco->codigo_produto,
                "descricao" => $preco->especificacoes->descricao,
                "preco_real" => $preco->preco_real,
                "preco_dolar" => $preco->preco_dolar,
                "custo_gerencial" => $preco->compra_real,
            ]);
            $arr["preco_real"] = parserValor($preco->compra_real * $margem);
            $arr["preco_dolar"] = parserValor($preco->preco_dolar);
            $arr["compra_real"] = parserValor($preco->compra_real);
            $arr["atualiza_grupo_subgrupo"] = "agrupado";
            $arr["origem"] = "atualizacao_margem";
            $arr["campo"] = "real";

            $request = new Request($arr);

            $historicoAtualizacaoAutomaticaMargemObj = new HistoricoAtualizacaoAutomaticaMargem;
            $historicoAtualizacaoAutomaticaMargemObj->produto_codigo = $preco->codigo_produto;
            $historicoAtualizacaoAutomaticaMargemObj->preco_venda_anterior = $preco->preco_real;
            $historicoAtualizacaoAutomaticaMargemObj->preco_venda_atualizado = $preco->compra_real * $margem;
            $historicoAtualizacaoAutomaticaMargemObj->preco_compra = $preco->compra_real;
            $historicoAtualizacaoAutomaticaMargemObj->created_by = 1;
            $historicoAtualizacaoAutomaticaMargemObj->save();

            $body .= '<strong>Produto:</strong> ' . $preco->codigo_produto . ' - ' . $preco->especificacoes->descricao . '  <br>';

            $body .= '<strong>Alterado por:</strong> Sistema - <strong>Data e hora:</strong>  '. date('d/m/Y H:i:s') .' <br>'; 

            $body .= '<strong>Preço em real anterior:</strong> '. parserValor($preco->preco_real) .' - <strong>Preço em real atualizado:</strong> '. parserValor($preco->compra_real * $margem) .'</p>';
    
            $this->atualizaPreco($request);
        }

        $body .= '<p><strong>Obs.:</strong> Preço FOB à vista 12% para Nacional e 4% para Importado.</p>'; 

        $emailControllerObj = new EmailController;

        $mail_result = $emailControllerObj->sendEmailToken('01', 'alteracao_preco_portal', [], ['body' => $body]);

    }

    public function atualizacaoPrecoPorDePara(){
        $data_atual = Carbon::now();
        $query = AjusteEstoqueNasajon::where('codigo_estabelecimento','<>','20');
        $query->where('historico','ilike' ,'Reclassificação de Peças - De%');
        $query->where('tipo','ilike' ,'Entrada');
        $query->where('data', $data_atual);
        $result = $query->get();

        foreach($result as $value){
            $dados = $value->historico;
            $dados = str_replace("Reclassificação de Peças - De", "", $dados );
            $dados = str_replace("Para", "", $dados );
            $dados = trim($dados);
            $dados = explode(" ", $dados);
            $de_codigo = '';
            $para_codigo = '';
            foreach($dados as $dado){
                if(!empty(trim($dado))){
                    if(empty($de_codigo)){
                        $de_codigo = $dado;
                    }else{
                        $para_codigo = $dado;
                    }                    
                }
            }

            $precoDeObj = Preco::select()->where('codigo_produto', $de_codigo)->first();
            $precoParaObj = Preco::select()->where('codigo_produto', $para_codigo)->first();

            if($precoParaObj->preco_real < $precoDeObj->preco_real){
                $precoAlteracaoPorDeParaObj = new PrecoAlteracaoPorDePara;
                $precoAlteracaoPorDeParaObj->produto_codigo_de = $de_codigo;
                $precoAlteracaoPorDeParaObj->produto_codigo_para = $para_codigo;
                $precoAlteracaoPorDeParaObj->preco_venda_de = $precoDeObj->preco_real;
                $precoAlteracaoPorDeParaObj->preco_venda_para = $precoParaObj->preco_real;
                $precoAlteracaoPorDeParaObj->preco_venda_novo_para = $precoDeObj->preco_real;
                $precoAlteracaoPorDeParaObj->save();
    
                $arr = [];
                $arr["hash"] = encrypt([
                    "grupo" => $precoParaObj->especificacoes->grupo,
                    "subgrupo" => $precoParaObj->especificacoes->subgrupo,
                    "marca" => $precoParaObj->especificacoes->marca,
                    "linha" => $precoParaObj->especificacoes->linha,
                    "ativo" => $precoParaObj->especificacoes->ativo,
                    "codigo_produto" => $precoParaObj->codigo_produto,
                    "descricao" => $precoParaObj->especificacoes->descricao,
                    "preco_real" => $precoParaObj->preco_real,
                    "preco_dolar" => $precoParaObj->preco_dolar,
                    "custo_gerencial" => $precoParaObj->compra_real,
                ]);
                $arr["preco_real"] = parserValor($precoDeObj->preco_real);
                $arr["preco_dolar"] = parserValor($precoDeObj->preco_dolar);
                $arr["compra_real"] = parserValor($precoDeObj->compra_real);
                $arr["atualiza_grupo_subgrupo"] = "agrupado";
                $arr["origem"] = "de_para";
                $arr["campo"] = "real";
                $arr["produto_de"] = $precoDeObj->codigo_produto.' - '.$precoDeObj->especificacoes->descricao;
                $request = new Request($arr);
    
                $this->atualizaPreco($request);
            }
        }
    }
}
