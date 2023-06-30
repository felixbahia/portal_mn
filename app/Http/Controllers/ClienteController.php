<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\Cliente;
use App\CepCidade;
use App\CepEstado;
use Carbon\Carbon;
use App\Orcamentos;
use App\ClienteNovo;
use App\PedidoVenda;
use App\PedidoPortal;
use App\TipoUsuario;
use App\Movimentacao;

use App\ContasReceber;

use App\Transportador;
use App\ClienteCredito;
use App\ClienteNasajon;
use App\ContratoCliente;
use App\ClienteDocumento;
use App\GrupoEmpresarial;
use App\PedidosVendaNasajon;
use Illuminate\Http\Request;
use App\TransportadorNasajon;
use App\ChequesEmAbertoNasajon;
use App\TitulosEmAbertoNasajon;
use App\NotasDebitoReceberNasajon;
use Illuminate\Support\Facades\DB;
use App\NotasCreditoReceberNasajon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClienteController extends Controller
{
    private $estabelecimento_prologos = [];
    private $pedidos_nasajon_status_exibidos = ['Em Faturamento', 'Em separação', 'Aberto'];
    private $codigo_cliente_balcao = ['0000010069999'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Cliente") === false){
            return abort(403);
        }
        $estados = $this->estados();
        $cidades = $this->cidades($request);
        $request->session()->flash('model', 'App\Cliente');
        return view('programs.cliente.index',['estados' => $estados,'cidades' => $cidades]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request){
        $field = $request->only(["codcad", "estabelecimento"]);
        $estabelecimento = isset($field['estabelecimento']) ? intval($field['estabelecimento']) : 0;
        $codcad = $field["codcad"];

        $ClienteObj = ClienteNasajon::where('codigo', $codcad)->with(['contatos', 'contrato', 'representante', 'representante.supervisor'])->firstOrFail();
        $clienteNovo = ClienteNovo::with('conceito', 'vendedor', 'transportador', 'referencias', 'socios')->where('cpf_cnpj', $ClienteObj->cpf_cnpj)->whereIn('status', [2, 4])->first();
        $documentos_query = ClienteDocumento::with('clienteNovo','clienteNasajon')->where('cpf_cnpj', $ClienteObj->cpf_cnpj)->get();

        if(!is_null($clienteNovo)){
            $dados = [];
            $clienteNovo = $clienteNovo->toArray();
            $dados = $clienteNovo;
            $dados['nome_razao']                    = trim($ClienteObj->nome);
            $dados['guerra_apelido']                = trim($ClienteObj->nomefantasia);
            $dados['inscricao_estadual']            = trim($ClienteObj->inscricaoestadual);
            $dados['indicadorinscricaoestadual']    = trim($ClienteObj->getIndicadorInscricaoAttribute());
            $dados['telefone']                      = trim($ClienteObj->telefones);
            $dados['email']                         = trim($ClienteObj->email);
            $dados['cpf_cnpj']                      = trim($ClienteObj->cpf_cnpj);
            $dados['data_desde']                    = Carbon::createFromTimestamp(strtotime($clienteNovo['created_at']))->format('d/m/Y');

            $dados['faturamento_cep']               = mask(str_replace('-', '',$ClienteObj->cep), '#####-###');
            $dados['cobranca_cep']                  = mask(str_replace('-', '',$ClienteObj->cep), '#####-###');

            $dados['cobranca_bairro']               = trim($ClienteObj->bairro);
            $dados['cobranca_bairro']               = trim($ClienteObj->bairro);

            $dados['cobranca_cidade']               = trim($ClienteObj->cidade);
            $dados['cobranca_cidade']               = trim($ClienteObj->cidade);

            $dados['cobranca_estado']               = trim($ClienteObj->uf);
            $dados['cobranca_estado']               = trim($ClienteObj->uf);

            $dados['faturamento_endereco']          = $ClienteObj->logradouro. ', '. $ClienteObj->numero. ' '. $ClienteObj->complemento;
            $dados['cobranca_endereco']             = $ClienteObj->logradouro. ', '. $ClienteObj->numero. ' '. $ClienteObj->complemento;



            $vendedorObj = User::where('codigo_representante', $ClienteObj->vendedor_codigo)->first();
            if(!is_null($vendedorObj) && !is_null($ClienteObj->vendedor_codigo)){
                $vendedorObj = $vendedorObj->toArray();
                $dados["vendedor"] = [
                    "codigo" => $vendedorObj["codigo_representante"],
                    "nome" => $vendedorObj["name"]
                ];
            }else{
                $dados["vendedor"] = [
                    "codigo" => '',
                    "nome" => 'Nenhum'
                ];
            }
            $TransportadorObj = TransportadorNasajon::where('codigo', $dados['transportador_codigo'])->first();
            if(!is_null($TransportadorObj)){
                $TransportadorObj = $TransportadorObj->toArray();
                $dados["transportador"] = [
                    "codigo" => $TransportadorObj["codigo"],
                    "nome" => $TransportadorObj["nome"]
                ];
            }else{
                $dados["transportador"] = [
                    "codigo" => '',
                    "nome" => 'Nenhum'
                ];
            }

            $dados['alerta'] = '';

            $contatos = [];
            if($ClienteObj->contatos->count() > 0){
                foreach($ClienteObj->contatos as $contato){
                    $contatos[] = [
                        'cargo' => $contato->contato_cargo,
                        'nome' => $contato->contato_nome,
                        'telefone' => "(".$contato->contato_ddd.") ".$contato->contato_telefone,
                        'email' => $contato->contato_email,
                    ];
                }
            }

            $documentos = [];
            if($documentos_query->count() > 0){
                foreach($documentos_query as $documento){
                    $extensao = substr($documento->documento,-3);

                    $documentos[] = [
                        'descricao' => $documento->descricao_documento,
                        'documento' => Storage::url($documento->documento),
                        'extensao' => $extensao
                    ];
                }
            }

            $dados["contatos"] = $contatos;
            $dados["documentos"] = $documentos;

            if(!empty($ClienteObj->representante->supervisor)){
                $dados['gerente'] = $ClienteObj->representante->supervisor->name;
            }
            else{
                $dados['gerente'] = '';
            }

            $dados['contrato_caminho'] = empty($ClienteObj->contrato)? '' : Storage::url($ClienteObj->contrato->caminho_contrato);
            $dados['contrato_email'] = empty($ClienteObj->contrato)? '' : $ClienteObj->contrato->email;
            $dados['contrato_criacao'] = empty($ClienteObj->contrato)? '' : $ClienteObj->contrato->created_at->format('d/m/Y H:i');
            $dados['contrato_ip'] = empty($ClienteObj->contrato)? '' : $ClienteObj->contrato->ip;

            return view('programs.cliente.view_completo')->with("dados",$dados);
        }else{
            $dados = [];
            $contatos = [];
            $cliente = $ClienteObj->toArray();
            foreach ($cliente as $key => $value) {
                if(empty($value)){
                    $value = " ";
                }
                if($key === "cliente_desde" && !empty($value)){
                    $value = date("d/m/Y", strtotime($value));
                }

                if($key == 'vendedor_codigo'){
                    $vendedorObj = User::where('codigo_representante', $value)->get()->first();

                    $dados['vendedor'] = !is_null($vendedorObj) ? $vendedorObj->codigo_representante . ' - ' . $vendedorObj->name:'Nenhum';
                }
                if($key !== 'id'){
                    if(is_array($value)){
                        if($key === 'contatos'){
                            foreach($value as $contato){
                                $contatos[] = [
                                    'cargo' => $contato['contato_cargo'],
                                    'nome' => $contato['contato_nome'],
                                    'telefone' => "(".$contato['contato_ddd'].") ".$contato['contato_telefone'],
                                    'email' => $contato['contato_email'],
                                ];
                            }
                        }
                    }else if(!empty(trim($value))){
                        $dados[strtolower($key)] = "<div data-toggle=\"tooltip\" data-trigger=\"hover focus click\" data-placement=\"top\" title=\"".trim($value)."\">".trim($value)."</div>";
                    }else{
                        $dados[strtolower($key)] = "";
                    }
                }
            }

            $endereco = $cliente['logradouro'] . ', '. $cliente['complemento'];
            $numero   = $cliente['numero'];

            $documentos = [];
            if($documentos_query->count() > 0){
                foreach($documentos_query as $documento){
                    $extensao = substr($documento->documento,-3);

                    $documentos[] = [
                        'descricao' => $documento->descricao_documento,
                        'documento' => Storage::url($documento->documento),
                        'extensao' => $extensao
                    ];
                }
            }

            $dados['endereco'] = "<div data-toggle=\"tooltip\" data-trigger=\"hover focus click\" data-placement=\"top\" title=\"" . $endereco . "\">" . $endereco ."</div>";
            $dados['numero']   = "<div data-toggle=\"tooltip\" data-trigger=\"hover focus click\" data-placement=\"top\" title=\"" . $numero . "\">" . $numero ."</div>";
            $dados['indicadorinscricaoestadual']    = trim($ClienteObj->getIndicadorInscricaoAttribute());

            if(!empty($cliente['representante']['supervisor'])){
                $dados['gerente'] = $cliente['representante']['supervisor']['name'];
            }
            else{
                $dados['gerente'] = '';
            }
            
            $dados['contatos'] = $contatos;     
            $dados['documentos'] = $documentos;

            $dados['contrato_caminho'] = empty($ClienteObj->contrato)? '' : Storage::url($ClienteObj->contrato->caminho_contrato);
            $dados['contrato_email'] = empty($ClienteObj->contrato)? '' : $ClienteObj->contrato->email;
            $dados['contrato_criacao'] = empty($ClienteObj->contrato)? '' : $ClienteObj->contrato->created_at->format('d/m/Y H:i');
            $dados['contrato_ip'] = empty($ClienteObj->contrato)? '' : $ClienteObj->contrato->ip;

            return view('programs.cliente.view_nasajon')->with("dados",$dados);
        }
    }

    /**
     * Filtro da listagem
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only(['cdcad', 'nome', 'nome_guerra', 'cnpj_cpf', 'data_inicio', 'data_fim']);

        $query = ClienteNasajon::query();
        $query->with(['contrato']);

        if(!empty($fields['cdcad'])){
            $query->where('codigo', $fields['cdcad']);
        }
        if(!empty($fields['nome'])){
            $query->where('nome', 'ilike', "%" . $fields['nome'] . "%");
        }
        if(!empty($fields['nome_guerra'])){
            $query->where('nomefantasia', 'ilike', "%" . $fields['nome_guerra']. "%");
        }
        if(!empty($fields['cnpj_cpf'])){
            $query->where('cpf_cnpj', 'ilike', $fields['cnpj_cpf'] . "%");
        }

        if(isset($fields['data_inicio']) || isset($fields['data_fim'])){

            $query_data = 'SELECT max("data_saida"), documento_cliente FROM "integracoes"."vw_notas_de_venda" GROUP BY "integracoes"."vw_notas_de_venda"."documento_cliente" HAVING ';

            if(isset($fields['data_inicio'])){
                $data_inicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio']);
                $query_data .= 'max("data_saida") >= \'' . $data_inicio->format('Y-m-d') . '\'';
            }

            if(isset($fields['data_inicio']) && isset($fields['data_fim'])){
                $query_data .= ' AND ';
            }

            if(isset($fields['data_fim'])){
                $data_fim = Carbon::createFromFormat("d/m/Y", $fields['data_fim']);
                $query_data .= 'max("data_saida") <= \'' . $data_fim->format('Y-m-d') . '\'';
            }

            $clientes_ativos_data = collect(DB::connection('nasajon')->select($query_data));

            $query->whereIn('cpf_cnpj', $clientes_ativos_data->pluck('documento_cliente'));

        }

        if(Auth::user()->tipo_usuario_id === 12 && Auth::user()->codigo_representante != '998'){
            if(!empty(Auth::user()->codigo_representante)){
                $query->where(function($q){
                    $q->orWhere("vendedor_codigo", Auth::user()->codigo_representante);
                    $q->orWhere("vendedor_codigo", '001');
                    $q->orWhere("vendedor_codigo", '');
                    $q->orWhereNull("vendedor_codigo");
                });
            }
        }
        $query->where('bloqueado', false);

        $results_user = $query->get();
        
        $results_user->load('ultimaVenda');

        $key = 0;
        $results = [];
        $equipes = $this->getEquipes($results_user->pluck('vendedor_codigo'));
        foreach($results_user as $result){
            $results[$key]["codcad"] = $result->codigo;
            $results[$key]["nome"] = $result->nome;
            $results[$key]["guerra"] = $result->nomefantasia;
            $results[$key]["cgc_cpf"] = $result->cpf_cnpj;
            $results[$key]["cidade"] = $result->cidade;
            $results[$key]["estado"] = $result->uf;
            $results[$key]["ultima_venda"] = !empty($result->ultimaVenda->ultima_venda) ? parserData($result->ultimaVenda->ultima_venda) : '';
            $results[$key]['hash'] = Crypt::encrypt($result->codigo);

            if(strpos($result['cpf_cnpj'], "___.___.___-__") !== false){
                $results[$key]["cgc_cpf"] = "";
            }
            
            $results[$key]["contrato"] = empty($result->contrato)? '' : Storage::url($result->contrato->caminho_contrato);

            if(!empty($equipes[$result->vendedor_codigo])){
                $results[$key]['gerente'] = $equipes[$result->vendedor_codigo]['gerente'];
                $results[$key]['equipe'] = $equipes[$result->vendedor_codigo]['equipe'];
            }else{
                $results[$key]['gerente'] = "";
                $results[$key]['equipe'] = "";
            }

            $key++;
        }
        return response()->json($results);
    }

    public function indexDialog(Request $request){
        return view('programs.cliente.index_dialog');
    }

    public function autoComplete(Request $request){
        $fields = $request->only(["name", "term", 'busca_pedido']);
        $return = [];

        $query = ClienteNasajon::select('nome')
            ->limit("15")
            ->orderBy('nome', "ASC")
            ->where("nome",  'ilike', "%". $fields["term"] ."%")
            ->distinct('nome')->get()
            ->toArray();

        foreach ($query as $value){
            $value = (array) $value;
            $return[] = $value['NOME'];
        }
        return response()->json($return);
    }

    public function autoCompleteId(Request $request){
        $fields = $request->only(["name", "term", 'busca_pedido']);
        $return = [];

        $query = ClienteNasajon::select('id', 'nome', 'cpf_cnpj')
            ->limit("15")
            ->orderBy('nome', "ASC")
            ->where(DB::Raw("CONCAT(LOWER(TRIM(nome)), ' - ', cpf_cnpj)"),  'ilike', "%". $fields["term"] ."%")
            ->distinct('nome')->get()
            ->toArray();

        foreach ($query as $value){
            $value = (array) $value;
            $return[] = [
                'label' => $value['nome'] . ' - ' . $value['cpf_cnpj'],
                'value' => $value['id']
            ];
        }
        return response()->json($return);
    }

    public function autoCompleteNome(Request $request){
        $fields = $request->only(["term", "conta_ordem", "estabelecimento", 'bloqueado']);
        $conta_ordem = isset($fields['conta_ordem']) ? boolval($fields['conta_ordem']) : false;
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
        $return = [];
 
        $consulta = ClienteNasajon::select('codigo','nome', 'cpf_cnpj', 'cidade', 'uf')
            ->limit("15")
            ->orderBy('nome', "ASC")
            ->where(DB::Raw("CONCAT(LOWER(TRIM(nome)), ' - ', cpf_cnpj)"), 'ilike', "%".addslashes($fields["term"]). "%")
            ->distinct('nome');
        if(!isset($fields["bloqueado"]) || $fields["bloqueado"] == 'true'){
            $consulta->where('bloqueado', false);
        }
        if(
            Auth::user()->tipo_usuario_id == 12 &&
            Auth::user()->codigo_representante != '998'
        ){
            if(Auth::user()->usar_clientes_carteira == true){
                if($conta_ordem === false){
                    $consulta->where("vendedor_codigo", Auth::user()->codigo_representante);
                }
                $consulta->where('codigo', '!=', '0000010069999');
            }else{
                if($conta_ordem === false){
                    $consulta->where(function($query){
                        $query->orWhere("vendedor_codigo", Auth::user()->codigo_representante)
                            ->orWhere("vendedor_codigo", "001")
                            ->orWhere("vendedor_codigo", '')
                            ->orWhereNull("vendedor_codigo");
                    });
                }
                $consulta->where('codigo', '!=', '0000010069999');
            }
        }
        $query = $consulta
            ->get()
            ->toArray();

        foreach ($query as $value){
            $value = (array) $value;
            $return[] = [
                'label' => trim($value['nome']). ' - '. $value['cpf_cnpj'],
                'cpf_cnpj' => $value['cpf_cnpj'],
                'nome' => trim($value['nome']),
                'value' => $value['codigo'],
                'cidade' => $value['cidade'],
                'estado' => $value['uf'],
            ];
        }

        // $query = ClienteNovo::select("id as codigo", "nome_razao as nome", 'cpf_cnpj');
        // $query->where("nome_razao", 'ilike', "%".addslashes($fields["term"])."%");
        // $query->where("status", "<>", "2");
        // $query->where("status", "<>", "4");
        // $query = $query->get();
        // $results_user_novo = $query->toArray();
        // foreach ($results_user_novo as $value){
        //     $value = (array) $value;
        //     $return[] = [
        //         'label' => trim(utf8_encode($value['nome'])) . ' - '. $value['cpf_cnpj'],
        //         'cpf_cnpj' => $value['cpf_cnpj'],
        //         'nome' => trim(utf8_encode($value['nome'])),
        //         'value' => $value['codigo']
        //     ];
        // }

        return response()->json($return);
    }

    public function autoCompleteNomeVenda(Request $request){
        $fields = $request->only(["term", "conta_ordem", "estabelecimento", 'bloqueado']);
        $conta_ordem = isset($fields['conta_ordem']) ? boolval($fields['conta_ordem']) : false;
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
        $return = [];
 
        $consulta = ClienteNasajon::select('codigo','nome', 'cpf_cnpj', 'cidade', 'uf')
            ->limit("15")
            ->orderBy('nome', "ASC")
            ->where(DB::Raw("CONCAT(LOWER(TRIM(nome)), ' - ', cpf_cnpj)"), 'ilike', "%".addslashes($fields["term"]). "%")
            ->distinct('nome');
        if(!isset($fields["bloqueado"]) || $fields["bloqueado"] == 'true'){
            $consulta->where('bloqueado', false);
        }
        if(
            in_array(Auth::user()->tipo_usuario_id, [12, 16, 13]) &&
            Auth::user()->codigo_representante != '998'
        ){
            if(Auth::user()->usar_clientes_carteira == true){
                if($conta_ordem === false){
                    $consulta->where("vendedor_codigo", Auth::user()->codigo_representante);
                }
                $consulta->where('codigo', '!=', '0000010069999');
            }else{
                if($conta_ordem === false){
                    $consulta->where(function($query){
                        $query->orWhere("vendedor_codigo", Auth::user()->codigo_representante)
                            ->orWhere("vendedor_codigo", "001")
                            ->orWhere("vendedor_codigo", '')
                            ->orWhereNull("vendedor_codigo");
                    });
                }
                $consulta->where('codigo', '!=', '0000010069999');
            }
        }
        $query = $consulta
            ->get()
            ->toArray();

        foreach ($query as $value){
            $value = (array) $value;
            $return[] = [
                'label' => trim($value['nome']). ' - '. $value['cpf_cnpj'],
                'cpf_cnpj' => $value['cpf_cnpj'],
                'nome' => trim($value['nome']),
                'value' => $value['codigo'],
                'cidade' => $value['cidade'],
                'estado' => $value['uf'],
            ];
        }

        // $query = ClienteNovo::select("id as codigo", "nome_razao as nome", 'cpf_cnpj');
        // $query->where("nome_razao", 'ilike', "%".addslashes($fields["term"])."%");
        // $query->where("status", "<>", "2");
        // $query->where("status", "<>", "4");
        // $query = $query->get();
        // $results_user_novo = $query->toArray();
        // foreach ($results_user_novo as $value){
        //     $value = (array) $value;
        //     $return[] = [
        //         'label' => trim(utf8_encode($value['nome'])) . ' - '. $value['cpf_cnpj'],
        //         'cpf_cnpj' => $value['cpf_cnpj'],
        //         'nome' => trim(utf8_encode($value['nome'])),
        //         'value' => $value['codigo']
        //     ];
        // }

        return response()->json($return);
    }

    public function codParaNome(Request $request){
        $codigo = $request->codigo;
        $clienteObj = null;
        $clienteObj = ClienteNasajon::where('codigo', $codigo)->first();
        if (is_null($clienteObj)) {
            $clienteObj = null;
            $clienteObj = ClienteNovo::find($codigo);
            if (is_null($clienteObj)) {
                $return = [
                    'status' => 'error',
                    'message' => '',
                    'error' => [],
                    'response' => []
                ];
                return response()->json($return, 422);
            }else{
                $return = [
                    'status' => 'success',
                    'message' => '',
                    'error' => [],
                    'response' => [
                        "nome" => trim($clienteObj->nome_razao) . ' - '. $clienteObj->cpf_cnpj, 
                        "cpf_cnpj" => $clienteObj->cpf_cnpj
                    ]
                ];
            }
        }else{
            $informacoes_clientes = $this->getInformacoesClientePedidoNasajon($clienteObj);
            $response = [
                "nome" => trim($clienteObj->nome). ' - '. $clienteObj->cpf_cnpj,
                "cpf_cnpj" => utf8_encode($clienteObj->cpf_cnpj)
            ];
            $response = array_merge($response, $informacoes_clientes);
            $return = [
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => $response
            ];
        }

        return response()->json($return);
    }

    public function indexDialogCadastro(Request $request){
        $field = $request->only(['conta_ordem', 'estabelecimento']);
        $conta_ordem = isset($field['conta_ordem']) ? ($field['conta_ordem']) : false;
        $estabelecimento = isset($field['estabelecimento']) ? $field['estabelecimento'] : '0';
        return view('programs.cliente.index_dialog_cadastro')->with(['conta_ordem' => $conta_ordem, 'estabelecimento' => $estabelecimento]);
    }

    public function indexDialogCadastroVenda(Request $request){
        $field = $request->only(['conta_ordem', 'estabelecimento']);
        $conta_ordem = isset($field['conta_ordem']) ? ($field['conta_ordem']) : false;
        $estabelecimento = isset($field['estabelecimento']) ? $field['estabelecimento'] : '0';
        return view('programs.cliente.index_dialog_cadastro_venda')->with(['conta_ordem' => $conta_ordem, 'estabelecimento' => $estabelecimento]);
    }

    public function filterCadastro(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only(['codigo', 'nome_razao', 'nome_guerra', 'cpf_cnpj', 'conta_ordem', 'estabelecimento']);
        $conta_ordem = isset($fields['conta_ordem']) ? ($fields['conta_ordem']) : 'false';
        $where = [];
        $where_novo = [];
        $where_nasajon = [];
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
        
        $query = ClienteNasajon::select('codigo', 'nome', 'nomefantasia as apelido_guerra', 'cpf_cnpj', 'cidade', 'uf as estado');

        if(!empty($fields['codigo'])){
            $query->where('codigo', $fields['codigo']);
        }
        if(!empty($fields['nome_razao'])){
            $query->where('nome', 'ilike', '%' . $fields['nome_razao'] . '%');
        }
        if(!empty($fields['nome_guerra'])){
            $query->where('nomefantasia', 'ilike', '%' . $fields['nome_guerra'] . '%');
        }
        if(!empty($fields['cpf_cnpj'])){
            $query->where(DB::Raw("regexp_replace(cpf_cnpj, '[^\d]*', '', 'g')"), 'ilike', '%' . preg_replace("/[^\d]/", '', $fields['cpf_cnpj']) . '%');
        }

        if (Auth::user()->tipo_usuario_id == 12){
            if(Auth::user()->usar_clientes_carteira == true){
                if($conta_ordem === 'false'){
                    $query->where("vendedor_codigo", Auth::user()->codigo_representante);
                }
                $query->where('codigo', '!=', '0000010069999');
            }else{
                if($conta_ordem === 'false'){
                    $query->where(function($query){
                        $query->orWhere("vendedor_codigo", Auth::user()->codigo_representante)
                            ->orWhere("vendedor_codigo", "001")
                            ->orWhere("vendedor_codigo", '')
                            ->orWhereNull("vendedor_codigo");
                    });
                }
                $query->where('codigo', '!=', '0000010069999');
            }
        }
        // foreach($where_nasajon as $value){
        //     if(count($value) === 3){
        //         $query->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
        //     } else {
        //         $query->where($value[0], $value[1]);
        //     }
        // }
        $query->where('bloqueado', 'false');

        $query = $query->get();
        $results_user = $query->toArray();
        $key = 0;
        $results = [];
        foreach($results_user as $result){
            $result = (array) $result;
            foreach($result as $k => $value){
                if(empty($value)){
                    $results[$key][strtolower($k)] = " ";
                }else{
                    $results[$key][strtolower($k)] = trim($value);
                }
            }
            if(strpos(trim($result["cpf_cnpj"]), "___.___.___-__") !== false){
                $results[$key]["cpf_cnpj"] = "";
                
            }
            $key++;
        }

        return response()->json($results);
    }

    public function filterCadastroVenda(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only(['codigo', 'nome_razao', 'nome_guerra', 'cpf_cnpj', 'conta_ordem', 'estabelecimento']);
        $conta_ordem = isset($fields['conta_ordem']) ? ($fields['conta_ordem']) : 'false';
        $where = [];
        $where_novo = [];
        $where_nasajon = [];
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
        
        $query = ClienteNasajon::select('codigo', 'nome', 'nomefantasia as apelido_guerra', 'cpf_cnpj', 'cidade', 'uf as estado');

        if(!empty($fields['codigo'])){
            $query->where('codigo', $fields['codigo']);
        }
        if(!empty($fields['nome_razao'])){
            $query->where('nome', 'ilike', '%' . $fields['nome_razao'] . '%');
        }
        if(!empty($fields['nome_guerra'])){
            $query->where('nomefantasia', 'ilike', '%' . $fields['nome_guerra'] . '%');
        }
        if(!empty($fields['cpf_cnpj'])){
            $query->where(DB::Raw("regexp_replace(cpf_cnpj, '[^\d]*', '', 'g')"), 'ilike', '%' . preg_replace("/[^\d]/", '', $fields['cpf_cnpj']) . '%');
        }

        if (in_array(Auth::user()->tipo_usuario_id, [12, 16, 13])){
            if(Auth::user()->usar_clientes_carteira == true){
                if($conta_ordem === 'false'){
                    $query->where("vendedor_codigo", Auth::user()->codigo_representante);
                }
                $query->where('codigo', '!=', '0000010069999');
            }else{
                if($conta_ordem === 'false'){
                    $query->where(function($query){
                        $query->orWhere("vendedor_codigo", Auth::user()->codigo_representante)
                            ->orWhere("vendedor_codigo", "001")
                            ->orWhere("vendedor_codigo", '')
                            ->orWhereNull("vendedor_codigo");
                    });
                }
                $query->where('codigo', '!=', '0000010069999');
            }
        }
        // foreach($where_nasajon as $value){
        //     if(count($value) === 3){
        //         $query->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
        //     } else {
        //         $query->where($value[0], $value[1]);
        //     }
        // }
        $query->where('bloqueado', 'false');

        $query = $query->get();
        $results_user = $query->toArray();
        $key = 0;
        $results = [];
        foreach($results_user as $result){
            $result = (array) $result;
            foreach($result as $k => $value){
                if(empty($value)){
                    $results[$key][strtolower($k)] = " ";
                }else{
                    $results[$key][strtolower($k)] = trim($value);
                }
            }
            if(strpos(trim($result["cpf_cnpj"]), "___.___.___-__") !== false){
                $results[$key]["cpf_cnpj"] = "";
                
            }
            $key++;
        }

        return response()->json($results);
    }

    public static function salvaClientePadrao(Request $request){

        if ($cliente = ClienteNasajon::where('codigo', $request->codcad)->first()){

            Auth::user()->cliente_padrao_id = $cliente->codigo;
            Auth::user()->save();
        }

    }

    public static function apagaClientePadrao(){
        Auth::user()->cliente_padrao_id = null;
        Auth::user()->save();
    }


    public function nameToCod(Request $request){

        $field = $request->only(['name', 'estabelecimento']);
        $estabelecimento = isset($field['estabelecimento']) ? intval($field['estabelecimento']) : 0;

        $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), $field['name'])->where('bloqueado', 'false')->first();

        if (!is_null($cliente)){
            if(!in_array($cliente->codigo, $this->codigo_cliente_balcao)){
                $informacoes_clientes = $this->getInformacoesClientePedidoNasajon($cliente);
                $informacoes_clientes = array_merge($informacoes_clientes, ['cliente_balcao' => false]);
            }else{
                $informacoes_clientes = ['cliente_balcao'=>true];
            }

            $response = [
                'id' => $cliente->codigo,
                'id_ecrypt' => encrypt($cliente->codigo),
                'nome' => trim($cliente->nome).' - '. $cliente->cpf_cnpj
            ];

            $response = array_merge($response, $informacoes_clientes);

            $return = [
                'status' => 'success', 
                'message' => '',
                'error' => [],
                'response' => $response
            ];
            return response()->json($return, 200);
        } else {
            $return = [
                'status' => 'error',
                'message' => 'Nome inválido',
                'error' => [],
                'response' => []
            ];
            return response()->json($return, 422);
        }
    }

    public function getInformacoesClientePedidoNasajon(ClienteNasajon $Cliente){

        $return = [
            'limite_credito' => 0,
            'limite_credito_disponivel' => 0,
            'data_valida_limite' => '',
            'data_limite_credito' => '',
            'pedido_pronta_entrega' => [
                'valor' => 0,
                'quantidade' => 0,
                'total' => 0
            ],
            'pedido_futuro' => [
                'valor' => 0,
                'quantidade' => 0,
                'total' => 0
            ],
            'titulos_em_aberto' => [
                'valor' => 0,
                'quantidade' => 0,
                'total' => 0
            ],
            'titulos_em_atraso' => [
                'valor' => 0,
                'quantidade' => 0,
                'total' => 0
            ],
            'notas_debito' => [
                'valor' => 0,
                'quantidade' => 0,
                'total' => 0
            ],
            'notas_credito' => 0,
			'alerta' => "",
			'blacklist' => ''
        ];
		if(in_array(str_replace('.', '', explode('/', $Cliente->cpf_cnpj)[0]), ['05075884', '06311274'])){
            return $return;
		}
        if($Cliente->nome === 'CLIENTE BALCAO'){
            return $return;
        }
        if(strlen(trim($Cliente->cpf_cnpj)) == 18){
            $grupo = substr($Cliente->cpf_cnpj, 0, 10);
        }
        else{
            $grupo = trim($Cliente->cpf_cnpj);    
        }
        $grupoEmpresarialObj = GrupoEmpresarial::
            with('participantes')
            ->where('raiz_cnpj', $grupo)
            ->orWhereHas('participantes', function($query) use ($grupo){
                $query->where('raiz_cnpj', $grupo);
            })
            ->first();
        
		$clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

		if (!is_null($grupoEmpresarialObj)){
			$clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
				if(isset($grupoEmpresarialObj->participantes)){
					foreach($grupoEmpresarialObj->participantes as $participante){
						$query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
					}
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
		}
		else {
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $grupo . '%');
		}

        $grupo_empresarial = $clientesNasajonQuery->get();

        $codcads = $grupo_empresarial->pluck('id');
        $codigo_clientes = $grupo_empresarial->pluck('codigo');
        

        foreach($codcads as $cod_cadastro){
            $pedidosEmAberto = PedidosVendaNasajon::whereIn('situacao_descricao', $this->pedidos_nasajon_status_exibidos)
            ->where('cliente', $cod_cadastro)
            ->where('rascunho', false)
            ->where(function($query){
                $query->where('grupodeoperacao', '=', 'VENDA')
                ->orWhere(function($query){
                    $query->whereNull('grupodeoperacao')
                    ->where('grupodeoperacao_pedido','ilike','VENDA');
                });
            })
            ->orderBy('emissao', 'desc')
            ->get();
            foreach($pedidosEmAberto as $value){
                $return['limite_credito_disponivel'] -= $value->valor;
                $return['pedido_pronta_entrega']['valor'] += $value->valor;
                $return['pedido_pronta_entrega']['quantidade'] ++;
            }

            unset($pedidosEmAberto);
        }

        $titulos_aberto = TitulosEmAbertoNasajon::selectRaw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota")->whereIn('id_cliente', $codcads)->orderBy('nota_numero')->orderBy('parcela')->get();

        $limite_credito = 0;

        if(!is_null($grupoEmpresarialObj)){
            $raiz_cnpj = array_merge([$grupoEmpresarialObj->raiz_cnpj], $grupoEmpresarialObj->participantes->pluck('raiz_cnpj')->toArray());
        }
        else{
            $raiz_cnpj = $Cliente->cpf_cnpj;
            if(strlen(trim($raiz_cnpj)) == 18){
                $raiz_cnpj = substr($raiz_cnpj, 0, 10);
    
            }
            $raiz_cnpj = [$raiz_cnpj];
        }

        $data_atulizacaco_limite = '';
        $ClienteCreditoObj = ClienteCredito::whereIn('raiz_cnpj', $raiz_cnpj)->get();
        if(empty($ClienteCreditoObj)){
            $check_limite_credito = false;
            $data_atulizacaco_limite = 'Em analise';
        }
        else{

            $limite_credito = (float) $ClienteCreditoObj->sum('valor');

            $data_atulizacaco_limite = new Carbon($ClienteCreditoObj->min('data_atualizacao'));
            $data_atulizacaco_limite->addmonth(6);
            $data_atulizacaco_limite = $data_atulizacaco_limite->format('d/m/Y');
        }
        $return['limite_credito'] = $limite_credito;
        $return['limite_credito_disponivel'] = $limite_credito;
        $return['data_valida_limite'] = $data_atulizacaco_limite;

        $return['pedido_pronta_entrega'] = [
            'valor' => 0,
            'quantidade' => 0
        ];
        $return['pedido_futuro'] = [
            'valor' => 0,
            'quantidade' => 0
        ];

        $return['titulos_em_aberto'] = [
            'valor' => 0,
            'quantidade' => 0
        ];
        $return['titulos_em_atraso'] = [
            'valor' => 0,
            'quantidade' => 0
        ];

    	$notas_credito = 0;
    	$notas_debito = ["a_vencer" => 0, "vencidas" => 0, "total"=>0];
        $titulos_faturados = ["a_vencer" => 0, "vencidas" => 0, "total"=>0];
        
        $titulos_aberto->each(function ($item) use (&$titulos_faturados){
            $agoraCarbon = Carbon::Now();
            $vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $item->vencimento);
        
            if($vencimentoCarbon->gt($agoraCarbon)) {
                $titulos_faturados["a_vencer"] += $item->saldotitulo;
            }
            else{
                $titulos_faturados["vencidas"] += $item->saldotitulo;
            }
        
            $titulos_faturados["total"] += $item->saldotitulo;
        });
        $return['limite_credito_disponivel'] -= $titulos_faturados["total"];

        $query_debito = NotasDebitoReceberNasajon::select()
	        ->whereIn('cod_cliente', $codigo_clientes);
        $query_debito = $query_debito->get();
        foreach ($query_debito as $key => $value) {
            if(strtotime($value["vencimento"]) >= strtotime(date("Y-m-d 00:00:00")) ){
                $notas_debito["a_vencer"] += $value->valor;
            }elseif(strtotime($value["vencimento"]) < strtotime(date("Y-m-d 00:00:00")) ){
                $notas_debito["vencidas"] += $value->valor;
            }
            $notas_debito["total"] += $value->valor;
        }
        $return['limite_credito_disponivel'] -= $notas_debito["total"];

        $cheques_a_receber = ["a_vencer" => [], "vencidas" => [], "total"=>[]];
        
        // Cheques em aberto Nasajon
        $ChequesEmAbertoObj = ChequesEmAbertoNasajon::whereIn('cod_cliente', $codigo_clientes)->get();
        
        $ChequesEmAbertoObj->each(function($cheque) use (&$cheques_a_receber) {
            $vencimentoCarbon = Carbon::CreateFromFormat("Y-m-d", $cheque['data_vencimento']);
            $agoraCarbon = Carbon::now()->setTime(0, 0, 0);
            if($vencimentoCarbon->lt($agoraCarbon)){
                $cheques_a_receber['vencidas'][] = floatval($cheque['valor']);
            }else{
                $cheques_a_receber["a_vencer"][] = floatval($cheque['valor']);
            }
        });
        $cheques_a_receber["total"] = floatval($cheques_a_receber["a_vencer"]) + floatval($cheques_a_receber["vencidas"]);
        $return['limite_credito_disponivel'] -= $cheques_a_receber["total"];

        $query_credito = NotasCreditoReceberNasajon::select()
            ->whereIn('cod_cliente', $codigo_clientes);
        $query_credito = $query_credito->get();
        foreach ($query_credito as $key => $value) {
            if(!empty($value->valor)){
                $notas_credito += $value->valor;
            }
            
        }
        $return['limite_credito_disponivel'] += $notas_credito;
		foreach($titulos_faturados as $chave => $valor){
			if($valor > 0){
				$titulos_faturados[$chave] = 'R$ ' . parserValor($valor);
			}
		}
        $return['titulos_em_aberto'] = $titulos_faturados;
        $return['notas_credito'] = $notas_credito;
		$return['notas_debito'] = $notas_debito;
		
        if($return['notas_credito'] > 0){
            $return['notas_credito'] = 'R$ ' . parserValor($return['notas_credito']);

        }else{
            $return['notas_credito'] = '';
        }

        if($return['limite_credito_disponivel'] <= 0){
            $return['alerta'] = 'Será analisado Credito';
        }
        $return['limite_credito'] = 'R$ ' . parserValor($return['limite_credito']);
        $return['limite_credito_disponivel'] = 'R$ ' . parserValor($return['limite_credito_disponivel']);
        

        if($return['pedido_futuro']['quantidade'] > 0){
            $return['pedido_futuro']['valor'] = 'R$ ' . parserValor($return['pedido_futuro']['valor']);
        }else{
            $return['pedido_futuro']['quantidade'] = '';
            $return['pedido_futuro']['valor'] = '';
        }

        if($return['pedido_pronta_entrega']['quantidade'] > 0){
            $return['pedido_pronta_entrega']['valor'] = 'R$ ' . parserValor($return['pedido_pronta_entrega']['valor']);
        }else{
            $return['pedido_pronta_entrega']['quantidade'] = '';
            $return['pedido_pronta_entrega']['valor'] = '';
		}
		$ClienteAnaliseSinteticaControllerObj = new ClienteAnaliseSinteticaController();
		$blacklist = $ClienteAnaliseSinteticaControllerObj->statusBlackList($grupo_empresarial->toArray());
		$return['blacklist'] = $blacklist;
		
        return $return;
    }
    
    public function getEquipes($array_codigo_vendedor){
        $retorno = [];

        $query = User::select();
        $query->whereIn('codigo_representante', $array_codigo_vendedor);
        $query->with(['unidadeNegocioMetaUser' =>function($query){
            $query->with(['detalhesUnidadeNegocioMeta' => function($query){
                $query->where('data', date("Y-m")."-01");
                $query->with(['detalhesUnidadeNegocio.detalhesUsuarioResponsavel']);
            }]);
            $query->whereHas('detalhesUnidadeNegocioMeta', function($query){
                $query->where('data', date("Y-m")."-01");
            });
        }]);
        $query->whereHas('unidadeNegocioMetaUser', function($query){
            $query->whereHas('detalhesUnidadeNegocioMeta', function($query){
                $query->where('data', date("Y-m")."-01");
            });
        });
        $result = $query->get();

        foreach($result as $respresentante){
            foreach($respresentante->unidadeNegocioMetaUser as $unidade_negocio_meta_user){
                if(empty($retorno[$respresentante->codigo_representante])){
                    $retorno[$respresentante->codigo_representante] = [
                        'equipe' => $unidade_negocio_meta_user->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade,
                        'gerente' => $unidade_negocio_meta_user->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->detalhesUsuarioResponsavel->name,
                    ];
                }else{
                    $retorno[$respresentante->codigo_representante]['equipe'] = $retorno[$respresentante->codigo_representante]['equipe']." - ".$unidade_negocio_meta_user->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade;
                    $retorno[$respresentante->codigo_representante]['gerente'] = $retorno[$respresentante->codigo_representante]['gerente']." - ".$unidade_negocio_meta_user->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->detalhesUsuarioResponsavel->name;
                }
            }
        }

        return $retorno;
    }

    public function filterConsulta(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $fields = $request->only(['cdcad', 'nome', 'nome_guerra', 'cnpj_cpf', 'data_inicio', 'data_fim','estados','cidades']);

        $query = ClienteNasajon::query();
        $query->with(['contrato', 'representante', 'representante.supervisor']);

        if(!empty($fields['cdcad'])){
            $query->where('codigo', $fields['cdcad']);
        }
        if(!empty($fields['nome'])){
            $query->where('nome', 'ilike', "%" . $fields['nome'] . "%");
        }
        if(!empty($fields['nome_guerra'])){
            $query->where('nomefantasia', 'ilike', "%" . $fields['nome_guerra']. "%");
        }
        if(!empty($fields['cnpj_cpf'])){
            $query->where('cpf_cnpj', 'ilike', $fields['cnpj_cpf'] . "%");
        }

        if(isset($fields['data_inicio']) || isset($fields['data_fim'])){

            $query_data = 'SELECT max("data_saida"), documento_cliente FROM "integracoes"."vw_notas_de_venda" GROUP BY "integracoes"."vw_notas_de_venda"."documento_cliente" HAVING ';

            if(isset($fields['data_inicio'])){
                $data_inicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio']);
                $query_data .= 'max("data_saida") >= \'' . $data_inicio->format('Y-m-d') . '\'';
            }

            if(isset($fields['data_inicio']) && isset($fields['data_fim'])){
                $query_data .= ' AND ';
            }

            if(isset($fields['data_fim'])){
                $data_fim = Carbon::createFromFormat("d/m/Y", $fields['data_fim']);
                $query_data .= 'max("data_saida") <= \'' . $data_fim->format('Y-m-d') . '\'';
            }

            $clientes_ativos_data = collect(DB::connection('nasajon')->select($query_data));

            $query->whereIn('cpf_cnpj', $clientes_ativos_data->pluck('documento_cliente'));

        }

        if(Auth::user()->tipo_usuario_id === 12 && Auth::user()->codigo_representante != '998'){
            if(!empty(Auth::user()->codigo_representante)){
                $query->where(function($q){
                    $q->orWhere("vendedor_codigo", Auth::user()->codigo_representante);
                    $q->orWhere("vendedor_codigo", '001');
                    $q->orWhere("vendedor_codigo", '');
                    $q->orWhereNull("vendedor_codigo");
                });
            }
        }

        if(!empty($fields['estados'])){

            $query->where('uf',$fields['estados']);
        }

        if(!empty($fields['cidades'])){

            $query->where('cidade','ilike','%'.$fields['cidades'].'%');
        
        }

        $results_user = $query->get();
        
        $results_user->load('ultimaVenda');

        $results = [];
        foreach($results_user as $result){
            $linha = [];

            $linha["codcad"] = $result->codigo;
            $linha["nome"] = $result->nome;
            $linha["guerra"] = $result->nomefantasia;
            $linha["cgc_cpf"] = $result->cpf_cnpj;
            $linha["cidade"] = $result->cidade;
            $linha["estado"] = $result->uf;
            $linha["ultima_venda"] = !empty($result->ultimaVenda->ultima_venda) ? parserData($result->ultimaVenda->ultima_venda) : '';
            $linha['hash'] = Crypt::encrypt($result->codigo);

            if(strpos($result['cpf_cnpj'], "___.___.___-__") !== false){
                $linha["cgc_cpf"] = "";
            }
            
            $linha["contrato"] = empty($result->contrato)? '' : Storage::url($result->contrato->caminho_contrato);

            if(!empty($result->representante->supervisor)){
                $linha['gerente'] = $result->representante->supervisor->name;
            }
            else{
                $linha['gerente'] = '';
            }

            $linha['bloqueado'] = $result->bloqueado ? 'Sim' : '';

            $results[] = $linha;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Realizado com sucesso!',
            'error' => [],
            'response' => $results
        ]);
    }

    private function estados(){
        $estados = CepEstado::select('uf','estado')->get();
        $return =[];
        foreach($estados as $estado){
            $return[$estado->uf] = $estado->estado;
        }
        return $return;
    }

    public function cidades(Request $request){
        $fields = $request->only(['estados']);
  
        $return =[];
        if(!empty($fields['estados'])){
        
        $cidades = CepCidade::select('uf','cidade')->where('uf',$fields['estados'])->orderBy('cidade','asc')->get();
        
        foreach($cidades as $cidade){
            $return[$cidade->cidade] = $cidade->cidade;
        }

      
     
        return response()->json([
            'status' => 'success',
            'message' => 'Realizado com sucesso!',
            'error' => [],
            'response' =>  $return
        ]);
    }else{
        return $return;

    }
      
    }

    public function envioNovoContratoEmail(){   
              
        $tipoUsuarioObj = TipoUsuario::where('nome', 'Cliente')->first();
        
        $users = User::select()
            ->where('tipo_usuario_id', $tipoUsuarioObj->id)
            ->where('contrato', false)
            ->get();
        
        foreach($users as $user){
              
            $clienteNasajonObj = ClienteNasajon::select()->where('cpf_cnpj', $this->formatCnpjCpf($user->username))->where('bloqueado', 'false')->first();

            $date = Carbon::now();
            $date = $date->subMonth(12);
            $periodo =  $date->format('Y-m-d');

                       
            if(!empty($clienteNasajonObj->codigo)){                
 
                $totalVendas = Movimentacao::where('sinal', 'SAIDA')
                ->where('documento', '!=', ' ')
                ->where('cliente_codigo','=', $clienteNasajonObj->codigo)
                ->whereNotNull('data_movimentacao')
                ->where('data_movimentacao', '>=', $periodo)
                ->whereIn('cfop', [5922, 5949, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118, 5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101])
                ->sum('quantidade');

                             
                if($totalVendas > 0){  
                    try{
                        $this->enviarEmail($user->name, $user->email);
                    }catch(\Exception $e){
                        throw new \Exception($e);
                    }
                                
                }
            }     
           
        }
        
        
    }

    public function enviarEmail($nome_cliente, $email_cliente){
        
            $EmailObj = new EmailController();

            $email_send [] = $email_cliente;

            $variaveis = [
                'nome_cliente' => $nome_cliente,
            ];
            
            $retorno = $EmailObj->sendEmailToken('00', 'contrato_novo_cliente', $email_send, $variaveis);
            if($retorno['status'] == 'error'){
                throw new \Exception($retorno['error']);
            }
        
}

    public function formatCnpjCpf($value){
        $cnpj_cpf = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        } 

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }

}
