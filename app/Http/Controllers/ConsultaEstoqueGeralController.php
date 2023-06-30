<?php

namespace App\Http\Controllers;

use Auth;
use App\FracaoNasajon;
use App\ProdutoNasajon;
use App\ProdutosEstoque;
use Illuminate\Http\Request;

use App\EstoquePoderTerceiro;
use App\NasajonEstabelecimento;
use App\FracaoDisponivelNasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\LocalDeEstoqueEnderecoNasajon;
use Illuminate\Support\Facades\Storage;

class ConsultaEstoqueGeralController extends Controller
{
    private $consultaEstoqueGeral;

    public function index(Request $request)
    {
        if (Auth::user()->hasPermissionTo("programas App\ConsultaEstoqueGeral") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaEstoqueGeral');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        return view('programs.consulta_estoque_geral.index', ['estabelecimentos' => $estabelecimentos]);
    }

    public function consulta(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $fields = $request->only('estabelecimento', 'grupo', 'linha', 'marca', 'subgrupo');
        
        $consultaEstoqueGeral =  ProdutosEstoque::with(['especificacao' => function ($query) {
            $query->select('codigo_produto', 'unidade', 'peso');
        }, 'precos', 'custoPortal'])
            ->where('estoque', '>', 0);

        if (!empty($fields['estabelecimento'])) {
            $consultaEstoqueGeral->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }
        
         if (
            (isset($fields['grupo']) && !empty($fields['grupo'])) ||
            (isset($fields['subgrupo']) && !empty($fields['subgrupo'])) ||
            (isset($fields['linha']) && !empty($fields['linha'])) ||
            (isset($fields['marca']) && !empty($fields['marca']))
        ) {
            $consultaEstoqueGeral->whereHas('especificacao', function ($query) use ($fields) {

                $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
                if (!empty($fields['grupo'])) {
                    $query->where('produto_grupos.descricao', 'ilike', $fields['grupo']);
                }
                if (!empty($fields['subgrupo'])) {
                    $query->where('subgrupo', 'ilike', $fields['subgrupo']);
                }
                if (!empty($fields['linha'])) {
                    $query->where('linha', 'ilike', $fields['linha']);
                }
                if (!empty($fields['marca'])) {
                    $query->where('marca', 'ilike', $fields['marca']);
                }
                $query->where('ativo', true);
            });
        } else {
            $consultaEstoqueGeral->whereHas('especificacao', function ($query) {
                $query->where('ativo', true);
            });
        }


        $result  = $consultaEstoqueGeral->get();   

        $resultado = [];
        $result->each(function ($produto) use (&$resultado) {
        
            if (isset($produto->especificacao)) {
                if($produto->estoque > 0){
                    $estabelecimento = str_pad($produto->estabelecimento, 2, '0', STR_PAD_LEFT);
                    if ($produto->precos->isNotEmpty()) {
                        $ultima_compra = $produto->precos[0]->compra_real;
                    } else {
                        $ultima_compra = 0;
                    }
    
                    if (!isset($resultado[$estabelecimento]['valor_custo_contabil_nasajon'])) {
                        $resultado[$estabelecimento]['valor_custo_contabil_nasajon'] = $produto->custo * $produto->estoque;
                    } else {
                        $resultado[$estabelecimento]['valor_custo_contabil_nasajon'] += $produto->custo * $produto->estoque;
                    }
    
                    if (!isset($resultado[$estabelecimento]['valor_custo_gerencial'])) {
                        $resultado[$estabelecimento]['valor_custo_gerencial'] = $ultima_compra * $produto->estoque;
                    } else {
                        $resultado[$estabelecimento]['valor_custo_gerencial'] += $ultima_compra * $produto->estoque;
                    }
    
                    $custo_produto = [];
                    if ($produto->custoPortal->isNotEmpty()) {
                        $custo_produto = $produto->custoPortal->where('estabelecimento', $estabelecimento)->first();
                    }
                    if (!isset($resultado[$estabelecimento]['valor_custo_contabil_portal'])) {
                        $resultado[$estabelecimento]['valor_custo_contabil_portal'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_contabil_portal'] = $custo_produto->custo_medio_contabil * $produto->estoque;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_contabil_portal'] += $custo_produto->custo_medio_contabil * $produto->estoque;
                        }
                    }
                    if (!isset($resultado[$estabelecimento]['valor_custo_gerencial_portal'])) {
                        $resultado[$estabelecimento]['valor_custo_gerencial_portal'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_gerencial_portal'] = $custo_produto->custo_medio_gerencial * $produto->estoque;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_gerencial_portal'] += $custo_produto->custo_medio_gerencial * $produto->estoque;
                        }
                    }
                    if (!isset($resultado[$estabelecimento]['valor_custo_armazem'])) {
                        $resultado[$estabelecimento]['valor_custo_armazem'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_armazem'] = $custo_produto->custo_medio_armazem * $produto->estoque;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_armazem'] += $custo_produto->custo_medio_armazem * $produto->estoque;
                        }
                    }
                    if (!isset($resultado[$estabelecimento]['valor_custo_armazem_ultimo'])) {
    
                        $resultado[$estabelecimento]['valor_custo_armazem_ultimo'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_armazem_ultimo'] = $custo_produto->custo_armazem * $produto->estoque;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$estabelecimento]['valor_custo_armazem_ultimo'] += $custo_produto->custo_armazem * $produto->estoque;
                        }
                    }
                   
                    $unidade = 'M';
                    if (isset($produto->especificacao->unidade)) {
                        $unidade = strtoupper(trim($produto->especificacao->unidade));
                    }
                    if ($unidade == 'KG') {
                        if (!isset($resultado[$estabelecimento]['peso'])) {
                            $resultado[$estabelecimento]['peso'] = $produto->estoque;
                        } else {
                            $resultado[$estabelecimento]['peso'] += $produto->estoque;
                        }
    
                  
                    } else if (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) {
                        if (!isset($resultado[$estabelecimento]['metros'])) {
                            $resultado[$estabelecimento]['metros'] = $produto->estoque;
                        } else {
                            $resultado[$estabelecimento]['metros'] += $produto->estoque;
                        }
    
                  
                    }else {
                        if (!isset($resultado[$estabelecimento]['outras_unidades'])) {
                            $resultado[$estabelecimento]['outras_unidades'] = $produto->estoque;
                        } else {
                            $resultado[$estabelecimento]['outras_unidades'] += $produto->estoque;
                        }
                    }
                    if (!isset($resultado[$estabelecimento]['rolos'])) {
                        $resultado[$estabelecimento]['rolos'] =0;
                    }
                }
            }
        });

        $fracionado = FracaoDisponivelNasajon::select();
        $fracionado->with(['produtoDadosPortal' => function($query) use($fields){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if (!empty($fields['grupo'])) {
                $query->where('produto_grupos.descricao', 'ilike', $fields['grupo']);
            }
            if (!empty($fields['subgrupo'])) {
                $query->where('subgrupo', 'ilike', $fields['subgrupo']);
            }
            if (!empty($fields['linha'])) {
                $query->where('linha', 'ilike', $fields['linha']);
            }
            if (!empty($fields['marca'])) {
                $query->where('marca', 'ilike', $fields['marca']);
            }
            $query->where('ativo', true);
        }]);
        if (!empty($fields['estabelecimento'])) {
            $fracionado->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }
        $fracionado = $fracionado->get();

        foreach ($fracionado as $obj) {  
            if(!empty($obj->produtoDadosPortal)){
                if (!isset( $resultado[$obj->estabelecimento_codigo]['rolos'])) {
                    $resultado[$obj->estabelecimento_codigo]['rolos'] = 0;
                }
    
                $resultado[$obj->estabelecimento_codigo]['rolos']++;
            }
        }

        $estoque_terceiro =  EstoquePoderTerceiro::with('produtoDetalhe')->where('saldo_em_terceiro', '>', 0);
        $estoque_terceiro->with(['produtoDetalhe' => function($query) use($fields){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if (!empty($fields['grupo'])) {
                $query->where('produto_grupos.descricao', 'ilike', $fields['grupo']);
            }
            if (!empty($fields['subgrupo'])) {
                $query->where('subgrupo', 'ilike', $fields['subgrupo']);
            }
            if (!empty($fields['linha'])) {
                $query->where('linha', 'ilike', $fields['linha']);
            }
            if (!empty($fields['marca'])) {
                $query->where('marca', 'ilike', $fields['marca']);
            }
        }, 'precos', 'custoPortal', 'estoque']);
        $estoque_terceiro->whereHas('produtoDetalhe', function($query) use($fields){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if (!empty($fields['grupo'])) {
                $query->where('produto_grupos.descricao', 'ilike', $fields['grupo']);
            }
            if (!empty($fields['subgrupo'])) {
                $query->where('subgrupo', 'ilike', $fields['subgrupo']);
            }
            if (!empty($fields['linha'])) {
                $query->where('linha', 'ilike', $fields['linha']);
            }
            if (!empty($fields['marca'])) {
                $query->where('marca', 'ilike', $fields['marca']);
            }
        });

        $estoque_terceiro->each(function ($produto) use (&$resultado) {
            $estabelecimento = 'Em Poder Terceiro';
            $estabelecimento_origem = str_pad($produto->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);

            if ($produto->precos->isNotEmpty()) {
                $ultima_compra = $produto->precos[0]->compra_real;
            } else {
                $ultima_compra = 0;
            }
            $custo_nasajon = 0;
            if ($produto->estoque->isNotEmpty()) {
                $custo_nasajon = $produto->estoque->where('estabelecimento', $estabelecimento_origem)->first();
                $custo_nasajon = empty($custo_nasajon)? 0 : $custo_nasajon->custo;
            }
            if (!isset($resultado[$estabelecimento]['valor_custo_contabil_nasajon'])) {
                $resultado[$estabelecimento]['valor_custo_contabil_nasajon'] = $custo_nasajon * $produto->saldo_em_terceiro;
            } else {
                $resultado[$estabelecimento]['valor_custo_contabil_nasajon'] += $custo_nasajon * $produto->saldo_em_terceiro;
            }

            if (!isset($resultado[$estabelecimento]['valor_custo_gerencial'])) {
                $resultado[$estabelecimento]['valor_custo_gerencial'] = $ultima_compra * $produto->saldo_em_terceiro;
            } else {
                $resultado[$estabelecimento]['valor_custo_gerencial'] += $ultima_compra * $produto->saldo_em_terceiro;
            }

            $custo_produto = [];
            if ($produto->custoPortal->isNotEmpty()) {
                $custo_produto = $produto->custoPortal->where('estabelecimento', $estabelecimento_origem)->first();
            }
            if(empty($custo_produto)){
                $custo_contabil = 0;
            }else{
                $custo_contabil = $custo_produto->custo_medio_contabil;
            }
            if (!isset($resultado[$estabelecimento]['valor_custo_contabil_portal'])) {
                $resultado[$estabelecimento]['valor_custo_contabil_portal'] = $custo_contabil * $produto->saldo_em_terceiro;
            } else {
                $resultado[$estabelecimento]['valor_custo_contabil_portal'] += $custo_contabil* $produto->saldo_em_terceiro;
            }
            if (!isset($resultado[$estabelecimento]['valor_custo_gerencial_portal'])) {
                $resultado[$estabelecimento]['valor_custo_gerencial_portal'] = 0;
                if (!empty($custo_produto)) {
                    $resultado[$estabelecimento]['valor_custo_gerencial_portal'] = $custo_produto->custo_medio_gerencial * $produto->saldo_em_terceiro;
                }
            } else {
                if (!empty($custo_produto)) {
                    $resultado[$estabelecimento]['valor_custo_gerencial_portal'] += $custo_produto->custo_medio_gerencial * $produto->saldo_em_terceiro;
                }
            }
            if (!isset($resultado[$estabelecimento]['valor_custo_armazem'])) {
                $resultado[$estabelecimento]['valor_custo_armazem'] = 0;
                if (!empty($custo_produto)) {
                    $resultado[$estabelecimento]['valor_custo_armazem'] = $custo_produto->custo_medio_armazem * $produto->saldo_em_terceiro;
                }
            } else {
                if (!empty($custo_produto)) {
                    $resultado[$estabelecimento]['valor_custo_armazem'] += $custo_produto->custo_medio_armazem * $produto->saldo_em_terceiro;
                }
            }
            if (!isset($resultado[$estabelecimento]['valor_custo_armazem_ultimo'])) {

                $resultado[$estabelecimento]['valor_custo_armazem_ultimo'] = 0;
                if (!empty($custo_produto)) {
                    $resultado[$estabelecimento]['valor_custo_armazem_ultimo'] = $custo_produto->custo_armazem * $produto->saldo_em_terceiro;
                }
            } else {
                if (!empty($custo_produto)) {
                    $resultado[$estabelecimento]['valor_custo_armazem_ultimo'] += $custo_produto->custo_armazem * $produto->saldo_em_terceiro;
                }
            }
        
            $unidade = 'M';
            if (isset($produto->produtoDetalhe->unidade)) {
                $unidade = strtoupper(trim($produto->produtoDetalhe->unidade));
            }
            if ($unidade == 'KG') {
                if (!isset($resultado[$estabelecimento]['peso'])) {
                    $resultado[$estabelecimento]['peso'] = $produto->saldo_em_terceiro;
                } else {
                    $resultado[$estabelecimento]['peso'] += $produto->saldo_em_terceiro;
                }

        
            } else if (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) {
                if (!isset($resultado[$estabelecimento]['metros'])) {
                    $resultado[$estabelecimento]['metros'] = $produto->saldo_em_terceiro;
                } else {
                    $resultado[$estabelecimento]['metros'] += $produto->saldo_em_terceiro;
                }

        
            }else {
                if (!isset($resultado[$estabelecimento]['outras_unidades'])) {
                    $resultado[$estabelecimento]['outras_unidades'] = $produto->saldo_em_terceiro;
                } else {
                    $resultado[$estabelecimento]['outras_unidades'] += $produto->saldo_em_terceiro;
                }
            }
            if (!isset($resultado[$estabelecimento]['rolos'])) {
                $resultado[$estabelecimento]['rolos'] =0;
            }
        });

        $empresas = returnEmpresasNasajonView();

        $metros_final_total = 0;
        $peso_final_total = 0;
        $custo_contabil_total_nasajon = 0;
        $custo_contabil_total_portal = 0;
        $custo_gerencial_total_portal = 0;
        $custo_gerencial_total = 0;
        $outras_unidades_total = 0;
        $custo_armazem_total = 0;
        $custo_armazem_total_ultimo = 0;
        $rolos_final_total = 0;

        $response = [];
      
        foreach ($resultado as $key => $estabelecimento) {
 
            if($key == 'Em Poder Terceiro'){
                $response[$key]['empresa'] = "<a href='#' onclick=\"detalhe('" . $key . "', '" . ($fields['grupo'] ?? '') . "', '" . ($fields['subgrupo'] ?? '') . "', '" . ($fields['linha'] ?? '') . "', '" . ($fields['marca'] ?? '') . "', 'Em Poder Terceiro')\">Em Poder Terceiro</a>";
            }else{
                $response[$key]['empresa'] = "<a href='#' onclick=\"detalhe('" . $key . "', '" . ($fields['grupo'] ?? '') . "', '" . ($fields['subgrupo'] ?? '') . "', '" . ($fields['linha'] ?? '') . "', '" . ($fields['marca'] ?? '') . "', '" . $empresas[intval($key)] . "')\">" . $empresas[intval($key)] . "</a>";
            }

            if (isset($estabelecimento['valor_custo_contabil_nasajon'])) {
                $response[$key]['valor_custo_contabil_nasajon'] = parserValor($estabelecimento['valor_custo_contabil_nasajon']);
                $custo_contabil_total_nasajon += $estabelecimento['valor_custo_contabil_nasajon'];
            } else {
                $response[$key]['valor_custo_contabil_nasajon'] = 0;
            }

            if (isset($estabelecimento['valor_custo_contabil_portal'])) {
                $response[$key]['valor_custo_contabil_portal'] = parserValor($estabelecimento['valor_custo_contabil_portal']);
                $custo_contabil_total_portal += $estabelecimento['valor_custo_contabil_portal'];
            } else {
                $response[$key]['valor_custo_contabil_portal'] = 0;
            }

            if (isset($estabelecimento['valor_custo_gerencial'])) {
                $response[$key]['valor_custo_gerencial'] = parserValor($estabelecimento['valor_custo_gerencial']);
                $custo_gerencial_total += $estabelecimento['valor_custo_gerencial'];
            } else {
                $response[$key]['valor_custo_gerencial'] = 0;
            }

            if (isset($estabelecimento['valor_custo_gerencial_portal'])) {
                $response[$key]['valor_custo_gerencial_portal'] = parserValor($estabelecimento['valor_custo_gerencial_portal']);
                $custo_gerencial_total_portal += $estabelecimento['valor_custo_gerencial_portal'];
            } else {
                $response[$key]['valor_custo_gerencial_portal'] = 0;
            }
            if (isset($estabelecimento['valor_custo_armazem'])) {
                $response[$key]['valor_custo_armazem'] = empty($estabelecimento['valor_custo_armazem']) ? '' :  parserValor($estabelecimento['valor_custo_armazem']);
                $custo_armazem_total += $estabelecimento['valor_custo_armazem'];
            } else {
                $response[$key]['valor_custo_armazem'] = 0;
            }

            if (isset($estabelecimento['valor_custo_armazem_ultimo'])) {
                $response[$key]['valor_custo_armazem_ultimo'] = empty($estabelecimento['valor_custo_armazem_ultimo']) ? '' :  parserValor($estabelecimento['valor_custo_armazem_ultimo']);
                $custo_armazem_total_ultimo += $estabelecimento['valor_custo_armazem_ultimo'];
            } else {
                $response[$key]['valor_custo_armazem_ultimo'] = 0;
            }

            if (isset($estabelecimento['peso'])) {
                $response[$key]['peso'] = parserValor($estabelecimento['peso']);
                $peso_final_total += $estabelecimento['peso'];
            } else {
                $response[$key]['peso'] = 0;
            }

            if (isset($estabelecimento['metros'])) {
                $response[$key]['metros'] = parserValor($estabelecimento['metros']);
                $metros_final_total += $estabelecimento['metros'];
            } else {
                $response[$key]['metros'] = 0;
            }

            if (isset($estabelecimento['outras_unidades'])) {
                $response[$key]['outras_unidades'] = parserValor($estabelecimento['outras_unidades']);
                $outras_unidades_total += $estabelecimento['outras_unidades'];
            } else {
                $response[$key]['outras_unidades'] = 0;
            }

            if (isset($estabelecimento['rolos'])) {
                if($key != 'Em Poder Terceiro'){
                    $estabelecimento['rolos']=$estabelecimento['rolos'];
                    $response[$key]['rolos'] =parserValorInteiro ($estabelecimento['rolos']);
                     $rolos_final_total += $estabelecimento['rolos'];
                }else{
                    $response[$key]['rolos'] = 0;
                }
            } else {
                $response[$key]['rolos'] = 0;
            }
     

        }

        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'metros_total' => parserValor($metros_final_total),
                'peso_total' => parserValor($peso_final_total),
                'outras_unidades_total' => parserValor($outras_unidades_total),
                'rolos_total' => parserValorInteiro($rolos_final_total),
                'valor_custo_contabil_nasajon' => parserValor($custo_contabil_total_nasajon),
                'custo_contabil_portal_total' => parserValor($custo_contabil_total_portal),
                'custo_gerencial_total_portal' => parserValor($custo_gerencial_total_portal),
                'custo_gerencial_total' => parserValor($custo_gerencial_total),
                'custo_armazem_total' => parserValor($custo_armazem_total),
                'custo_armazem_total_ultimo' => parserValor($custo_armazem_total_ultimo),
                'dados' => $response
            ],
        ];

        return $return;
    }

    public function detalhes(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        $resultado  = [];

        $fields =  $request->only('estabelecimentos', 'grupos', 'subgrupos', 'linhas', 'marcas');

        $estabelecimentos = returnEmpresasNasajonView();

        if (!in_array($fields['estabelecimentos'], ['Em Poder Terceiro'])){
            $consultaEstoqueGeral = ProdutosEstoque::with('especificacao', 'especificacao.produtoGrupo', 'precos', 'custoPortal');
            $consultaEstoqueGeral->where('estoque', '>', 0);
                
            if (!in_array($fields['estabelecimentos'], [''])) {
                $consultaEstoqueGeral->where('estabelecimento', str_pad($fields['estabelecimentos'], 2, '0', STR_PAD_LEFT));
            }
    
            if (
                (isset($fields['grupos']) && !empty($fields['grupos'])) ||
                (isset($fields['subgrupos']) && !empty($fields['subgrupos'])) ||
                (isset($fields['linhas']) && !empty($fields['linhas'])) ||
                (isset($fields['marcas']) && !empty($fields['marcas']))
            ) {
                $consultaEstoqueGeral->whereHas('especificacao', function ($query) use ($fields) {
                    $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
                    if (!empty($fields['grupos'])) {
                        $query->where('produto_grupos.descricao', 'ilike', $fields['grupos']);
                    }
                    if (!empty($fields['subgrupos'])) {
                        $query->where('subgrupo', 'ilike', $fields['subgrupos']);
                    }
                    if (!empty($fields['linhas'])) {
                        $query->where('linha', 'ilike', $fields['linhas']);
                    }
                    if (!empty($fields['marcas'])) {
                        $query->where('marca', 'ilike', $fields['marcas']);
                    }
                    $query->where('ativo', true);
                });
            } else {
                $consultaEstoqueGeral->whereHas('especificacao', function ($query) {
                    $query->where('ativo', true);
                });
            }
    
            $result = $consultaEstoqueGeral->get();
    
            $totalCustoContabil = 0;
            $totalCustoGerencial = 0;
            $totalCustoUnidade = 0;
            $totalQtdUnidade = 0;
            $total_estoque_terceiro = 0;
            $totalCustoContabil_portal = 0;
            $totalCustoGerencial_portal = 0;
            $totalCustoArmazem = 0;
            $totalCustoArmazemUltimo = 0;
    
    
            $result->each(function ($estabelecimento) use (&$totalQtdUnidade, &$totalCustoUnidade, &$totalCustoContabil, &$totalCustoGerencial, &$totalCustoContabil_portal, &$totalCustoGerencial_portal, &$totalCustoArmazem, $totalCustoArmazemUltimo, $fields) {
    
                if (isset($estabelecimento->precos) && $estabelecimento->precos->isNotEmpty()) {
                    $ultima_compra = $estabelecimento->precos[0]->compra_real;
                } else {
                    $ultima_compra = 0;
                }
    
                $totalQtdUnidade += $estabelecimento->estoque;
                $totalCustoContabil += $estabelecimento->custo * $estabelecimento->estoque;
                $totalCustoGerencial += $ultima_compra * $estabelecimento->estoque;
                
                $codigo_estabelecimento = str_pad($estabelecimento->estabelecimento, 2, '0', STR_PAD_LEFT);
                $custo_produto = [];
                if ($estabelecimento->custoPortal->isNotEmpty()) {
                    $custo_produto = $estabelecimento->custoPortal->where('estabelecimento', $codigo_estabelecimento)->first();
                    if (isset($custo_produto->custo_medio_contabil)) {
                        $totalCustoContabil_portal += $custo_produto->custo_medio_contabil * $estabelecimento->estoque;
                        $totalCustoGerencial_portal += $custo_produto->custo_medio_gerencial * $estabelecimento->estoque;
                    }
                    if (isset($custo_produto->custo_medio_armazem)) {
                        $totalCustoArmazem += $custo_produto->custo_medio_armazem * $estabelecimento->estoque;
                    }
                    if (isset($custo_produto->custo_armazem)) {
                        $totalCustoArmazemUltimo += $custo_produto->custo_armazem * $estabelecimento->estoque;
                    }
                }
            });
    
            foreach ($estabelecimentos as $keyEstabelecimento => $empresas) {
                foreach ($result as $grupo) {
    
                    $codigoEstabelecimento = str_pad($keyEstabelecimento, 2, '0', STR_PAD_LEFT);
    
                    if ($grupo->estabelecimento == $codigoEstabelecimento) {
    
                        if (isset($grupo->precos) && $grupo->precos->isNotEmpty()) {
                            $ultima_compra = $grupo->precos[0]->compra_real;
                        } else {
                            $ultima_compra = 0;
                        }
    
                        $totalCustoUnidade   = ($grupo->custo + $grupo->estoque) / $grupo->estoque;
    
                        if (empty($grupo->especificacao)) {
                            $agrupamento = 'A CADASTRAR' . 'A CADASTRAR' . 'A CADASTRAR' . 'A CADASTRAR' . '0' . '0';
                            $resultado[$agrupamento]['estabelecimento'] = $empresas;
                            $resultado[$agrupamento]['grupo'] = 'A CADASTRAR';
                            $resultado[$agrupamento]['subgrupo'] = 'A CADASTRAR';
                            $resultado[$agrupamento]['linha'] = 'A CADASTRAR';
                            $resultado[$agrupamento]['marca'] = 'A CADASTRAR';
    
                            $resultado[$agrupamento]['unidade'] = 'UN';
    
                            $linha = [
                                'grupo' => 'A CADASTRAR',
                                'subgrupo' => 'A CADASTRAR',
                                'marca' => 'A CADASTRAR',
                                'linha' => 'A CADASTRAR',
                                'compra_real' => 0,
                                'custo' => 0,
                                'ativo' => true,
                                'estoque' => true
                            ];
    
                            $resultado[$agrupamento]['hash'] = Crypt::encrypt($linha);
                        } else {
                            $agrupamento = $grupo->especificacao->grupo . $grupo->especificacao->subgrupo . $grupo->especificacao->linha . $grupo->especificacao->marca . $ultima_compra . $grupo->custo;
                            $resultado[$agrupamento]['estabelecimento'] = $empresas;
                            $resultado[$agrupamento]['grupo'] = $grupo->especificacao->grupo;
                            $resultado[$agrupamento]['subgrupo'] = $grupo->especificacao->subgrupo;
                            $resultado[$agrupamento]['linha'] = $grupo->especificacao->linha;
                            $resultado[$agrupamento]['marca'] = $grupo->especificacao->marca;
    
                            $resultado[$agrupamento]['unidade'] = $grupo->especificacao->unidade;
    
                            $linha = [
                                'grupo' => ($grupo->especificacao->grupo),
                                'subgrupo' => ($grupo->especificacao->subgrupo),
                                'marca' => ($grupo->especificacao->marca),
                                'linha' => ($grupo->especificacao->linha),
                                'compra_real' => $ultima_compra,
                                'custo' => $grupo->custo,
                                'ativo' => true,
                                'estoque' => true
                            ];
    
                            $resultado[$agrupamento]['hash'] = Crypt::encrypt($linha);
                        }
    
                        if (!isset($resultado[$agrupamento]['custo_contabil'])) {
                            $resultado[$agrupamento]['custo_contabil'] = $grupo->custo * $grupo->estoque;
                        } else {
                            $resultado[$agrupamento]['custo_contabil'] += $grupo->custo * $grupo->estoque;
                        }
    
                        if (!isset($resultado[$agrupamento]['custo_gerencial'])) {
                            $resultado[$agrupamento]['custo_gerencial'] = $ultima_compra * $grupo->estoque;
                        } else {
                            $resultado[$agrupamento]['custo_gerencial'] += $ultima_compra * $grupo->estoque;
                        }
    
                        $resultado[$agrupamento]['custo_unitario_gerencial'] = $ultima_compra;
                        $resultado[$agrupamento]['custo_unitario_armazem'] = $ultima_compra;
                        $resultado[$agrupamento]['custo_armazem_ultimo'] = $ultima_compra;
    
                        if (!isset($resultado[$agrupamento]['qtd_unidade'])) {
                            $resultado[$agrupamento]['qtd_unidade'] = $grupo->estoque;
                        } else {
                            $resultado[$agrupamento]['qtd_unidade'] += $grupo->estoque;
                        }
    
                        $resultado[$agrupamento]['laudo'] = '';
    
                        $custo_produto = [];
                        if ($grupo->custoPortal->isNotEmpty()) {
                            $custo_produto = $grupo->custoPortal->where('estabelecimento', $codigoEstabelecimento)->first();
                        }
    
                        if (!isset($resultado[$agrupamento]['custo_contabil_portal'])) {
                            $resultado[$agrupamento]['custo_contabil_portal'] = 0;
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_contabil_portal'] = $custo_produto->custo_medio_contabil * $grupo->estoque;
                            }
                        } else {
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_contabil_portal'] += $custo_produto->custo_medio_contabil * $grupo->estoque;
                            }
                        }
                        if (!isset($resultado[$agrupamento]['custo_gerencial_portal'])) {
                            $resultado[$agrupamento]['custo_gerencial_portal'] = 0;
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_gerencial_portal'] = $custo_produto->custo_medio_gerencial * $grupo->estoque;
                            }
                        } else {
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_gerencial_portal'] += $custo_produto->custo_medio_gerencial * $grupo->estoque;
                            }
                        }
                        if (!isset($resultado[$agrupamento]['custo_armazem'])) {
                            $resultado[$agrupamento]['custo_armazem'] = 0;
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_armazem'] = $custo_produto->custo_medio_armazem * $grupo->estoque;
                            }
                        } else {
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_armazem'] += $custo_produto->custo_medio_armazem * $grupo->estoque;
                            }
                        }
                        if (!isset($resultado[$agrupamento]['custo_armazem_ultimo'])) {
                            $resultado[$agrupamento]['custo_armazem_ultimo'] = 0;
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_armazem_ultimo'] = $custo_produto->custo_armazem * $grupo->estoque;
                            }
                        } else {
                            if (!empty($custo_produto)) {
                                $resultado[$agrupamento]['custo_armazem_ultimo'] += $custo_produto->custo_armazem * $grupo->estoque;
                            }
                        }
                    }
                }
            }
        }else{
            $consultaEstoqueGeral = EstoquePoderTerceiro::with('produtoDetalhe', 'produtoDetalhe.produtoGrupo',  'precos', 'custoPortal');
            $consultaEstoqueGeral->where('saldo_em_terceiro', '>', 0);

            $consultaEstoqueGeral->whereHas('produtoDetalhe', function($query) use($fields){
                $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
                if (!empty($fields['grupos'])) {
                    $query->where('produto_grupos.descricao', 'ilike', $fields['grupos']);
                }
                if (!empty($fields['subgrupos'])) {
                    $query->where('subgrupo', 'ilike', $fields['subgrupos']);
                }
                if (!empty($fields['linhas'])) {
                    $query->where('linha', 'ilike', $fields['linhas']);
                }
                if (!empty($fields['marcas'])) {
                    $query->where('marca', 'ilike', $fields['marcas']);
                }
                $query->where('ativo', true);
            });
    
            $result = $consultaEstoqueGeral->get();
    
            $totalCustoContabil = 0;
            $totalCustoGerencial = 0;
            $totalCustoUnidade = 0;
            $totalQtdUnidade = 0;
            $total_estoque_terceiro = 0;
            $totalCustoContabil_portal = 0;
            $totalCustoGerencial_portal = 0;
            $totalCustoArmazem = 0;
            $totalCustoArmazemUltimo = 0;
    
    
            $result->each(function ($estabelecimento) use (&$totalQtdUnidade, &$totalCustoUnidade, &$totalCustoContabil, &$totalCustoGerencial, &$totalCustoContabil_portal, &$totalCustoGerencial_portal, &$totalCustoArmazem, $totalCustoArmazemUltimo, $fields) {
    
                if (isset($estabelecimento->precos) && $estabelecimento->precos->isNotEmpty()) {
                    $ultima_compra = $estabelecimento->precos[0]->compra_real;
                } else {
                    $ultima_compra = 0;
                }
    
                $totalQtdUnidade += $estabelecimento->saldo_em_terceiro;
                $totalCustoContabil += $estabelecimento->custo * $estabelecimento->saldo_em_terceiro;
                $totalCustoGerencial += $ultima_compra * $estabelecimento->saldo_em_terceiro;
                
                $codigo_estabelecimento = str_pad($estabelecimento->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);
                $custo_produto = [];
                if ($estabelecimento->custoPortal->isNotEmpty()) {
                    $custo_produto = $estabelecimento->custoPortal->where('estabelecimento', $codigo_estabelecimento)->first();
                    if (isset($custo_produto->custo_medio_contabil)) {
                        $totalCustoContabil_portal += $estabelecimento->valor_custo_contabil_portal * $estabelecimento->saldo_em_terceiro;
                        $totalCustoGerencial_portal += $custo_produto->custo_medio_gerencial * $estabelecimento->saldo_em_terceiro;
                    }
                    if (isset($custo_produto->custo_medio_armazem)) {
                        $totalCustoArmazem += $custo_produto->custo_medio_armazem * $estabelecimento->saldo_em_terceiro;
                    }
                    if (isset($custo_produto->custo_armazem)) {
                        $totalCustoArmazemUltimo += $custo_produto->custo_armazem * $estabelecimento->saldo_em_terceiro;
                    }
                }
            });

            foreach ($estabelecimentos as $keyEstabelecimento => $empresas) {
                foreach ($result as $grupo) {
                    $codigoEstabelecimento = str_pad($keyEstabelecimento, 2, '0', STR_PAD_LEFT);
    
                    if (isset($grupo->precos) && $grupo->precos->isNotEmpty()) {
                        $ultima_compra = $grupo->precos[0]->compra_real;
                    } else {
                        $ultima_compra = 0;
                    }

                    $totalCustoUnidade   = ($grupo->custo + $grupo->saldo_em_terceiro) / $grupo->saldo_em_terceiro;

                    if (empty($grupo->produtoDetalhe)) {
                        $agrupamento = 'A CADASTRAR' . 'A CADASTRAR' . 'A CADASTRAR' . 'A CADASTRAR' . '0' . '0';
                        $resultado[$agrupamento]['estabelecimento'] = $empresas;
                        $resultado[$agrupamento]['grupo'] = 'A CADASTRAR';
                        $resultado[$agrupamento]['subgrupo'] = 'A CADASTRAR';
                        $resultado[$agrupamento]['linha'] = 'A CADASTRAR';
                        $resultado[$agrupamento]['marca'] = 'A CADASTRAR';

                        $resultado[$agrupamento]['unidade'] = 'UN';

                        $linha = [
                            'grupo' => 'A CADASTRAR',
                            'subgrupo' => 'A CADASTRAR',
                            'marca' => 'A CADASTRAR',
                            'linha' => 'A CADASTRAR',
                            'compra_real' => 0,
                            'custo' => 0,
                            'ativo' => true,
                            'estoque' => true
                        ];

                        $resultado[$agrupamento]['hash'] = Crypt::encrypt($linha);
                    } else {
                        $agrupamento = $grupo->produtoDetalhe->grupo . $grupo->produtoDetalhe->subgrupo . $grupo->produtoDetalhe->linha . $grupo->produtoDetalhe->marca . $ultima_compra . $grupo->custo;
                        $resultado[$agrupamento]['estabelecimento'] = $empresas;
                        $resultado[$agrupamento]['grupo'] = $grupo->produtoDetalhe->grupo;
                        $resultado[$agrupamento]['subgrupo'] = $grupo->produtoDetalhe->subgrupo;
                        $resultado[$agrupamento]['linha'] = $grupo->produtoDetalhe->linha;
                        $resultado[$agrupamento]['marca'] = $grupo->produtoDetalhe->marca;

                        $resultado[$agrupamento]['unidade'] = $grupo->produtoDetalhe->unidade;

                        $linha = [
                            'grupo' => ($grupo->produtoDetalhe->grupo),
                            'subgrupo' => ($grupo->produtoDetalhe->subgrupo),
                            'marca' => ($grupo->produtoDetalhe->marca),
                            'linha' => ($grupo->produtoDetalhe->linha),
                            'compra_real' => $ultima_compra,
                            'custo' => $grupo->custo,
                            'ativo' => true,
                            'estoque' => true
                        ];

                        $resultado[$agrupamento]['hash'] = Crypt::encrypt($linha);
                    }

                    if (!isset($resultado[$agrupamento]['custo_contabil'])) {
                        $resultado[$agrupamento]['custo_contabil'] = $grupo->custo * $grupo->saldo_em_terceiro;
                    } else {
                        $resultado[$agrupamento]['custo_contabil'] += $grupo->custo * $grupo->saldo_em_terceiro;
                    }

                    if (!isset($resultado[$agrupamento]['custo_gerencial'])) {
                        $resultado[$agrupamento]['custo_gerencial'] = $ultima_compra * $grupo->saldo_em_terceiro;
                    } else {
                        $resultado[$agrupamento]['custo_gerencial'] += $ultima_compra * $grupo->saldo_em_terceiro;
                    }

                    $resultado[$agrupamento]['custo_unitario_gerencial'] = $ultima_compra;
                    $resultado[$agrupamento]['custo_unitario_armazem'] = $ultima_compra;
                    $resultado[$agrupamento]['custo_armazem_ultimo'] = $ultima_compra;

                    if (!isset($resultado[$agrupamento]['qtd_unidade'])) {
                        $resultado[$agrupamento]['qtd_unidade'] = $grupo->saldo_em_terceiro;
                    } else {
                        $resultado[$agrupamento]['qtd_unidade'] += $grupo->saldo_em_terceiro;
                    }

                    $resultado[$agrupamento]['laudo'] = '';

                    $custo_produto = [];
                    if ($grupo->custoPortal->isNotEmpty()) {
                        $custo_produto = $grupo->custoPortal->where('estabelecimento', $codigoEstabelecimento)->first();
                    }

                    if (!isset($resultado[$agrupamento]['custo_contabil_portal'])) {
                        $resultado[$agrupamento]['custo_contabil_portal'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_contabil_portal'] = $custo_produto->custo_medio_contabil * $grupo->saldo_em_terceiro;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_contabil_portal'] += $custo_produto->custo_medio_contabil * $grupo->saldo_em_terceiro;
                        }
                    }
                    if (!isset($resultado[$agrupamento]['custo_gerencial_portal'])) {
                        $resultado[$agrupamento]['custo_gerencial_portal'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_gerencial_portal'] = $custo_produto->custo_medio_gerencial * $grupo->saldo_em_terceiro;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_gerencial_portal'] += $custo_produto->custo_medio_gerencial * $grupo->saldo_em_terceiro;
                        }
                    }
                    if (!isset($resultado[$agrupamento]['custo_armazem'])) {
                        $resultado[$agrupamento]['custo_armazem'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_armazem'] = $custo_produto->custo_medio_armazem * $grupo->saldo_em_terceiro;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_armazem'] += $custo_produto->custo_medio_armazem * $grupo->saldo_em_terceiro;
                        }
                    }
                    if (!isset($resultado[$agrupamento]['custo_armazem_ultimo'])) {
                        $resultado[$agrupamento]['custo_armazem_ultimo'] = 0;
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_armazem_ultimo'] = $custo_produto->custo_armazem * $grupo->saldo_em_terceiro;
                        }
                    } else {
                        if (!empty($custo_produto)) {
                            $resultado[$agrupamento]['custo_armazem_ultimo'] += $custo_produto->custo_armazem * $grupo->saldo_em_terceiro;
                        }
                    }
                }
            }
        }

        

        foreach ($resultado as $agrupamento => $calculos) {

            $custo = $calculos['custo_contabil'] / $calculos['qtd_unidade'];

            $custo_contabil_portal = $calculos['custo_contabil_portal'] / $calculos['qtd_unidade'];
            $custo_gerencial_portal = $calculos['custo_gerencial_portal'] / $calculos['qtd_unidade'];

            $custo_armazem = $calculos['custo_armazem'] / $calculos['qtd_unidade'];
            $custo_armazem_ultimo = $calculos['custo_armazem_ultimo'] / $calculos['qtd_unidade'];

            $resultado[$agrupamento]['custo_contabil'] = parserValor($calculos['custo_contabil']);
            $resultado[$agrupamento]['custo_gerencial'] = parserValor($calculos['custo_gerencial']);

            $resultado[$agrupamento]['custo_contabil_portal'] = parserValor($calculos['custo_contabil_portal']);
            $resultado[$agrupamento]['custo_gerencial_portal'] = parserValor($calculos['custo_gerencial_portal']);
            $resultado[$agrupamento]['custo_armazem'] = parserValor($calculos['custo_armazem']);
            $resultado[$agrupamento]['qtd_unidade'] = parserValor($calculos['qtd_unidade']);

            if ($totalCustoGerencial_portal == 0) {
                $resultado[$agrupamento]['valorPorcentGerencial_portal'] = 0;
            } else {
                $resultado[$agrupamento]['valorPorcentGerencial_portal'] = parserValor(round(($calculos['custo_gerencial_portal'] / $totalCustoGerencial_portal) * 100, 2));
            }

            if ($totalCustoContabil_portal == 0) {
                $resultado[$agrupamento]['valorPorcentContabil_portal'] = 0;
            } else {
                $resultado[$agrupamento]['valorPorcentContabil_portal'] = parserValor(round(($calculos['custo_contabil_portal'] / $totalCustoContabil_portal) * 100, 2));
            }

            if ($totalCustoContabil == 0) {
                $resultado[$agrupamento]['valorPorcentContabil'] = 0;
            } else {
                $resultado[$agrupamento]['valorPorcentContabil'] = parserValor(round(($calculos['custo_contabil'] / $totalCustoContabil) * 100, 2));
            }

            if ($totalCustoGerencial == 0) {
                $resultado[$agrupamento]['valorPorcentGerencial'] = 0;
                $resultado[$agrupamento]['porcentGerencial'] = 0;
            } else {
                $resultado[$agrupamento]['valorPorcentGerencial'] = parserValor(round(($calculos['custo_gerencial'] / $totalCustoGerencial) * 100, 2));
                $resultado[$agrupamento]['porcentGerencial'] = parserValor(($custo / $totalCustoGerencial) * 100);
            }

            if ($totalCustoArmazem == 0) {
                $resultado[$agrupamento]['valorPorcentArmazem'] = 0;
                $resultado[$agrupamento]['porcentArmazem'] = 0;
            } else {
                $resultado[$agrupamento]['valorPorcentArmazem'] = parserValor(round(($calculos['custo_armazem'] / $totalCustoArmazem) * 100, 2));
            }
            if ($totalCustoArmazemUltimo == 0) {
                $resultado[$agrupamento]['valorPorcentArmazemUltimo'] = 0;
                $resultado[$agrupamento]['porcentArmazemUltimo'] = 0;
            } else {
                $resultado[$agrupamento]['valorPorcentArmazemUltimo'] = parserValor(round(($calculos['custo_armazem_ultimo'] / $totalCustoArmazemUltimo) * 100, 2));
            }

            $resultado[$agrupamento]['estabelecimento'] = $calculos['estabelecimento'];
            $resultado[$agrupamento]['grupo'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $calculos['grupo'] . "'>" . $calculos['grupo'] . "</div></div>";
            $resultado[$agrupamento]['subgrupo'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $calculos['subgrupo'] . "'>" . $calculos['subgrupo'] . "</div></div>";
            $resultado[$agrupamento]['marca'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $calculos['marca'] . "'>" . $calculos['marca'] . "</div></div>";
            $resultado[$agrupamento]['linha'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $calculos['linha'] . "'>" . $calculos['linha'] . "</div></div>";

            $resultado[$agrupamento]['custo_unitario_gerencial'] = parserValor($resultado[$agrupamento]['custo_unitario_gerencial']);
            $resultado[$agrupamento]['custo_unitario_contabil'] = parserValor($custo);

            $resultado[$agrupamento]['custo_unitario_contabil_portal'] = parserValor($custo_contabil_portal);
            $resultado[$agrupamento]['custo_unitario_gerencial_portal'] = parserValor($custo_gerencial_portal);
            $resultado[$agrupamento]['custo_unitario_armazem'] = parserValor($custo_armazem);
            $resultado[$agrupamento]['custo_armazem_ultimo'] = parserValor($custo_armazem_ultimo);
        }

        return view(
            'programs.consulta_estoque_geral.modal.dialog',
            [
                'estabelecimentos' => $resultado, 'totalCustoContabil' => $totalCustoContabil, 'totalCustoGerencial' => $totalCustoGerencial, 'totalQtdUnidade' => $totalQtdUnidade, 'totalCustoUnidade' => $totalCustoUnidade, 'totalCustoContabil_portal' => $totalCustoContabil_portal, 'totalCustoGerencial_portal' => $totalCustoGerencial_portal, 'totalCustoArmazem' => $totalCustoArmazem, 'totalCustoArmazemUltimo' => $totalCustoArmazemUltimo
            ]
        );
    }

    public function filter(Request $request)
    {


        $response[] = [
            'empresa'      => '',
            'valor_custo'  => '',
            'peso_metros' => '',
            'valor_total'  => '',
        ];

        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $response
        ];
        return $return;
    }
}
