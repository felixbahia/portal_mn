<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ListagemDePrecosController;

use App\FichaTecnicaInfoAdicional;
use App\FichaTecnicaProduto;
use App\FichaTecnicaProdutoTecido;
use App\FichaTecnicaProdutoInsumo;
use App\FichaTecnicaProdutoServico;
use App\ProdutoEspecificacao;
use App\FichaTecnicaTabelaMedidas;
use App\FichaTecnicaSequenciaOperacional;
use App\FichaTecnicaArquivo;
use App\ProdutoNasajon;
use App\ComprasNasajon;
use App\LancamentoProjetoProduto;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\FichaTecnicaCadastroEditarComposicaoRequest;
use App\Http\Requests\FichaTecnicaCadastroSalvarComposicaoRequest;
use App\Http\Requests\ListaDePrecosRequest;
use App\Http\Requests\ProdutoNovoRequest;
use App\Http\Requests\FichaTecnicaCadastroSalvarInformacoesAdicionaisRequest;
use App\Http\Requests\FichaTecnicaCadastroNovaMontagemRequest;
use App\Http\Requests\FichaTecnicaCadastroNovaEtiquetaRequest;
use App\Http\Requests\FichaTecnicaCadastroMedidasRequest;
use App\Http\Requests\FichaTecnicaCadastroSequenciaRequest;
use App\Http\Requests\FichaTecnicaCadastroNovoRequest;
use App\Http\Requests\FichaTecnicaDuplicarCadastroRequest;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

use Auth;

class FichaTecnicaCadastroController extends Controller
{

