<?php

namespace App\Http\Controllers;

use Auth;

use App\CepEstado;
use App\CepEndereco;
use App\ClienteNovo;
use App\ClienteNasajon;
use App\ClienteDocumento;

use App\NetrinApiRetorno;
use Illuminate\Http\Request;
use App\TransportadorNasajon;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ClienteEdicaoRequest;

class ClienteEdicaoController extends Controller
{
    
    public $path = 'public/cliente_documento/';
    
    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\ClienteEdicao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ClienteEdicao');

        $estadosObj = CepEstado::select('uf', 'estado')->get();

        $estados = [];

        $estadosObj->each(function ($estado) use(&$estados){
            $estados[$estado->uf] = $estado->estado;
        });

        $indicador_inscricao_estadual = [
            '' => 'Inscrição Estadual Indicador',
            '1' => '1 - Contribuinte de ICMS.',
            '2' => '2 - Contribuinte Isento de Inscrição no Cadastro de Contribuintes de ICMS.',
            '9' => '9 - Não Contribuinte, que pode ou não possuir inscrição estadual.'
        ];

        return view('programs.cliente_edicao.index')->with(['indicador_inscricao_estadual' => $indicador_inscricao_estadual, 'estados' => $estados]);
    }

    public function infoCliente(Request $request){

        $fields = $request->only('cliente_nome_cpf_cnpj', 'id');

        if(isset($fields['id']) && !is_null($fields['id'])){
            $clienteObj = ClienteNasajon::find($request->id);
        }
        else if(isset($fields['cliente_nome_cpf_cnpj']) && !is_null($fields['cliente_nome_cpf_cnpj'])){
            $clienteObj = ClienteNasajon::where(DB::Raw("CONCAT(TRIM(nome), ' - ', cpf_cnpj)"), 'ilike', $fields['cliente_nome_cpf_cnpj'])->first();
        }
        else{
            $clienteObj = null;
        }

        $result = [];

        if(!is_null($clienteObj)){

            preg_match('/(\(.*?\))(\S+)/', $clienteObj->telefones, $telefone);
            if(isset($telefone[1])){
                $ddd = preg_replace('/[\(\)]/', '', $telefone[1]);
            }
            else{
                $ddd = '';
            }

            if(isset($telefone[2])){
                $telefone = explode(',', $telefone[2])[0];
            }
            else{
                $telefone = '';
            }

            if (strlen($clienteObj->cep) == 9){
                $cep = $clienteObj->cep;
            }
            else{
                $cep = mask(str_pad($clienteObj->cep, "0", STR_PAD_LEFT), "#####-###");
            }

            $documentos = [];
            $cliente_portal = ClienteDocumento::where("cpf_cnpj", 'ilike', $clienteObj->cpf_cnpj)->get();

            if(!empty($cliente_portal)){
                foreach($cliente_portal as $documento){
                    $documentos[] = [
                        'descricao' => $documento->descricao_documento,
                        'documento' => Storage::url($documento->documento),
                        'id_documento' => encrypt($documento->id),
                    ];
                }
            }

            $result = [
                'razao_social' => $clienteObj->nome,
                'inscricaoestadual' => $clienteObj->inscricaoestadual,
                'telefone' => $telefone,
                'email' => $clienteObj->email,
                'tipo_logradouro' => $clienteObj->tipologradouro,
                'logradouro' => $clienteObj->logradouro,
                'numero' => $clienteObj->numero,
                'complemento' => $clienteObj->complemento,
                'bairro' => $clienteObj->bairro,
                'cidade' => $clienteObj->cidade,
                'cep' => $cep,
                'ddd' => $ddd,
                'indicador_inscricao_estadual' => $clienteObj->indicadorinscricaoestadual,
                'uf' => $clienteObj->uf,
                'documentos' => (!empty($documentos)) ? $documentos : null,
                'cpf_cnpj' => encrypt($clienteObj->cpf_cnpj),
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dados recuperados com sucesso!',
            'error' => [],
            'response' => $result
        ], 200);

    }

    public function salvarEdicao(ClienteEdicaoRequest $request){
        $fields = $request->only('id', 'razao_social', 'inscricaoestadual', 'ddd', 'telefone', 'email', 'tipo_logradouro', 'logradouro', 'numero', 'complemento', 'bairro', 'cep', 'cidade', 'uf', 'indicador_inscricao_estadual','documento','descricao_documento');
        $clienteObj = ClienteNasajon::find($fields['id']);

        $enderecoObj = CepEndereco::with('cidadeBusca')->where('cep', str_replace('-', '', $fields['cep']))->first();

        if($fields['indicador_inscricao_estadual'] == 2){
            $fields["inscricaoestadual"] = '';
        }

        $fields["razao_social"] = str_replace("'", "", $fields["razao_social"]);
        $fields["logradouro"] = str_replace("'", "", $fields["logradouro"]);
        $cidade = $enderecoObj->cidadeBusca->cidade;
        $cidade = str_replace("'", "", $cidade);
        $bairro = $fields["bairro"];
        $bairro = str_replace("'", "", $bairro);
        try {
            $result = DB::connection('nasajon')->select("SELECT * from integracoes.api_clientealterar(
                '". $fields["id"] ."',
                '". $fields["razao_social"] ."',
                '". $clienteObj->nomefantasia ."',
                '". $clienteObj->cpf_cnpj ."',
                '". $fields["inscricaoestadual"] ."',
                '',
                '". $fields["email"] ."',
                0,
                '". $fields["tipo_logradouro"] ."',
                '". $fields["logradouro"] ."',
                '". $fields["numero"] ."',
                '". $fields["complemento"] ."',
                '". $fields["cep"] ."',
                '". $bairro ."',
                '". $fields["uf"] ."',
                '". $enderecoObj->cidadeBusca->cod_ibge ."',
                '". $cidade ."',
                '',
                '". $fields["ddd"] ."',
                '". $fields["telefone"] ."',
                '". $fields['indicador_inscricao_estadual'] ."'
            )");

            $documentos = $request->file('documento');

            if($request->hasFile('documento'))
            {
                foreach($documentos as $key => $documento){
                    $cliente_documento_objeto = new ClienteDocumento();
                    $cliente_documento_objeto->cpf_cnpj = $clienteObj->cpf_cnpj;
                    $cliente_documento_objeto->descricao_documento = $fields['descricao_documento'][$key];
                    $cliente_documento_objeto->created_by = Auth::user()->id;
                    $cliente_documento_objeto->save();
                    $file = $cliente_documento_objeto->id.'.' .$documento->getClientOriginalExtension();
                    $cliente_documento_objeto->documento = $this->path.$file;
                    $documento->storeAs($this->path, $file);
                    $cliente_documento_objeto->save();
                }
            }

        } catch (\Illuminate\Database\QueryException $th) {

            return response()->json([
                'status' => 'success',
                'message' => 'Erro na transação!',
                'error' => [ 
                    "mensagem" => $th->getMessage()
                ],
                'response' => []
            ], 422);
        }

        $result = json_decode($result[0]->mensagem);
        
        $mensagem = $result->mensagem;

        if($result->codigo == 'OK'){
    
            return response()->json([
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!',
                'error' => [],
                'response' => [
                    'id' => $mensagem
                ]
            ], 200);
        }
        else{
            return response()->json([
                'status' => 'erro',
                'message' => 'Ocorreu um erro!',
                'error' => [
                    "mensagem" => $mensagem
                ],
                'response' => []
            ], 422);
        }
    }

    public function excluirDocumento(Request $request){
        $filter = $request->only(['id']);

        try{
            $fields = decrypt($filter['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        try{
            $documento = ClienteDocumento::findOrFail($fields);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'erro',
                'message' => 'Ocorreu um erro!',
                'error' => $e->getMessage(),
                'response' => []
            ]);
        }

        Storage::delete($documento->documento);
        $documento->deleted_by = Auth::user()->id;
        $documento->save();
        $documento->delete();

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => []
        ]);
    }

	public function atualizaTefoneCliente(ClienteNasajon $Cliente, $telefone_novo){
		$ddd = '';
		$telefone = '';
		if(!empty($Cliente->telefones)){
			$telefone_temp = str_replace([' ', '-'], '',$telefone_novo);
			$telefones_cliente = explode(',', str_replace([' ', '-'], '',$Cliente->telefones));
			if(in_array($telefone_temp, $telefones_cliente)){
				return [
					'status' => 'success',
					'message' => 'telefone já cadastrado',
					'error' => [],
					'response' => []
				];
			}
		}
		$telefone_novo = str_replace(['(', ')'], '', $telefone_novo);
		$telefone_novo = explode(' ', $telefone_novo);
		$ddd = empty($telefone_novo[0])? '' : $telefone_novo[0];
		$telefone = empty($telefone_novo[1])? '' : $telefone_novo[1];

        if(empty($ddd) || empty($telefone)){
            return [
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => []
            ];
        }

        try {
            $result = DB::connection('nasajon')->select("SELECT * from integracoes.api_clientealterar(
                '". $Cliente->id ."',
                '". $Cliente->nome ."',
                '". $Cliente->nomefantasia ."',
                '". $Cliente->cpf_cnpj ."',
                '". $Cliente->inscricaoestadual ."',
                '',
                '". $Cliente->email ."',
                0,
                '". $Cliente->tipologradouro ."',
                '". $Cliente->logradouro ."',
                '". $Cliente->numero ."',
                '". $Cliente->complemento ."',
                '". $Cliente->cep ."',
                '". $Cliente->bairro ."',
                '". $Cliente->uf ."',
                '". $Cliente->ibge ."',
                '". $Cliente->cidade ."',
                '',
                '". $ddd ."',
                '". $telefone ."',
                '". $Cliente->indicadorinscricaoestadual ."'
            )");
        } catch (\Illuminate\Database\QueryException $th) {
            return [
                'status' => 'erro',
                'message' => 'Erro na transação!',
                'error' => [],
                'response' => []
            ];
        }

        $result = json_decode($result[0]->mensagem);
        
        $mensagem = $result->mensagem;

        if($result->codigo == 'OK'){
            return [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!',
                'error' => [],
                'response' => []
            ];
        }
        else{
            return [
                'status' => 'erro',
                'message' => $mensagem,
                'error' => [],
                'response' => []
            ];
        }
	}



}
