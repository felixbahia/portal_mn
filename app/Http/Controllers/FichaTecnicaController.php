<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\ListaDePrecosRequest;

use App\Http\Controllers\ListagemDePrecosController;

use App\FichaTecnicaProduto;
use App\ProdutoEspecificacao;
use App\ProdutosEstoque;

use Illuminate\Support\Facades\Storage;

use Auth;

class FichaTecnicaController extends Controller
{
    protected $storage = 'public/ficha_tecnica/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FichaTecnica") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FichaTecnica');
        return view('programs.ficha_tecnica_visualizacao.index');
    }

    public function filter(Request $request){

        $fields = $request->only('codigo_produto', 'marca', 'linha', 'grupo', 'subgrupo', 'descricao');

        $fichaTecnicaQuery = FichaTecnicaProduto::with('produto_detalhes');

        if(isset($fields['codigo_produto']) && !empty($fields['codigo_produto'])){
            $fichaTecnicaQuery->where('codigo_produto', $fields['codigo_produto']);
        }

        if(
            (isset($fields['marca']) && !empty($fields['marca'])) ||
            (isset($fields['linha']) && !empty($fields['linha'])) ||
            (isset($fields['grupo']) && !empty($fields['grupo'])) ||
            (isset($fields['subgrupo']) && !empty($fields['subgrupo'])) ||
            (isset($fields['descricao']) && !empty($fields['descricao']))
        ){
            $fichaTecnicaQuery->whereHas('produto_detalhes', function ($query) use ($fields){
                if (isset($fields['marca']) && !empty($fields['marca'])){
                    $query->where('marca', 'ilike', '%' . $fields['marca'] . '%');
                }

                if (isset($fields['linha']) && !empty($fields['linha'])){
                    $query->where('linha', 'ilike', '%' . $fields['linha'] . '%');
                }

                if (isset($fields['grupo']) && !empty($fields['grupo'])){
                    $query->where('grupo', 'ilike', '%' . $fields['grupo'] . '%');
                }

                if (isset($fields['subgrupo']) && !empty($fields['subgrupo'])){
                    $query->where('subgrupo', 'ilike', '%' . $fields['subgrupo'] . '%');
                }

                if (isset($fields['descricao']) && !empty($fields['descricao'])){
                    $query->where('descricao', 'ilike', '%' . $fields['descricao'] . '%');
                }

            });
        }

        $fichasTecnicasObj = $fichaTecnicaQuery->get();

        $result = [];

        $fichasTecnicasObj->each(function($ficha_tecnica) use (&$result){

            $linha = [];

            $linha['codigo_produto'] = $ficha_tecnica->codigo_produto;
            $linha['id'] = $ficha_tecnica->id;

            if(!is_null($ficha_tecnica->produto_detalhes)){
                $linha['marca'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->marca . "\">" . $ficha_tecnica->produto_detalhes->marca . "</div></div>";
                $linha['linha'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->linha . "\">" . $ficha_tecnica->produto_detalhes->linha . "</div></div>";
                $linha['grupo'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->grupo . "\">" . $ficha_tecnica->produto_detalhes->grupo . "</div></div>";
                $linha['subgrupo'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->subgrupo . "\">" . $ficha_tecnica->produto_detalhes->subgrupo . "</div></div>";
                $linha['descricao'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->descricao . "\">" . $ficha_tecnica->produto_detalhes->descricao . "</div></div>";
            }
            else{
                $linha['marca'] = '';
                $linha['linha'] = '';
                $linha['grupo'] = '';
                $linha['subgrupo'] = '';
                $linha['descricao'] = '';
            }

            $result[] = $linha;

        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [
            ],
            'response' => [
                'produtos' => $result
            ]
        ], 200);
    
    }
    
    public function modal(Request $request){

        $fields = $request->only('id', 'exibicao_custo_fixo');

        $fichaTecnicaObj = FichaTecnicaProduto::with([
                'produto_detalhes',
                'tecidos',
                'tecidos.tecido_detalhes',
                'tecidos.custoPortal' => function($query){
					$query->where('estabelecimento', '05');
				},
                'insumos',
                'insumos.insumo_detalhes',
                'insumos.custoPortal' => function($query){
					$query->where('estabelecimento', '05');
				},
                'servicos',
                'servicos.servico_detalhes',
                'servicos.preco',
                'servicos.custoPortal' => function($query){
					$query->where('estabelecimento', '05');
				},
                'estoque',
                'info_adicional',
                'montagem',
                'etiquetas',
                'medidas',
                'sequencia_operacional',
                'tecidosTodos',
                'insumosTodos',
                'servicosTodos'
            ])
            ->find($fields['id']);

        $tecidos = [];
        $insumos = [];
        $servicos = [];
        
        $custo_tecidos_total = 0;
        $custo_insumos_total = 0;
        $custo_gerencial_tecidos_total = 0;
        $custo_gerencial_insumos_total = 0;
        $custo_servicos_total = 0;
        $custo_gerencial_servicos_total = 0;

        $fichaTecnicaObj->tecidos->each(function ($tecido) use (&$tecidos, &$custo_tecidos_total, &$custo_gerencial_tecidos_total){

            $linha = [];

            $custo = 0;
            $custo_total = 0;

            if(isset($tecido->custoPortal)){
				foreach($tecido->custoPortal as $custos){
					$custo += $custos->custo_medio_contabil;
				}

                $custo_total = parserQtd3CasaDecimais($custo * $tecido->consumo_unitario);
                $custo_tecidos_total += $custo * $tecido->consumo_unitario;
                $custo = parserQtd3CasaDecimais($custo);
                $custo_gerencial = parserQtd3CasaDecimais($tecido->preco->compra_real);
                $custo_gerencial_total = parserQtd3CasaDecimais($tecido->preco->compra_real * $tecido->consumo_unitario);
                $custo_gerencial_tecidos_total += $tecido->preco->compra_real * $tecido->consumo_unitario;
            }

            $linha['id'] = $tecido->id;
            $linha['codigo'] = $tecido->codigo_produto;
            $linha['grupo'] = $tecido->tecido_detalhes->grupo;
            $linha['linha'] = $tecido->tecido_detalhes->linha;
            $linha['descricao'] = $tecido->tecido_detalhes->descricao;
            $linha['consumo'] = parserQtd3CasaDecimais($tecido->consumo_unitario);
            $linha['custo'] = $custo;
            $linha['custo_total'] = $custo_total;
            $linha['custo_gerencial'] = $custo_gerencial;
            $linha['custo_gerencial_total'] = $custo_gerencial_total;

            $tecidos[] = $linha;
            
        });

        $fichaTecnicaObj->insumos->each(function ($insumo) use (&$insumos, &$custo_insumos_total, &$custo_gerencial_insumos_total){

            $linha = [];

            $custo = 0;
            $custo_total = 0;
            if(isset($insumo->estoque)){
				foreach($insumo->custoPortal as $custos){
					$custo += $custos->custo_medio_contabil;
				}

                $custo_total = parserQtd3CasaDecimais($custo * $insumo->consumo_unitario);
                $custo_insumos_total += $custo * $insumo->consumo_unitario;
                $custo = parserQtd3CasaDecimais($custo);
                $custo_gerencial = parserQtd3CasaDecimais($insumo->preco->compra_real);
                $custo_gerencial_total = parserQtd3CasaDecimais($insumo->preco->compra_real * $insumo->consumo_unitario);
                $custo_gerencial_insumos_total += $insumo->preco->compra_real * $insumo->consumo_unitario;
            }
            
            $linha['id'] = $insumo->id;
            $linha['codigo'] = $insumo->codigo_produto;
            $linha['descricao'] = $insumo->insumo_detalhes->descricao;
            $linha['grupo'] = $insumo->insumo_detalhes->grupo;
            $linha['linha'] = $insumo->insumo_detalhes->linha;
            $linha['consumo'] = parserQtd3CasaDecimais($insumo->consumo_unitario);
            $linha['custo'] = $custo;
            $linha['custo_total'] = $custo_total;
            $linha['custo_gerencial'] = $custo_gerencial;
            $linha['custo_gerencial_total'] = $custo_gerencial_total;

            $insumos[] = $linha;

        });

        $fichaTecnicaObj->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total){

            $linha = [];
			$custo = 0;
            foreach($servico->custoPortal as $custos){
                $custo += $custos->custo_medio_contabil;
            }

            if(empty($custo)){
                $custo = $servico->preco->preco_real / 1.43;
            }
            

            $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;

            $custo_servicos_total += $custo;
            $custo_gerencial_servicos_total += $custo_gerencial;

            $linha['id'] = $servico->id;
            $linha['codigo'] = $servico->codigo_produto;
            $linha['grupo'] = $servico->servico_detalhes->grupo;
            $linha['linha'] = $servico->servico_detalhes->linha;
            $linha['descricao'] = $servico->servico_detalhes->descricao;
            $linha['consumo'] = '1,00';
            $linha['custo'] = empty($custo)? '' : parserQtd3CasaDecimais($custo);
            $linha['custo_total'] = empty($custo)? '' : parserQtd3CasaDecimais($custo);
            $linha['custo_gerencial'] = empty($custo_gerencial)? '' : parserQtd3CasaDecimais($custo_gerencial);
            $linha['custo_gerencial_total'] = empty($custo_gerencial)? '' : parserQtd3CasaDecimais($custo_gerencial);
            
            $servicos[] = $linha;

        }); 

        $custo_total = $custo_tecidos_total + $custo_insumos_total + $custo_gerencial_servicos_total;

        if(!empty($custo_total)){
            if(Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Analise Compras') || Auth::user()->hasRole('Gerencia Comercial') || Auth::user()->hasRole('Diretoria Comercial') || Auth::user()->hasRole('Operador Produção')){
                $custo_total = 'Custo contábil total do produto: ' . parserQtd3CasaDecimais($custo_total).' - Custo gerencial total do produto: ' . parserQtd3CasaDecimais($custo_gerencial_tecidos_total + $custo_gerencial_insumos_total + $custo_gerencial_servicos_total);
            }else{
                $custo_total = 'Custo total do produto: ' . parserQtd3CasaDecimais($custo_total);
            }
        }
        else{
            $custo_total = '';
        }

        if(!empty($custo_tecidos_total) || !empty($custo_insumos_total)){
            $custo_composicao_total = parserQtd3CasaDecimais($custo_tecidos_total + $custo_insumos_total);
        }
        else{
            $custo_composicao_total = '';
        }

        if(!empty($custo_servicos_total)){
            $custo_servicos_total = parserQtd3CasaDecimais($custo_servicos_total);
        }
        else{
            $custo_servicos_total = '';
        }

        $info_adicional = [];

        if(isset($fichaTecnicaObj->info_adicional) && !empty($fichaTecnicaObj->info_adicional)){

            if(!empty($fichaTecnicaObj->info_adicional->imagem_produto && Storage::exists($this->storage .  $fichaTecnicaObj->codigo_produto . '/' . $fichaTecnicaObj->info_adicional->imagem_produto))){
                $imagem = Storage::url($this->storage . $fichaTecnicaObj->codigo_produto . '/' . $fichaTecnicaObj->info_adicional->imagem_produto);

                $info_adicional['imagem_produto'] = "<a href='" . $imagem . "' class='foto-thumb'><img class='mx-2 border' id='info-adicional-foto-visualizacao' src='" . $imagem . "'></a>";
            }

            if(!empty($fichaTecnicaObj->info_adicional->lavagem)){
                $info_adicional['lavagem'] = $fichaTecnicaObj->info_adicional->lavagem;
            }

            if(!empty($fichaTecnicaObj->info_adicional->encolhimento)){
                $info_adicional['encolhimento'] = $fichaTecnicaObj->info_adicional->encolhimento;
            }
        }

        $montagem = [];

        $fichaTecnicaObj->montagem->each(function ($linha) use (&$montagem, $fichaTecnicaObj){
            if(Storage::exists($this->storage . $fichaTecnicaObj->codigo_produto . '/' . $linha->arquivo)){
                $imagem = Storage::url($this->storage . $fichaTecnicaObj->codigo_produto . '/' . $linha->arquivo);
                $montagem[] = [
                    'imagem' => "<a href='" . $imagem . "' class='foto-thumb'><img class='etiqueta-foto mt-2' src='" . $imagem . "' max-height='150' max-width='150'></a><br>" . $linha->descricao,
                ];
            }
        });

        $etiquetas = [];

        $fichaTecnicaObj->etiquetas->each(function($etiqueta) use (&$etiquetas, $fichaTecnicaObj){
            if(Storage::exists($this->storage . $fichaTecnicaObj->codigo_produto . '/' . $etiqueta->arquivo)){
                $imagem = Storage::url($this->storage .  $fichaTecnicaObj->codigo_produto . '/' .$etiqueta->arquivo);
                $etiquetas[] = [
                    'imagem' => "<a href='" . $imagem . "' class='foto-thumb'><img class='etiqueta-foto mt-2' src='" . $imagem . "' max-height='150' max-width='150'></a>"
                ];
            }
        });

        $medidas = [];

        $fichaTecnicaObj->medidas->each(function ($medida) use (&$medidas){
            $linha = [];

            $linha['ordem'] = $medida->ordem;
            $linha['medida_descricao'] = $medida->medida_descricao;
            $linha['medida_p'] = parserQtd3CasaDecimais($medida->medida_p);
            $linha['medida_m'] = parserQtd3CasaDecimais($medida->medida_m);
            $linha['medida_g'] = parserQtd3CasaDecimais($medida->medida_g);
            $linha['medida_gg'] = parserQtd3CasaDecimais($medida->medida_gg);
            $linha['medida_xg'] = parserQtd3CasaDecimais($medida->medida_xg);
            $linha['medida_xgg'] = parserQtd3CasaDecimais($medida->medida_xgg);
            $linha['tolerancia'] = parserQtd3CasaDecimais($medida->tolerancia);

            $medidas[] = $linha;
        });

        $sequencia_operacional = [];

        $fichaTecnicaObj->sequencia_operacional->each(function ($instrucao) use (&$sequencia_operacional){

            $linha = [];
            $linha['ordem'] = $instrucao->ordem;
            $linha['operacao'] = $instrucao->operacao;
            $linha['tipo_ponto'] = $instrucao->tipo_ponto;

            $sequencia_operacional[] = $linha;
        });

        $ficha_tecnica_criacao = parserData($fichaTecnicaObj->created_at);
        $ficha_tecnica_tecido_update = is_null($fichaTecnicaObj->tecidosTodos->max('updated_at')) ? $fichaTecnicaObj->created_at : $fichaTecnicaObj->tecidosTodos->max('updated_at');
        $ficha_tecnica_insumo_update = is_null($fichaTecnicaObj->insumosTodos->max('updated_at')) ? $fichaTecnicaObj->created_at : $fichaTecnicaObj->insumosTodos->max('updated_at');
        $ficha_tecnica_servico_update = is_null($fichaTecnicaObj->servicosTodos->max('updated_at')) ? $fichaTecnicaObj->created_at : $fichaTecnicaObj->servicosTodos->max('updated_at');

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

        $produto = [
            'id' => $fichaTecnicaObj->id,
            'marca' => $fichaTecnicaObj->produto_detalhes->marca,
            'linha' => $fichaTecnicaObj->produto_detalhes->linha,
            'grupo' => $fichaTecnicaObj->produto_detalhes->grupo,
            'subgrupo'  => $fichaTecnicaObj->produto_detalhes->subgrupo,
            'codigo_produto' => $fichaTecnicaObj->codigo_produto,
            'descricao' => $fichaTecnicaObj->produto_detalhes->descricao,
            'unidade' => $fichaTecnicaObj->produto_detalhes->unidade,
            'custo' => $custo_total,
            'ficha_tecnica_update' => $ficha_tecnica_update,
            'ficha_tecnica_criacao' => $ficha_tecnica_criacao,
        ];

        $custo_gerencial_total = empty($custo_gerencial_tecidos_total + $custo_gerencial_insumos_total)? '' : parserQtd3CasaDecimais($custo_gerencial_tecidos_total + $custo_gerencial_insumos_total);

        if(empty($fields['exibicao_custo_fixo'])){
            $exibicao_custo_fixo = false;
        }else{
            $exibicao_custo_fixo = true;
        }

        return view('programs.ficha_tecnica_visualizacao.modal.modal')
            ->with([
                'produto' => $produto,
                'tecidos' => $tecidos,
                'insumos' => $insumos,
                'servicos' => $servicos,
                'custo_composicao_total' => $custo_composicao_total,
                'custo_servicos_total' => $custo_servicos_total,
                'info_adicional' => $info_adicional,
                'montagem' => $montagem,
                'etiquetas' => $etiquetas,
                'medidas' => $medidas,
                'sequencia_operacional' => $sequencia_operacional,
                'custo_gerencial_total' => $custo_gerencial_total,
                'exibicao_custo_fixo' => $exibicao_custo_fixo,
                'custo_gerencial_servicos_total' => empty($custo_gerencial_servicos_total)? '' : parserQtd3CasaDecimais($custo_gerencial_servicos_total),
            ]);
    }
}
