<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CepEstado;
use App\EstabelecimentoCidadeFob;

use Auth;
use App\Http\Requests\EstabelecimentoCidadeFobSalvar;


class EstabelecimentoCidadeFobController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\EstabelcimentoCidadeFob") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\EstabelcimentoCidadeFob');
    	return view("programs.estabelecimento_cidade_fob.index");
    }

    public function filter(Request $request){
        $fields = $request->only(['estabelecimento']);
        $EstabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::select('*');
        if(isset($fields['estabelecimento'])){
            $EstabelecimentoCidadeFobObj->where('estabelecimento', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        $EstabelecimentoCidadeFobObj = $EstabelecimentoCidadeFobObj->get();
        $response = [];
        $estabelecimentos = returnEmpresasNasajonView();
        foreach ($EstabelecimentoCidadeFobObj as $key => $cidade) {
            $response[] = [
                'estabelecimento' => $estabelecimentos[intval($cidade->estabelecimento)],
                'cidade' => $cidade->cidade,
                'uf' => $cidade->uf,
                'id' => encrypt($cidade->id)
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
        $CepEstadoObj = CepEstado::orderBy('estado')->get()->toArray();
        $uf = [''=>''];
        foreach ($CepEstadoObj as $key => $value) {
            $uf[$value['uf']] = $value['estado'];
        }
        $estabelecimentos = ['estabelecimentos' => 'Selecione'];
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
    	return view("programs.estabelecimento_cidade_fob.modal.adicionar")->with(['uf' => $uf, 'estabelecimentos' => $estabelecimentos]);
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
            $EstabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $dados = $EstabelecimentoCidadeFobObj->toArray();
        $CepEstadoObj = CepEstado::orderBy('estado')->get()->toArray();
        $uf = [''=>''];
        foreach ($CepEstadoObj as $key => $value) {
            $uf[$value['uf']] = $value['estado'];
        }
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);

        $cidades = [];

		$CepEstado = CepEstado::where('uf', $dados['uf'])->with(["cidades"])->first();
		if(is_null($CepEstado)){
			return response()->json(["status" => "error", "message" => "Estado não encontrado!", "dados" => []]);
		}
		$CepEstado = $CepEstado->toArray();
		foreach ($CepEstado["cidades"] as $key => $value) {
			$cidades[$value["cidade"]] = $value["cidade"];
		}
    	return view("programs.estabelecimento_cidade_fob.modal.editar")->with(['uf' => $uf, 'cidades' => $cidades, 'estabelecimentos' => $estabelecimentos, 'dados' => $dados]);
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
            $EstabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::find($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $dados = $EstabelecimentoCidadeFobObj->toArray();
    	return view("programs.estabelecimento_cidade_fob.modal.deletar")->with(['dados' => $dados]);
    }
    

    public function adicionar(EstabelecimentoCidadeFobSalvar $request){
        $fields = $request->only(['estabelecimento', 'uf', 'cidade']);
        
        $EstabelecimentoCidadeFobObj = new EstabelecimentoCidadeFob;
        $EstabelecimentoCidadeFobObj->estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        $EstabelecimentoCidadeFobObj->uf = $fields['uf'];
        $EstabelecimentoCidadeFobObj->cidade = $fields['cidade'];
        $EstabelecimentoCidadeFobObj->created_by = Auth::id();
        $EstabelecimentoCidadeFobObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(EstabelecimentoCidadeFobSalvar $request){
        $fields = $request->only(['id','estabelecimento', 'uf', 'cidade']);
        
        $EstabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::find($fields['id']);
        $EstabelecimentoCidadeFobObj->estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
        $EstabelecimentoCidadeFobObj->uf = $fields['uf'];
        $EstabelecimentoCidadeFobObj->cidade = $fields['cidade'];
        $EstabelecimentoCidadeFobObj->updated_by = Auth::id();
        $EstabelecimentoCidadeFobObj->save();

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
        
        $EstabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::find($fields['id']);
        $EstabelecimentoCidadeFobObj->deleted_by = Auth::id();
        $EstabelecimentoCidadeFobObj->save();
        $EstabelecimentoCidadeFobObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }


}
