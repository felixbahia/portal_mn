<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\User;
use App\Cliente;
use App\Produto;
use App\ProdutoPromocional;
use App\ListagemDePrecosController;
use App\ClienteNasajon;

use App\Helpers;

use Carbon\Carbon;

use App\Http\Controllers\ProdutoController;

use App\Http\Requests\ProdutoPromocionalNovoRequest;
use App\Http\Requests\ProdutoPromocionalEditarRequest;

class ProdutoPromocionalController extends Controller
{
    private $estabelecimentos = [];

    public function __construct(){
        $estabelecimentos = returnEmpresasNasajonView();
        ksort($estabelecimentos);
        $this->estabelecimentos = $estabelecimentos;
    }
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ListaPrecosProdutosPromocionais") === false){
            return abort(403);
        }
        $estabelecimentos = $this->dadosEstabelecimentos();    
        $tipo_frete = $this->dadosFrete();
        $dropdown_usuarios = $this->dadosVendedor();;
        $request->session()->flash('model', 'App\ListaPrecosProdutosPromocionais');
        return view('programs.produto_promocional.index')->with(['dropdown_usuarios' => $dropdown_usuarios, 'estabelecimentos'=> $estabelecimentos]);
    }

    public function modalAdicionar(){
        $estabelecimentos = $this->dadosEstabelecimentos();    

        $tipo_frete = $this->dadosFrete();

        $dropdown_usuarios = $this->dadosVendedor();

        $comissao = $this->getComissao();

        $tipo_promocional = $this->getTipoPromocional();

        return view('programs.produto_promocional.modal.adicionar')->with(['dropdown_usuarios' => $dropdown_usuarios, 'estabelecimentos'=> $estabelecimentos, 'tipo_frete' => $tipo_frete, 'comissao' => $comissao, 'tipo_promocional' => $tipo_promocional]);
    }

    public function modalEditar(Request $request){
        $id = $request->only(['id'])['id'];
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
        try{
            $query = ProdutoPromocional::select();
            $query->with('produto', 'produto.preco', 'cliente');
            $query->where('id', $id);
            $result = $query->first();
            $dados = [
                'grupo' => $result->grupo,
                'codigo_produto' => empty($result->codigo_produto)? '':$result->codigo_produto,
                'descricao' => empty($result->produto)? '': $result->produto->descricao,
                'cliente' => empty($result->cliente)? '':$result->cliente->nome.' - '.$result->cliente->cpf_cnpj,
                'codigo_cliente' => $result->codigo_cliente,
                'estabelecimento' => $result->codigo_estabelecimento,
                'tipo_frete' => $result->tipo_frete,
                'codigo_vendedor' => $result->codigo_vendedor,
                'preco_real' => !empty($result->preco_real)?parserValor($result->preco_real):'',
                'desconto_porcentagem' => !empty($result->desconto_porcentagem)?parserValor($result->desconto_porcentagem):'',
                'procedencia' => empty($result->produto)? '':$result->produto->procedencia,
                'preco_fob' => empty($result->produto)? '':parserValor($result->produto->preco->preco_real),
                'data_expiracao' => empty($result->data_expiracao)? '' : parserData($result->data_expiracao),
                'id' => encrypt($id),
                'comissao' => (string) ($result->comissao),
                'sem_desconto_adicional' => $result->sem_desconto_adicional,
                'tipo_promocional' => $result->tipo_promocional
            ];
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $estabelecimentos = $this->dadosEstabelecimentos();    

        $tipo_frete = $this->dadosFrete();

        $dropdown_usuarios = $this->dadosVendedor();

        $comissao = $this->getComissao();

        $tipo_promocional = $this->getTipoPromocional();

    	return view("programs.produto_promocional.modal.editar")->with(['dropdown_usuarios' => $dropdown_usuarios, 'estabelecimentos'=> $estabelecimentos, 'tipo_frete' => $tipo_frete, 'dados' => $dados, 'comissao' => $comissao, 'tipo_promocional'=> $tipo_promocional]);
    }

    public function modalDeletar(Request $request){
        $id = $request->only(['id'])['id'];
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
        try{
            $query = ProdutoPromocional::select();
            $query->with(['produto' => function($query){
                $query->with(['preco']);
            }, 'cliente', 'vendedor']);
            $query->where('id', $id);
            $result = $query->first();
            $dados = [
                'grupo' => $result->grupo,
                'codigo_produto' => empty($result->codigo_produto)? 'Todos':$result->codigo_produto,
                'descricao' => empty($result->produto)? 'Todos': $result->produto->descricao,
                'cliente' => empty($result->cliente)? 'Todos':$result->cliente->nome.' - '.$result->cliente->cpf_cnpj,
                'codigo_cliente' => $result->codigo_cliente,
                'estabelecimento' => empty($result->codigo_estabelecimento)?'Todos':$this->estabelecimentos[intval($result->codigo_estabelecimento)],
                'tipo_frete' => $result->tipo_frete,
                'vendedor' => empty($result->vendedor->name)? 'Todos':$result->vendedor->name,
                'preco_real' => parserValor($result->preco_real),
                'procedencia' => empty($result->produto)? '':$result->produto->procedencia,
                'preco_fob' => empty($result->produto)? '':parserValor($result->produto->preco->preco_real),
                'data_expiracao' => parserData($result->data_expiracao),
                'id' => encrypt($id),
                'sem_desconto_adicional' => $result->sem_desconto_adicional,
                'tipo_promocional' => $result->tipo_promocional
            ];
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
    	return view("programs.produto_promocional.modal.deletar")->with(['dados' => $dados]);
    }

    public function adicionar(ProdutoPromocionalNovoRequest $request){
        $fields = $request->only(
            'codigo_produto',
            'descricao',
            'estabelecimento',
            'tipo_frete',
            'vendedor',
            'preco_real',
            'data_expiracao',
            'codigo_cliente',
            'grupo',
            'comissao',
            'cliente',
            'preco_fob_val',
            'sem_desconto_adicional',
            'tipo_promocional',
            'desconto_porcentagem',
            'tipo_desconto'
        );

        $data_expiracao = Carbon::createFromFormat('d/m/Y', $fields['data_expiracao']);

        if($fields['tipo_promocional'] === "pedido"){
            $ProdutoPromocionalObj = new ProdutoPromocional;
            $ProdutoPromocionalObj->grupo = strtoupper($fields['grupo']);
            $ProdutoPromocionalObj->codigo_produto = $fields['codigo_produto'];
            $ProdutoPromocionalObj->codigo_estabelecimento = $fields['estabelecimento']? str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT):null;
            $ProdutoPromocionalObj->tipo_frete = $fields['tipo_frete'];
            $ProdutoPromocionalObj->codigo_cliente = $fields['codigo_cliente'];
            $ProdutoPromocionalObj->codigo_vendedor = $fields['vendedor'];
            if($fields['tipo_desconto'] == 'valor'){
                $ProdutoPromocionalObj->preco_real = str_replace(',', '.', $fields['preco_real']); 
                $ProdutoPromocionalObj->desconto_porcentagem = null;
            }
            else if($fields['tipo_desconto'] == 'porcentagem'){
                $ProdutoPromocionalObj->desconto_porcentagem = str_replace(',', '.', $fields['desconto_porcentagem']); 
                $ProdutoPromocionalObj->preco_real = null; 

            }
            $ProdutoPromocionalObj->data_expiracao = $data_expiracao->format('Y-m-d');
            $ProdutoPromocionalObj->comissao = floatval($fields['comissao']);
            $ProdutoPromocionalObj->sem_desconto_adicional = !empty($fields['sem_desconto_adicional']) ? $fields['sem_desconto_adicional'] : false;
            $ProdutoPromocionalObj->tipo_promocional = "pedido";
            $ProdutoPromocionalObj->created_by = Auth::id();
            $ProdutoPromocionalObj->save();
        }else if($fields['tipo_promocional'] === "projeto"){
            $ProdutoPromocionalObj = new ProdutoPromocional;
            $ProdutoPromocionalObj->codigo_cliente = $fields['codigo_cliente'];
            $ProdutoPromocionalObj->codigo_vendedor = $fields['vendedor'];
            $ProdutoPromocionalObj->data_expiracao = $data_expiracao->format('Y-m-d');
            $ProdutoPromocionalObj->comissao = floatval($fields['comissao']);
            $ProdutoPromocionalObj->tipo_promocional = "projeto";
            $ProdutoPromocionalObj->created_by = Auth::id();
            $ProdutoPromocionalObj->save();
        }


        $this->enviarEmail($fields, $ProdutoPromocionalObj->created_detalhes->name);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
        
    }

    public function editar(ProdutoPromocionalEditarRequest $request){
        $id = $request->only(['id'])['id'];
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
        
        $fields = $request->only(
            'codigo_produto',
            'descricao',
            'estabelecimento',
            'tipo_frete',
            'vendedor',
            'preco_real',
            'data_expiracao',
            'codigo_cliente',
            'grupo',
            'comissao',
            'cliente',
            'preco_fob_val',
            'sem_desconto_adicional',
            'tipo_promocional',
            'desconto_porcentagem',
            'tipo_desconto'
        );

        $data_expiracao = Carbon::createFromFormat('d/m/Y', $fields['data_expiracao']);
        
        $ProdutoPromocionalObj = ProdutoPromocional::find($id);
        if($fields['tipo_promocional'] === "pedido"){
            $ProdutoPromocionalObj->grupo = $fields['grupo'];
            $ProdutoPromocionalObj->codigo_produto = $fields['codigo_produto'];
            $ProdutoPromocionalObj->codigo_estabelecimento = $fields['estabelecimento']? str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT):null;
            $ProdutoPromocionalObj->tipo_frete = $fields['tipo_frete'];
            $ProdutoPromocionalObj->codigo_cliente = $fields['codigo_cliente'];
            $ProdutoPromocionalObj->codigo_vendedor = $fields['vendedor'];
            if($fields['tipo_desconto'] == 'valor'){
                $ProdutoPromocionalObj->preco_real = str_replace(',', '.', $fields['preco_real']);
                $ProdutoPromocionalObj->desconto_porcentagem = null;
            }
            else if($fields['tipo_desconto'] == 'porcentagem'){
                $ProdutoPromocionalObj->desconto_porcentagem = str_replace(',', '.', $fields['desconto_porcentagem']); 
                $ProdutoPromocionalObj->preco_real = null;
            }
            $ProdutoPromocionalObj->data_expiracao = $data_expiracao->format('Y-m-d');
            $ProdutoPromocionalObj->comissao = floatval($fields['comissao']);
            $ProdutoPromocionalObj->sem_desconto_adicional = !empty($fields['sem_desconto_adicional']) ? $fields['sem_desconto_adicional'] : false;
            $ProdutoPromocionalObj->tipo_promocional = 'pedido';
            $ProdutoPromocionalObj->updated_by = Auth::id();
        }else if($fields['tipo_promocional'] === "projeto")  {
            $ProdutoPromocionalObj->grupo = null;
            $ProdutoPromocionalObj->codigo_produto = null;
            $ProdutoPromocionalObj->codigo_estabelecimento = null;
            $ProdutoPromocionalObj->tipo_frete = null;
            $ProdutoPromocionalObj->codigo_cliente = $fields['codigo_cliente'];
            $ProdutoPromocionalObj->codigo_vendedor = $fields['vendedor'];
            $ProdutoPromocionalObj->preco_real = null; 
            $ProdutoPromocionalObj->data_expiracao = $data_expiracao->format('Y-m-d');
            $ProdutoPromocionalObj->comissao = floatval($fields['comissao']);
            $ProdutoPromocionalObj->sem_desconto_adicional = false;
            $ProdutoPromocionalObj->tipo_promocional = 'projeto';
            $ProdutoPromocionalObj->updated_by = Auth::id();
        }
        $ProdutoPromocionalObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
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
            ]);
        }
        
        
        $ProdutoPromocionalObj = ProdutoPromocional::find($id);
        $ProdutoPromocionalObj->deleted_by = Auth::id();
        $ProdutoPromocionalObj->save();
        $ProdutoPromocionalObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only(
            'codigo_produto',
            'descricao',
            'estabelecimento',
            'grupo',
            'marca',
            'codigo_cliente',
            'cliente',
            'vendedor',
            'data_inicio',
            'data_fim',
            'ativos'
        );
        $pesquisa_cliente = false;
        $estabelecimento = $fields['estabelecimento']? str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT):'';
        $query = ProdutoPromocional::select();
        $query->with(['cliente' => function($query) use ($fields){
            $query->select('codigo', 'nome', 'cpf_cnpj');
            if(!empty($fields['cliente'])){
                $cliente_busca = ClienteNasajon::select('codigo')->whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ILIKE \'%'.utf8_decode($fields['cliente']).'%\'')->get();
                $cod_cliente = $cliente_busca->pluck('codigo');
                $query->whereIn('codigo', $cod_cliente);
            }
        }]);
        $query->with(['vendedor' => function($query){
            $query->select('id', 'name');
        }]);
        $query->with(['produto' => function($query) use ($fields){
            $query->select();
            if($fields['descricao']){
                $query->where('descricao', 'ilike', '%'.$fields['descricao'].'%');
            }
        }]);
        if(!empty($fields['codigo_produto'])){
            $query->where('codigo_produto', 'ilike', '%'.$fields['codigo_produto'].'%');
        }
        if(!empty($fields['estabelecimento'])){
            $query->where('codigo_estabelecimento', '=', $estabelecimento);
        }
        if(!empty($fields['vendedor'])){
            $query->where('codigo_vendedor', '=', $fields['vendedor']);
        }
        if(!empty($fields['data_inicio']) and !empty($fields['data_fim'])){
            $query->whereBetween('data_expiracao', [$fields['data_inicio'], $fields['data_fim']]);
        }else{
            if(!empty($fields['data_inicio']) && empty($fields['data_fim'])){
                $data_expiracao = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0,0,0);
                $query->where('data_expiracao', '>=', $data_expiracao);
            }else if(empty($fields['data_inicio']) && !empty($fields['data_fim'])){
                $data_expiracao = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->setTime(0,0,0);
                $query->where('data_expiracao', '<=', $data_expiracao);
            }
        }
        if(!empty($fields['cliente'])){
            $pesquisa_cliente = true;
        }
        if(!empty($fields['grupo'])){
            $query->where('grupo', 'ilike', '%'.strtoupper(utf8_decode(trim($fields['grupo']))).'%');
        }
        if(isset($fields['ativos']) && !empty($fields['ativos'])){
            $hoje = Carbon::Now();
            $query->where('data_expiracao', '>=', $hoje->format('Y-m-d'));
        }
        
        $result = $query->get();
        $produtos = [];

        foreach($result as $produto){
            $produtos[] = [
                'grupo' => empty($produto->grupo)? 'Todos': $produto->grupo,
                'descricao' => empty($produto->produto)? "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Todos'>Todos</div></div>" : "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='".($produto->codigo_produto." - ".$produto->produto->descricao)."'>".($produto->codigo_produto." - ".$produto->produto->descricao)."</div></div>",
                'estabelecimento' => empty($produto->codigo_estabelecimento)? "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Todos'>Todos</div></div>" : "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='".$this->estabelecimentos[intval($produto->codigo_estabelecimento)]."'>".$this->estabelecimentos[intval($produto->codigo_estabelecimento)]."</div></div>",
                'cliente' => empty($produto->cliente)? 'Todos':"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='".($produto->cliente->nome)." - ".$produto->cliente->cpf_cnpj."'>".($produto->cliente->nome)." - ".$produto->cliente->cpf_cnpj."</div></div>",
                'vendedor' => empty($produto->vendedor)? 'Todos':"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='".($produto->vendedor->name)."'>".($produto->vendedor->name)."</div></div>",
                'preco_real' => empty($produto->preco_real)? '' : parserValor($produto->preco_real),
                'desconto_porcentagem' => empty($produto->desconto_porcentagem)?'':parserValor($produto->desconto_porcentagem).'%',
                'data_expiracao' => empty($produto->data_expiracao)? '' : parserData($produto->data_expiracao),
                'id' => encrypt($produto->id),
                'tipo_promocional' => $produto->tipo_promocional
            ];
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $produtos
        ];
        return response()->json($retorno);
    }

    private function array_replace_key($array, $retorno_array, $pesquisa_cliente, $table){
        $newarray = [];
        $retorno = [];
        foreach($array as $key => $value){
            foreach($value as $key_value => $dados){
                switch($key_value){
                    case 'produto':
                        if($table){
                            $newarray[$key_value] = $dados?"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='".utf8_encode($dados['CODPRD']." - ".$dados['DESCR'])."'>".utf8_encode($dados['CODPRD']." - ".$dados['DESCR'])."</div></div>":'Todos';
                        }else{
                            $newarray[$key_value] = $dados?utf8_encode($dados['CODPRD']." - ".$dados['DESCR']):'Todos';
                        }
                        break;
                    case 'estabelecimento':
                        $newarray[$key_value] = $dados['apelido']? utf8_encode($dados['ESTABEL']." - ".$dados['apelido']):'Todos';
                        break;
                    case 'cliente':
                        if($table){
                            $newarray[$key_value] = $dados['nome']?"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='".utf8_encode($dados['nome'])."'>".utf8_encode($dados['nome'])."</div></div>":'Todos';
                        }else{
                            $newarray[$key_value] = $dados['nome']?$dados['nome']." - ".$dados['CGC_CPF']:'Todos';
                        }
                        break;
                    case 'vendedor':
                        if($table){
                            $newarray[$key_value] = $dados['name']?"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='".utf8_encode($dados['name'])."'>".utf8_encode($dados['name'])."</div></div>":'Todos';
                        }else{
                            $newarray[$key_value] = $dados['name']?utf8_encode($dados['name']):'Todos';
                        }
                        break;
                    case 'data_expiracao':
                        $data = new Carbon($dados);
                        $data = $data->format('d/m/Y');
                        $newarray['data_expiracao'] = $data;
                        break;
                    case 'PRCVND_PREFIX_A':
                    case 'preco_real':
                    case 'preco_dolar':
                        $newarray[$key_value] = $dados?parserValor($dados):''; 
                        break;
                    case 'id':
                        $newarray[$key_value] = encrypt($dados);
                        break;
                    case 'codigo_produto':
                    case 'codigo_estabelecimento':
                    case 'codigo_cliente':
                        break;
                    default:
                        $newarray[$key_value] = $dados; 
                        break;
                }
            }
            if($retorno_array){
                return $newarray;
            }
            if($pesquisa_cliente){
                if(strcasecmp($newarray['cliente'], 'Todos') != 0){
                    $retorno[] = $newarray;
                }
            }else{
                $retorno[] = $newarray; 
            }
        }
        return $retorno;

    }

    private function dadosFrete(){
        return $tipo_frete = [
            '' => 'Todos os fretes',
            'CIF' => 'CIF',
            'FOB' => 'FOB'
        ];
    }

    private function dadosVendedor(){
        $dropdown_usuarios = ['' => 'Todos os vendedores'];
        if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
            if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::id()))->orderBy('codigo_representante', 'asc')->get();
            }
            else if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->orderBy('codigo_representante', 'asc')->get();
                $status_pedido['cancelados'] = 'Cancelados';
            }
            else if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::user()->responsavel))->orderBy('codigo_representante', 'asc')->get();
            }
            foreach ($users as $user) {
                if(is_null($user->tipo_usuario)){
                    continue;
                }
                if (!is_null($user->codigo_representante)){
                    $dropdown_usuarios[$user->id] = strtoupper($user->codigo_representante." - ".$user->name);
                }

            }
        }

        return $dropdown_usuarios;
    }

    private function dadosEstabelecimentos(){
        $estabelecimentos = ['' => 'Escolha um estabelecimento'];
        $estabelecimentos = array_merge($estabelecimentos, returnEmpresasNasajonView());
        unset($estabelecimentos[0]);
        return $estabelecimentos; 
    }

    private function enviarEmail($fields, $nome){
		try{

            $mensagem = '';

			$EmailObj = new EmailController();

            $email_send = [];

            if(!empty($fields['grupo'])){
                $mensagem = $mensagem."<p><b>Grupo:</b> ".$fields['grupo'];
                if(!empty($fields['codigo_produto'])){
                    $mensagem = $mensagem."<p><b>Produto:</b> ".$fields['codigo_produto']." - ".$fields['descricao'];
                }
            }
            
            if(!empty($fields['cliente'])){
                $mensagem = $mensagem."<p><b>Cliente:</b> ".$fields['cliente'];
            }else{
                $mensagem = $mensagem."<p><b>Cliente:</b> Todos";
            }
            
            if(!empty($fields['estabelecimento'])){
                $mensagem = $mensagem."<p><b>Estabelecimento:</b> ".$this->estabelecimentos[intval($fields['estabelecimento'])];
            }else{
                $mensagem = $mensagem."<p><b>Estabelecimento:</b> Todos";
            }
            if(!empty($fields['tipo_frete'])){
                $mensagem = $mensagem."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tipo de Frete:</b> ".$fields['tipo_frete'];
            }else{
                $mensagem = $mensagem."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tipo de Frete:</b> Todos";
            }
            if(!empty($fields['vendedor'])){
                $dropdown_usuarios = $this->dadosVendedor();
                $mensagem = $mensagem."<p><b>Vendedor:</b> ".$fields['vendedor']." - ".$dropdown_usuarios[$fields['vendedor']];
            }else{
                $mensagem = $mensagem."<p><b>Vendedor:</b> Todos";
            }
            if($fields['tipo_desconto'] == 'valor'){
                $mensagem = $mensagem."<p><b>Preço Real:</b> ".$fields['preco_real'];
            }
            else if($fields['tipo_desconto'] == 'porcentagem'){
                $mensagem = $mensagem."<p><b>Porcentagem de desconto:</b> ".$fields['desconto_porcentagem'].'%';
            }
    
            $mensagem = $mensagem."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Preço FOB À Vista:</b> ".parserValor($fields['preco_fob_val']);
            if(!empty($fields['data_expiracao'])){
                $mensagem = $mensagem."<p><b>Data de Expiração:</b> ".$fields['data_expiracao'];
            }
            if(!empty($fields['comissao'])){
                $mensagem = $mensagem."<p><b>Comissão:</b> ".parserValor($fields['comissao'])."%";
            }
            $mensagem = $mensagem."<p><b>Usuário:</b> ".$nome."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Data:</b> ".date('d/m/Y')." &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Horário:</b> ".date('H:i');

            $variaveis = [
                'dados_promocional' => $mensagem,
            ];

            $EmailObj->sendEmailToken(1, "cadastro_produto_promocional", $email_send, $variaveis);
		}catch(\Exception $e){

		}

    }


    private function getComissao(){
        $comissao["1"] = "1,00%";
        $comissao["1.5"] = "1,50%";
        $comissao["2"] = "2,00%"; 
        $comissao["2.5"] = "2,50%"; 
        $comissao["3"] = "3,00%"; 
        $comissao["5"] = "5,00%"; 

        return $comissao;
    }

    private function getTipoPromocional(){
        $retorno["pedido"] = "Pedido";
        $retorno["projeto"] = "Projeto";

        return $retorno;
    }
}