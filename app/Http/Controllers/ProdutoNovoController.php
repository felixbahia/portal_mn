<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\ProdutoNovo;
use App\ProdutoEspecificacao;
use App\UnidadeMedidaNasajon;
use App\LancamentoProjeto;
use App\LancamentoProjetoProduto;
use App\LancamentoProjetoFaccao;
use App\Preco;
use App\PrecosLog;
use App\ClienteNasajon;
use App\OrigemMercadoria;
use App\GrupoDeInventario;
use App\ProdutoNasajon;
use App\AliquotaPreco;
use App\ParametroHospitalar;
use App\MargemPrazo;
use App\NcmNasajon;
use App\HistoricoProjeto;
use App\Familia;
use App\Segmento;
use App\Composicao;
use App\Construcao;
use App\Sazonalidade;

use App\Http\Requests\ProdutoNovoRequest;

use App\Http\Controllers\LancamentoProjetoController;
use App\ProdutoGrupo;
use Auth;

class ProdutoNovoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutoNovo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoNovo');

        return view('programs.produto.novo.index');
    }

    public function modalAdicionar(Request $request){

        $reponse = [];
        
        $unidades = $this->getUnidade();

        $grupo_de_inventario = $this->getGrupoDeInventario();
        $origem_mercadoria = $this->getOrigemMercadoria();

        $segmentos = Segmento::all()->pluck('descricao', 'id');
        $familia = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicao = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcao = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidade = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');
        $bookVirtualControllerObj = new BookVirtualController;
        
        return view('programs.produto.novo.modal.adicionar')
        ->with([
            'unidades' => $unidades, 
            'grupo_de_inventario' => $grupo_de_inventario, 
            'origem_mercadoria' => $origem_mercadoria,
            'segmentos' => $segmentos, 
            'tipos_materiais' => $bookVirtualControllerObj->tipos_materiais,
            'padroes_codigo' => $bookVirtualControllerObj->padroes_codigo,
            'familia' => $familia,
            'tipo_generos' => $bookVirtualControllerObj->tipo_generos,
            'composicao' => $composicao,
            'construcao' => $construcao,
            'sazonalidade' => $sazonalidade,
            'tipo_gramatura' => $bookVirtualControllerObj->tipo_gramatura,
        ]);
    }

    public function adicionar(ProdutoNovoRequest $request){
        $fields = $request->only('cod_produto', 'composicao', 'marca', 'linha', 'grupo', 'subgrupo', 'descricao', 'unidade', 'largura', 'gramatura', 'preco_venda', 'origem_mercadoria', 'grupo_de_inventario', 'ncm', 'peso', 'rendimento','segmento', 'tipo_material', 'familia', 'pecas', 'caracteristicas', 'origem', 'padrao_codigo', 'tipo_genero', 'gramatura_tipo', 'composicao_predominante', 'construcao', 'sazonal');
		$fields['cod_produto'] = strtoupper($fields['cod_produto']);

        $unidade_nasajon = $this->getUnidadeNasajon($fields['unidade']);

        $cadastro_nasajon = $this->integracaoProdutoNovoNasajon(strtoupper($fields['cod_produto']), str_replace("'", " ", strtoupper($fields['descricao'])), $unidade_nasajon, $fields['preco_venda'], $fields['origem_mercadoria'], $fields['grupo_de_inventario'], $fields['composicao'], '', $fields['ncm'], $fields['peso']);

        if(!empty($cadastro_nasajon)){
            return response()->json([
                'status' => 'error',
                'message' => 'Error de integração com Nasajon.',
                'error' => [],
                'response' => []
            ],422);
        }

        $query_produto = ProdutoEspecificacao::select()->where('codigo_produto', 'ilike', $fields['cod_produto'])->first();

        if(empty($query_produto)){
            $produtoEspecificacaoObj = new ProdutoEspecificacao;  
    
            $precoObj = new Preco;
            $precoObj->created_by = Auth::id();
			$preco_antigo = 0;
        }else{
            $produtoEspecificacaoObj = ProdutoEspecificacao::find($fields['cod_produto']); 
    
            $precoObj = Preco::find($fields['cod_produto']);
            $precoObj->updated_by = Auth::id();
			$preco_antigo = $precoObj->preco_real;
        }

        $produtoGrupo = ProdutoGrupo::where('descricao', strtoupper($fields['grupo']))->first();

        $produtoEspecificacaoObj->codigo_produto = strtoupper($fields['cod_produto']);
        $produtoEspecificacaoObj->marca = strtoupper($fields['marca']);
        $produtoEspecificacaoObj->linha = strtoupper($fields['linha']);
        $produtoEspecificacaoObj->produto_grupos_id = $produtoGrupo->id;
        $produtoEspecificacaoObj->subgrupo = strtoupper($fields['subgrupo']);
        $produtoEspecificacaoObj->descricao = str_replace("'", " ", strtoupper($fields['descricao']));
        $produtoEspecificacaoObj->unidade = strtoupper($fields['unidade']);
        $produtoEspecificacaoObj->procedencia = 0;
        $produtoEspecificacaoObj->save();

        $precoObj->codigo_produto = strtoupper($fields['cod_produto']);
        $precoObj->preco_real = floatval(str_replace(",", ".", str_replace(".", "", $fields['preco_venda'])));
        $precoObj->save();

		$PrecosLogObj = new PrecosLog();
		$PrecosLogObj->codigo_produto = strtoupper($fields['cod_produto']);
		$PrecosLogObj->preco_real_antigo = $preco_antigo;
		$PrecosLogObj->preco_real_novo = (float) floatval(str_replace(",", ".", str_replace(".", "", $fields['preco_venda'])));
		$PrecosLogObj->created_by = Auth::id();
		$PrecosLogObj->save();
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('produto', 'num_projeto', 'cliente');
        
        $retorno = [];

        $query = ProdutoNovo::select();
        $query->whereNull('codigo_produto');
        if(!empty($fields['produto'])){
            $query->where('descricao', 'ilike', '%'.$fields['produto'].'%');
        }
        if(!empty($fields['num_projeto']) || !empty($fields['cliente'])){
            $query->whereHas('projeto_produto', function($query) use($fields){
                if(is_numeric($fields['num_projeto'])){
                    $query->where('lancamento_projetos_id', $fields['num_projeto']);
                }
                if(!empty($fields['cliente'])){
                    $query->whereHas('projeto_detalhes', function($query) use($fields){

                        $cliente_busca = ClienteNasajon::select('cpf_cnpj')
                            ->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'');
                        $cliente_busca = $cliente_busca->get();
                        $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
                    });
                }
            });
        }
        $result = $query->get();

        foreach($result as $produto_novo){
            $retorno [] = [
                'id' => encrypt($produto_novo->id),
                'num_projeto' => empty($produto_novo->projeto_produto)? $produto_novo->projeto_tecido->projeto_detalhes->id : $produto_novo->projeto_produto->projeto_detalhes->id,
                'descricao' => $produto_novo->descricao,
                'cliente' => empty($produto_novo->projeto_produto)? $produto_novo->projeto_tecido->projeto_detalhes->cliente->nome.' - '.$produto_novo->projeto_tecido->projeto_detalhes->cliente->cpf_cnpj : $produto_novo->projeto_produto->projeto_detalhes->cliente->nome.' - '.$produto_novo->projeto_produto->projeto_detalhes->cliente->cpf_cnpj, 
                'projeto_produtos_id' => encrypt($produto_novo->lancamento_projeto_produtos_id),
                'projeto_tecidos_id' => encrypt($produto_novo->lancamento_projeto_tecidos_id),
                'data_requisicao' => parserData($produto_novo->data_requisicao),
                'tecido'=> empty($produto_novo->projeto_produto)? true : false,
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalEditar(Request $request){
        $id = $request->only('id')['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $produto = ProdutoNovo::find($id);
        
        $reponse = [];
        
        $unidades = $this->getUnidade();

        $grupo_de_inventario = $this->getGrupoDeInventario();
        $origem_mercadoria = $this->getOrigemMercadoria();
        
        $dados = [
            'id' => encrypt($produto->id),
            'descricao' => $produto->descricao,
            'preco_venda' => parserValor($produto->preco_venda),
            'peso' => parserQtd3CasaDecimais($produto->peso),
            'ncm' => $produto->ncm
        ];
        return view('programs.produto.novo.modal.editar')->with(['dados' => $dados, 'unidades' => $unidades, 'grupo_de_inventario' => $grupo_de_inventario, 'origem_mercadoria' => $origem_mercadoria]);
    }

    public function editar(ProdutoNovoRequest $request){
        $fields = $request->only('id','cod_produto', 'composicao', 'marca', 'linha', 'grupo', 'subgrupo', 'descricao', 'unidade', 'largura', 'gramatura', 'preco_venda', 'origem_mercadoria', 'grupo_de_inventario', 'peso', 'ncm', 'rendimento');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $unidade_nasajon = $this->getUnidadeNasajon($fields['unidade']);

        $produtoNovoObj = ProdutoNovo::find($id);

        if(!empty($produtoNovoObj->lancamento_projeto_produtos_id)){
            $id_projeto = $produtoNovoObj->projeto_produto->lancamento_projetos_id;
        }else{
            $id_projeto = $produtoNovoObj->projeto_tecido->lancamento_projetos_id;
        }

        $cadastro_nasajon = $this->integracaoProdutoNovoNasajon(strtoupper($fields['cod_produto']), str_replace("'", " ", strtoupper($fields['descricao'])), $unidade_nasajon, $fields['preco_venda'], $fields['origem_mercadoria'], $fields['grupo_de_inventario'], $fields['composicao'], $id_projeto, $fields['ncm'], $fields['peso']);

        if(!empty($cadastro_nasajon)){
            return response()->json([
                'status' => 'error',
                'message' => 'Error de integração com Nasajon.',
                'error' => [],
                'response' => []
            ],422);
        }
        
        $produtoNovoObj->codigo_produto = strtoupper($fields['cod_produto']);
        $produtoNovoObj->composicao = strtoupper($fields['composicao']);
        $produtoNovoObj->descricao = str_replace("'", " ", strtoupper($fields['descricao']));
        $produtoNovoObj->ncm = strtoupper($fields['ncm']);
        $produtoNovoObj->peso = floatval(str_replace(",", ".", str_replace(".", "", $fields['peso'])));
        $produtoNovoObj->updated_by = Auth::id();
        $produtoNovoObj->save();  

        if(!empty($produtoNovoObj->lancamento_projeto_produtos_id)){
            $lancamentoProjetoProdutoObj = LancamentoProjetoProduto::find($produtoNovoObj->lancamento_projeto_produtos_id);
            $lancamentoProjetoProdutoObj->codigo_produto = strtoupper($fields['cod_produto']);
            $lancamentoProjetoProdutoObj->descricao = str_replace("'", " ", strtoupper($fields['descricao']));
            $lancamentoProjetoProdutoObj->updated_by = Auth::id();
            $lancamentoProjetoProdutoObj->save();
        }else if(!empty($produtoNovoObj->lancamento_projeto_tecidos_id)){
            $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::select()->where('lancamento_projeto_tecidos_id', $produtoNovoObj->lancamento_projeto_tecidos_id)->first();
            $lancamentoProjetoFaccaoObj->codigo_produto_acabado = strtoupper($fields['cod_produto']);
            $lancamentoProjetoFaccaoObj->updated_by = Auth::id();
            $lancamentoProjetoFaccaoObj->save();
        }

        $query_produto = ProdutoEspecificacao::select()->where('codigo_produto', 'ilike', $fields['cod_produto'])->first();

        if(empty($query_produto)){
            $produtoEspecificacaoObj = new ProdutoEspecificacao;  

            $precoObj = new Preco;
            $precoObj->created_by = Auth::id();
            $precoObj->codigo_produto = strtoupper($fields['cod_produto']);
            $precoObj->preco_real = floatval(str_replace(",", ".", str_replace(".", "", $fields['preco_venda'])));
            $precoObj->save();

			$PrecosLogObj = new PrecosLog();
			$PrecosLogObj->codigo_produto = strtoupper($fields['cod_produto']);
			$PrecosLogObj->preco_real_antigo = 0;
			$PrecosLogObj->preco_real_novo = (float) floatval(str_replace(",", ".", str_replace(".", "", $fields['preco_venda'])));
			$PrecosLogObj->created_by = Auth::id();
			$PrecosLogObj->save();
    
        }else{
            $produtoEspecificacaoObj = ProdutoEspecificacao::find($query_produto->codigo_produto); 
    
        }

        $produtoGrupo = ProdutoGrupo::where('descricao', strtoupper($fields['grupo']))->first();

        $produtoEspecificacaoObj->codigo_produto = strtoupper($fields['cod_produto']);
        $produtoEspecificacaoObj->marca = strtoupper($fields['marca']);
        $produtoEspecificacaoObj->linha = strtoupper($fields['linha']);
        $produtoEspecificacaoObj->produto_grupos_id = $produtoGrupo->id;
        $produtoEspecificacaoObj->subgrupo = strtoupper($fields['subgrupo']);
        $produtoEspecificacaoObj->descricao = str_replace("'", " ", strtoupper($fields['descricao']));
        $produtoEspecificacaoObj->unidade = strtoupper($fields['unidade']);
        $produtoEspecificacaoObj->procedencia = 0;
        $produtoEspecificacaoObj->industrializado = empty($produtoNovoObj->lancamento_projeto_produtos_id)? false : true;
        $produtoEspecificacaoObj->save();


        if(!empty($lancamentoProjetoProdutoObj)){
            $fichaTecnicaProdutoControllerObj = new FichaTecnicaProdutoController;
            $fichaTecnicaProdutoControllerObj->adicionarAtravesProjeto($produtoNovoObj->lancamento_projetos_id, $lancamentoProjetoProdutoObj->id);
        }
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    private function integracaoProdutoNovoNasajon($codigo, $descricao, $unidademedida, $precovenda, $origem_mercadoria, $grupo_de_inventario, $composicao, $id_projeto = '', $ncm = '', $peso = ''){
        $query = ProdutoNasajon::where('codigo', $codigo);
        $result = $query->first();
        if(!empty($ncm)){
            $NcmNasajonObj = NcmNasajon::where('ncm', $ncm)->first();
            $ncm_id = $NcmNasajonObj->ncm;
        }else{
            $ncm_id = 'null';
        }
        $peso = parserNumber($peso);
        if(empty($result)){
            $sql_insertproduto = "do
            $$
            
            declare t_produto estoque.tprodutonovo;
            declare VAR_RETURN ns.trecibo;
            
            begin
            
            
            t_produto.codigo = '".$codigo."';
            t_produto.especificacao = '".$descricao."';
            t_produto.unidadedemedida = '".$unidademedida."';
            t_produto.precovenda = ".floatval(str_replace(",",".", str_replace(".", "", $precovenda))).";
            t_produto.origemmercadoria = ".$origem_mercadoria.";
            t_produto.grupodeinventario = ".$grupo_de_inventario.";
            t_produto.composicao = '".$composicao."';
            t_produto.pesobruto = ".($peso * (1 + (1 / 100))).";
            t_produto.pesoliquido = ".$peso.";
            t_produto.tipi = '".$ncm_id."';
            t_produto.controlalote = true;
            t_produto.figuratributaria = '418ecc74-8afd-46eb-aa20-ff2144b742b4';
            
            t_produto.produto = uuid_generate_v4();
            t_produto.observacaonaofiscal = '';
            t_produto.grupoempresarial = '424b5493-92de-4f39-962f-46673398aa3e';
            
            VAR_RETURN := integracoes.api_produtonovo(t_produto);
            
            IF VAR_RETURN.mensagem->>'codigo' = 'ERRO' THEN
                raise exception 'Não foi possível criar o produto: %',VAR_RETURN.mensagem->>'mensagem' ;
            END IF;
            
            end
            $$";
    
            try{
                $insert_produto_novo = DB::connection('nasajon')->select($sql_insertproduto);
            }catch(\Exception $e){
                Log::error('Erro ao cadastrar o produto novo');
                if(!empty($id_projeto)){
                    $historicoProjetoObj = new HistoricoProjeto();
                    $historicoProjetoObj->lancamento_projetos_id = $id_projeto;
                    $historicoProjetoObj->natureza = 'error_integracao_nasajon';
                    $historicoProjetoObj->motivo = 'Erro ao gerar cadastro produto: '.$descricao.'\n Mensagem de Erro: '.$e->getMessage();
                    $historicoProjetoObj->users_id = Auth::id();
                    $historicoProjetoObj->created_by = Auth::id();
                    $historicoProjetoObj->save();
                }
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $e,
                    'response' => []
                ];
            }
        }
        return "";
    }

    private function getUnidade(){
        $query = UnidadeMedidaNasajon::select();
        $query->where('descricao', '<>', '');
        $query->orderBy('descricao');
        $result = $query->get();

        $unidades[''] = "Selecione a Unidade";

        foreach($result as $unidade){
            $unidades[$unidade->codigo] = $unidade->descricao;
        }

        return $unidades;
    }

    private function getOrigemMercadoria(){
        $query = OrigemMercadoria::select();
        $query->orderBy('id');
        $result = $query->get();

        foreach($result as $origem_mercadoria){
            $origem_mercadorias[$origem_mercadoria->id] = $origem_mercadoria->id.' - '.$origem_mercadoria->descricao;
        }

        return $origem_mercadorias;
    }

    private function getGrupoDeInventario(){
        $query = GrupoDeInventario::select();
        $query->orderBy('id');
        $result = $query->get();

        foreach($result as $grupo_de_inventario){
            $grupo_de_inventarios[$grupo_de_inventario->id] = $grupo_de_inventario->id.' - '.$grupo_de_inventario->descricao;
        }

        return $grupo_de_inventarios;
    }

    private function getUnidadeNasajon($value){
        $query = UnidadeMedidaNasajon::select('unidade');
        $query->where('codigo', $value);
        $result = $query->first();

        return $result->unidade;
    }

    public function precoBase($preco_final, $tipo_frete, $origem, $estado, $valor_ipi, $prazo_medio, $tipo_cliente, $desconto, $procedencia = "nacional"){

        switch(strtoupper($tipo_frete)){
            case "CIF":
                $query_frete = AliquotaPreco::select()->where('origem', $origem)->where('estado', $estado);
                $frete = $query_frete->first()->frete_adicional;
                break;
            default:
                $frete = 0;
        }

        $valor_frete = 1 + ($frete / 100);

        $valor_ipi = 1 + ($valor_ipi / 100);

        if($origem === 'RO' || $procedencia === "internacional"){
            $icm_base = 0.04;
            $query_icms = AliquotaPreco::select()->where('origem', $origem)->where('estado', $estado)->where('internacional', true);
        }else{
            $icm_base = 0.12;
            $query_icms = AliquotaPreco::select()->where('origem', $origem)->where('estado', $estado)->where('internacional', false);
        }

        
        switch($tipo_cliente){
            case "isento":
                $icms = $query_icms->first()->icms_venda_cliente_isento;
                break;
            default:
                $icms = $query_icms->first()->icms_venda;
                break;
        }

        $valor_icms = 1 + (($icms / 100) - $icm_base);
        switch($origem){
            case "RO":
                $estabelecimento = "3";
                break;
            case "TO":
                $estabelecimento = "4";
                break;
            default:
                $estabelecimento = "5";
        }

        $query_margem_prazo = MargemPrazo::select();
        $query_margem_prazo->where('estabelecimento', $estabelecimento);
        $fator_diario = $query_margem_prazo->first()->fator_diario;

        if ($prazo_medio < 15){
            $valor_prazo_medio = 1 + (0 * $fator_diario);
        }
        else if($prazo_medio >= 15 && $prazo_medio < 30){
            $valor_prazo_medio = 1 + (15 * $fator_diario);
        }
        else if($prazo_medio >= 30 && $prazo_medio < 45){
            $valor_prazo_medio = 1 + (30 * $fator_diario);
        }
        else if($prazo_medio >= 45 && $prazo_medio < 60){
            $valor_prazo_medio = 1 + (45 * $fator_diario);
        }
        else if($prazo_medio >= 60 && $prazo_medio < 75){
            $valor_prazo_medio = 1 + (60 * $fator_diario);
        }
        else if($prazo_medio >= 75 && $prazo_medio < 90){
            $valor_prazo_medio = 1 + (75 * $fator_diario);
        }
        else if($prazo_medio >= 90 && $prazo_medio < 120){
            $valor_prazo_medio = 1 + (90 * $fator_diario);
        }else{
            $valor_prazo_medio = 1 + (120 * $fator_diario);
        }

        $desconto = 1 - ($desconto / 100);

        $preco_base = ($preco_final / ($valor_prazo_medio * ($valor_ipi * $valor_frete * $valor_icms))) / $desconto;

        return round($preco_base, 2);
    }
}