    protected $storage = 'public/ficha_tecnica/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FichaTecnicaCadastro") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FichaTecnicaCadastro');
        return view('programs.ficha_tecnica_cadastro.index');
    }

    public function filter(Request $request){

        $fields = $request->only('codigo_produto', 'marca', 'linha', 'grupo', 'subgrupo', 'descricao');

        $fichaTecnicaQuery = FichaTecnicaProduto::with('produto_detalhes.produtoGrupo');

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
                    $query->whereHas('produtoGrupo', function ($query) use ($fields){
                        $query->where('descricao', 'ilike', '%' . $fields['grupo'] . '%');
                    });
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
                $linha['grupo'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->produtoGrupo->descricao . "\">" . $ficha_tecnica->produto_detalhes->produtoGrupo->descricao . "</div></div>";
                $linha['subgrupo'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->subgrupo . "\">" . $ficha_tecnica->produto_detalhes->subgrupo . "</div></div>";
                $linha['descricao'] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" data-placement=\"right\" title=\"" . $ficha_tecnica->produto_detalhes->descricao . "\">" . $ficha_tecnica->produto_detalhes->descricao . "</div></div>";
                $linha['descricao_limpa'] = $ficha_tecnica->produto_detalhes->descricao;
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

    public function modalNovo(Request $request){
        return view('programs.ficha_tecnica_cadastro.modal.nova');
    }

    public function salvarNovaFichaTecnica(FichaTecnicaCadastroNovoRequest $request){

        $fields = $request->only('codigo_produto');

        $fichaTecnicaProdutoObj = new FichaTecnicaProduto;

        $fichaTecnicaProdutoObj->codigo_produto = $fields['codigo_produto'];
        $fichaTecnicaProdutoObj->created_by = Auth::id();

        $fichaTecnicaProdutoObj->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Ficha técnica cadastrada com sucesso',
            'error' => [
            ],
            'response' => [
                'id' => $fichaTecnicaProdutoObj->id
            ]
        ], 200);
    }


    public function modalEditar(Request $request){

        $fields = $request->only('id');

        $fichaTecnicaObj = FichaTecnicaProduto::with([
                'produto_detalhes',
                'tecidos',
                'tecidos.tecido_detalhes',
                'tecidos.estoque',
                'tecidos.custoPortal' => function($query){
					$query->where('estabelecimento', '05');
				},
                'insumos',
                'insumos.insumo_detalhes',
                'insumos.estoque',
                'insumos.custoPortal' => function($query){
					$query->where('estabelecimento', '05');
				},
                'servicos',
                'servicos.servico_detalhes',
                'servicos.preco',
                'estoque',
                'info_adicional',
                'etiquetas',
                'medidas',
                'sequencia_operacional',
                'montagem',
                'tecidosTodos',
                'insumosTodos',
                'servicosTodos'
            ])
            ->find($fields['id']);

        if(is_null($fichaTecnicaObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ficha técnica não encontrada',
                'error' => [
                ],
                'response' => [
                ]
            ], 422);
        }

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
                $custo_gerencial = empty($tecido->preco)? 0 : parserQtd3CasaDecimais($tecido->preco->compra_real);
                $custo_gerencial_total = empty($tecido->preco)? 0 : parserQtd3CasaDecimais($tecido->preco->compra_real * $tecido->consumo_unitario);
                $custo_gerencial_tecidos_total += empty($tecido->preco)? 0 : $tecido->preco->compra_real * $tecido->consumo_unitario;
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
            $linha['custo'] = empty($custo)? '' : parserQtd3CasaDecimais($custo_gerencial);
            $linha['custo_total'] = empty($custo)? '' : parserQtd3CasaDecimais($custo_gerencial);
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
            $custo_servicos_total = 'Custo total da mão de obra: ' . parserQtd3CasaDecimais($custo_servicos_total);
        }
        else{
            $custo_servicos_total = '';
        }

        $ficha_tecnica_criacao = parserData($fichaTecnicaObj->created_at);
        $ficha_tecnica_tecido_update = is_null($fichaTecnicaObj->tecidosTodos->max('updated_at')) ? $fichaTecnicaObj->created_at : $fichaTecnicaObj->tecidosTodos->max('updated_at');
        $ficha_tecnica_insumo_update = is_null($fichaTecnicaObj->insumosTodos->max('updated_at')) ? $fichaTecnicaObj->created_at : $fichaTecnicaObj->insumosTodos->max('updated_at');
        $ficha_tecnica_servico_update = is_null($fichaTecnicaObj->servicosTodos->max('updated_at')) ? $fichaTecnicaObj->created_at : $fichaTecnicaObj->servicosTodos->max('updated_at');

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

        $produto = [
            'id' => $fichaTecnicaObj->id,
            'codigo_produto' => $fichaTecnicaObj->codigo_produto,
            'marca' => $fichaTecnicaObj->produto_detalhes->marca,
            'linha' => $fichaTecnicaObj->produto_detalhes->linha,
            'grupo' => $fichaTecnicaObj->produto_detalhes->grupo,
            'subgrupo' => $fichaTecnicaObj->produto_detalhes->subgrupo,
            'descricao' => $fichaTecnicaObj->produto_detalhes->descricao,
            'peso' => $fichaTecnicaObj->produto_detalhes->peso,
            'ativo' => $fichaTecnicaObj->produto_detalhes->ativo,
            'ficha_tecnica_update' => $ficha_tecnica_update,
            'ficha_tecnica_criacao' => $ficha_tecnica_criacao,
        ];
        
        $info_adicional = [];

        if(isset($fichaTecnicaObj->info_adicional) && !empty($fichaTecnicaObj->info_adicional)){

            if(!empty($fichaTecnicaObj->info_adicional->imagem_produto) && Storage::exists($this->storage . $fichaTecnicaObj->codigo_produto . '/' . $fichaTecnicaObj->info_adicional->imagem_produto)){
                $info_adicional['imagem'] = true;
                $imagem = Storage::url($this->storage . $fichaTecnicaObj->codigo_produto . '/'.$fichaTecnicaObj->info_adicional->imagem_produto);
                
            }
            else{
                $info_adicional['imagem'] = false;
                $imagem = '';
            }
            
            $info_adicional['imagem_produto'] = "<a href='" . $imagem . "' class='foto-thumb'><img id='info-adicional-foto' src='" . $imagem . "'></a>";
            
            $info_adicional['lavagem'] = $fichaTecnicaObj->info_adicional->lavagem;
            $info_adicional['encolhimento'] = $fichaTecnicaObj->info_adicional->encolhimento;
        }
        else{
            $info_adicional['imagem_produto'] = "<a href='' class='foto-thumb'><img id='info-adicional-foto' src='' height='150' width='150'></a>";
            $info_adicional['imagem'] = false;
            $info_adicional['lavagem'] = '';
            $info_adicional['encolhimento'] = '';
        }

        $etiquetas = [];

        $fichaTecnicaObj->etiquetas->each(function($etiqueta) use (&$etiquetas, $fichaTecnicaObj){
            if(Storage::exists($this->storage . $fichaTecnicaObj->codigo_produto . '/' . $etiqueta->arquivo)){
                $imagem = Storage::url($this->storage. $fichaTecnicaObj->codigo_produto . '/' . $etiqueta->arquivo);
                $etiquetas[] = [
                    'imagem' => "<a href='" . $imagem . "' class='foto-thumb'><img class='etiqueta-foto mt-2' src='" . $imagem . "' max-height='200' max-width='200'></a>",
                    'id' => Crypt::encrypt($etiqueta->id)
                ];
            }
        });

        $medidas = [];

        $fichaTecnicaObj->medidas->each(function($medida) use (&$medidas){

            $linha = [];

            $linha['descricao'] = $medida->medida_descricao;
            $linha['medida_p'] = parserQtd3CasaDecimais($medida->medida_p);
            $linha['medida_m'] = parserQtd3CasaDecimais($medida->medida_m);
            $linha['medida_g'] = parserQtd3CasaDecimais($medida->medida_g);
            $linha['medida_gg'] = parserQtd3CasaDecimais($medida->medida_gg);
            $linha['medida_xg'] = parserQtd3CasaDecimais($medida->medida_xg);
            $linha['medida_xgg'] = parserQtd3CasaDecimais($medida->medida_xgg);
            $linha['tolerancia'] = parserQtd3CasaDecimais($medida->tolerancia);

            $medidas[] = $linha;
        });

        $sequencias = [];

        $fichaTecnicaObj->sequencia_operacional->each(function($sequencia) use (&$sequencias){

            $linha = [];

            $linha['operacao'] = $sequencia->operacao;
            $linha['tipo_ponto'] = $sequencia->tipo_ponto;

            $sequencias[] = $linha;
        });

        $montagem = [];

        $fichaTecnicaObj->montagem->each(function ($linha) use(&$montagem, $fichaTecnicaObj){
            if(Storage::exists($this->storage . $fichaTecnicaObj->codigo_produto . '/' . $linha->arquivo)){
                $imagem = Storage::url($this->storage. $fichaTecnicaObj->codigo_produto . '/' . $linha->arquivo);
                $montagem[] = [
                    'imagem' => "<a href='" . $imagem . "' class='foto-thumb'><img class='etiqueta-foto mt-2' src='" . $imagem . "' max-height='350'></a>",
                    'descricao' => $linha->descricao,
                    'id' => Crypt::encrypt($linha->id)
                ];
            }

        });

        $custo_gerencial_total = empty($custo_gerencial_tecidos_total + $custo_gerencial_insumos_total)? '' : parserQtd3CasaDecimais($custo_gerencial_tecidos_total + $custo_gerencial_insumos_total);

        return view('programs.ficha_tecnica_cadastro.modal.editar')
            ->with([
                'produto' => $produto,
                'tecidos' => $tecidos,
                'insumos' => $insumos,
                'servicos' => $servicos,
                'info_adicional' => $info_adicional,
                'etiquetas' => $etiquetas,
                'medidas' => $medidas,
                'sequencias' => $sequencias,
                'montagem' => $montagem,
                'custo_composicao_total' => $custo_composicao_total,
                'custo_gerencial_total' => $custo_gerencial_total,
                'custo_total' => $custo_total,
                'custo_gerencial_servicos_total' => empty($custo_gerencial_servicos_total)? '' : parserQtd3CasaDecimais($custo_gerencial_servicos_total),
            ]);

    }
    
    public function criarComposicaoModal(Request $request){
        $fields = $request->only('ficha_id');

        return view('programs.ficha_tecnica_cadastro.modal.composicao.novo')->with(['ficha_id' => $fields['ficha_id']]);
    }

    public function editarComposicaoModal(Request $request){

        $fields = $request->only('id', 'origem');

        $composicaoObj = null;

        if($fields['origem'] == 'tecido'){
            $composicaoObj = FichaTecnicaProdutoTecido::find($fields['id']);

        }
        else if($fields['origem'] == 'insumo'){
            $composicaoObj = FichaTecnicaProdutoInsumo::find($fields['id']);
        }
        else if($fields['origem'] == 'servico'){
            $composicaoObj = FichaTecnicaProdutoServico::find($fields['id']);
        }

        if(!is_null($composicaoObj)){
            $produtoEspecificacaoObj = ProdutoEspecificacao::where('codigo_produto', $composicaoObj->codigo_produto)->first();

            $return = [];

            $return['id'] = $composicaoObj->id;
            $return['codigo_produto'] = $produtoEspecificacaoObj->codigo_produto;
            $return['descricao'] = $produtoEspecificacaoObj->descricao;
            $return['consumo_unitario'] = parserQtd3CasaDecimais($composicaoObj->consumo_unitario);
            $return['origem'] = $fields['origem'];

            return view('programs.ficha_tecnica_cadastro.modal.composicao.editar')->with($return);
        }
        else{
            return response()->json([
                'status' => 'error',
                'message' => 'Item não encontrado',
                'error' => [
                ],
                'response' => [
                ]
            ], 422);
        }

    }

    public function excluirComposicao(Request $request){
        $fields = $request->only('id', 'origem');

        $composicaoObj = null;

        if($fields['origem'] == 'tecido'){
            $composicaoObj = FichaTecnicaProdutoTecido::find($fields['id']);
        }
        else if($fields['origem'] == 'insumo'){
            $composicaoObj = FichaTecnicaProdutoInsumo::find($fields['id']);
        }
        else if($fields['origem'] == 'servico'){
            $composicaoObj = FichaTecnicaProdutoServico::find($fields['id']);
        }

        if(!is_null($composicaoObj)){

            $id_ficha_tecnica = $composicaoObj->ficha_tecnica_produtos_id;
            $composicaoObj->delete();

            $errors = $this->cadastroFichaTecnicaNasajon($id_ficha_tecnica);
        
    
            if (($errors['status'] == 'error')){
                return response()->json($errors, 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Item deletado com sucesso',
                'error' => [
                ],
                'response' => [
                ]
            ], 200);
        }
        else{
            return response()->json([
                'status' => 'error',
                'message' => 'Item não encontrado',
                'error' => [
                ],
                'response' => [
                ]
            ], 422);
        }
    }

    public function salvarComposicaoNova(FichaTecnicaCadastroSalvarComposicaoRequest $request){

        $fields = $request->only('ficha_id', 'origem', 'codigo_produto', 'consumo_unitario');

        $composicaoObj = null;

        if($fields['origem'] == 'tecido'){
            $composicaoObj = new FichaTecnicaProdutoTecido;

        }
        else if($fields['origem'] == 'insumo'){
            $composicaoObj = new FichaTecnicaProdutoInsumo;
        }
        else if($fields['origem'] == 'servico'){
            $composicaoObj = new FichaTecnicaProdutoServico;
        }
        $composicaoObj->ficha_tecnica_produtos_id = $fields['ficha_id'];
        $composicaoObj->created_by = Auth::id();
        $composicaoObj->codigo_produto = $fields['codigo_produto'];

        if($fields['origem'] != 'servico'){
            $composicaoObj->consumo_unitario = parserNumber($fields['consumo_unitario']);
        }

        $composicaoObj->save();

        $id_ficha_tecnica = $composicaoObj->ficha_tecnica_produtos_id;

        $errors = $this->cadastroFichaTecnicaNasajon($id_ficha_tecnica);

        if (($errors['status'] == 'error')){
            return response()->json($errors, 422);
        }

        $linha = [];

        $custo_gerencial = empty($composicaoObj->preco)? '' : parserValor($composicaoObj->preco->compra_real);
        $custo_gerencial_total = empty($composicaoObj->preco)? '' : parserValor($composicaoObj->preco->compra_real * $composicaoObj->consumo_unitario);

        if($fields['origem'] == 'servico'){

            $composicaoObj->load('preco');

            if(isset($composicaoObj->preco) && !empty($composicaoObj->preco)){
                $custo = parserValor($composicaoObj->preco->preco_real / 1.43);
                $custo_total = parserValor($composicaoObj->preco->preco_real / 1.43);
            }
            else{
                $custo = '';
                $custo_total = '';
            }
        }
        else{
            $composicaoObj->load(['custoPortal' => function($query){
                $query->where('estabelecimento', '05');
            }]);

            if($composicaoObj->custoPortal->isNotEmpty() && $composicaoObj->custoPortal->first()->custo_medio_contabil > 0){
                $custo = parserValor($composicaoObj->custoPortal->first()->custo_medio_contabil);
                $custo_total = parserValor($composicaoObj->custoPortal->first()->custo_medio_contabil * $composicaoObj->consumo_unitario);
            }
            else{
                $custo = '';
                $custo_total = '';
            }

        }

        $produtoEspecificacaoObj = ProdutoEspecificacao::where('codigo_produto', $composicaoObj->codigo_produto)->first();

        $linha['id'] = $composicaoObj->id;
        $linha['codigo'] = $composicaoObj->codigo_produto;
        $linha['descricao'] = $produtoEspecificacaoObj->descricao;
        $linha['grupo'] = $produtoEspecificacaoObj->grupo;
        $linha['linha'] = $produtoEspecificacaoObj->linha;
        
        if($fields['origem'] != 'servico'){
            $linha['consumo'] = parserValor($composicaoObj->consumo_unitario);
        }
        else{
            $linha['consumo'] = '1,000';
        }
        
        $linha['custo'] = $custo;
        $linha['custo_total'] = $custo_total;
        $linha['custo_gerencial'] = $custo_gerencial;
        $linha['custo_gerencial_total'] = $custo_gerencial_total;

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [
            ],
            'response' => [
                'origem' => $fields['origem'],
                'linha' => $linha
            ]
        ], 200);
    }

    public function salvarComposicaoEdicao(FichaTecnicaCadastroEditarComposicaoRequest $request){

        $fields = $request->only('id', 'origem', 'codigo_produto', 'consumo_unitario');

        $composicaoObj = null;

        if($fields['origem'] == 'tecido'){
            $composicaoObj = FichaTecnicaProdutoTecido::find($fields['id']);
        }
        else if($fields['origem'] == 'insumo'){
            $composicaoObj = FichaTecnicaProdutoInsumo::find($fields['id']);
        }
        else if($fields['origem'] == 'servico'){
            $composicaoObj = FichaTecnicaProdutoServico::find($fields['id']);
        }

        $composicaoObj->codigo_produto = $fields['codigo_produto'];

        if($fields['origem'] != 'servico'){
            $composicaoObj->consumo_unitario = parserNumber($fields['consumo_unitario']);
        }

        $composicaoObj->save();

        $id_ficha_tecnica = $composicaoObj->ficha_tecnica_produtos_id;

        $errors = $this->cadastroFichaTecnicaNasajon($id_ficha_tecnica);

        if (($errors['status'] == 'error')){
            return response()->json($errors, 422);
        }

        $linha = [];

        $custo_gerencial = '';
        $custo_gerencial_total = '';

        if($fields['origem'] == 'servico'){
            
            $composicaoObj->load('preco');

            if(isset($composicaoObj->preco) && !empty($composicaoObj->preco)){
                $custo = parserValor($composicaoObj->preco->preco_real / 1.43);
                $custo_total = parserValor($composicaoObj->preco->preco_real / 1.43);
                $custo_gerencial = parserValor($composicaoObj->preco->compra_real);
                $custo_gerencial_total = parserValor($composicaoObj->preco->compra_real * $composicaoObj->consumo_unitario);
            }
            else{
                $custo = '';
                $custo_total = '';
            }
        }
        else{
            $composicaoObj->load(['custoPortal' => function($query){
                $query->where('estabelecimento', '05');
            }]);

            if($composicaoObj->custoPortal->isNotEmpty() && $composicaoObj->custoPortal->first()->custo_medio_contabil > 0){

                $custo = parserValor($composicaoObj->custoPortal->first()->custo_medio_contabil);
                $custo_total = parserValor($composicaoObj->custoPortal->first()->custo_medio_contabil * $composicaoObj->consumo_unitario);
                $custo_gerencial = parserValor($composicaoObj->preco->compra_real);
                $custo_gerencial_total = parserValor($composicaoObj->preco->compra_real * $composicaoObj->consumo_unitario);
            }
            else{
                $custo = '';
                $custo_total = '';
            }

        }

        $produtoEspecificacaoObj = ProdutoEspecificacao::where('codigo_produto', $composicaoObj->codigo_produto)->first();

        $linha['id'] = $composicaoObj->id;
        $linha['codigo'] = $composicaoObj->codigo_produto;
        $linha['descricao'] = $produtoEspecificacaoObj->descricao;
        $linha['grupo'] = $produtoEspecificacaoObj->grupo;
        $linha['linha'] = $produtoEspecificacaoObj->linha;
        
        if($fields['origem'] != 'servico'){
            $linha['consumo'] = parserQtd3CasaDecimais($composicaoObj->consumo_unitario);
        }
        else{
            $linha['consumo'] = '1,00';
        }
        
        $linha['custo'] = $custo;
        $linha['custo_total'] = $custo_total;
        $linha['custo_gerencial'] = $custo_gerencial;
        $linha['custo_gerencial_total'] = $custo_gerencial_total;

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [
            ],
            'response' => [
                'origem' => $fields['origem'],
                'linha' => $linha
            ]
        ], 200);
    }

    public function salvarInformacoesAdicionais(FichaTecnicaCadastroSalvarInformacoesAdicionaisRequest $request){
        $fields = $request->only('ficha_id', 'lavagem', 'encolhimento');

        $file = $request->file('imagem_produto');

        $fichaTecnicaProdutoObj = FichaTecnicaProduto::with('info_adicional')
            ->find($fields['ficha_id']);
                    
        $url = '';

        if(!is_null($fichaTecnicaProdutoObj->info_adicional)){
            $fichaTecnicaProdutoObj->info_adicional->lavagem = $fields['lavagem'];
            $fichaTecnicaProdutoObj->info_adicional->encolhimento = $fields['encolhimento'];

            if(!empty($file)){
    
                if(!is_null($fichaTecnicaProdutoObj->info_adicional->imagem_produto)){
                    Storage::delete($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/' . $fichaTecnicaProdutoObj->info_adicional->imagem_produto);
                }

                $filename = $fichaTecnicaProdutoObj->produto_detalhes->codigo_produto . '.' . $file->getClientOriginalExtension();
                $file->storeAs($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/', $filename);

                $fichaTecnicaProdutoObj->info_adicional->imagem_produto = $filename;
                $url = Storage::url($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/'.$filename);
            }

            $fichaTecnicaProdutoObj->info_adicional->push();
        }
        else{

            $fichaTecnicaInfoAdicionalObj = new FichaTecnicaInfoAdicional;

            $fichaTecnicaInfoAdicionalObj->lavagem = $fields['lavagem'];
            $fichaTecnicaInfoAdicionalObj->encolhimento = $fields['encolhimento'];

            if(!empty($file)){
                $filename = $fichaTecnicaProdutoObj->produto_detalhes->codigo_produto . '.' . $file->getClientOriginalExtension();
                $file->storeAs($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/', $filename);

                $fichaTecnicaInfoAdicionalObj->imagem_produto = $filename;
                $url = Storage::url($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/'.$filename);
            }

            $fichaTecnicaProdutoObj->info_adicional()->save($fichaTecnicaInfoAdicionalObj);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Informações adicionais da ficha salvas com sucesso',
            'error' => [
            ],
            'response' => [
                'url' => $url
            ]
        ], 200); 
    }

    public function apagarImagemProduto(Request $request){
        $fields = $request->only('ficha_id');

        $fichaTecnicaProdutoObj = FichaTecnicaProduto::where('id', $fields['ficha_id'])
            ->with('info_adicional')
            ->first();
        
        Storage::delete($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/' . $fichaTecnicaProdutoObj->info_adicional->imagem_produto);
        $fichaTecnicaProdutoObj->info_adicional->imagem_produto = null;
        $fichaTecnicaProdutoObj->push();

        return response()->json([
            'status' => 'success',
            'message' => 'Imagem excluída com sucesso',
            'error' => [
            ],
            'response' => [
                'url' => ''
            ]
        ], 200); 
    }

    public function salvarEtiqueta(FichaTecnicaCadastroNovaEtiquetaRequest $request){

        $fields = $request->only('ficha_id');
        $file = $request->file('etiqueta');

        $fichaTecnicaImagemObj = new FichaTecnicaArquivo;

        $fichaTecnicaProdutoObj = FichaTecnicaProduto::find($fields['ficha_id']);

        $fichaTecnicaImagemObj->ficha_tecnica_produtos_id = $fields['ficha_id'];
        $fichaTecnicaImagemObj->arquivo = time() . '.' . $file->getClientOriginalExtension();
        $fichaTecnicaImagemObj->created_by = Auth::id();
        $fichaTecnicaImagemObj->tipo = 'etiqueta';

        $fichaTecnicaImagemObj->save();

        $file->storeAs($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/', $fichaTecnicaImagemObj->arquivo);

        $imagem = Storage::url($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/'. $fichaTecnicaImagemObj->arquivo);

        return response()->json([
            'status' => 'success',
            'message' => 'Imagem salva com sucesso',
            'error' => [
            ],
            'response' => [
                'imagem' => "<div class='box-exibicao-100 m-1'>
                    <div class='img-container-thumbnail' data-toggle='tooltip' data-html='true' data-placement='right' title='' data-original-title='Clique para expandir'>
                        <a href='" . $imagem . "' class='foto-thumb'><img class='etiqueta-foto mt-2' src='" . $imagem . "' max-height='200' max-width='200'></a><br>
                    </div>
                    <div style='text-align: center;'>
                        <button class='btn btn-sm btn-primary mt-2 btn-apagar-etiqueta' data-id='" . Crypt::encrypt($fichaTecnicaImagemObj->id) . "' type='button'>Excluir</button>
                    </div>
                </div>",
            ]
        ], 200); 

    }

    public function apagarEtiqueta(Request $request){

        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            
        }

        $fichaTecnicaImagemObj = FichaTecnicaArquivo::with('ficha_tecnica_produto')->find($id);

        Storage::delete($this->storage . $fichaTecnicaImagemObj->ficha_tecnica_produto->codigo_produto . '/' . $fichaTecnicaImagemObj->arquivo);

        $fichaTecnicaImagemObj->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Imagem excluída com sucesso',
            'error' => [
            ],
            'response' => [
                'id' => $id,
            ]
        ], 200); 

    }

    public function salvarMedidas(FichaTecnicaCadastroMedidasRequest $request){

        $fields = $request->only('ficha_id', 'medidas');

        FichaTecnicaTabelaMedidas::where('ficha_tecnica_produtos_id', $fields['ficha_id'])
            ->whereNull('deleted_at')
            ->update(
                ['updated_by' => Auth::id(),
                'deleted_by' => Auth::id(),
                'deleted_at' => date('Y-m-d H:i:s')]
            );

        $contador = 1;

        if(isset($fields['medidas'])){
            foreach($fields['medidas'] as $medida){
                $fichaTecnicaTabelaMedidasobj = new FichaTecnicaTabelaMedidas;
    
                $fichaTecnicaTabelaMedidasobj->ficha_tecnica_produtos_id = $fields['ficha_id'];
                $fichaTecnicaTabelaMedidasobj->ordem = $contador++;
                $fichaTecnicaTabelaMedidasobj->medida_descricao = $medida['medida_descricao'];
                $fichaTecnicaTabelaMedidasobj->medida_p = parserNumber($medida['medida_p']);
                $fichaTecnicaTabelaMedidasobj->medida_m = parserNumber($medida['medida_m']);
                $fichaTecnicaTabelaMedidasobj->medida_g = parserNumber($medida['medida_g']);
                $fichaTecnicaTabelaMedidasobj->medida_gg = parserNumber($medida['medida_gg']);
                $fichaTecnicaTabelaMedidasobj->medida_xg = parserNumber($medida['medida_xg']);
                $fichaTecnicaTabelaMedidasobj->medida_xgg = parserNumber($medida['medida_xgg']);
                $fichaTecnicaTabelaMedidasobj->tolerancia = parserNumber($medida['tolerancia']);
                $fichaTecnicaTabelaMedidasobj->created_by = Auth::id();
    
                $fichaTecnicaTabelaMedidasobj->save();
            }    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Medidas salvas com sucesso',
            'error' => [
            ],
            'response' => []
        ], 200); 
    }

    public function salvarSequencia(FichaTecnicaCadastroSequenciaRequest $request){

        $fields = $request->only('ficha_id', 'sequencia');

        FichaTecnicaSequenciaOperacional::where('ficha_tecnica_produtos_id', $fields['ficha_id'])
            ->whereNull('deleted_at')
            ->update(
                ['updated_by' => Auth::id(),
                'deleted_by' => Auth::id(),
                'deleted_at' => date('Y-m-d H:i:s')]
            );
            $contador = 1;

        if(isset($fields['sequencia'])){
            foreach($fields['sequencia'] as $sequencia){
                $FichaTecnicaSequenciaOperacionalobj = new FichaTecnicaSequenciaOperacional;
    
                $FichaTecnicaSequenciaOperacionalobj->ficha_tecnica_produtos_id = $fields['ficha_id'];
                $FichaTecnicaSequenciaOperacionalobj->ordem = $contador++;
                $FichaTecnicaSequenciaOperacionalobj->operacao = $sequencia['operacao'];
                $FichaTecnicaSequenciaOperacionalobj->tipo_ponto = $sequencia['tipo_ponto'];
                $FichaTecnicaSequenciaOperacionalobj->created_by = Auth::id();
    
                $FichaTecnicaSequenciaOperacionalobj->save();
            }    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Sequencia salva com sucesso',
            'error' => [
            ],
            'response' => []
        ], 200); 
    }

    public function salvarMontagem(FichaTecnicaCadastroNovaMontagemRequest $request){

        $fields = $request->only('ficha_id', 'descricao');
        $file = $request->file('imagem');

        $fichaTecnicaImagemObj = new FichaTecnicaArquivo;

        $fichaTecnicaProdutoObj = FichaTecnicaProduto::find($fields['ficha_id']);

        $fichaTecnicaImagemObj->ficha_tecnica_produtos_id = $fields['ficha_id'];
        $fichaTecnicaImagemObj->descricao = $fields['descricao'];
        $fichaTecnicaImagemObj->arquivo = time() . '.' . $file->getClientOriginalExtension();
        $fichaTecnicaImagemObj->tipo = 'montagem';

        $fichaTecnicaImagemObj->created_by = Auth::id();

        $fichaTecnicaImagemObj->save();

        $file->storeAs($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/', $fichaTecnicaImagemObj->arquivo);
        
        $imagem = Storage::url($this->storage . $fichaTecnicaProdutoObj->codigo_produto . '/' . $fichaTecnicaImagemObj->arquivo);

        $resposta = "<div class='box-exibicao-300 m-1'>
                    <div data-toggle='tooltip' data-html='true' data-placement='right' title='' class='img-container-thumbnail-300' data-original-title='Clique para expandir'>
                        <a href='" . $imagem . "' class='foto-thumb'><img class='etiqueta-foto mt-2' src='" . $imagem . "' max-height='350'></a><br>
                    </div>
                    <div style='text-align: center;'>
                        <div class='montagem-descricao-text'>
                        " . $fichaTecnicaImagemObj->descricao . "<br>
                        </div>
                    </div>
                    <div style='text-align: center;'>
                        <button class='btn btn-sm btn-primary mt-2 btn-apagar-instrucao' data-id='" . Crypt::encrypt($fichaTecnicaImagemObj->id) . "' type='button'>Excluir</button>
                    </div>
                </div>";

        return response()->json([
            'status' => 'success',
            'message' => 'Imagem salva com sucesso',
            'error' => [
            ],
            'response' => ['imagem' => $resposta]
        ], 200); 

    }

    public function apagarMontagem(Request $request){

        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            
        }

        $fichaTecnicaImagemObj = FichaTecnicaArquivo::find($id);

        Storage::delete($this->storage . $fichaTecnicaImagemObj->ficha_tecnica_produto->codigo_produto .'/' . $fichaTecnicaImagemObj->arquivo);

        $fichaTecnicaImagemObj->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Imagem excluída com sucesso',
            'error' => [
            ],
            'response' => [
                'id' => $id,
            ]
        ], 200); 

    }

    public function modalDuplicar(Request $request){
        $campo = $request->only('id');

        $FichaTecnicaProduto = FichaTecnicaProduto::with('produto_detalhes')
        ->find($campo['id']);
        
        $saida = [
            'id' => encrypt($FichaTecnicaProduto->id),
            'codigo' => $FichaTecnicaProduto->codigo_produto,
            'descricao' => $FichaTecnicaProduto->produto_detalhes->descricao
        ];
        return view('programs.ficha_tecnica_cadastro.modal.duplicar')->with(['dados' => $saida]);
    }

    public function salvarFichaTecnicaDuplicar(FichaTecnicaDuplicarCadastroRequest $request){
        $campo = $request->only('id', 'codigo_produto');

        $id = decrypt($campo['id']);

        $FichaTecnicaProduto = FichaTecnicaProduto::find($id);
        $codigo_produto_antigo = $FichaTecnicaProduto->codigo_produto;
        $FichaTecnicaProduto->codigo_produto = $campo['codigo_produto'];
        $FichaTecnicaProduto->lancamento_projetos_id = null;
        $FichaTecnicaProduto->created_by = Auth::id();

        $NovaFichaTecnicaProduto = $FichaTecnicaProduto->replicate();
        $NovaFichaTecnicaProduto->save();

        $FichaTecnicaProduto->load('tecidos', 'insumos', 'servicos','etiquetas', 'medidas', 'sequencia_operacional', 'montagem', 'info_adicional');

        foreach($FichaTecnicaProduto->getRelations() as $relation => $items){
           if($relation == 'info_adicional'){
               if(isset($items)){
                    if(Storage::exists($this->storage . $codigo_produto_antigo . '/' . $items->imagem_produto)){
                        Storage::copy($this->storage . $codigo_produto_antigo . '/' . $items->imagem_produto, $this->storage . $NovaFichaTecnicaProduto->codigo_produto . '/' . $items->imagem_produto);
                    }
                    $NovaFichaTecnicaProduto->$relation()->save($items->replicate());
               }
           }else {
                foreach($items as $item){
                    if(isset($item->arquivo)){
                        if(Storage::exists($this->storage . $codigo_produto_antigo . '/' . $item->arquivo)){
                            Storage::copy($this->storage . $codigo_produto_antigo . '/' . $item->arquivo, $this->storage . $NovaFichaTecnicaProduto->codigo_produto . '/' . $item->arquivo);
                        }
                    }
                    $NovaFichaTecnicaProduto->$relation()->save($item->replicate());
                }
            }
        }
        
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
    
    public function cadastroFichaTecnicaNasajon($id_ficha_tecnica){
        $valor_servico = 0;

        $ficha_tecnica = FichaTecnicaProduto::with(['produtoNasajon',
        'servicos',
        'servicos.preco',
        'tecidos',
        'tecidos.produtoNasajon',
        'tecidos.produtoNasajon.unidadeMedida',
        'tecidos.custoPortal' => function($query){
            $query->where('estabelecimento', '05');
        },
        'insumos',
        'insumos.produtoNasajon',
        'insumos.produtoNasajon.unidadeMedida',
        'insumos.custoPortal'=> function($query){
            $query->where('estabelecimento', '05');
        }
        ])->find($id_ficha_tecnica);

        $ficha_tecnica->servicos->each(function($query_servico) use (&$valor_servico){

            if(empty($query_servico->preco->compra_real)){
                if(!empty($query_servico->preco->preco_real)){
                    $valor_servico += $query_servico->preco->preco_real / 1.43;
                }
            }else{
                $valor_servico += $query_servico->preco->compra_real;
            }

        });


        if(!empty($ficha_tecnica->produtoNasajon->produto)){

            $produto_uuid = $ficha_tecnica->produtoNasajon->produto;
            $produto_acabado_unidade = $ficha_tecnica->produtoNasajon->unidadeMedida->unidade;

            $retorno_produto_acabado = DB::connection('nasajon')->select("select * from integracoes.api_benefecio_excluir(
                '{$produto_uuid}'
            )");

            $this->tratarRetornoApiNasajon($retorno_produto_acabado);
            
            $retorno_produto_acabado = DB::connection('nasajon')->select("select * from integracoes.api_benefecio_produto_acabado(
                '{$produto_uuid}',
                '1',
                '{$produto_acabado_unidade}',
                '{$valor_servico}'
            )");

            $retorno = $this->tratarRetornoApiNasajon($retorno_produto_acabado);
            
            if($retorno['status'] == 'error'){
                return $retorno;
            }

            $ficha_tecnica->tecidos->each(function($query_tecidos) use ($produto_uuid){
                $custo = 0;

                if(($query_tecidos->custoPortal->isNotEmpty())){
                    $custo = $query_tecidos->custoPortal->first()->custo_medio_contabil * $query_tecidos->consumo_unitario;
                }

                $insumo_uuid = (!empty($query_tecidos->produtoNasajon->produto)) ? $query_tecidos->produtoNasajon->produto : '';
                $unidade_medida = (!empty($query_tecidos->produtoNasajon->unidadeMedida)) ? $query_tecidos->produtoNasajon->unidadeMedida->unidade : '';

                if(!empty($insumo_uuid)){
                    $retorno_produto_tecidos = DB::connection('nasajon')->select("select * from integracoes.api_benefecio_produto_insumo(
                        '{$produto_uuid}',
                        '{$insumo_uuid}',
                        '{$query_tecidos->consumo_unitario}',
                        '{$unidade_medida}',
                        '{$custo}'
                    )");
    
                    $retorno = $this->tratarRetornoApiNasajon($retorno_produto_tecidos);
    
                    if($retorno['status'] == 'error'){
                        return $retorno;
                    }
                }
                
            });

            $ficha_tecnica->insumos->each(function($query_insumo) use ($produto_uuid){
                $custo = 0;

                if($query_insumo->custoPortal->isNotEmpty()){
                    $custo = $query_insumo->custoPortal->first()->custo_medio_contabil * $query_insumo->consumo_unitario;
                }

                $insumo_uuid = (!empty($query_insumo->produtoNasajon->produto)) ? $query_insumo->produtoNasajon->produto : '';
                $unidade_medida = (!empty($query_insumo->produtoNasajon->unidadeMedida)) ? $query_insumo->produtoNasajon->unidadeMedida->unidade : '';

                if(!empty($insumo_uuid)){
                    $retorno_produto_insumos = DB::connection('nasajon')->select("select * from integracoes.api_benefecio_produto_insumo(
                        '{$produto_uuid}',
                        '{$insumo_uuid}',
                        '{$query_insumo->consumo_unitario}',
                        '{$unidade_medida}',
                        '{$custo}'
                    )");

                    $retorno = $this->tratarRetornoApiNasajon($retorno_produto_insumos);

                    if($retorno['status'] == 'error'){
                        return $retorno;
                    }
                }
            });
        }
    }

    private function tratarRetornoApiNasajon($retorno){
        $retorno = reset($retorno);
        $mensagem = $retorno->mensagem;
        unset($retorno);
        $mensagem = json_decode($mensagem);
        
        if($mensagem->codigo == 'ERRO'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$mensagem],
                'response' => []
            ];
        }
    }

    public function modalExcluir(Request $request){

        $fields = $request->only('id');

        $fichaTecnicaObj = FichaTecnicaProduto::with([
                'produto_detalhes'
            ])
            ->find($fields['id']);

        if(is_null($fichaTecnicaObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ficha técnica não encontrada',
                'error' => [
                ],
                'response' => [
                ]
            ], 422);
        }

        $produto_existe_projeto = LancamentoProjetoProduto::select();
        $produto_existe_projeto->where('codigo_produto', $fichaTecnicaObj->codigo_produto);
        $produto_existe_projeto = $produto_existe_projeto->first();

        if(!empty($produto_existe_projeto)){
            return response()->json([
                'status' => 'error',
                'message' => 'Há projeto(s) como esse produto, não sendo possível excluir a ficha técnica.',
                'error' => [
                ],
                'response' => [
                ]
            ], 422);
        }

        $produto_existe_compras = ComprasNasajon::select();
        $produto_existe_compras->where('cod_produto', $fichaTecnicaObj->codigo_produto);
        $produto_existe_compras = $produto_existe_compras->first();

        if(!empty($produto_existe_compras)){
            return response()->json([
                'status' => 'error',
                'message' => 'Há compra(s) como esse produto, não sendo possível excluir a ficha técnica.',
                'error' => [
                ],
                'response' => [
                ]
            ], 422);
        }

        $dados = [
            'id' => encrypt($fichaTecnicaObj->id),
            'descricao' => $fichaTecnicaObj->produto_detalhes->descricao,
        ];

        return view('programs.ficha_tecnica_cadastro.modal.excluir')->with(['dados' => $dados]);

    }

    public function excluir(Request $request){
        $id = $request->only(['id'])['id'];

        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $fichaTecnicaProdutoObj = FichaTecnicaProduto::find($id);
        $fichaTecnicaProdutoObj->deleted_by = Auth::id();
        $fichaTecnicaProdutoObj->save();
        $fichaTecnicaProdutoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
