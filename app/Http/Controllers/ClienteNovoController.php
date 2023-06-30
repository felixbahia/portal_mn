<?php

namespace App\Http\Controllers;

use Auth;
use Hash;
use App\User;
use App\Cliente;
use App\CepEstado;
use App\CepEndereco;
use App\ClienteNovo;
use App\TipoUsuario;
use App\PedidoPortal;
use App\ClienteCredito;
use App\ClienteNasajon;
use App\ClienteDocumento;
use App\ClienteNovoSocio;
use App\ConceitosCliente;

use Illuminate\Http\Request;
use App\ClienteNovoReferencia;

use App\UsuarioClientePendente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\EmailController;

use App\Http\Requests\ClienteNovoRequest;
use App\Http\Requests\AprovacaoClienteNovoRequest;
use App\Http\Requests\ValidarCPFCNPJExisteRequest;

use App\Http\Requests\ReprovacaoClienteNovoRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClienteNovoController extends Controller{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $path = 'public/cliente_documento/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ClienteNovo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ClienteNovo');
        return view('programs.cliente_novo.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){

        $estadosObj = CepEstado::all()->toArray();
        $estados = array(""=>"Estado");
        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }

        $inscricao_estadual = [
            '' => 'Inscrição Estadual Indicador',
            '1' => '1 - Contribuinte de ICMS.',
            '2' => '2 - Contribuinte Isento de Inscrição no Cadastro de Contribuintes de ICMS.',
            '9' => '9 - Não Contribuinte, que pode ou não possuir inscrição estadual.'
        ];

        return view('programs.cliente_novo.cadastro')->with(['estados' => $estados, 'inscricaoEstadual' => $inscricao_estadual]);
    }

    public function validarCPF_CNPJ(ValidarCPFCNPJExisteRequest $request){
      
        $cnpj = $request->cnpj;
        
        $clienteNetrinApiController  = new ClienteNetrinApiController();
        $netrinApiRetorno = $clienteNetrinApiController->consultaCliente($cnpj);
        $clienteNetrin =json_decode($netrinApiRetorno->json_retorno);
         $response = [];
        if(empty($clienteNetrin->receitaFederal) && empty($clienteNetrin->sintegra) ){

            $response = [
                "mensagemError" =>'Dados Não Encontrado!',
             ];

            $return = [
                "status" => "error",
                "message" => "Dados Não Encontrado!",
                "response" => $response,
                "error" => ""
            ];
         
			return response()->json($return);
		}
        if(!empty($clienteNetrin->receitaFederal)){
                    $response = [
                        "nome_razao" =>$clienteNetrin->receitaFederal->razaoSocial,
                        "guerra_apelido" =>$clienteNetrin->receitaFederal->nomeFantasia,
                        "cep" => str_replace([".", ",", "/"], "", $clienteNetrin->receitaFederal->cep) ,
                        "logradouro" =>$clienteNetrin->receitaFederal->logradouro,
                        "numero" =>$clienteNetrin->receitaFederal->numero,
                        "tipo_logradouro" => '',
                        "complemento" =>$clienteNetrin->receitaFederal->complemento,
                        "local" => '',
                        "bairro" => $clienteNetrin->receitaFederal->bairro,
                        "cidade" =>  $clienteNetrin->receitaFederal->municipio,
                        "uf" =>$clienteNetrin->receitaFederal->uf,
                        "telefone" => $clienteNetrin->receitaFederal->telefone,
                        "email" => $clienteNetrin->receitaFederal->email,
                        "socios" => '',
                        "inscricao_estadual" =>'',
                        "inscricao_estadual_indicador" =>'',
                        "tem_suframa" =>'',
                        "situacao_cadastral"=>$clienteNetrin->receitaFederal->situacaoCadastral
                    ];



         }

         if(!empty( $clienteNetrin->receitaFederal->qsa)){
            $response ['socios']  = $clienteNetrin->receitaFederal->qsa;
         }
        
         if(!empty($clienteNetrin->sintegra->inscricoesEstaduais)){
            $response ['inscricao_estadual']=$clienteNetrin->sintegra->inscricoesEstaduais[0]->inscricaoEstadual;
            if($clienteNetrin->sintegra->inscricoesEstaduais[0]->situacaoCadastral == 'INATIVO'){
                $response ['inscricao_estadual_indicador']=9;
            }else{
                $response ['inscricao_estadual_indicador']=1;
            }
           
         }else{
            $response ['inscricao_estadual_indicador']=9;
         }
         if(!empty($clienteNetrin->suframa->inscricoesEstaduais)){
            $response ['tem_suframa']=$clienteNetrin->suframa->inscricoesEstaduais[0]->inscricaoEstadual;
        }
        if(!empty($response['email'])){
            if(!filter_var($response['email'], FILTER_VALIDATE_EMAIL)){
                $response['email'] ='';
            }
        }
        if(!empty($response['telefone'])){
            if(strlen($response['telefone']) > 20){
                $response['telefone'] = substr($response['telefone'], 0,20);
            }
        }

		foreach ($response as $key => $value) {
			if(is_null($value) || empty($value)){
				$response[$key] = "";
			}
		}

        $return = [
            "status" => "success",
            "message" => "",
            "response" => $response,
            "error" => ""
        ];
      
       return response()->json($return);


    }


    public function store(ClienteNovoRequest $request){
        
        $fields = $request->all();
        
        if (!isset($fields['use_dados_faturamento'])){
            $fields['use_dados_faturamento'] = null;
        }

        $clienteNovoObj = new ClienteNovo();
        $clienteNovoObj->status                         = 1;
        $clienteNovoObj->ja_foi_cliente                 = $fields['ja_foi_cliente'];
        $clienteNovoObj->ja_foi_cliente_quando          = $fields['ja_foi_cliente_quando'];
        $clienteNovoObj->fisica_juridica                = substr($fields['fisica_juridica'], 0, 1);
        $clienteNovoObj->cpf_cnpj                       = (strtolower(substr($fields['fisica_juridica'], 0, 1)) === "f") ? $fields['cpf'] : $fields['cnpj'];
        if($fields['inscricao_estadual_indicador'] == 2){
            $clienteNovoObj->inscricao_estadual         = '';
        }
        else{
            $clienteNovoObj->inscricao_estadual         = $fields['inscricao_estadual'];
        }
        $clienteNovoObj->inscricao_estadual_indicador   = $fields['inscricao_estadual_indicador'];
        $clienteNovoObj->inscricao_municipal            = $fields['inscricao_municipal'];
        $clienteNovoObj->suframa                        = $fields['suframa'];
        $clienteNovoObj->nome_razao                     = $fields['nome_razao'];
        $clienteNovoObj->guerra_apelido                 = $fields['guerra_apelido'];
        $clienteNovoObj->telefone                       = $fields['telefone'];
        $clienteNovoObj->telefone_fax                   = $fields['telefone_fax'];
        $clienteNovoObj->email                          = $fields['email'];
        $clienteNovoObj->vendedor_codigo                = $fields['vendedor_codigo'];
        $clienteNovoObj->transportador_codigo           = $fields['transportador_codigo'];
        $clienteNovoObj->forte_cliente                  = isset($fields['forte_cliente']) ? $fields['forte_cliente'] : null;
        $clienteNovoObj->tamanho_cliente                = isset($fields['tamanho_cliente']) ? $fields['tamanho_cliente'] : null;
        $clienteNovoObj->ramo_atividade                 = isset($fields['ramo_atividade']) ? $fields['ramo_atividade'] : null;
        $clienteNovoObj->numero_filiais                 = $fields['numero_filiais'];
        $clienteNovoObj->numero_empregados              = $fields['numero_empregados'];
        $clienteNovoObj->predio_proprio                 = isset($fields['predio_proprio']) ? $fields['predio_proprio'] : null;
        $clienteNovoObj->aluguel                        = $fields['aluguel'];
        $clienteNovoObj->sucessora_de                   = $fields['sucessora_de'];
        $clienteNovoObj->ligacao_com                    = $fields['ligacao_com'];
        $clienteNovoObj->sugestao_credito               = $fields['sugestao_credito'];
        $clienteNovoObj->historico_cliente_praca        = $fields['historico_cliente_praca'];
        $clienteNovoObj->faturamento_cep                = $fields['faturamento_cep'];
        $clienteNovoObj->faturamento_logradouro         = $fields['faturamento_logradouro'];
        $clienteNovoObj->faturamento_numero             = $fields['faturamento_numero'];
        $clienteNovoObj->faturamento_complemento        = $fields['faturamento_complemento'];
        $clienteNovoObj->faturamento_bairro             = $fields['faturamento_bairro'];
        $clienteNovoObj->faturamento_cidade             = $fields['faturamento_cidade'];
        $clienteNovoObj->faturamento_estado             = $fields['faturamento_estado'];
        $clienteNovoObj->faturamento_telefone           = $fields['faturamento_telefone'];
        $clienteNovoObj->faturamento_telefone_fax       = $fields['faturamento_telefone_fax'];
        $clienteNovoObj->faturamento_email              = $fields['faturamento_email'];
        
        $clienteNovoObj->cobranca_cep               = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_cep'] : $fields['faturamento_cep'];
        
        $clienteNovoObj->cobranca_logradouro        = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_logradouro'] : $fields['faturamento_logradouro'];

        $clienteNovoObj->cobranca_numero            = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_numero'] : $fields['faturamento_numero'];
        
        $clienteNovoObj->cobranca_complemento       = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_complemento'] :$fields['faturamento_complemento'];
        
        $clienteNovoObj->cobranca_bairro            = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_bairro'] : $fields['faturamento_bairro'];
        
        $clienteNovoObj->cobranca_cidade            = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_cidade'] : $fields['faturamento_cidade'];
        
        $clienteNovoObj->cobranca_estado            = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_estado'] : $fields['faturamento_estado'];
        
        $clienteNovoObj->cobranca_telefone          = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_telefone'] : $fields['faturamento_telefone'];
        
        $clienteNovoObj->cobranca_telefone_fax      = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_telefone_fax'] : $fields['faturamento_telefone_fax'];
        
        $clienteNovoObj->cobranca_banco = $fields['cobranca_banco'];
        $clienteNovoObj->cobranca_agencia = $fields['cobranca_agencia'];
        $clienteNovoObj->cobranca_conta = $fields['cobranca_conta'];
        $clienteNovoObj->use_dados_faturamento = $fields['use_dados_faturamento'] == 'dados_faturamento' ? true : false;
        $clienteNovoObj->created_by = Auth::user()->id;
  
        if($clienteNovoObj->save()){
            if(isset($fields['socio'])){
                foreach ($fields['socio'] as $key => $value) {
                    $ClienteNovoSocioObj = new ClienteNovoSocio();
                    $ClienteNovoSocioObj->cliente_novo_id = $clienteNovoObj->id;
                    $ClienteNovoSocioObj->nome = $value["nome"];
                    $ClienteNovoSocioObj->cpf = $value["cpf"];
                    $ClienteNovoSocioObj->parte = $value["parte"];
                    $ClienteNovoSocioObj->created_by = Auth::user()->id;
                    $ClienteNovoSocioObj->save();
                }
            }
            if(isset($fields['referencia'])){
                foreach ($fields['referencia'] as $key => $value) {
                    $ClienteNovoReferenciaObj = new ClienteNovoReferencia();
                    $ClienteNovoReferenciaObj->cliente_novo_id = $clienteNovoObj->id;
                    $ClienteNovoReferenciaObj->empresa = $value["empresa"];
                    $ClienteNovoReferenciaObj->contato = $value["contato"];
                    $ClienteNovoReferenciaObj->telefone_ddd = $value["telefone_ddd"];
                    $ClienteNovoReferenciaObj->telefone = $value["telefone"];
                    $ClienteNovoReferenciaObj->estado = $value["estado"];
                    $ClienteNovoReferenciaObj->cidade = $value["cidade"];
                    $ClienteNovoReferenciaObj->created_by = Auth::user()->id;
                    $ClienteNovoReferenciaObj->save();
                }
            }

            $documentos = $request->file('documento');

            if($request->hasFile('documento'))
            {
                foreach($documentos as $key => $documento){
                    $cliente_documento_objeto = new ClienteDocumento();
                    $cliente_documento_objeto->cpf_cnpj = $clienteNovoObj->cpf_cnpj;
                    $cliente_documento_objeto->descricao_documento = $fields['descricao_documento'][$key];
                    $cliente_documento_objeto->created_by = Auth::user()->id;
                    $cliente_documento_objeto->save();
                    $file = $cliente_documento_objeto->id.'.' .$documento->getClientOriginalExtension();
                    $cliente_documento_objeto->documento = $this->path.$file;
                    $documento->storeAs($this->path, $file);
                    $cliente_documento_objeto->save();
                }
            }

            $aprovacaoClienteRequest = new AprovacaoClienteNovoRequest([
                "id" => Crypt::encrypt($clienteNovoObj->id),
                "limite_credito" => 1,
                "conceito" => Crypt::encrypt('1')
            ]);

            $this->aprovarCadastro($aprovacaoClienteRequest);

            $this->criacaoUsuarioCliente($clienteNovoObj);

            return response()->json(["status"=>"success"]);
        }else{
            return response()->json(["status"=>"error", "message"=>"Ocorreu um erro ao salvar"]);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ClienteNovo  $ClienteNovo
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request){
        $id = $request->input("id");
        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Código informado errado!"
            ]);
        }
        try {
            $clienteNovoObj = ClienteNovo::with(["socios", "referencias", "vendedor", "transportador", "status_descricao"])->findOrFail($id)->toArray();
        } catch (\Exception $e){
            return abort(404);
        }

        $estadosObj = CepEstado::all()->toArray();
        $estados = array(""=>"Estado");
        
        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }
        if(!is_null($clienteNovoObj["vendedor"])){
            $clienteNovoObj["vendedor"] = [
                "codigo" => $clienteNovoObj["vendedor"]["codigo"],
                "nome" => $clienteNovoObj["vendedor"]["nome"]
            ];
        }
        if(!is_null($clienteNovoObj["transportador"])){
            $clienteNovoObj["transportador"] = [
                "codigo" => $clienteNovoObj["transportador"]["codigo"],
                "nome" => $clienteNovoObj["transportador"]["nome"]
            ];
        }
        $clienteNovoObj["status_descricao"] = $clienteNovoObj['status_descricao']['status'];
        $inscricao_estadual = [
            '' => 'Inscrição Estadual Indicador',
            '1' => '1 - Contribuinte de ICMS.',
            '2' => '2 - Contribuinte Isento de Inscrição no Cadastro de Contribuintes de ICMS.',
            '9' => '9 - Não Contribuinte, que pode ou não possuir inscrição estadual.'
        ];
        return view('programs.cliente_novo.editar')->with(['estados' => $estados, 'dados' => $clienteNovoObj, 'inscricaoEstadual' => $inscricao_estadual]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ClienteNovo  $ClienteNovo
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request){
        $id = $request->input("id");
        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Código informado errado!"
            ]);
        }
        try {
            $clienteNovoObj = ClienteNovo::with(["socios", "referencias", "vendedor", "transportador","documentos"])->findOrFail($id)->toArray();
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Cliente não encontrado!"
            ]);
        }
        $estadosObj = CepEstado::all()->toArray();
        $estados = array(""=>"Estado");
        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }
        if(!is_null($clienteNovoObj["vendedor"])){
            $clienteNovoObj["vendedor"] = [
                "codigo" => $clienteNovoObj["vendedor"]["codigo"],
                "nome" => $clienteNovoObj["vendedor"]["nome"]
            ];
        }
        if(!is_null($clienteNovoObj["transportador"])){
            $clienteNovoObj["transportador"] = [
                "codigo" => $clienteNovoObj["transportador"]["codigo"],
                "nome" => $clienteNovoObj["transportador"]["nome"]
            ];
        }

        return view('programs.cliente_novo.visualizar')->with(['estados' => $estados, 'dados' => $clienteNovoObj]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ClienteNovo  $ClienteNovo
     * @return \Illuminate\Http\Response
     */
    public function update(ClienteNovoRequest $request){
        $fields = $request->all();
        $clienteNovoObj = ClienteNovo::findOrFail($fields["id"]);
        $clienteNovoObj->status = 1;
        $clienteNovoObj->ja_foi_cliente = $fields['ja_foi_cliente'];
        $clienteNovoObj->ja_foi_cliente_quando = $fields['ja_foi_cliente_quando'];
        $clienteNovoObj->fisica_juridica = substr($fields['fisica_juridica'], 0, 1);
        $clienteNovoObj->cpf_cnpj = (strtolower(substr($fields['fisica_juridica'], 0, 1)) === "f") ? $fields['cpf'] : $fields['cnpj'];
        $clienteNovoObj->inscricao_estadual = $fields['inscricao_estadual'];
        $clienteNovoObj->inscricao_estadual_indicador = $fields['inscricao_estadual_indicador'];
        $clienteNovoObj->inscricao_municipal = $fields['inscricao_municipal'];
        $clienteNovoObj->suframa = $fields['suframa'];
        $clienteNovoObj->nome_razao = $fields['nome_razao'];
        $clienteNovoObj->guerra_apelido = $fields['guerra_apelido'];
        $clienteNovoObj->telefone = $fields['telefone'];
        $clienteNovoObj->telefone_fax = $fields['telefone_fax'];
        $clienteNovoObj->email = $fields['email'];
        $clienteNovoObj->vendedor_codigo = $fields['vendedor_codigo'];
        $clienteNovoObj->transportador_codigo = $fields['transportador_codigo'];
        $clienteNovoObj->forte_cliente = isset($fields['forte_cliente']) ? $fields['forte_cliente'] : null;
        $clienteNovoObj->tamanho_cliente = isset($fields['tamanho_cliente']) ? $fields['tamanho_cliente'] : null;
        $clienteNovoObj->ramo_atividade = isset($fields['ramo_atividade']) ? $fields['ramo_atividade'] : null;
        $clienteNovoObj->numero_filiais = $fields['numero_filiais'];
        $clienteNovoObj->numero_empregados = $fields['numero_empregados'];
        $clienteNovoObj->predio_proprio = isset($fields['predio_proprio']) ? $fields['predio_proprio'] : null;
        $clienteNovoObj->aluguel = $fields['aluguel'];
        $clienteNovoObj->sucessora_de = $fields['sucessora_de'];
        $clienteNovoObj->ligacao_com = $fields['ligacao_com'];
        $clienteNovoObj->sugestao_credito = $fields['sugestao_credito'];
        $clienteNovoObj->historico_cliente_praca = $fields['historico_cliente_praca'];
        $clienteNovoObj->faturamento_cep = $fields['faturamento_cep'];
        $clienteNovoObj->faturamento_logradouro = $fields['faturamento_logradouro'];
        $clienteNovoObj->faturamento_numero = $fields['faturamento_numero'];
        $clienteNovoObj->faturamento_complemento = $fields['faturamento_complemento'];
        $clienteNovoObj->faturamento_bairro = $fields['faturamento_bairro'];
        $clienteNovoObj->faturamento_cidade = $fields['faturamento_cidade'];
        $clienteNovoObj->faturamento_estado = $fields['faturamento_estado'];
        $clienteNovoObj->faturamento_telefone = $fields['faturamento_telefone'];
        $clienteNovoObj->faturamento_telefone_fax = $fields['faturamento_telefone_fax'];
        $clienteNovoObj->faturamento_email = $fields['faturamento_email'];
        $clienteNovoObj->cobranca_cep = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_cep'] : $fields['faturamento_cep'];
        $clienteNovoObj->cobranca_logradouro = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_logradouro'] : $fields['faturamento_logradouro'];
        $clienteNovoObj->cobranca_numero = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_numero'] : $fields['faturamento_numero'];
        $clienteNovoObj->cobranca_complemento = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_complemento'] :$fields['faturamento_complemento'];
        $clienteNovoObj->cobranca_bairro = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_bairro'] : $fields['faturamento_bairro'];
        $clienteNovoObj->cobranca_cidade = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_cidade'] : $fields['faturamento_cidade'];
        $clienteNovoObj->cobranca_estado = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_estado'] : $fields['faturamento_estado'];
        $clienteNovoObj->cobranca_telefone = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_telefone'] : $fields['faturamento_telefone'];
        $clienteNovoObj->cobranca_telefone_fax = $fields['use_dados_faturamento'] !== 'dados_faturamento' ? $fields['cobranca_telefone_fax'] : $fields['faturamento_telefone_fax'];
        $clienteNovoObj->cobranca_banco = $fields['cobranca_banco'];
        $clienteNovoObj->cobranca_agencia = $fields['cobranca_agencia'];
        $clienteNovoObj->cobranca_conta = $fields['cobranca_conta'];
        $clienteNovoObj->use_dados_faturamento = $fields['use_dados_faturamento'] == 'dados_faturamento' ? true : false;
        $clienteNovoObj->motivo_recusa = "";
        $clienteNovoObj->updated_by = Auth::user()->id;
    
        if($clienteNovoObj->save()){
            ClienteNovoSocio::where("cliente_novo_id", $clienteNovoObj->id)->delete();
            ClienteNovoReferencia::where("cliente_novo_id", $clienteNovoObj->id)->delete();
            if(isset($fields['socio'])){
                foreach ($fields['socio'] as $key => $value) {
                    $ClienteNovoSocioObj = new ClienteNovoSocio();
                    $ClienteNovoSocioObj->cliente_novo_id = $clienteNovoObj->id;
                    $ClienteNovoSocioObj->nome = $value["nome"];
                    $ClienteNovoSocioObj->cpf = $value["cpf"];
                    $ClienteNovoSocioObj->parte = $value["parte"];
                    $ClienteNovoSocioObj->created_by = Auth::user()->id;
                    $ClienteNovoSocioObj->save();
                }
            }
            if(isset($fields['referencia'])){
                foreach ($fields['referencia'] as $key => $value) {
                    $ClienteNovoReferenciaObj = new ClienteNovoReferencia();
                    $ClienteNovoReferenciaObj->cliente_novo_id = $clienteNovoObj->id;
                    $ClienteNovoReferenciaObj->empresa = $value["empresa"];
                    $ClienteNovoReferenciaObj->contato = $value["contato"];
                    $ClienteNovoReferenciaObj->telefone_ddd = $value["telefone_ddd"];
                    $ClienteNovoReferenciaObj->telefone = $value["telefone"];
                    $ClienteNovoReferenciaObj->estado = $value["estado"];
                    $ClienteNovoReferenciaObj->cidade = $value["cidade"];
                    $ClienteNovoReferenciaObj->created_by = Auth::user()->id;
                    $ClienteNovoReferenciaObj->save();
                }
            }

            $aprovacaoClienteRequest = new AprovacaoClienteNovoRequest([
                "id" => Crypt::encrypt($clienteNovoObj->id),
                "limite_credito" => 1,
                "conceito" => Crypt::encrypt('1')
            ]);

            $this->aprovarCadastro($aprovacaoClienteRequest);

            return response()->json(["status"=>"success"]);
        }else{
            return response()->json(["status"=>"error", "message"=>"Ocorreu um erro ao salvar"]);
        }
    }

    public function delete(Request $request){
        $id = $request->input("id");
        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Código informado errado!"
            ]);
        }
        $clienteNovoObj = ClienteNovo::with(["socios","referencias"])->findOrFail($id)->toArray();
        return view('programs.cliente_novo.excluir')->with(['dados' => $clienteNovoObj]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ClienteNovo  $ClienteNovo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request){
        $fields = $request->only(["id"]);
        $clienteNovoObj = ClienteNovo::findOrFail($fields["id"]);
        try{
            $clienteNovoObj->deleted_by = Auth::user()->id;
            $clienteNovoObj->delete();
            return response()->json(["status" => "success"]);
        } catch (\Exception $e){
            return response()->json(["status" => "error", "message" => "Não foi possivel excluir este cliente"]);
        }
    }

    /**
     * Filtro de cliente
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request){
        $fields = $request->only(["nome", "nome_guerra", "cnpj_cpf"]);
        $where = [];
        if(!empty($fields["nome"])){
            $where[] = ["LOWER(nome_razao)", "like", "%".strtolower(trim($fields["nome"]))."%"];
        }
        if(!empty($fields["nome_guerra"])){
            $where[] = ["LOWER(guerra_apelido)", "like", "%".strtolower(trim($fields["nome_guerra"]))."%"];
        }
        if(!empty($fields["cnpj_cpf"])){
            $where[] = ["cpf_cnpj", "like", "%".strtolower(trim($fields["cnpj_cpf"]))."%"];
        }
        $busca = ClienteNovo::select("id", "status", "nome_razao", "guerra_apelido", "cpf_cnpj", "faturamento_cidade as cidade", "faturamento_estado as estado")->with(['status_descricao']);
        foreach ($where as $key => $value) {
            $busca->whereRaw("{$value[0]} {$value[1]} '{$value[2]}'");
        }
        $busca->where("status", '<>', 2);
        if (
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") === false && 
            strtolower(Auth::user()->tipo_usuario->nome) !== 'administrador' &&
            strtolower(Auth::user()->tipo_usuario->nome) !== 'credito'
        )
            {
            $busca->where("created_by", Auth::id());
        }
        $return_busca = $busca->get()->toArray();
        $response = [];
        foreach ($return_busca as $key => $cliente) {
            $exibe_botao = false;
            if($cliente["status"] == 3){
                $exibe_botao = true;
            }
            $status_descricao = $cliente["status_descricao"]['status'];
            $response[] = [
                "id" => Crypt::encrypt($cliente['id']),
                "exibe_botao" => $exibe_botao,
                "nome_razao" => $cliente['nome_razao'],
                "guerra_apelido" => $cliente['guerra_apelido'],
                "cpf_cnpj" => $cliente['cpf_cnpj'],
                "cidade" => $cliente['cidade'],
                "estado" => $cliente['estado'],
                "status_descricao" => $status_descricao
            ];
            unset($return_busca[$key]);
        }
        unset($return_busca);
        $return = [
            "status" => "success",
            "message" => "",
            "response" => $response,
            "error" => ""
        ];
        return response()->json($return);
    }

    /**
     * Filtro de cliente
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function filterAprovacao(Request $request){
        $fields = $request->only(["nome", "nome_guerra", "cnpj_cpf"]);
        $where = [];
        if(!empty($fields["nome"])){
            $where[] = ["LOWER(nome_razao)", "like", "%".strtolower(trim($fields["nome"]))."%"];
        }
        if(!empty($fields["nome_guerra"])){
            $where[] = ["LOWER(guerra_apelido)", "like", "%".strtolower(trim($fields["nome_guerra"]))."%"];
        }
        if(!empty($fields["cnpj_cpf"])){
            $where[] = ["cpf_cnpj", "like", "%".strtolower(trim($fields["cnpj_cpf"]))."%"];
        }
        $busca = ClienteNovo::select("id", "nome_razao", "guerra_apelido", "cpf_cnpj", "faturamento_cidade as cidade", "faturamento_estado as estado", "transportador_codigo", "vendedor_codigo");
        foreach ($where as $key => $value) {
            $busca->whereRaw("{$value[0]} {$value[1]} '{$value[2]}'");
        }
        $busca->where("status", '1');
        $return_busca = $busca->with(['vendedor', 'transportador', 'pedidos', 'pedidos.valor_total'])->get()->toArray();
        $response = [];
        foreach ($return_busca as $key => $cliente) {
            $vendedor = $cliente["vendedor"]['codigo'].' - '.$cliente["vendedor"]['nome'];

            $pedidos_count = count($cliente["pedidos"]) > 0 ? count($cliente["pedidos"]) : "" ;
            $pedidos_total = 0;
            foreach ($cliente["pedidos"] as $pedido) {
                $pedidos_total += floatval($pedido["valor_total"]["total"]);
            }
            $pedidos_total = (floatval($pedidos_total) > 0) ? parserQtd($pedidos_total) : "";

            $response[] = [
                "id" => Crypt::encrypt($cliente['id']),
                "nome_razao" => htmlentities($cliente['nome_razao']),
                "guerra_apelido" => htmlentities($cliente['guerra_apelido']),
                "cpf_cnpj" => $cliente['cpf_cnpj'],
                "vendedor" => utf8_encode($vendedor),
                "pedidos_count" => $pedidos_count,
                "pedidos_total" => $pedidos_total,
            ];
            unset($return_busca[$key]);
        }
        unset($return_busca);
        $return = [
            "status" => "success",
            "message" => "",
            "response" => $response,
            "error" => ""
        ];
        return response()->json($return);
    }

    public function checkClienteExiste($cpf_cnpj){
        if(strlen(trim($cpf_cnpj)) === 14 && !valiteCPF($cpf_cnpj)){
            $response = [
                "status" => "error",
                "message" => "CPF Informado está inválido."
            ];
            return $response;
        }
        if(strlen(trim($cpf_cnpj)) > 14 && !valiteCNPJ($cpf_cnpj)){
            $response = [
                "status" => "error",
                "message" => "CNPJ Informado está inválido."
            ];
            return $response;
        }
        $cpf_cnpj = trim($cpf_cnpj);
        $cpf_cnpj = str_replace("-", "", $cpf_cnpj);
        $cpf_cnpj = str_replace(".", "", $cpf_cnpj);
        $cpf_cnpj = str_replace("/", "", $cpf_cnpj);
        $cpf_cnpj = trim($cpf_cnpj);

        $return = [];
        $return_busca = [];
        $busca = Cliente::where(DB::raw("REPLACE( REPLACE( REPLACE( REPLACE( CGC_CPF, '.', '' ), '-', '' ), '/', '' ), '_', '' )"), $cpf_cnpj)->limit(1);
        try {
            $return_busca = $busca->get();
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => "Ocorreu uma instabilidade.<br>Tente novamente mais tarde!"
            ];
        }
        if(count($return_busca) === 0){
            return [
                "status" => "success"
            ];
        }else{
            if(strlen(trim($cpf_cnpj)) === 14){
                return [
                    "status" => "error",
                    "message" => "CPF já cadastrado no sistema!<br />Verifique os dados cadastrados!"
                ];
            }else{
                return [
                    "status" => "error",
                    "message" => "CNPJ já cadastrado no sistema!<br />Verifique os dados cadastrados!"
                ];
            }
        }

    }

    public function indexAprovacao(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ClienteNovoAprovacao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ClienteNovoAprovacao');
        return view('programs.cliente_novo.aprovacao.index');
    }

    public function confirmacaoAprovar(Request $request){
        $clienteNovoObj = null;

        $id = $request->input("id");
        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Código informado errado!"
            ], 422);
        }
        try{
            $clienteNovoObj = ClienteNovo::with(["socios", "referencias", "vendedor", "transportador"])->findOrFail($id);
        } catch (\Exception $e){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        if(is_null($clienteNovoObj)){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        $dados = $clienteNovoObj->toArray();
        $dados["id"] = Crypt::encrypt($dados["id"]);

        $conceitosObj = ConceitosCliente::all();
        $conceitos = [""=>""];
        foreach ($conceitosObj as $key => $conceito) {
            $conceitos[Crypt::encrypt($conceito->id)] =  $conceito->codigo." - ".$conceito->descricao;
        }

        return view('programs.cliente_novo.aprovacao.aprovar')->with(['dados' => $dados])->with(['conceitos' => $conceitos]);
    }

    public function confirmacaoReprovar(Request $request){
        $clienteNovoObj = null;
        $id = $request->input("id");
        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Código informado errado!"
            ], 422);
        }
        try{
            $clienteNovoObj = ClienteNovo::with(["socios", "referencias", "vendedor", "transportador"])->findOrFail($id);
        } catch (\Exception $e){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        if(is_null($clienteNovoObj)){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        $dados = $clienteNovoObj->toArray();

        return view('programs.cliente_novo.aprovacao.reprovar')->with(['dados' => $dados]);
    }

    public function aprovarCadastro(AprovacaoClienteNovoRequest $request){
        $filds = $request->only(["id", "limite_credito", "conceito"]);
        $clienteNovoObj = null;
        $id = $request->input("id");
        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Código informado errado!"
            ], 422);
        }
        try{
            $clienteNovoObj = ClienteNovo::findOrFail($id);
        } catch (\Exception $e){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        if(is_null($clienteNovoObj)){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        // $checkClienteExiste = $this->checkClienteExiste($clienteNovoObj->cpf_cnpj);
        // if($checkClienteExiste["status"] !== "success"){
        //     return response()->json($checkClienteExiste, 422);
        // }
        $limite_credito = $filds["limite_credito"];
        $limite_credito = str_replace("R$ ","", $limite_credito);
        $limite_credito = str_replace(".","", $limite_credito);
        $limite_credito = str_replace(",",".", $limite_credito);
        $limite_credito = floatval($limite_credito);

        $clienteNovoObj->status = 4;
        $clienteNovoObj->limite_credito = $limite_credito;
        $clienteNovoObj->conceitos_clientes_id = Crypt::decrypt($filds["conceito"]);
        $clienteNovoObj->updated_by = Auth::user()->id;

        $save = $this->saveDadosNasajon($clienteNovoObj);

        $raiz_cnpj = $clienteNovoObj->cpf_cnpj;
        if(strlen($raiz_cnpj) === 18){
            $raiz_cnpj = substr($raiz_cnpj, 0, 10);
        }
        $ClienteCreditoObj = ClienteCredito::where('raiz_cnpj', $raiz_cnpj)->first();
        if(is_null($ClienteCreditoObj)){
			$ClienteCreditoObj = new ClienteCredito;
			$ClienteCreditoObj->raiz_cnpj = $raiz_cnpj;
			$ClienteCreditoObj->data_atualizacao = date('Y-m-d');
			$ClienteCreditoObj->valor = $limite_credito;
			$ClienteCreditoObj->created_by = Auth::user()->id;
			$ClienteCreditoObj->save();
		}
        if($save !== true){
            return $save;
        }
        $clienteNovoObj->save();
        return response()->json(["status"=>"success","message"=>"Cliente cadastrado com sucesso!"]);
    }

    private function saveDadosNasajon(ClienteNovo $cliente_novo){
        $codigo = str_replace([".", ",", "/", "-"], "", $cliente_novo->cpf_cnpj);
        $codigo = substr($codigo, 0, -2);
        if(strlen(trim($cliente_novo->cpf_cnpj)) === 14){
            $codigo = str_pad($codigo, 14, "0", STR_PAD_LEFT);
        }else{
            $codigo = str_pad($codigo, 14, "0", STR_PAD_LEFT);
        }

        $nome = str_replace('\'', ' ', $cliente_novo->nome_razao);
        $nome_fantasia = str_replace('\'', ' ', $cliente_novo->guerra_apelido);
        $documento = $cliente_novo->cpf_cnpj;
        $inscricao_estadual = $cliente_novo->inscricao_estadual;
        $inscricao_estadual_indicador = $cliente_novo->inscricao_estadual_indicador;
        if($inscricao_estadual_indicador === 0){
            $inscricao_estadual_indicador = 2;
        }
        $telefone_dd = substr($cliente_novo->telefone, 1,2);
        $telefone_numero = substr(str_replace(" ","",$cliente_novo->telefone),4);
        $identidade = '';
        $email = $cliente_novo->email;
        $limte_de_credito = $cliente_novo->limite_credito;
        $numero = $cliente_novo->faturamento_numero;
        $complemento = $cliente_novo->faturamento_complemento;
        $cep = $cliente_novo->faturamento_cep;
        $bairro = $cliente_novo->faturamento_bairro;
        $uf = $cliente_novo->faturamento_estado;
        $cidade = $cliente_novo->faturamento_cidade;
        $referencia = '';
        $vendedor_uuid = null;
        if(isset($cliente_novo->vendedor)){
            $vendedor_uuid = $cliente_novo->vendedor->id;
        }

        $cep_busca =  str_replace('-', '', $cliente_novo->faturamento_cep);
        
        $endereco = CepEndereco::find($cep_busca);


        $tipo_logradouro = $endereco->tipo_logradouro;
        $logradouro = trim(str_replace($endereco->tipo_logradouro, '', str_replace("'", "", $cliente_novo->faturamento_logradouro)));
        $codigo_ibge = $endereco->cidadeBusca->cod_ibge;

 

        $numero_cobranca = $cliente_novo->cobranca_numero;
        $complemento_cobranca = $cliente_novo->cobranca_complemento;
        $cep_cobranca = $cliente_novo->cobranca_cep;
        $bairro_cobranca = $cliente_novo->cobranca_bairro;
        $uf_cobranca = $cliente_novo->cobranca_estado;
        $cidade_cobranca = $cliente_novo->cobranca_cidade;
        $referencia_cobranca = '';
 

        $cep_busca_cobranca = str_replace('-', '', $cliente_novo->cobranca_cep);
        $endereco_cobranca = CepEndereco::find($cep_busca_cobranca);

        $tipo_logradouro_cobranca = $endereco_cobranca->tipo_logradouro;
        $logradouro_cobranca = trim(str_replace($endereco_cobranca->tipo_logradouro, '', str_replace("'", "", $cliente_novo->cobranca_logradouro)));
        $codigo_ibge_cobranca = $endereco_cobranca->cidadeBusca->cod_ibge;


        /* Campo: a_qualificacao/$qualificacao.

        Lista:
        0 - Pessoa Jurídica em Geral;
        1 - Órgão, autarquia ou fundação de administração pública federal;
        2 - Órgão, autarquia ou fundação de administração pública estadual;
        3 - Órgão, autarquia ou fundação de administração pública municipal;
        4 - Cooperativa de Crédito;
        5 - Sociedade Cooperativa Agropecuária;
        6 - Sociedade Cooperativa;
        7 - Financeira;
        8 - Soc. Seguradora, de Capitalização;
        9 - Corretora Autônoma de Seguro;
        10 - Entidade Aberta de Prev. Complementar;
        11 - Entidade Fechada de Prev. Complementar;
        12 - Sociedade de Economia Mista;
        13 - Outras Entidades da Administração Pública Federal;
        90 - Pessoa Física em Geral;
        91 - Pessoa Agregada;
        99 - Outros. 
        */

        if($cliente_novo->fisica_juridica == 'f'){
            $qualificacao = 90;
        }
        else{
            $qualificacao = 0;
        }

        $sql_api_nasajon = [
            $codigo,
            $nome,
            $nome_fantasia,
            $documento,
            $inscricao_estadual,
            $identidade,
            $email,
            $limte_de_credito,
            $tipo_logradouro,
            $logradouro,
            $numero,
            $complemento,
            $cep,
            $bairro,
            $uf,
            $codigo_ibge,
            $cidade,
            $referencia,
            $telefone_dd,
            $telefone_numero,
            (empty($inscricao_estadual_indicador)?null:$inscricao_estadual_indicador),
            $qualificacao,
            $vendedor_uuid,
            $tipo_logradouro_cobranca,
            $logradouro_cobranca,
            $numero_cobranca,
            $complemento_cobranca,
            $cep_cobranca,
            $bairro_cobranca,
            $uf_cobranca,
            $codigo_ibge_cobranca,
            $cidade_cobranca,
            $referencia_cobranca
        ];
 
        try{
            $retorno_api = DB::connection("nasajon")->select(
                "select integracoes.api_clientenovo(
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?)", $sql_api_nasajon);
          
        }catch(\Illuminate\Database\QueryException $e){
            $error = $e->errorInfo[2];
            $error = strstr(trim(str_replace('ERROR:', '', $error)), "\n", TRUE);
            return response()->json(["message" => $error, 'status' => 'error', 'error' => vsprintf(str_replace(['?'], ['\'%s\''], $e->getSql()), $e->getBindings()), 
            'response' => []], 422);
        }

        return true;
    }

    private function saveDadosPrologos($cliente_novo){
        $codcad = str_replace([".", ",", "/", "-"], "", $cliente_novo->cpf_cnpj);
        $codcad = substr($codcad, 0, -1);
        if(strlen(trim($cliente_novo->cpf_cnpj)) === 14){
            $codcad = str_pad($codcad, 14, "0");
        }else{
            $codcad = str_pad($codcad, 14, "0", STR_PAD_LEFT);
        }
        $cliente = [
            "CODCAD" =>  utf8_decode($codcad),
            "NOME" => utf8_decode($cliente_novo->nome_razao),
            "GUERRA" => utf8_decode($cliente_novo->guerra_apelido) ?? "",
            "CEP" =>  utf8_decode($cliente_novo->faturamento_cep),
            "ENDERECO" => utf8_decode($cliente_novo->faturamento_logradouro.", ".$cliente_novo->faturamento_numero.(!empty($cliente_novo->faturamento_complemento) ? " ".$cliente_novo->faturamento_complemento : "")),
            "BAIRRO" =>  utf8_decode($cliente_novo->faturamento_bairro),
            "CIDADE" => utf8_decode($cliente_novo->faturamento_cidade),
            "ESTADO" =>  utf8_decode($cliente_novo->faturamento_estado),
            "CGC_CPF" =>  utf8_decode($cliente_novo->cpf_cnpj),
            "IEST" =>  utf8_decode($cliente_novo->inscricao_estadual ?? ""),
            "IMUN" =>  utf8_decode($cliente_novo->inscricao_municipal ?? ""),
            "TELEFONE" =>  utf8_decode($cliente_novo->telefone),
            "FAX" =>  !empty(trim($cliente_novo->telefone_fax)) ? utf8_decode($cliente_novo->telefone_fax) : " - ",
            "EMAIL" =>  $cliente_novo->email,
            "CODREGIAO" =>  "01", /// verificar
            "CODAREA" =>  " ",
            "CODZONA" =>  " ",
            "CONTATO" =>  " ",
            "DTDESDE" =>  date("Y-m-d 00:00:00"),
            "PENDENCIA" =>  "N",
            "ATIVIDADE" =>  "25",
            "DIVISAO" =>  "0",
            "TIPOPER" =>  " ",
            "REPASSE" =>  " ",
            "FATOR_CLIENTE" =>  "1",
            "CODVND" =>  utf8_decode($cliente_novo->vendedor_codigo),
            "COMISSAO" =>  "0",
            "CODVCT" =>  " ",
            "FORMA_PGTO" =>  "I",
            "SITUACAO_CR" =>  "?",
            "PORTADOR_CR" =>  "0000",
            "CODTRAN" =>  utf8_decode($cliente_novo->transportador_codigo),
            "TPEMISNF" =>  "1",
            "TPAGRUPA" =>  "N",
            "CONCEITO" =>  utf8_decode($cliente_novo->conceito->codigo),
            "LIMCRED" => (double) $cliente_novo->limite_credito,
            "INDJUROS" =>  "S",
            "ALERTA" =>  " ",
            "SUFRAMA" =>  utf8_decode($cliente_novo->suframa??''),
            "TARE" =>  " ",
            "CODCAD_ENTREGA" =>  " ",
            "CEP_COBRANCA" =>  utf8_decode($cliente_novo->cobranca_cep),
            "ENDERECO_COBRANCA" => utf8_decode($cliente_novo->cobranca_logradouro . ", " . $cliente_novo->cobranca_numero.(!empty($cliente_novo->cobranca_complemento) ? " ".$cliente_novo->cobranca_complemento : "")),
            "BAIRRO_COBRANCA" =>  utf8_decode($cliente_novo->cobranca_bairro),
            "CIDADE_COBRANCA" => utf8_decode($cliente_novo->cobranca_cidade),
            "ESTADO_COBRANCA" =>  utf8_decode($cliente_novo->cobranca_estado),
            "FONE_COBRANCA" =>  utf8_decode($cliente_novo->cobranca_telefone),
            "DTULTVND" =>  "",
            "VLULTVND" =>  "0.0",
            "DTMAIORVND" =>  "",
            "VLMAIORVND" =>  "0.0",
            "DTMAIORACUM" =>  "",
            "VLMAIORACUM" =>  "0.0",
            "DTULTVND_DONO" =>  "",
            "DTULTATR" =>  "",
            "DIASULTATR" =>  "0",
            "DTMAIORATR" =>  "",
            "DIASMAIORATR" =>  "0",
            "DIASATR_1" =>  "0",
            "DIASATR_2" =>  "0",
            "DIASATR_3" =>  "0",
            "DIASATR_4" =>  "0",
            "DIASATR_5" =>  "0",
            "SIGLAIED" =>  " ",
            "DTULTPRCREP" =>  "",
            "INDPRCBASE" =>  "N",
            "PRAZOVCT" =>  "0",
            "PARCELA_IPI" =>  "S",
            "DTULTLNF_E" =>  "",
            "ESTABEL" =>  "00",
            "DHALTCAD" =>  "",
            "FLAG_IAD" =>  " ",
            "COMISSAO_SERVICO" =>  "0",
            "IND_GNRE" =>  "N",
            "USO_CLIENTE_ALFA" =>  " ",
            "USO_CLIENTE_NUM" =>  "0",
            "DADOS1" =>  " ",
            "DADOS2" =>  " ",
            "DADOS3" =>  " ",
            "CELULAR" =>  " ",
            "EMAIL_ADIC1" =>  utf8_decode($cliente_novo->email),
            "EMAIL_ADIC2" =>  utf8_decode($cliente_novo->email),
            "DADOS4" =>  " ",
            "IND_PROTESTAR_SN" =>  "S",
            "IND_VALMINVCT_SN" =>  "S",
            "IND_TARIFABOL_SN" =>  "S"
        ];
        try{
            $salvar_bd00 = DB::connection("srv_prologos")->table("TBCAD1")->insert($cliente);
        }catch(\Exception $e){
            return response()->json(["message"=>"Ocorreu uma instabilidade! Por valor verificar com o departamento responsavel", $e], 422);
        }/*
        try{
            $salvar_bd01 = DB::connection("srv_almirante")->table("TBCAD1")->insert($cliente);
        }catch(\Exception $e){
            return response()->json(["message"=>"Ocorreu uma instabilidade! Por valor verificar com o departamento responsavel", $e], 422);
        }
        try{
            $salvar_bd02 = DB::connection("srv_botelho")->table("TBCAD1")->insert($cliente);
        }catch(\Exception $e){
            return response()->json(["message"=>"Ocorreu uma instabilidade! Por valor verificar com o departamento responsavel", $e], 422);
        }
        try{
            $salvar_bd03 = DB::connection("srv_rondonia")->table("TBCAD1")->insert($cliente);
        }catch(\Exception $e){
            return response()->json(["message"=>"Ocorreu uma instabilidade! Por valor verificar com o departamento responsavel", $e], 422);
        }
	    /*$salvar_bd04 = DB::connection("srv_tocantins")->table("TBCAD1")->insert($cliente);*
        try{
            $salvar_bd05 = DB::connection("srv_xavantes")->table("TBCAD1")->insert($cliente);
        }catch(\Exception $e){
            return response()->json(["message"=>"Ocorreu uma instabilidade! Por valor verificar com o departamento responsavel", $e], 422);
        }
        try{
            $salvar_bdArmazen = DB::connection("srv_armazen")->table("TBCAD1")->insert($cliente);
        }catch(\Exception $e){
            return response()->json(["message"=>"Ocorreu uma instabilidade! Por valor verificar com o departamento responsavel", $e], 422);
        }*/
        return true;
    }

    public function reprovarCadastro(ReprovacaoClienteNovoRequest $request){
        $filds = $request->only(["id", "motivo_repovacao"]);
        $clienteNovoObj = null;
        try{
            $clienteNovoObj = ClienteNovo::findOrFail($filds["id"]);
        } catch (\Exception $e){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        if(is_null($clienteNovoObj)){
            return response()->json(["message"=>"Cliente não encontrado!"], 422);
        }
        $clienteNovoObj->status = 3;
        $clienteNovoObj->motivo_recusa = $filds["motivo_repovacao"];
        $clienteNovoObj->updated_by = Auth::user()->id;
        try{
            $clienteNovoObj->save();
            return response()->json(["status" => "success", "message" => "Cliente Reprovado com sucesso"]);
        }catch(\Exception $e){
            return response()->json(["message"=>"Não foi possivel salvar Cliente"], 422);
        }
    }

    private function criacaoUsuarioCliente(ClienteNovo $clienteNovoObj){
        $user = new User;
        $user->username = preg_replace('/[_\-\/\.]/','', $clienteNovoObj->cpf_cnpj);
        $user->name = $clienteNovoObj->nome_razao;
        $user->email = $clienteNovoObj->email;
        $user->setor = 'Cliente';
    
        $tipoUsuarioObj = TipoUsuario::where('nome', 'Cliente')->first();
    
        $user->tipo_usuario_id = $tipoUsuarioObj->id;
    
        $alfanumericos = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alfanumericos = str_shuffle($alfanumericos);
        $senha = substr($alfanumericos, 0, 8);
    
        $user->password = Hash::make($senha);
    
        if($user->save()){
    
            $user->assignRole('Cliente');
    
            UsuarioClientePendente::where('cpf_cnpj', $user->username)
            ->whereNull('deleted_at')
            ->update([
                'status' => 'reprovado',
                'updated_by' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
    
            $this->enviarEmail($clienteNovoObj->nome_razao, $clienteNovoObj->email, $user->username, $senha);
        }
    }

    public function enviarEmail($nome_cliente, $email_cliente, $username, $senha){
        try{
            $EmailObj = new EmailController();

            $email_send [] = $email_cliente;

            $variaveis = [
                'nome_cliente' => $nome_cliente,
                'usuario' => $username,
                'senha' => $senha,
            ];
            
            $EmailObj->sendEmailToken('00', 'cliente_novo_portal', $email_send, $variaveis);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [ 'mensagem' => $e],
                'response' => []
            ], 422);
		}
    }
   
    public function reenvioEmail(){
        $tipoUsuarioObj = TipoUsuario::where('nome', 'Cliente')->first();
        
        $users = User::select()
            ->where('tipo_usuario_id', $tipoUsuarioObj->id)
            ->where('contrato', false)
            ->where('created_at', '>=', '2021-01-01 00:00:00')
            ->get();
        
        foreach($users as $user){
            $alfanumericos = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $alfanumericos = str_shuffle($alfanumericos);
            $senha = substr($alfanumericos, 0, 8);
        
            $user->password = Hash::make($senha);

            if($user->save()){
                $this->enviarEmail($user->name, $user->email, $user->username, $senha);
            }
        }
    }
}
