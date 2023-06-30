<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ClienteCredito;
use App\ClienteNasajon;
use App\GrupoEmpresarial;

use Auth;

use Carbon\Carbon;

use App\Http\Requests\ClienteCreditoSalvar;
use Illuminate\Support\Facades\DB;

class ClienteCreditoController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ClienteCredito") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ClienteCredito');
        return view('programs.cliente_limite_credito.index');
    }

    public function filter(Request $request){
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '2024M');
        $fields = $request->only(['cliente_nome', 'start', 'length', 'draw', 'columns', 'order']);

        $order = $fields["order"];
        $column = $fields["columns"];
        $order_name = $column[intval($order[0]['column'])]["data"];
        $order_dir = $order[0]["dir"];
        switch ($order_name){
            case 'raiz_cnpj':
                $order_name = 'raiz_cnpj';
                break;
            case 'valor_limite':
                $order_name = 'valor';
                break;
            case 'data_credito':
                $order_name = 'data_atualizacao';
                break;
        }
        $offset = intval($fields["start"]);
        $limit = intval($fields["length"]);


        $ClienteCreditoObj = ClienteCredito::with('createdby', 'updatedby')->select('*')
            ->orderBy($order_name, $order_dir);
            
        $cliente_nome = trim($fields['cliente_nome']);
        $raiz_cnpj = [];
        if(!empty($cliente_nome)){
            $clientes = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ILIKE', trim($cliente_nome))->get();

            foreach($clientes as $cliente){
                if(strlen($cliente->cpf_cnpj) === 18){
                    $raiz_cnpj[] = substr($cliente->cpf_cnpj, 0, 10);
                    $ClienteCreditoObj->whereIn('raiz_cnpj', $raiz_cnpj );
                }else{
                    $raiz_cnpj[] = $cliente->cpf_cnpj;
                    $ClienteCreditoObj->whereIn('raiz_cnpj', $raiz_cnpj);
                }
            }
        }
            
        $total_registros = $ClienteCreditoObj->count('*');

        $ClienteCreditoObj = $ClienteCreditoObj
            ->offset($offset)
            ->limit($limit)
            ->get();
        $response = [];
        $estabelecimentos = returnEmpresasNasajonView();
      
        foreach ($ClienteCreditoObj as $key => $credito) {
            $data = new Carbon($credito->data_atualizacao);

            $cliente = ClienteNasajon::whereRaw("cpf_cnpj ILIKE '" . $credito->raiz_cnpj . "%'")->first();
          
            $usuario_cadastrou_alterou='';
            if(!empty($credito->createdby)){
              $usuario_cadastrou_alterou=$credito->createdby->name;
            }
            if(!empty($credito->updatedby)){
              $usuario_cadastrou_alterou=$credito->updatedby->name;
            }

            $response[$key] = [
                'raiz_cnpj' => $credito->raiz_cnpj . ' - '. ($cliente ? $cliente->nome : ''),
                'valor_limite' => \parserValor($credito->valor),
                'data_credito' => $data->format('d/m/Y'),
                'usuario_cadastrou_alterou' => $usuario_cadastrou_alterou,
                'id' => encrypt($credito->id)
            ];
        }
        $return = [
            "draw" => $fields["draw"],
            "recordsTotal" => $total_registros,
            "recordsFiltered" => $total_registros,
            "data" => $response
        ];
        return response()->json($return);
    }


    public function modalAdicionar(Request $request){
    	return view("programs.cliente_limite_credito.modal.adicionar");
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
            $ClienteCreditoObj = ClienteCredito::find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $dados = $ClienteCreditoObj->toArray();
        $dados['valor'] = \parserValor($dados['valor']);

        if(!empty($dados['ultima_consulta_serasa'])){
            $dados['ultima_consulta_serasa'] = Carbon::parse($dados['ultima_consulta_serasa'])->format('%d/%m/%Y');
        }

        $data = Carbon::parse($dados['data_atualizacao']);
        $dados['data_atualizacao'] = $data->format('d/m/Y');
        $dados['cliente'] = '';
        $cliente = ClienteNasajon::whereRaw('cpf_cnpj ILIKE \''.$dados['raiz_cnpj'].'%\'')->first();
        $dados['cliente'] = utf8_decode($cliente->nome) . ' - ' .$cliente->cpf_cnpj;

    	return view("programs.cliente_limite_credito.modal.editar")->with(['dados' => $dados]);
    }

    public function modalAdicionarAprovacao(Request $request){
        $fields = $request->only(['cpf_cnpj']);

        $cpf_cnpj = $fields['cpf_cnpj'];

        if (strlen($cpf_cnpj) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $ClienteCreditoObj = ClienteCredito::where('raiz_cnpj', $cpf_cnpj)->first();
        if(!is_null($ClienteCreditoObj)){
            $dados = $ClienteCreditoObj->toArray();
            $dados['valor'] = \parserValor($dados['valor']);
            $data = new Carbon($dados['data_atualizacao']);
            $dados['data_atualizacao'] = $data->format('d/m/Y');
            $dados['cliente'] = '';
            $cliente = ClienteNasajon::whereRaw('cpf_cnpj ILIKE \''.$dados['raiz_cnpj'].'%\'')->first();
            $dados['cliente'] = utf8_decode($cliente->nome) . ' - ' .$cliente->cpf_cnpj;

            if(isset($dados['ultima_consulta_serasa']) && !empty($dados['ultima_consulta_serasa'])){
                $dados['ultima_consulta_serasa'] = Carbon::createFromFormat('Y-m-d', $dados['ultima_consulta_serasa'])->format('%d/%m/%Y');
            }
            else{
                $dados['ultima_consulta_serasa'] = '';
            }

            return view("programs.cliente_limite_credito.modal.editar")->with(['dados' => $dados]);
        }
        else{
            return view("programs.cliente_limite_credito.modal.adicionar_aprovacao")->with(['raiz_cnpj' => $cpf_cnpj]);
        }
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
            $ClienteCreditoObj = ClienteCredito::find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $dados = $ClienteCreditoObj->toArray();
        $dados['valor'] = \parserValor($dados['valor']);
        $data = new Carbon($dados['data_atualizacao']);
        $dados['data_atualizacao'] = $data->format('d/m/Y');
    	return view("programs.cliente_limite_credito.modal.deletar")->with(['dados' => $dados]);
    }
    

    public function adicionar(ClienteCreditoSalvar $request){
        $fields = $request->only(['raiz_cnpj', 'valor', 'ultima_consulta_serasa', 'motivo_reavaliacao']);
        
        $valor = (float) str_replace('.', '.', str_replace('.', '', $fields['valor']));

        $ClienteCreditoObj = new ClienteCredito;
        $ClienteCreditoObj->raiz_cnpj = $fields['raiz_cnpj'];
        $ClienteCreditoObj->data_atualizacao = date('Y-m-d');
        $ClienteCreditoObj->valor = $valor;
        
        if(!empty($fields['ultima_consulta_serasa'])){
            $ClienteCreditoObj->ultima_consulta_serasa = Carbon::createFromFormat('d/m/Y', $fields['ultima_consulta_serasa'])->toDateTimeString();
        }
        
        $ClienteCreditoObj->motivo_reavaliacao = $fields['motivo_reavaliacao'];
        $ClienteCreditoObj->created_by = Auth::id();
        $ClienteCreditoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(ClienteCreditoSalvar $request){
        $fields = $request->only(['id', 'raiz_cnpj', 'valor', 'ultima_consulta_serasa', 'motivo_reavaliacao']);
        
        $valor = (float) str_replace('.', '.', str_replace('.', '', $fields['valor']));

        
        $ClienteCreditoObj = ClienteCredito::find($fields['id']);
        $ClienteCreditoObj->raiz_cnpj = $fields['raiz_cnpj'];
        $ClienteCreditoObj->data_atualizacao = date('Y-m-d');
        $ClienteCreditoObj->valor = $valor;
        
        if(!empty($fields['ultima_consulta_serasa'])){
            $ClienteCreditoObj->ultima_consulta_serasa = Carbon::createFromFormat('d/m/Y', $fields['ultima_consulta_serasa'])->toDateTimeString();
        }
        else{
            $ClienteCreditoObj->ultima_consulta_serasa = null;            
        }

        $ClienteCreditoObj->motivo_reavaliacao = $fields['motivo_reavaliacao'];
        $ClienteCreditoObj->updated_by = Auth::id();
        $ClienteCreditoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
    
    public function excluir(Request $request){
        $fields = $request->only(['id']);
        
        $ClienteCreditoObj = ClienteCredito::find($fields['id']);
        $ClienteCreditoObj->deleted_by = Auth::id();
        $ClienteCreditoObj->save();
        $ClienteCreditoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
