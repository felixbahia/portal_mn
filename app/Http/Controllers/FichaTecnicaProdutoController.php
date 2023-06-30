<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\FichaTecnicaProduto;
use App\FichaTecnicaProdutoInsumo;
use App\FichaTecnicaProdutoTecido;
use App\FichaTecnicaProdutoServico;
use App\LancamentoProjetoProduto;
use App\LancamentoProjetoTecido;
use App\LancamentoProjetoInsumo;
use App\LancamentoProjetoFaccao;
use App\Http\Controllers\FichaTecnicaCadastroController;

use App\Http\Requests\FichaTecnicaProdutoRequest;

use Illuminate\Support\Facades\DB;

class FichaTecnicaProdutoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FichaTecnicaProduto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FichaTecnicaProduto');
        return view('programs.ficha_tecnica_produto.index');
    }

    public function filter(Request $request){

    	$fields = $request->only('estabelecimento', 'cod_produto', 'nome_produto');

    	$fichaTecnicaProdutoObj = FichaTecnicaProduto::with('produto_detalhes', 'produto_detalhes.unidade_medida');

    	if (isset($fields['estabelecimento'])){
    		$fichaTecnicaProdutoObj->where('estabelecimento', $fields['estabelecimento']);
    	}

    	$fichasTecnicas = $fichaTecnicaProdutoObj->get();

    	if (isset($fields['cod_produto'])){
    		$fichasTecnicas = $fichasTecnicas->where('produto_detalhes.produto_codigo', $fields['cod_produto']);
    	}

    	if (isset($fields['nome_produto'])){

    		$termos = explode(' ', $fields['nome_produto']);

    		foreach ($termos as $value){
	    		$fichasTecnicas = $fichasTecnicas->filter(function($item) use ($value){
	    			return false !== stristr($item->produto_detalhes->produto_especificacao, $value);
    			});
    		};

    	}
		$estabelecimentos = returnEmpresasPrologusView();

    	$response = [];

		// dd($fichasTecnicas);
    	
    	foreach ($fichasTecnicas as $value) {
			
			$custo = 0;
			
			foreach ($value->insumos as $v){
				$custo += $v->quantidade * $v->insumo_detalhes->produto_precovenda;
			}
			
            $response[] = [
				'id' => $value->id,
				'estabelecimento' => $estabelecimentos[$value->estabelecimento],
                'cod_produto' => $value->produto_detalhes->produto_codigo,
                'descricao' => $value->produto_detalhes->produto_especificacao,
                'unidade' => $value->produto_detalhes->unidade_medida->codigo??'',
                'valor' => parserValor($value->produto_detalhes->produto_precovenda),
                'inclusão' => $value->created_at->format('d/m/Y H:i'),
                'custo' => parserValor($custo),
                'insumos' => $value->insumos,
			];

    	 } 

    	return response()->json($response, 200);

    }


    public function cadastraProduto(FichaTecnicaProdutoRequest $request){

    	$fields = $request->only('estabelecimento', 'produto', 'insumo');

    	$errors = DB::transaction(function() use ($fields) {
            
            $errors = [];
            $insumo = 0;

			$fichaTecnicaProdutoObj =FichaTecnicaProduto::create([
				'estabelecimento' => $fields['estabelecimento'],
				'produto' => $fields['produto'],
				'created_by' => Auth::id(),
			]);

            foreach ($fields['insumo'] as $key => $value) {

                if (!empty($value['produto']) && !empty($value['quantidade'])){
                    FichaTecnicaProdutoInsumo::create([
                        'ficha' => $fichaTecnicaProdutoObj->id,
                        'insumo' => $value['produto'],
                        'quantidade' => str_replace(',', '.', str_replace(".", '', $value['quantidade'])),
                        'created_by' => Auth::id(),
                    ]);

                    $insumo++;
                }
                else if(!empty($value['produto']) && empty($value['quantidade'])) {
                    $errors['Produto'] = ["Há um item sem quantidade, favor verificar"];
                }
                else if(empty($value['produto']) && !empty($value['quantidade'])){
                    $errors['Quantidade'] = ["Há uma quantidade sem item, favor verificar"];
                }
                
            }

            if ($insumo == 0){
                $errors['Insumos'] = ['Pelo menos um insumo desve ser preenchido para compor o item'];
            }

            return $errors;

    	});

        if (!empty($errors)){
            DB::rollBack();

            $retorno = [
                'message' => 'Erro na validação de dados',
                'errors' => $errors
            ];

            return response()->json($retorno, 422);

        }
        else{
            return response()->json(['ok' => 'ok'], 200);
        }

    }

    public function editaProduto(FichaTecnicaProdutoRequest $request){

    	$fields = $request->only('id', 'estabelecimento', 'produto', 'insumo');

    	$errors = DB::transaction(function() use ($fields) {

            $errors = [];
            $insumo = 0;

			$fichaTecnicaProdutoObj = FichaTecnicaProduto::find($fields['id']);

			$fichaTecnicaProdutoObj->fill([
				'estabelecimento' => $fields['estabelecimento']??'',
				'produto' => $fields['produto']??'',
				'updated_by' => Auth::id(),
			]);

			FichaTecnicaProdutoInsumo::where('ficha', $fields['id'])->delete();

            foreach ($fields['insumo'] as $key => $value) {

                if (!empty($value['produto']) && !empty($value['quantidade'])){
                    FichaTecnicaProdutoInsumo::create([
                        'ficha' => $fichaTecnicaProdutoObj->id,
                        'insumo' => $value['produto'],
                        'quantidade' => str_replace(',', '.', str_replace(".", '', $value['quantidade'])),
                        'created_by' => Auth::id(),
                    ]);

                    $insumo++;
                }
                else if(!empty($value['produto']) && empty($value['quantidade'])) {
                    $errors['Produto'] = ["Há um item sem quantidade, favor verificar"];
                }
                else if(empty($value['produto']) && !empty($value['quantidade'])){
                    $errors['Quantidade'] = ["Há uma quantidade sem item, favor verificar"];
                }
                
            }

            if ($insumo == 0){
                $errors['Insumos'] = ['Pelo menos um insumo desve ser preenchido para compor o item'];
            }

            return $errors;

    	});

        if (!empty($errors)){
            DB::rollBack();

            $retorno = [
                'message' => 'Erro na validação de dados',
                'errors' => $errors
            ];

            return response()->json($retorno, 422);

        }
        else{
            return response()->json(['ok' => 'ok'], 200);
        }


    }


    public function excluiProduto(Request $request){

        FichaTecnicaProdutoInsumo::destroy([$request->id]);

        return response()->json(['ok' => 'ok'], 200);

    }

    public function cadastraProdutoModal(Request $request){

    	return view('programs.ficha_tecnica_produto.criar');

    }

    public function editaProdutoModal(Request $request){

    	$fields = $request->only('id');

    	$produtoObj = FichaTecnicaProduto::with('produto_detalhes', 'insumos')->find($fields['id']);

    	foreach ($produtoObj->insumos as $value) {
    		$insumos[] = [
    			"produto_produto" => $value->insumo_detalhes->produto_produto,
    			"produto_especificacao" => $value->insumo_detalhes->produto_especificacao,
    			"quantidade" => parserValor($value->quantidade),
    		];  		
    	}

    	$produto = [
			'id' => $produtoObj->id,
			'estabelecimento' => $produtoObj->estabelecimento,
			'produto_produto' => $produtoObj->produto_detalhes->produto_produto,
			'produto_especificacao' => $produtoObj->produto_detalhes->produto_especificacao,
			'insumos' => $insumos
    	];

    	// dd($produto);

    	return view('programs.ficha_tecnica_produto.editar')->with(['produto' => $produto]);

    }

    public function adicionarAtravesProjeto($id_projeto, $id_produto = ''){
        $query_projeto_produtos = LancamentoProjetoProduto::where('lancamento_projetos_id', $id_projeto)->whereNotNull('codigo_produto');
        if(!empty($id_produto)){
            $query_projeto_produtos->where('id', $id_produto); 
        }
        $query_projeto_produtos = $query_projeto_produtos->get();

        foreach($query_projeto_produtos as $produto){
            $tecidos_utilizado = [];
            $insumos_utilizado = [];
            $servicos_utilizado = [];

            $query_duplicidade_produto = FichaTecnicaProduto::where('codigo_produto', $produto->codigo_produto);
            $query_duplicidade_produto = $query_duplicidade_produto->first();
            if(empty($query_duplicidade_produto)){
                $fichaTecnicaProdutoObj = new FichaTecnicaProduto;
                $fichaTecnicaProdutoObj->lancamento_projetos_id = $produto->lancamento_projetos_id;
                $fichaTecnicaProdutoObj->codigo_produto = $produto->codigo_produto;
                $fichaTecnicaProdutoObj->preco_venda = $produto->preco_venda;
                $fichaTecnicaProdutoObj->created_by = 1;
                $fichaTecnicaProdutoObj->save();

                $id_ft_produto = $fichaTecnicaProdutoObj->id;
            }else{
                $query_duplicidade_produto->lancamento_projetos_id = $produto->lancamento_projetos_id;
                $query_duplicidade_produto->preco_venda = $produto->preco_venda;
                $query_duplicidade_produto->updated_by = 1;
                $query_duplicidade_produto->save();

                $id_ft_produto = $query_duplicidade_produto->id;
            }

            $query_projeto_tecidos = LancamentoProjetoTecido::where('lancamento_projeto_produtos_id', $produto->id);
            $query_projeto_tecidos = $query_projeto_tecidos->get();

            foreach($query_projeto_tecidos as $tecido){
                $query_duplicidade_tecido = FichaTecnicaProdutoTecido::where('ficha_tecnica_produtos_id', $id_ft_produto)->where('codigo_produto', $tecido->codigo_produto);
                $query_duplicidade_tecido = $query_duplicidade_tecido->first();

                if(empty($query_duplicidade_tecido)){
                    $fichaTecnicaProdutoTecidoObj = new FichaTecnicaProdutoTecido;
                    $fichaTecnicaProdutoTecidoObj->ficha_tecnica_produtos_id = $id_ft_produto;
                    $fichaTecnicaProdutoTecidoObj->codigo_produto = $tecido->codigo_produto;
                    $fichaTecnicaProdutoTecidoObj->consumo_unitario = $tecido->consumo_unitario;
                    $fichaTecnicaProdutoTecidoObj->created_by = 1;
                    $fichaTecnicaProdutoTecidoObj->save();
                }else{
                    $query_duplicidade_tecido->consumo_unitario = $tecido->consumo_unitario;
                    $query_duplicidade_tecido->updated_by = 1;
                    $query_duplicidade_tecido->save();
                }

                $tecidos_utilizado = array_merge($tecidos_utilizado, [$tecido->codigo_produto]);
            }

            $query_projeto_insumos = LancamentoProjetoInsumo::where('lancamento_projeto_produtos_id', $produto->id);
            $query_projeto_insumos = $query_projeto_insumos->get();

            foreach($query_projeto_insumos as $insumo){
                $query_duplicidade_insumo = FichaTecnicaProdutoInsumo::where('ficha_tecnica_produtos_id', $id_ft_produto)->where('codigo_produto', $insumo->codigo_produto);
                $query_duplicidade_insumo = $query_duplicidade_insumo->first();

                $consumo_unitario = round(($insumo->consumo_total / $insumo->quantidade)*100)/100;

                if(empty($query_duplicidade_insumo)){
                    $fichaTecnicaProdutoInsumoObj = new FichaTecnicaProdutoInsumo;
                    $fichaTecnicaProdutoInsumoObj->ficha_tecnica_produtos_id = $id_ft_produto;
                    $fichaTecnicaProdutoInsumoObj->codigo_produto = $insumo->codigo_produto;
                    $fichaTecnicaProdutoInsumoObj->consumo_unitario = $consumo_unitario;
                    $fichaTecnicaProdutoInsumoObj->created_by = 1;
                    $fichaTecnicaProdutoInsumoObj->save();
                }else{
                    $query_duplicidade_insumo->consumo_unitario = $consumo_unitario;
                    $query_duplicidade_insumo->updated_by = 1;
                    $query_duplicidade_insumo->save();
                }

                $insumos_utilizado = array_merge($insumos_utilizado, [$insumo->codigo_produto]);
            }

            $query_projeto_servicos = LancamentoProjetoFaccao::where('lancamento_projeto_produtos_id', $produto->id);
            $query_projeto_servicos = $query_projeto_servicos->get();

            foreach($query_projeto_servicos as $servico){
                $query_duplicidade_servico = FichaTecnicaProdutoServico::where('ficha_tecnica_produtos_id', $id_ft_produto)->where('codigo_produto', $servico->tipo_servico_id);
                $query_duplicidade_servico = $query_duplicidade_servico->first();

                if(empty($query_duplicidade_servico)){
                    $fichaTecnicaProdutoServicoObj = new FichaTecnicaProdutoServico;
                    $fichaTecnicaProdutoServicoObj->ficha_tecnica_produtos_id = $id_ft_produto;
                    $fichaTecnicaProdutoServicoObj->codigo_produto = $servico->tipo_servico_id;
                    $fichaTecnicaProdutoServicoObj->created_by = 1;
                    $fichaTecnicaProdutoServicoObj->save();
                }else{
                    $query_duplicidade_servico->updated_by = 1;
                    $query_duplicidade_servico->save();
                }
                $servicos_utilizado = array_merge($servicos_utilizado, [$servico->tipo_servico_id]);
            }

            $query_ft_tecido = FichaTecnicaProdutoTecido::where('ficha_tecnica_produtos_id', $id_ft_produto);
            $query_ft_tecido->whereNotIn('codigo_produto', $tecidos_utilizado);
            $query_ft_tecido->deleted_by = 1;
            $query_ft_tecido->delete();

            $query_ft_insumo = FichaTecnicaProdutoInsumo::where('ficha_tecnica_produtos_id', $id_ft_produto);
            $query_ft_insumo->whereNotIn('codigo_produto', $insumos_utilizado);
            $query_ft_insumo->deleted_by = 1;
            $query_ft_insumo->delete();

            $query_ft_servico = FichaTecnicaProdutoServico::where('ficha_tecnica_produtos_id', $id_ft_produto);
            $query_ft_servico->whereNotIn('codigo_produto', $servicos_utilizado);
            $query_ft_servico->deleted_by = 1;
            $query_ft_servico->delete();

            $ficha_tecnica_cadastro = new FichaTecnicaCadastroController;
            $ficha_tecnica_cadastro->cadastroFichaTecnicaNasajon($id_ft_produto);

        }
    }


    public function existeFichaTecnica($codigo_produto){

        $query_produto = FichaTecnicaProduto::where('codigo_produto', $codigo_produto);
        $query_produto = $query_produto->first();

        if(empty($query_produto)){
            return false;
        }else{
            return true;
        }
    }

    public function viewModal(Request $request){
        $codigo_produto = $request->only('codigo_produto')['codigo_produto'];
        $return_tecido = [];
        $return_insumo = [];
        $return_servico = [];
        $total_tecido = 0;
        $total_insumo = 0;
        $total_servico = 0;
        
        $query_produto = FichaTecnicaProduto::where('codigo_produto', $codigo_produto);
        $query_produto->with(['produto_detalhes', 'preco']);
        $produto = $query_produto->first();

        if(empty($produto)){
            return response()->json([
                'status' => 'error',
                'message' => 'Produto sem Ficha Técnica',
                'error' => [],
                'response' => ''
            ],422);            
        }

        $query_tecido = FichaTecnicaProdutoTecido::where('ficha_tecnica_produtos_id', $produto->id);
        $query_tecido->with(['tecido_detalhes', 'preco']);
        $tecidos = $query_tecido->get();

        foreach($tecidos as $tecido){
            $custo_total = $tecido->preco->preco_real * $tecido->consumo_unitario;

            $return_tecido[] = [
                'codigo' => $tecido->codigo_produto,
                'descricao' => $tecido->tecido_detalhes->descricao,
                'consumo' => parserQtd($tecido->consumo_unitario),
                'custo' => parserValor($tecido->preco->preco_real),
                'custo_total' => parserValor($custo_total)
            ];

            $total_tecido += $custo_total;
        }

        $query_insumo = FichaTecnicaProdutoInsumo::where('ficha_tecnica_produtos_id', $produto->id);
        $query_insumo->with(['insumo_detalhes', 'preco']);
        $insumos = $query_insumo->get();

        foreach($insumos as $insumo){
            $custo_total = $insumo->preco->preco_real * $insumo->consumo_unitario;

            $return_insumo[] = [
                'codigo' => $insumo->codigo_produto,
                'descricao' => $insumo->insumo_detalhes->descricao,
                'consumo' => parserQtd($insumo->consumo_unitario),
                'custo' => parserValor($insumo->preco->preco_real),
                'custo_total' => parserValor($custo_total)
            ];

            $total_insumo += $custo_total;
        }

        $query_servico = FichaTecnicaProdutoServico::where('ficha_tecnica_produtos_id', $produto->id);
        $query_servico->with(['servico_detalhes', 'preco']);
        $servicos = $query_servico->get();

        foreach($servicos as $servico){
            $return_servico[] = [
                'codigo' => $servico->codigo_produto,
                'descricao' => $servico->servico_detalhes->descricao,
                'custo_total' => parserValor($servico->preco->preco_real)
            ];

            $total_servico += $servico->preco->preco_real;
        }

        $custo_total = $total_tecido + $total_insumo + $total_servico;

        $dados = [
            'codigo_produto' => $produto->codigo_produto,
            'nome_produto' => $produto->produto_detalhes->descricao,
            'tecidos' => $return_tecido,
            'insumos' => $return_insumo,
            'servicos' => $return_servico,
            'total_tecido' => parserValor($total_tecido),
            'total_insumo' => parserValor($total_insumo),
            'total_servico' => parserValor($total_servico),
            'custo_total' => parserValor($custo_total)
        ];

        return view('programs.ficha_tecnica_produto.modal.detalhes')->with(['dados' => $dados]);
    }

    public function viewSemCodigoModal(Request $request){
        $codigo_produto_projeto = $request->only('codigo_produto_projeto')['codigo_produto_projeto'];
        try{
            $codigo_produto_projeto = decrypt($codigo_produto_projeto);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $return_tecido = [];
        $return_insumo = [];
        $return_servico = [];
        $total_tecido = 0;
        $total_insumo = 0;
        $total_servico = 0;

        $produto = LancamentoProjetoProduto::find($codigo_produto_projeto);

        if(empty($produto)){
            return response()->json([
                'status' => 'error',
                'message' => 'Produto sem Ficha Técnica',
                'error' => [],
                'response' => ''
            ],422);            
        }

        $query_tecido = LancamentoProjetoTecido::where('lancamento_projeto_produtos_id', $codigo_produto_projeto);
        $query_tecido->with(['tecido_detalhes', 'preco']);
        $tecidos = $query_tecido->get();

        foreach($tecidos as $tecido){
            $custo_total = $tecido->preco->preco_real * $tecido->consumo_unitario;

            $return_tecido[] = [
                'codigo' => $tecido->codigo_produto,
                'descricao' => $tecido->tecido_detalhes->descricao,
                'consumo' => parserQtd($tecido->consumo_unitario),
                'custo' => parserValor($tecido->preco->preco_real),
                'custo_total' => parserValor($custo_total)
            ];

            $total_tecido += $custo_total;
        }

        $query_insumo = LancamentoProjetoInsumo::where('lancamento_projeto_produtos_id', $codigo_produto_projeto);
        $query_insumo->with(['insumo_detalhes', 'preco']);
        $insumos = $query_insumo->get();

        foreach($insumos as $insumo){
            $custo_total = $insumo->preco->preco_real * $insumo->consumo_unitario;

            $return_insumo[] = [
                'codigo' => $insumo->codigo_produto,
                'descricao' => $insumo->insumo_detalhes->descricao,
                'consumo' => parserQtd($insumo->consumo_unitario),
                'custo' => parserValor($insumo->preco->preco_real),
                'custo_total' => parserValor($custo_total)
            ];

            $total_insumo += $custo_total;
        }

        $query_servico = LancamentoProjetoFaccao::where('lancamento_projeto_produtos_id', $codigo_produto_projeto);
        $query_servico->with(['tipo_de_servico', 'preco']);
        $servicos = $query_servico->get();

        foreach($servicos as $servico){
            $return_servico[] = [
                'codigo' => $servico->tipo_servico_id,
                'descricao' => $servico->tipo_de_servico->descricao,
                'custo_total' => parserValor($servico->preco->preco_real),
                'tecido' => empty($servico->lancamento_projeto_tecidos_id)? '' : $servico->tecido->tecido_detalhes->descricao,
                'produto_acabado' => empty($servico->codigo_produto_acabado) ? '' : $servico->produto_acabado->descricao,
            ];

            $total_servico += $servico->preco->preco_real;
        }

        $custo_total = $total_tecido + $total_insumo + $total_servico;

        $dados = [
            'codigo_produto' => '',
            'nome_produto' => $produto->descricao,
            'tecidos' => $return_tecido,
            'insumos' => $return_insumo,
            'servicos' => $return_servico,
            'total_tecido' => parserValor($total_tecido),
            'total_insumo' => parserValor($total_insumo),
            'total_servico' => parserValor($total_servico),
            'custo_total' => parserValor($custo_total)
        ];
        return view('programs.ficha_tecnica_produto.modal.detalhes')->with(['dados' => $dados]);
    }

    public function viewSemCodigoTecidoModal(Request $request){
        $codigo_tecido_projeto = $request->only('codigo_produto_projeto')['codigo_produto_projeto'];
        try{
            $codigo_tecido_projeto = decrypt($codigo_tecido_projeto);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $return_tecido = [];
        $return_insumo = [];
        $return_servico = [];
        $total_tecido = 0;
        $total_insumo = 0;
        $total_servico = 0;

        $produto = LancamentoProjetoTecido::find($codigo_tecido_projeto);

        if(empty($produto)){
            return response()->json([
                'status' => 'error',
                'message' => 'Produto sem Ficha Técnica',
                'error' => [],
                'response' => ''
            ],422);            
        }

        $query_servico = LancamentoProjetoFaccao::where('lancamento_projeto_tecidos_id', $codigo_tecido_projeto);
        $query_servico->with(['tipo_de_servico', 'preco']);
        $servicos = $query_servico->get();

        foreach($servicos as $servico){
            $return_servico[] = [
                'codigo' => $servico->tipo_servico_id,
                'descricao' => $servico->tipo_de_servico->descricao,
                'custo_total' => parserValor($servico->valor_total),
                'tecido' => '',
                'produto_acabado' => empty($servico->codigo_produto_acabado) ? '' : $servico->produto_acabado->descricao,
            ];

            $total_servico += $servico->valor_total;
        }

        $custo_total = $total_tecido + $total_insumo + $total_servico;

        $dados = [
            'codigo_produto' => $produto->tecido_detalhes->codigo_produto,
            'nome_produto' => $produto->tecido_detalhes->descricao,
            'tecidos' => $return_tecido,
            'insumos' => $return_insumo,
            'servicos' => $return_servico,
            'total_tecido' => parserValor($total_tecido),
            'total_insumo' => parserValor($total_insumo),
            'total_servico' => parserValor($total_servico),
            'custo_total' => parserValor($custo_total)
        ];

        return view('programs.ficha_tecnica_produto.modal.detalhes')->with(['dados' => $dados]);
    }
}