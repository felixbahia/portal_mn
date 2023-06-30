<?php

namespace App\Http\Controllers;

use Auth;

use Exception;

use Illuminate\Http\Request;

use App\StoneCadastroMaquininha;
use App\StoneConfiguracaoMaquininha;
use App\StoneMaquininhaVinculosFechado;

use App\Http\Requests\StoneCadastroRegistrarRequest;
use App\Http\Requests\StoneCadastroConfiguracaoRequest;
use Illuminate\Support\Facades\Config;

class StoneIntegracaoCadastroController extends Controller
{
    private $chave_privada = '';
    private $token = '';
    private $url_token = '';
    private $url_create_stone = '';
    private $url_consulta_pos_dosponivel = '';
    private $url_excluir_maquininha = '';
    private $url_configuracao_pos = '';
    private $url_ativa_vinculo = '';
    private $url_desativa_vinculo = '';

    public function __construct(){
        $this->getUrls();
    }
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\StoneCadastroMaquininha") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\StoneCadastroMaquininha');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        return view('programs.stone_cadastros.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    private function getUrls(){
        $this->chave_privada = config('stone.chave_privada');
        $this->url_token = config('stone.url_token');
        $this->url_create_stone = config('stone.url_create_stone');
        $this->url_consulta_pos_dosponivel = config('stone.url_consulta_pos_dosponivel');
        $this->url_excluir_maquininha = config('stone.url_excluir_maquininha');
        $this->url_configuracao_pos = config('stone.url_configuracao_pos');
        $this->url_ativa_vinculo = config('stone.url_ativa_vinculo');
        $this->url_desativa_vinculo = config('stone.url_desativa_vinculo');
    }

    private function gerarToken(){
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $request = $client->request('GET', $this->url_token, [
            'headers' => [
                'authorization' => $this->chave_privada,
            ],
            'timeout' => 60
        ]);

        $response = json_decode($request->getBody()->getContents());
        
        if($response->success == true){
            return $this->token = $response->token;
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao capturar o token, contate o setor responsável.',
                'error' => [],
                'response' => []
            ],422);
        }
    }

    public function modalCadastrar(){
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        return view('programs.stone_cadastros.modal.cadastro')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function cadastrar(StoneCadastroRegistrarRequest $request){
        $campos = $request->only(['estabelecimento','razao_social','nome_fantasia','cnpj','stone_code','partner_stone','descricao']);
        
        if(!empty($this->token)){
            $token = $this->token;
        }else{
            $this->gerarToken();
            $token = $this->token;
        }
        $cnpj = $this->removerMascara(trim($campos['cnpj']));

        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $request = $client->request('POST', $this->url_create_stone, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'authorization' => 'Bearer '.$token,
            ],
            'query' => [
                'is_establishment_to_production' => 'false',
                'legal_name' => $campos['razao_social'],
                'business_name' => $campos['nome_fantasia'],
                'document_number' => $cnpj,
                'stone_code' => $campos['stone_code'],
                'partner_stone_id' => $campos['partner_stone'],
            ],
        ]);
        
        $response = json_decode($request->getBody()->getContents());
        
        if($response->success == true){
            $id_estabelecimento = $response->establishment->id;
        }else if($response->success == false && $response->error == '061'){
            return response()->json([
                'status' => 'error',
                'message' => $response->msg,
                'error' => [],
                'response' => []
            ],422);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => $response->msg,
                'error' => [],
                'response' => []
            ],422);
        }

        $cadastro = new StoneCadastroMaquininha;
        $cadastro->estabelecimento = str_pad($campos['estabelecimento'],2,'0',STR_PAD_LEFT);
        $cadastro->razao_social = $campos['razao_social'];
        $cadastro->nome_fantasia = $campos['nome_fantasia'];
        $cadastro->documento = $campos['cnpj'];
        $cadastro->stone_code = $campos['stone_code'];
        $cadastro->partner_stone_id = $campos['partner_stone'];
        $cadastro->descricao = $campos['descricao'];
        $cadastro->created_by = Auth::user()->id;
        $cadastro->estabelecimento_stone_id = $id_estabelecimento;

        try{
            $cadastro->save();
        }catch(\Illuminate\Database\QueryException $e){
            throw new Exception(vsprintf(str_replace(['?'], ['\'%s\''], $e->getSql()), $e->getBindings()));
        }

        return response()->json([
            'status' => 'sucess',
            'message' => 'Maquininha gravada com sucesso.',
            'error' => [],
            'response' => []
        ],200);
    }

    private function removerMascara($valor){
        
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", "", $valor);
        $valor = str_replace("-", "", $valor);
        $valor = str_replace("/", "", $valor);

        return $valor;
    }

    public function filtro(Request $request){
        $campos = $request->only(['estabelecimento']);

        $miquinunhas = StoneCadastroMaquininha::with('configuracaoMaquininha');

        if(!empty($campos['estabelecimento'])){
            $miquinunhas->where('estabelecimento',str_pad($campos['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        $miquinunhas = $miquinunhas->get();
        $retorno = [];

        if(!empty($this->token)){
            $token = $this->token;
        }else{
            $this->gerarToken();
            $token = $this->token;
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $miquinunhas->each(function($query) use (&$retorno,$token,$estabelecimentos){
            $retorno[] = [
                'id' => encrypt($query->id),
                'estabelecimento' => $estabelecimentos[(integer)$query->estabelecimento],
                'razao_social' => $query->razao_social,
                'post_id' => (isset($query->configuracaoMaquininha->pos_reference_id_to_link) && !empty($query->configuracaoMaquininha->pos_reference_id_to_link)) ? '<center><i class="fa fa-check check-icon" aria-hidden="true"></i></center>' : '',
                'stone_code' => $query->stone_code,
                'partner_stone_id' => $query->partner_stone_id,
                'descricao' => $query->descricao,
                'vinculo' => (isset($query->configuracaoMaquininha->vinculo)) ? $query->configuracaoMaquininha->vinculo : 'Aberto'
            ];

        });

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno' => $retorno
            ]
        ],200);
    }

    public function excluirMaquininha(Request $request){
        $id_crypt = $request->only(['id']);

        try{
            $id = decrypt($id_crypt['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $miquinunhas = StoneCadastroMaquininha::find($id);
        $estabelecimento_stone_id = '';
        
        if(!empty($this->token)){
            $token = $this->token;
        }else{
            $this->gerarToken();
            $token = $this->token;
        }

        if(isset($miquinunhas->estabelecimento_stone_id) && !empty($miquinunhas->estabelecimento_stone_id)){
            $estabelecimento_stone_id = $miquinunhas->estabelecimento_stone_id;
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Maquininha não encontrada.',
                'error' => [],
                'response' => []
            ],422);
        }

        $status_request_api = new \GuzzleHttp\Client(['http_errors' => true]);
        $request = $status_request_api->request('delete',$this->url_excluir_maquininha.$estabelecimento_stone_id, [
            'headers' => [
                'Accept' => 'application/json',
                'authorization' => 'Bearer '.$token,
            ]
        ]);

        $response = json_decode($request->getBody()->getContents());
        
        if($response->success == true){
            $miquinunhas->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Máquininha excluída com sucesso.',
                'error' => [],
                'response' => []
            ],200);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao excluir, contate o setor responsável.',
                'error' => [],
                'response' => []
            ],422);
        }

    }

    public function modalConfigurar(Request $request){
        $id_crypt = $request->only(['id']);

        try{
            $id = decrypt($id_crypt['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        $configuracao_array = [];
        $miquinunha = StoneCadastroMaquininha::with('vinculosMaquininha')->find($id);
        $configuracao_query = StoneConfiguracaoMaquininha::where('stone_cadastro_maquininha_id',$id)->first();
        $existe = false;

        if(!empty($miquinunha->vinculosMaquininha)){
            foreach($miquinunha->vinculosMaquininha as $vinculo){
                $configuracao_array['vinculos_Array'][] = [
                    'cashier_number' => $vinculo->cashier_number,
                    'pdv_number' => $vinculo->pdv_number,
                    'pos_link_label' => $vinculo->pos_link_label,
                    'serial' => $vinculo->serial,
                    'id' => encrypt($vinculo->id),
                ];
            }
        }
        
        if(!empty($configuracao_query->id)){
            $configuracao_array['vinculo'] = $configuracao_query->vinculo;
            $configuracao_array['identificacao_caixa'] = $configuracao_query->cashier_number;
            $configuracao_array['identificacao_pdv'] = $configuracao_query->pdv_number;
            $configuracao_array['vinculo_nome'] = $configuracao_query->pos_link_label;
            $configuracao_array['lista'] = $configuracao_query->activate_single_information_automatic_select;
            $existe = true;
        }

        return view('programs.stone_cadastros.modal.configuracao')->with (['existe' => $existe,'id' => encrypt($miquinunha->id),'configuracao' => $configuracao_array]);
    }

    public function configurar(StoneCadastroConfiguracaoRequest $request){
        $campos = $request->only(['id','vinculo','desativar_lista','identificacao_caixa','identificacao_pdv','nome_vinculo','serial','travar_app','tempo_transacao']);
        
        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        
        $use_without_pos_config = null;
        $activate_linked_pos_config = null;
        $activate_unlinked_and_linked_pos_config = null;
        $activate_single_information_automatic_select = ($campos['desativar_lista'] == 'sim') ? true : false;
        $activate_dispose_transaction_any_pos = null;
        
        $miquinunha = StoneCadastroMaquininha::with('configuracaoMaquininha')->find($id);

        if(!isset($miquinunha->id)){
            return response()->json([
                'status' => 'error',
                'message' => 'Maquininha não encontrada.',
                'error' => [],
                'response' => []
            ],422);
        }

        if(!empty($this->token)){
            $token = $this->token;
        }else{
            $this->gerarToken();
            $token = $this->token;
        }

        $establishment_id = $miquinunha->estabelecimento_stone_id;
        $pos_reference_id = '';
        
        if($campos['vinculo'] == 'aberto'){
            $use_without_pos_config = true;
            $activate_linked_pos_config = false;
            $activate_unlinked_and_linked_pos_config = false;
            $activate_dispose_transaction_any_pos = false;
        }else if($campos['vinculo'] == 'fechado'){
            $use_without_pos_config = false;
            $activate_linked_pos_config = true;
            $activate_unlinked_and_linked_pos_config = false;
            $activate_dispose_transaction_any_pos = false;
        }else if($campos['vinculo'] == 'misto'){
            $use_without_pos_config = false;
            $activate_linked_pos_config = false;
            $activate_unlinked_and_linked_pos_config = true;
            $activate_dispose_transaction_any_pos = true;
        }

        try{
            $client = new \GuzzleHttp\Client(['http_errors' => true]);
            $request_configuracao = $client->request('POST', $this->url_configuracao_pos, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'authorization' => 'Bearer '.$token,
                ],
                'json' => [
                    'establishment_id' => $establishment_id,
                    'use_without_pos_config' => $use_without_pos_config,
                    'activate_linked_pos_config' => $activate_linked_pos_config,
                    'activate_unlinked_and_linked_pos_config' => $activate_unlinked_and_linked_pos_config,
                    'activate_single_information_automatic_select' => $activate_single_information_automatic_select,
                    'activate_dispose_transaction_any_pos' => $activate_dispose_transaction_any_pos,
                    'lock_app' => (isset($campos['travar_app']) && $campos['travar_app'] == 'sim') ? true : false,
                    'instruction_activation_time' => (isset($campos['tempo_transacao']) && !empty($campos['tempo_transacao']) && is_numeric($campos['tempo_transacao']) && $campos['tempo_transacao'] >= 1500 && $campos['tempo_transacao'] <= 21600) ? (int)$campos['tempo_transacao'] : 1500
                ],
            ]);

            $response_configuracao = json_decode($request_configuracao->getBody()->getContents());
            
            if($response_configuracao->success != true){
                return response()->json([
                    'status' => 'error',
                    'message' => $response_configuracao->msg,
                    'error' => [],
                    'response' => []
                ],422);
            }
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => [],
                'response' => []
            ],422);
        }
        
        try{
            $request_consulta_configuracao = $client->request('GET', $this->url_configuracao_pos.'/'.$establishment_id, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'authorization' => 'Bearer '.$token,
                ]
            ]);

            $response_consulta_configuracao = json_decode($request_consulta_configuracao->getBody()->getContents());
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => [],
                'response' => []
            ],422);
        }

        $lock_app = $response_consulta_configuracao->pos_control_configuration->lock_app;
        $view_error_request = $response_consulta_configuracao->pos_control_configuration->view_error_request;
        $display_view_cancel_pre_transaction = $response_consulta_configuracao->pos_control_configuration->display_view_cancel_pre_transaction;
        $pos_configuration_control_id = $response_consulta_configuracao->pos_control_configuration->pos_configuration_control_id;
        $instruction_activation_time = $response_consulta_configuracao->pos_control_configuration->instruction_activation_time;
        $instruction_activation_time = $response_consulta_configuracao->pos_control_configuration->instruction_activation_time;

        if(isset($miquinunha->configuracaoMaquininha->id)){
            $configuracao_query = StoneConfiguracaoMaquininha::find($miquinunha->configuracaoMaquininha->id);
        }else{
            $configuracao_query = new StoneConfiguracaoMaquininha;
            $configuracao_query->stone_cadastro_maquininha_id = $miquinunha->id;
        }

        $configuracao_query->use_without_pos_config = (!empty($use_without_pos_config)) ? $use_without_pos_config : '0';
        $configuracao_query->activate_linked_pos_config = (!empty($activate_linked_pos_config)) ? $activate_linked_pos_config : '0';
        $configuracao_query->activate_unlinked_and_linked_pos_config = (!empty($activate_unlinked_and_linked_pos_config)) ? $activate_unlinked_and_linked_pos_config : '0';
        $configuracao_query->activate_single_information_automatic_select = (!empty($activate_single_information_automatic_select)) ? $activate_single_information_automatic_select : '0';
        $configuracao_query->activate_dispose_transaction_any_pos = (!empty($activate_dispose_transaction_any_pos)) ? $activate_dispose_transaction_any_pos : '0';
        $configuracao_query->lock_app = (!empty($lock_app)) ? $lock_app : '0';
        $configuracao_query->view_error_request = (!empty($view_error_request)) ? $view_error_request : '0';
        $configuracao_query->display_view_cancel_pre_transaction = (!empty($display_view_cancel_pre_transaction)) ? $display_view_cancel_pre_transaction : '0';;
        $configuracao_query->instruction_activation_time = (!empty($instruction_activation_time)) ? $instruction_activation_time : '0';
        $configuracao_query->pos_configuration_control_id = (!empty($pos_configuration_control_id)) ? $pos_configuration_control_id : '0';
        $configuracao_query->cashier_number = null;
        $configuracao_query->pdv_number = 'sem_number_unico';
        $configuracao_query->pos_link_label = null;
        $configuracao_query->vinculo = ucfirst($campos['vinculo']);
        $configuracao_query->created_by = Auth::user()->id;

        try{
            $configuracao_query->save();
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => [],
                'response' => []
            ],422);
        }


        if($campos['vinculo'] == 'fechado' && !empty($campos['identificacao_caixa'])){
            foreach($campos['identificacao_caixa'] as $key => $campo_vinculo){
                $verifica_vinculo = StoneMaquininhaVinculosFechado::where('serial',$campos['serial'][$key])->first();
                
                if(isset($verifica_vinculo->id) && !empty($verifica_vinculo)){
                    continue;
                }

                try{
                    $request_ativacao = $client->request('POST', $this->url_ativa_vinculo, [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'authorization' => 'Bearer '.$token,
                        ],
                        'query' => [
                            'cashier_number' => $campos['identificacao_caixa'][$key],
                            'pdv_number' => $campos['identificacao_pdv'][$key],
                            'pos_link_label' => $campos['nome_vinculo'][$key],
                            'pos_serial_number_to_link' => trim($campos['serial'][$key]),
                        ],
                    ]);

                    $response_ativacao = json_decode($request_ativacao->getBody()->getContents());

                    if($response_ativacao->success == true){
                        $pos_reference_id = $response_ativacao->pos_link->pos_reference_id;

                        $vinculo_fechado_model = new StoneMaquininhaVinculosFechado;
                        $vinculo_fechado_model->stone_cadastro_maquininha_id = $miquinunha->id;
                        $vinculo_fechado_model->serial = $campos['serial'][$key];
                        $vinculo_fechado_model->cashier_number = $campos['identificacao_caixa'][$key];
                        $vinculo_fechado_model->pdv_number = $campos['identificacao_pdv'][$key];
                        $vinculo_fechado_model->pos_link_label = $campos['nome_vinculo'][$key];
                        $vinculo_fechado_model->pos_reference_id = $pos_reference_id;
                        $vinculo_fechado_model->stone_configuracao_maquininha_id = $configuracao_query->id;
                        $vinculo_fechado_model->created_by = Auth::user()->id;
                        $vinculo_fechado_model->save();
                    }else{
                        return response()->json([
                            'status' => 'error',
                            'message' => $response_ativacao->msg,
                            'error' => [],
                            'response' => []
                        ],422);
                    }
                }catch(Exception $e){
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'error' => [],
                        'response' => []
                    ],422);
                }
            }
        }

        return response()->json([
            'status' => 'sucess',
            'message' => 'Configuração gravada com sucesso.',
            'error' => [],
            'response' => []
        ],200);

    }

    public function excluirVinculo(Request $request){
        $campos = $request->only(['id']);

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        
        if(!empty($this->token)){
            $token = $this->token;
        }else{
            $this->gerarToken();
            $token = $this->token;
        }

        $vinculo_fechado_model = StoneMaquininhaVinculosFechado::find($id);

        try{
            $client = new \GuzzleHttp\Client(['http_errors' => true]);
            $request_configuracao = $client->request('PUT', $this->url_desativa_vinculo.$vinculo_fechado_model->pos_reference_id, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'authorization' => 'Bearer '.$token,
                ]
            ]);
    
            $response_configuracao = json_decode($request_configuracao->getBody()->getContents());
            if(isset($response_configuracao->success) && $response_configuracao->success == true){
                $vinculo_fechado_model->delete();
            }
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => [],
                'response' => []
            ],422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Vínculo excluído',
            'error' => [],
            'response' => []
        ],200);
    }
}
