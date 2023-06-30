<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\EntradaDeCaixa;
use Auth;
use Carbon\Carbon;

use App\Http\Requests\EntradaDeCaixaSalvar;

class EntradaDeCaixaController extends Controller
{
    private $estabelecimentos = [];

    public function __construct(){
        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimentos[''] = 'ESTABELECIMENTO';
        // $estabelecimentos[20] = 'ARMAZÉM';
        ksort($estabelecimentos);
        $this->estabelecimentos = $estabelecimentos;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\EntradaDeCaixa") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\EntradaDeCaixa');
        $estabelecimentos = $this->estabelecimentos;
        return view('programs.entrada_de_caixa.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filter(Request $request){
        $fields = $request->only(['estabelecimento']);
        $EntradaDeCaixaObj = EntradaDeCaixa::select('*');
        if(isset($fields['estabelecimento'])){
            $EntradaDeCaixaObj->where('estabelecimento', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        $EntradaDeCaixaObj = $EntradaDeCaixaObj->get();
        $response = [];
        $estabelecimentos = $this->estabelecimentos;
        foreach ($EntradaDeCaixaObj as $key => $entrada) {
            $data = Carbon::createFromFormat('Y-m-d', $entrada->data)->setTime(0, 0, 0);
            $response[] = [
                'estabelecimento' => $estabelecimentos[intval($entrada->estabelecimento)],
                'data' => $data->format('d/m/Y'),
                'valor' => parserValor($entrada->valor_inicial),
                'id' => encrypt($entrada->id)
            ];
        }
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $response
        ];
        return response()->json($return);
    }

    public function modalAdicionar(Request $request){
        $estabelecimentos = $this->estabelecimentos;
    	return view("programs.entrada_de_caixa.modal.adicionar")->with(['estabelecimentos' => $estabelecimentos]);
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
            $EntradaDeCaixaObj = EntradaDeCaixa::find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $dados = $EntradaDeCaixaObj->toArray();
        $estabelecimentos = $this->estabelecimentos;
        $dados['valor_inicial'] = \parserValor($dados['valor_inicial']);
        $data = Carbon::createFromFormat('Y-m-d', $dados['data'])->setTime(0, 0, 0);
        $dados['data'] = $data->format('d/m/Y');

    	return view("programs.entrada_de_caixa.modal.editar")->with(['estabelecimentos' => $estabelecimentos, 'dados' => $dados]);
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
            $EntradaDeCaixaObj = EntradaDeCaixa::find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $dados = $EntradaDeCaixaObj->toArray();
        $dados['valor_inicial'] = \parserValor($dados['valor_inicial']);
        $data = Carbon::createFromFormat('Y-m-d', $dados['data'])->setTime(0, 0, 0);
        $dados['data'] = $data->format('d/m/Y');
    	return view("programs.entrada_de_caixa.modal.deletar")->with(['dados' => $dados]);
    }
    

    public function adicionar(EntradaDeCaixaSalvar $request){
        $fields = $request->only(['estabelecimento', 'data', 'valor']);
        
        $data = Carbon::createFromFormat('d/m/Y', $fields['data'])->setTime(0, 0, 0);
        $valor = (float) str_replace('.', '.', str_replace('.', '', $fields['valor']));

        $EntradaDeCaixaObj = new EntradaDeCaixa;
        $EntradaDeCaixaObj->estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        $EntradaDeCaixaObj->data = $data;
        $EntradaDeCaixaObj->valor_inicial = $valor;
        $EntradaDeCaixaObj->created_by = Auth::id();
        $EntradaDeCaixaObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(EntradaDeCaixaSalvar $request){
        $fields = $request->only(['id', 'estabelecimento', 'data', 'valor']);
        
        $data = Carbon::createFromFormat('d/m/Y', $fields['data'])->setTime(0, 0, 0);
        $valor = (float) str_replace('.', '.', str_replace('.', '', $fields['valor']));

        $EntradaDeCaixaObj = EntradaDeCaixa::find($fields['id']);
        $EntradaDeCaixaObj->estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        $EntradaDeCaixaObj->data = $data;
        $EntradaDeCaixaObj->valor_inicial = $valor;
        $EntradaDeCaixaObj->updated_by = Auth::id();
        $EntradaDeCaixaObj->save();

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
        
        $EntradaDeCaixaObj = EntradaDeCaixa::find($fields['id']);
        $EntradaDeCaixaObj->deleted_by = Auth::id();
        $EntradaDeCaixaObj->save();
        $EntradaDeCaixaObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

}
