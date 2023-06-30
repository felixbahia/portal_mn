<?php

namespace App\Http\Controllers;

use App\CepEstado;
use App\EstadoGnre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\EstadoGnreEditarRequest;
use App\Http\Requests\EstadoGnreAdicionarRequest;

class EstadoGnreController extends Controller
{
    public $path = 'public/estado_gnre';   
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\EstadoGnre") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\EstadoGnre');
    
        return view('programs.estado_gnre.index');
    }
    public function filtro(Request $request){
        
        $campo  = $request->only('estado');

        $estadoGnreObj = EstadoGnre::with('criadoPor');

        if(!empty($campo['estado'])){
            $estadoGnreObj->where('estado', 'ilike', '%'.$campo['estado'].'%');
        }
      
        $estadoGnre = $estadoGnreObj->get();
  
        $saida = [];
     
        foreach($estadoGnre as $estado){
            $saida[] = [
                'id' => encrypt($estado->id),
                'estado' => $estado->estado,
                'usuario' => $estado->criadoPor->name,
                'data_emissao' => parserDataEHora($estado->created_at),
             ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $saida
        ];
        return response()->json($response, 200);
    }

    public function modalAdicionar() {

        $estadosObj = CepEstado::all()->toArray();
        $estados = array(""=>"Estado");
        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }

 
        return view('programs.estado_gnre.modal.adicionar')->with(['estados' => $estados]);
    }

    public function adicionar(EstadoGnreAdicionarRequest $request){
        $fields = $request->only(['estado','liminar']);
 
        $estadoGnreObj = new EstadoGnre();
        $estadoGnreObj->estado = $fields['estado'];
        $estadoGnreObj->created_by = Auth::id();
       
        if(!empty($fields['liminar'])){
        
            $arquivo =$fields['liminar'];
                    $name_arquivo = $estadoGnreObj->estado ."_liminar" ;
                   
                    $name_arquivo = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                    
                    $path_file =  $arquivo->storeAs($this->path."/liminar", $name_arquivo);

                    $estadoGnreObj->liminar = $path_file;
    
    
            }
        $estadoGnreObj->save();

   
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalEditar(Request $request){
   
        $campo = $request->only('id');
       
        try{
            $id = decrypt($campo['id']);
       
         
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $estadoGnreObj = EstadoGnre::find($id);
        if(is_null($estadoGnreObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }
        
        $estadosObj = CepEstado::all()->toArray();
        $estados = array(""=>"Estado");
        foreach ($estadosObj as $value) {
            $estados[$value['uf']] = $value['estado'];
        }


        $dados = [
            'id' => encrypt($estadoGnreObj->id),
            'estado' => $estadoGnreObj->estado,
            'liminar' =>  Storage::url($estadoGnreObj->liminar),

        ];

        return view('programs.estado_gnre.modal.editar')->with(['dados' => $dados,'estados' => $estados]);
    }




    public function editar(EstadoGnreEditarRequest $request){
        $fields = $request->only(['id','estado','liminar']);

        $arquivo = $request->file('liminar');
        
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }
      
        $estadoGnreObj = EstadoGnre::find($id);
        $estadoGnreObj->estado = ($fields['estado']);

        $estadoGnreObj->updated_by = Auth::id();
  
       
        if(!empty($fields['liminar'])){
      
               $name_arquivo = $estadoGnreObj->estado ."_liminar" ;
                $name_arquivo = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
            
                if(Storage::exists($this->path."/liminar/" .$name_arquivo)){
                 
                    Storage::delete($this->path."/liminar/".$name_arquivo);
                }

                $path_file = $arquivo->storeAs($this->path."/liminar", $name_arquivo);
              
               $estadoGnreObj->liminar = $path_file;
                           
          
        }
        $estadoGnreObj->save(); 
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalDeletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $estadoGnreObj = EstadoGnre::find($id);
        if(is_null($estadoGnreObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
           
            'id' => encrypt($estadoGnreObj->id),
            'estado' => $estadoGnreObj->estado,
            'liminar' =>  $estadoGnreObj->liminar

        ];
        return view('programs.estado_gnre.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $estadoGnreObj = EstadoGnre::find($id);
        $estadoGnreObj->deleted_by = Auth::id();
        $estadoGnreObj->save();
        $estadoGnreObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response, 200);
    }

}
