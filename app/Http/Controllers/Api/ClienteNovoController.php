<?php

namespace App\Http\Controllers\Api;

use App\Cliente;
use App\ClienteNovo;
use App\ClienteNovoSocio;
use App\ClienteNovoReferencia;
use App\CondicoesPagamentoWeb;
use App\Vencimentos;
use App\CepEstado;
use App\Transportador;
use Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Requests\Api\ClienteNovoRequestApi;

use App\Http\Controllers\PedidoPortalController;
use App\Http\Controllers\ClienteController;

class ClienteNovoController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:api']);
    }
    public $successStatus = 200;
    public $errorStatus = 403;

    public function busca(Request $request){
        header('Access-Control-Allow-Origin: *');
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '2024M');
        $fields = $request->only(['id', 'codigo', 'nome_razao', 'nome_guerra', 'cpf_cnpj', 'limit', 'offset', 'count']);
        
        if(
            (!isset($fields['id']) || strlen(trim($fields['id'])) == 0) &&
            (!isset($fields['codigo']) || strlen(trim($fields['codigo'])) == 0) &&
            (!isset($fields['nome_razao']) || strlen(trim($fields['nome_razao'])) == 0) &&
            (!isset($fields['nome_guerra']) || strlen(trim($fields['nome_guerra'])) == 0) &&
            (!isset($fields['cpf_cnpj']) || strlen(trim($fields['cpf_cnpj'])) == 0)
        ){
            foreach ($fields as $key => $value) {
                if(empty($value)){
                    $fields[$key] = '';
                }
            }
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Necessário informar um campo para a busca",
                        "user" => "Necessário informar um campo para a busca"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
        if(
            !isset($fields['limit']) || strlen(trim($fields['limit'])) == 0 ||
            !isset($fields['offset']) || strlen(trim($fields['offset'])) == 0
        ){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Paramentro(s) informado(s) inválido(s)",
                        "user" => "Paramentro(s) informado(s) inválidos(s)"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
        $where = [];
        if(!empty($fields['codigo'])){
            $where[] = ['CODCAD', $fields['codigo']];
        }
        if(!empty($fields['id'])){
            $where[] = ['CODCAD', $fields['id']];
        }
        if(!empty($fields['nome_razao'])){
            $where[] = ['LOWER(NOME)', 'like', strtolower($fields['nome_razao'])];
        }
        if(!empty($fields['nome_guerra'])){
            $where[] = ['LOWER(GUERRA)', 'like', strtolower($fields['nome_guerra'])];
        }
        if(!empty($fields['cpf_cnpj'])){
            $where[] = ['CGC_CPF', 'like', strtolower($fields['cpf_cnpj'])];
        }
        $query = Cliente::select('CODCAD as id', 'NOME as nome_razao', 'GUERRA as nome_guerra', 'CGC_CPF as cpf_cnpj', 'CIDADE as cidade', 'ESTADO as estado');
        $query_total = Cliente::select('*');
        foreach($where as $value){
            if(count($value) === 3){
                $query->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
                $query_total->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
            } else {
                $query->where($value[0], $value[1]);
                $query_total->where($value[0], $value[1]);
            }
        }
        if(Auth::user()->tipo_usuario_id === 12){
            if(!empty(Auth::user()->codigo_representante)){
                $query->where("CODVND", Auth::user()->codigo_representante);
                $query_total->where("CODVND", Auth::user()->codigo_representante);
            }
        }
        $offset = intval($fields["offset"]);
        $limit = intval($fields["limit"]);
        
        // $query->where("ATIVIDADE", "<", 51);
        // $query_total->where("ATIVIDADE", "<", 51);
        $query->offset($offset);
        $query->limit($limit);
        $query->orderBy('NOME');
        if($fields['count'] === "true"){
            $total = $query_total->count();
        }
        unset($query_total);
        $query = $query->get();
        $results_user = $query->toArray();
        unset($query);
        $key = 0;
        $results = [];
        foreach($results_user as $result){
            $result = (array) $result;
            foreach($result as $k => $value){
                if(empty($value)){
                    $results[$key][strtolower($k)] = "";
                }else{
                    $results[$key][strtolower($k)] = trim(utf8_encode($value));
                }
            }
            if(strpos(trim($result["cpf_cnpj"]), "___.___.___-__") !== false){
                $results[$key]["cpf_cnpj"] = "";
                
            }
            $key++;
        }
        foreach ($fields as $key => $value) {
            if(empty($value)){
                $fields[$key] = '';
            }
        }
        if(count($results) == 0){
            $error = [
                'error' =>[
                    "error" => true,
                    "msg" => [
                        "dev" => "Sua pesquisa não retornou nenhum cliente",
                        "user" => "Sua pesquisa não retornou nenhum cliente"
                    ]
                ],
                'request' => $fields,
                'response' => []
            ];
            return response()->json($error, $this->errorStatus); 
        }
        if($fields['count'] === "true"){
            $response = [
                "total" => intval($total),
                "clientes" => $results
            ];
        }else{
            $response = [
                "clientes" => $results
            ];
        }
        
        $success = [
            'error' =>[
                "error" => false,
                "msg" => [
                    "dev" => "",
                    "user" => ""
                ]
            ],
            'request' => $fields,
            'response' => $response
        ];
        return response()->json($success, $this->successStatus);
    }

    public function cadastro(ClienteNovoRequestApi $request){
        header('Access-Control-Allow-Origin: *');
        $error = [
            "error" => [
                "error" => true,
                "msg" => [
                    "dev" => "Cadastro temporariamente desativado! Utilize o portal.tecidosmn.com.br para o cadastro!",
                    "user" => "Cadastro temporariamente desativado! Utilize o portal.tecidosmn.com.br para o cadastro!"
                ]
            ],
            "request" => $fields,
            "response" => new \stdClass()
        ];
        return response()->json($error, $this->errorStatus); 
        $fields = $request->all();
        $fields = $fields['param'];
        $clienteNovoObj = new ClienteNovo();
        $clienteNovoObj->status = 1;
        $clienteNovoObj->ja_foi_cliente = $fields['basicos']['ja_foi_cliente'] == 'true' ?'sim':'nao';
        $clienteNovoObj->ja_foi_cliente_quando = (isset($fields['basicos']['ja_foi_cliente_quando']) && empty($fields['basicos']['ja_foi_cliente_quando'])) ? $fields['basicos']['ja_foi_cliente_quando'] : null;
        $clienteNovoObj->fisica_juridica = substr($fields['basicos']['fisica_juridica'], 0, 1);
        $clienteNovoObj->cpf_cnpj = (strtolower(substr($fields['basicos']['fisica_juridica'], 0, 1)) === "f") ? $fields['basicos']['cpf'] : $fields['basicos']['cnpj'];
        $clienteNovoObj->inscricao_estadual = $fields['basicos']['inscricao_estadual'];
        $clienteNovoObj->inscricao_municipal = $fields['basicos']['inscricao_municipal'];
        $clienteNovoObj->nome_razao = $fields['basicos']['nome_razao'];
        $clienteNovoObj->guerra_apelido = $fields['basicos']['guerra_apelido'];
        $clienteNovoObj->telefone = $fields['basicos']['telefone'];
        $clienteNovoObj->telefone_fax = $fields['basicos']['telefone_fax'];
        $clienteNovoObj->email = $fields['basicos']['email'];
        $clienteNovoObj->vendedor_codigo = Auth::user()->codigo_representante;
        $clienteNovoObj->transportador_codigo = $fields['basicos']['transportador_codigo'];
        $clienteNovoObj->forte_cliente = isset($fields['basicos']['forte_cliente']) ? $fields['basicos']['forte_cliente'] : null;
        $clienteNovoObj->tamanho_cliente = isset($fields['basicos']['tamanho_cliente']) ? $fields['basicos']['tamanho_cliente'] : null;
        $clienteNovoObj->ramo_atividade = isset($fields['basicos']['ramo_atividade']) ? $fields['basicos']['ramo_atividade'] : null;
        $clienteNovoObj->numero_filiais = $fields['basicos']['numero_filiais'];
        $clienteNovoObj->numero_empregados = $fields['basicos']['numero_empregados'];
        $clienteNovoObj->predio_proprio = isset($fields['basicos']['predio_proprio']) ? ($fields['basicos']['predio_proprio'] == 'true' ? 'sim':'nao'): null;
        $clienteNovoObj->aluguel = (isset($fields['basicos']['aluguel']) && empty($fields['basicos']['aluguel'])) ? $fields['basicos']['aluguel'] : null;
        $clienteNovoObj->sucessora_de = $fields['basicos']['sucessora_de'];
        $clienteNovoObj->ligacao_com = $fields['basicos']['ligacao_com'];
        $clienteNovoObj->sugestao_credito = $fields['basicos']['sugestao_credito'];
        $clienteNovoObj->historico_cliente_praca = $fields['basicos']['historico_cliente_praca'];
        $clienteNovoObj->faturamento_cep = $fields['faturamento']['faturamento_cep'];
        $clienteNovoObj->faturamento_logradouro = $fields['faturamento']['faturamento_logradouro'];
        $clienteNovoObj->faturamento_numero = $fields['faturamento']['faturamento_numero'];
        $clienteNovoObj->faturamento_complemento = $fields['faturamento']['faturamento_complemento'];
        $clienteNovoObj->faturamento_bairro = $fields['faturamento']['faturamento_bairro'];
        $clienteNovoObj->faturamento_cidade = $fields['faturamento']['faturamento_cidade'];
        $clienteNovoObj->faturamento_estado = strtoupper($fields['faturamento']['faturamento_estado']);
        $clienteNovoObj->faturamento_telefone = $fields['faturamento']['faturamento_telefone'];
        $clienteNovoObj->faturamento_telefone_fax = $fields['faturamento']['faturamento_telefone_fax'];
        $clienteNovoObj->faturamento_email = $fields['faturamento']['faturamento_email'];
        $clienteNovoObj->cobranca_cep = $fields['cobranca']['cobranca_cep'];
        $clienteNovoObj->cobranca_logradouro = $fields['cobranca']['cobranca_logradouro'];
        $clienteNovoObj->cobranca_numero = $fields['cobranca']['cobranca_numero'];
        $clienteNovoObj->cobranca_complemento = $fields['cobranca']['cobranca_complemento'];
        $clienteNovoObj->cobranca_bairro = $fields['cobranca']['cobranca_bairro'];
        $clienteNovoObj->cobranca_cidade = $fields['cobranca']['cobranca_cidade'];
        $clienteNovoObj->cobranca_estado = strtoupper($fields['cobranca']['cobranca_estado']);
        $clienteNovoObj->cobranca_telefone = $fields['cobranca']['cobranca_telefone'];
        $clienteNovoObj->cobranca_telefone_fax = $fields['cobranca']['cobranca_telefone_fax'];
        $clienteNovoObj->cobranca_banco = $fields['cobranca']['cobranca_banco'];
        $clienteNovoObj->cobranca_agencia = $fields['cobranca']['cobranca_agencia'];
        $clienteNovoObj->cobranca_conta = $fields['cobranca']['cobranca_conta'];
        $clienteNovoObj->created_by = Auth::user()->id;

        if($clienteNovoObj->save()){
            if(isset($fields['socios'])){
                foreach ($fields['socios'] as $key => $value) {
                    $ClienteNovoSocioObj = new ClienteNovoSocio();
                    $ClienteNovoSocioObj->cliente_novo_id = $clienteNovoObj->id;
                    $ClienteNovoSocioObj->nome = $value["nome"];
                    $ClienteNovoSocioObj->cpf = $value["cpf"];
                    $ClienteNovoSocioObj->created_by = Auth::user()->id;
                    $ClienteNovoSocioObj->save();
                }
            }
            if(isset($fields['referencias'])){
                foreach ($fields['referencias'] as $key => $value) {
                    $ClienteNovoReferenciaObj = new ClienteNovoReferencia();
                    $ClienteNovoReferenciaObj->cliente_novo_id = $clienteNovoObj->id;
                    $ClienteNovoReferenciaObj->empresa = $value["empresa"];
                    $ClienteNovoReferenciaObj->contato = $value["contato"];
                    $ClienteNovoReferenciaObj->telefone = $value["telefone"];
                    $ClienteNovoReferenciaObj->telefone_ddd = '';
                    $ClienteNovoReferenciaObj->estado = strtoupper($value["estado"]);
                    $ClienteNovoReferenciaObj->cidade = $value["cidade"];
                    $ClienteNovoReferenciaObj->created_by = Auth::user()->id;
                    $ClienteNovoReferenciaObj->save();
                }
            }
            $success = [
                'error' =>[
                    "error" => false,
                    "msg" => [
                        "dev" => "",
                        "user" => ""
                    ]
                ],
                'request' => new \stdClass(),
                'response' => 'Cadastrado com sucesso'
            ];
            return response()->json($success, $this->successStatus);
        } else {
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Ocorreu um erro! Tente novamente.",
                        "user" => "Ocorreu um erro! Tente novamente."
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
    }
    public function buscaCondicoesPagamento(Request $request){
        header('Access-Control-Allow-Origin: *');
        $fields = $request->only(['descricao', 'codigo_cliente', 'limit', 'offset', 'count']);
        
        if(
            !isset($fields['codigo_cliente']) || strlen(trim($fields['codigo_cliente'])) == 0 ||
            !isset($fields['limit']) || strlen(trim($fields['limit'])) == 0 ||
            !isset($fields['offset']) || strlen(trim($fields['offset'])) == 0 ||
            !isset($fields['count']) || strlen(trim($fields['count'])) == 0
        ){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Paramentro(s) informado(s) inválido(s)",
                        "user" => "Paramentro(s) informado(s) inválidos(s)"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
        
        $ClienteObj = Cliente::where("CODCAD", $fields['codigo_cliente'])->first();
        if(is_null($ClienteObj)){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Cliente não encontrado!",
                        "user" => "Cliente não encontrado!"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus);
        }

        $condicoesObj = CondicoesPagamentoWeb::with('clientes')->select('condicoes_pagamento_web.*');
        $condicoesObj->leftJoin('clientes_vencimentos', 'clientes_vencimentos.condicao_id', '=', 'condicoes_pagamento_web.id');

        $query_total = CondicoesPagamentoWeb::with('clientes')->select('*');
        $query_total->leftJoin('clientes_vencimentos', 'clientes_vencimentos.condicao_id', '=', 'condicoes_pagamento_web.id');

        if (isset($fields['descricao']) && !empty($fields['descricao'])){
            $termos = explode(' ', $fields['descricao']);
            foreach ($termos as $value) {
                $condicoesObj->whereRaw('TRANSLATE(condicoes_pagamento_web.descricao,\'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ\',\'aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC\') ilike \'%' . strtolower(trim(utf8_decode($value))) . '%\'');
                $query_total->whereRaw('TRANSLATE(condicoes_pagamento_web.descricao,\'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ\',\'aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC\') ilike \'%' . strtolower(trim(utf8_decode($value))) . '%\'');
            }
        }
        if (Auth::user()->tipo_usuario_id == 12){
            $condicoesObj->where('condicoes_pagamento_web.liberado_representante', true);
            $query_total->where('condicoes_pagamento_web.liberado_representante', true);
        }

        $condicoesObj->where(function($query) use ($ClienteObj) {
            $query->where('clientes_vencimentos.cliente', $ClienteObj->CODCAD);
            $query->OrWhereNull('clientes_vencimentos.cliente');
        });
        $query_total->where(function($query) use ($ClienteObj) {
            $query->where('clientes_vencimentos.cliente', $ClienteObj->CODCAD);
            $query->OrWhereNull('clientes_vencimentos.cliente');
        });


        $offset = intval($fields["offset"]);
        $limit = intval($fields["limit"]);
        $condicoesObj->offset($offset);
        $condicoesObj->limit($limit);
        $result = $condicoesObj->get();
        unset($condicoesObj);

        if($fields['count'] === "true"){
            $total = $query_total->count();
        }
        unset($query_total);
        $condicoes = [];
        foreach ($result as $key => $condicao) {

            $vencimentosObj = Vencimentos::findOrFail(utf8_decode($condicao['id_web']));

            $vencimentos = $vencimentosObj->only('DIASVCT_1', 'DIASVCT_2', 'DIASVCT_3', 'DIASVCT_4', 'DIASVCT_5', 'DIASVCT_6', 'DIASVCT_7', 'DIASVCT_8', 'DIASVCT_9', 'DIASVCT_10', 'DIASVCT_11', 'DIASVCT_12', 'DIASVCT_13', 'DIASVCT_14', 'DIASVCT_15', 'DIASVCT_16', 'DIASVCT_17', 'DIASVCT_18', 'DIASVCT_19', 'DIASVCT_20', 'DIASVCT_21', 'DIASVCT_22', 'DIASVCT_23', 'DIASVCT_24');

            foreach ($vencimentos as $key => $value) {
                if (!empty($value)){
                    $vencimentos_sem_zero[] = $value;
                }
            }
            $condicoes[] = [
                "codigo" => $condicao->id,
                "descricao" => $condicao->descricao,
                "vencimentos" => implode("/", $vencimentos_sem_zero),
                "media" => $condicao->media
            ];
        }

        if($fields['count'] === "true"){
            $response = [
                "total" => intval($total),
                "condicoes" => $condicoes
            ];
        }else{
            $response = [
                "condicoes" => $condicoes
            ];
        }
        
        $success = [
            'error' =>[
                "error" => false,
                "msg" => [
                    "dev" => "",
                    "user" => ""
                ]
            ],
            'request' => $fields,
            'response' => $response
        ];
        return response()->json($success, $this->successStatus);
    }

    public function verificaCredito(Request $request){
        $fields = $request->only(['codigo_cliente']);
        $ClienteObj = Cliente::where("CODCAD", $fields['codigo_cliente'])->first();
        if(is_null($ClienteObj)){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Cliente não encontrado!",
                        "user" => "Cliente não encontrado!"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus);
        }
        

        $ClienteControllerObj = new ClienteController();
        $informacoes = $ClienteControllerObj->getInformacoesClientePedido($ClienteObj);

        $response = [
            "limite_credito" => $informacoes['limite_credito_disponivel'],
            "pedido_pronta_entrega_quantidade" => $informacoes['pedido_pronta_entrega']['quantidade'],
            "pedido_pronta_entrega_valor" => $informacoes['pedido_pronta_entrega']['valor'],
            "pedido_futuro_quantidade" => $informacoes['pedido_futuro']['quantidade'],
            "pedido_futuro_valor" => $informacoes['pedido_futuro']['valor'],
            "titulos_aberto_quantidade" => "",
            "titulos_aberto_valor" => "",
            "titulos_atraso_quantidade" => "",
            "titulos_atraso_valor" => "",
        ];
        $success = [
            'error' =>[
                "error" => false,
                "msg" => [
                    "dev" => "",
                    "user" => ""
                ]
            ],
            'request' => $fields,
            'response' => $response
        ];
        return response()->json($success, $this->successStatus);
    }
}
