<?php

namespace App\Http\Controllers;

use App\User;
use Exception;

use Carbon\Carbon;
use App\SugestaoCompra;
use App\SugestaoCompraFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\SugestaoCompraAprovaRequest;
use App\Http\Requests\SugestaoCompraEditarRequest;
use App\Http\Requests\SugestaoCompraMotivoRequest;
use App\Http\Requests\SugestaoCompraAdicionarRequest;

class SugestaoCompraController extends Controller
{  
    public $path = 'public/sugestao_compra/';   
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\SugestaoCompra") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\SugestaoCompra');
        $status_sugestao =$this->situacao();
     
        return view('programs.sugestao_compra.index')->with(['status_sugestao' => $status_sugestao]);
    }

    public function filtro(Request $request){
        
        $campo  = $request->only('descricao','data_inicio','data_fim', 'status','cliente','gerentes');

        $sugestaoCompraObj = SugestaoCompra::with('criadoPor');

        if(strtolower(Auth::user()->tipo_usuario->nome) === "vendedor interno" || strtolower(Auth::user()->tipo_usuario->nome) === "representante"){    
            $sugestaoCompraObj->where('created_by', '=',Auth::id());
        }

        if(!empty($campo['descricao'])){
            $sugestaoCompraObj->where('produto_descricao', 'ilike', '%'.$campo['descricao'].'%');
        }
        if(!empty($campo['cliente'])){
            $sugestaoCompraObj->where('cliente_nome', 'ilike', '%'.$campo['cliente'].'%');
        }
        if(!empty($campo['data_inicio']) && !empty($campo['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $campo['data_inicio'])->setTime(0,0,0);
            $data_fim = Carbon::createFromFormat('d/m/Y', $campo['data_fim'])->setTime(23,59,59);
            $sugestaoCompraObj->whereBetween('created_at', [$data_inicio, $data_fim]);
        }else if(!empty($campos['data_inicio'])){
             $data_inicio = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
             $sugestaoCompraObj->where('created_at', '>=', $data_inicio);
        }else if(!empty($campos['data_fim'])){
              $data_fim = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
              $sugestaoCompraObj->where('created_at', '<=', $data_fim);
        }
        
        if(!empty($campo['status'])){
               $sugestaoCompraObj->where('status',$campo['status']);
        }
                
        if(!empty($campo['gerentes'])){



                try{
                    $gerente = decrypt($campo['gerentes']);
                }catch(Exception $e){
                    $return = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                        'error' => '', 
                        'response' => '',
                    ];
                    return response()->json($return);
                }


                    $subordinados = UserController::varreSubordinados($gerente);
                    $users = User::whereIn('id', $subordinados)->get()->pluck('id')->filter()->toArray();

                    $sugestaoCompraObj->whereIn('created_by',$users);
       }
        $sugestaoCompraObj->orderBy('id','DESC');
        $status_sugestao =$this->situacao();

        $sugestaoCompras = $sugestaoCompraObj->get();
        $saida = [];
     
        foreach($sugestaoCompras as $sugestaoCompra){
            $saida[] = [
                'id' => encrypt($sugestaoCompra->id),
                'produto_descricao' => $sugestaoCompra->produto_descricao,
                'volume_produto' => $sugestaoCompra->volume_produto,
                'valor_estimado_venda' => parserValor($sugestaoCompra->valor_estimado_venda),
                'cliente_nome' => $sugestaoCompra->cliente_nome,
                'usuario' => $sugestaoCompra->criadoPor->name,
                'status' => $status_sugestao[$sugestaoCompra->status],
                'motivo' => $sugestaoCompra->motivo,
                'data_emissao' => parserDataEHora($sugestaoCompra->created_at),
                'composicao' => $sugestaoCompra->composicao,

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
 
        return view('programs.sugestao_compra.modal.adicionar');
    }

    public function adicionar(SugestaoCompraAdicionarRequest $request){
        $fields = $request->only(['produto_codigo','produto_descricao','cliente_nome','volume_produto','valor_estimado_venda','cliente_codigo','arquivo_foto_sugestao','composicao']);
 
        $sugestaoCompraObj = new SugestaoCompra;
        $sugestaoCompraObj->composicao = strtoupper($fields['composicao']);
        $sugestaoCompraObj->produto_codigo = strtoupper($fields['produto_codigo']);
        $sugestaoCompraObj->produto_descricao = strtoupper($fields['produto_descricao']);
        $sugestaoCompraObj->cliente_nome = strtoupper($fields['cliente_nome']);
        $sugestaoCompraObj->volume_produto = empty($fields['volume_produto'])? 0 : parserNumber($fields['volume_produto']);   
        $sugestaoCompraObj->valor_estimado_venda =empty($fields['valor_estimado_venda'])? 0 : parserNumber($fields['valor_estimado_venda']);   
        $sugestaoCompraObj->cliente_codigo = ($fields['cliente_codigo']);
        $sugestaoCompraObj->status = 1;
        $sugestaoCompraObj->created_by = Auth::id();
        $sugestaoCompraObj->save();

   
        

       if(!empty($fields['arquivo_foto_sugestao'])){
        
            $indice =  SugestaoCompraFoto::where('sugestao_compra_id', $sugestaoCompraObj->id)->count();
            foreach($fields['arquivo_foto_sugestao'] as $key => $arquivo){
                $name_arquivo = $sugestaoCompraObj->id."_foto_sugestao_".$indice;
               
                $name_arquivo_foto = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                
                $path_file =  $arquivo->storeAs($this->path.$sugestaoCompraObj->id."/foto", $name_arquivo_foto);

                $sugestaoCompraFoto = new SugestaoCompraFoto;
                $sugestaoCompraFoto->sugestao_compra_id = $sugestaoCompraObj->id;
                $sugestaoCompraFoto->nome_arquivo = $name_arquivo_foto;
                $sugestaoCompraFoto->caminho = $path_file;
                $sugestaoCompraFoto->created_by = Auth::id();
                $sugestaoCompraFoto->save();

                

                $indice++;
            }
        }


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

        $sugestaoCompraObj = SugestaoCompra::find($id);
        if(is_null($sugestaoCompraObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }
        


        $dados = [
            'id' => encrypt($sugestaoCompraObj->id),
            'produto_codigo' => $sugestaoCompraObj->produto_codigo,
            'produto_descricao' =>  $sugestaoCompraObj->produto_descricao,
            'cliente_nome' => $sugestaoCompraObj->cliente_nome,
            'volume_produto' => $sugestaoCompraObj->volume_produto,
            'valor_estimado_venda' => $sugestaoCompraObj->valor_estimado_venda,
            'cliente_codigo' =>  $sugestaoCompraObj->cliente_codigo,
            'composicao' => $sugestaoCompraObj->composicao,
        ];

        return view('programs.sugestao_compra.modal.editar')->with(['dados' => $dados]);
    }


    public function modalFoto(Request $request){
   
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
      $fotos =[];// SugestaoCompraFoto::where('sugestao_compra_id',$id)->get()->pluck('caminho');

        $sugestaoCompraFotos =  SugestaoCompraFoto::where('sugestao_compra_id',$id)->get();

       foreach($sugestaoCompraFotos as $sugestaoCompraFoto){

               $fotos[] = [
                    'foto' =>  Storage::url($sugestaoCompraFoto->caminho),
                ];

        }
      
      
        if(is_null($fotos)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

         return view('programs.sugestao_compra.modal.foto')->with(['fotos' => $fotos]);
    }

    public function editar(SugestaoCompraEditarRequest $request){
        $fields = $request->only(['id','produto_codigo','produto_descricao','cliente_nome','volume_produto','valor_estimado_venda','cliente_codigo','arquivo_foto_sugestao','composicao']);
        
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
      
        $sugestaoCompraObj = SugestaoCompra::find($id);
        $sugestaoCompraObj->composicao = strtoupper($fields['composicao']);
        $sugestaoCompraObj->produto_codigo = strtoupper($fields['produto_codigo']);
        $sugestaoCompraObj->produto_descricao = strtoupper($fields['produto_descricao']);
        $sugestaoCompraObj->cliente_nome = strtoupper($fields['cliente_nome']);
        $sugestaoCompraObj->volume_produto = empty($fields['volume_produto'])? 0 : parserNumber($fields['volume_produto']);   
        $sugestaoCompraObj->valor_estimado_venda =empty($fields['valor_estimado_venda'])? 0 : parserNumber($fields['valor_estimado_venda']);   
        $sugestaoCompraObj->cliente_codigo = ($fields['cliente_codigo']);
        $sugestaoCompraObj->status = 1;
        $sugestaoCompraObj->updated_by = Auth::id();
        $sugestaoCompraObj->save(); 

        if(!empty($fields['arquivo_foto_sugestao'])){
        
            $sugestaoCompraFotos =  SugestaoCompraFoto::where('sugestao_compra_id', $sugestaoCompraObj->id)->get();
            $indice =0;

            foreach($fields['arquivo_foto_sugestao'] as $key => $arquivo){
                $name_arquivo = $sugestaoCompraObj->id."_foto_sugestao_".$indice;
               
                $name_arquivo_foto = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
                
              
              
                if(Storage::exists($this->path.$sugestaoCompraObj->id."/foto/" .$name_arquivo_foto)){
                    Storage::delete($this->path.$sugestaoCompraObj->id."/foto/" . $name_arquivo_foto);
                    $sugestaoCompraFoto=  $sugestaoCompraFotos->where('nome_arquivo', $name_arquivo_foto)->first();
                 
              
                  $sugestaoCompraFoto->updated_by = Auth::id();
                }else{
                    $sugestaoCompraFoto = new SugestaoCompraFoto;
                    $sugestaoCompraFoto->created_by = Auth::id();
                }

                $path_file = $arquivo->storeAs($this->path.$sugestaoCompraObj->id."/foto", $name_arquivo_foto);

            
                $sugestaoCompraFoto->sugestao_compra_id = $sugestaoCompraObj->id;
                $sugestaoCompraFoto->nome_arquivo = $name_arquivo_foto;
                $sugestaoCompraFoto->caminho = $path_file;
              
                $sugestaoCompraFoto->save();

                $indice++;
            }
        }

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

        $sugestaoCompraObj = SugestaoCompra::find($id);
        if(is_null($sugestaoCompraObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
           
            'id' => encrypt($sugestaoCompraObj->id),
            'produto_codigo' => $sugestaoCompraObj->produto_codigo,
            'produto_descricao' =>  $sugestaoCompraObj->produto_descricao,
            'cliente_nome' => $sugestaoCompraObj->cliente_nome,
            'volume_produto' => $sugestaoCompraObj->volume_produto,
            'valor_estimado_venda' => $sugestaoCompraObj->valor_estimado_venda,
            'cliente_codigo' =>  $sugestaoCompraObj->cliente_codigo,
            'composicao' =>  $sugestaoCompraObj->composicao
        ];
        return view('programs.sugestao_compra.modal.deletar')->with(['dados' => $dados]);
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

        $sugestaoCompraObj = SugestaoCompra::find($id);
        $sugestaoCompraObj->deleted_by = Auth::id();
        $sugestaoCompraObj->save();
        $sugestaoCompraObj->delete();
        $sugestaoCompraFotos =  SugestaoCompraFoto::where('sugestao_compra_id', $sugestaoCompraObj->id)->get();

        foreach($sugestaoCompraFotos as $sugestaoCompraFoto){
            $sugestaoCompraFoto = SugestaoCompraFoto::find($sugestaoCompraFoto->id);
            $sugestaoCompraFoto->deleted_by = Auth::id();
            $sugestaoCompraFoto->save();
            $sugestaoCompraFoto->delete();
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response, 200);
    }

    public function situacao(){

        $status_sugestao = [
            "1" => 'Aberto',
            "2" => 'Aprovado',
            "3" => 'Reprovado',
            "9" => 'Cancelado'
        ];

        return $status_sugestao;
    }
    public function gerentes(){

  
        $gerentes = [];
      

            $subordinadosObj = User::with(["tipo_usuario"])->get();
            $subordinadosObj = $subordinadosObj->sortBy('name');

            foreach ($subordinadosObj as $key => $userObj) {
                if($userObj->id === Auth::id() || empty($userObj->tipo_usuario)){
                    continue;
                }
                if(strtolower($userObj->tipo_usuario->nome) === "gerente comercial"){
                    $gerentes[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }
            }
           

            unset($subordinadosObj);
        

        return  $gerentes;
    }


    public function indexAprovacao(Request $request){
      if(Auth::user()->hasPermissionTo("programas App\SugestaoCompraAprovacao") === false){
         return abort(403);
      }
    $request->session()->flash('model', 'App\SugestaoCompraAprovacao');

    $status_sugestao =$this->situacao();

    $gerentes =$this->gerentes();


     
       return view('programs.aprovacao_sugestao_compra.index')->with(['status_sugestao' => $status_sugestao,'gerentes' => $gerentes]);
    }

    public function modalMotivo(Request $request){
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

       $sugestaoCompra = SugestaoCompra::find($id);
        if(is_null( $sugestaoCompra  )){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
            'id' => encrypt( $sugestaoCompra ->id),
            'motivo' =>  $sugestaoCompra ->motivo
        ];
        return view('programs.aprovacao_sugestao_compra.modal.recusa')->with(['dados' => $dados]);
    }

    public function modalAprova(Request $request){
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

       $sugestaoCompra = SugestaoCompra::find($id);
    
        if(is_null( $sugestaoCompra  )){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
            'id' => encrypt($sugestaoCompra ->id),
            'motivo' =>  $sugestaoCompra->motivo,
            'produto_codigo' => $sugestaoCompra->produto_codigo,
            'produto_descricao' =>  $sugestaoCompra->produto_descricao,
        ];
       
        return view('programs.aprovacao_sugestao_compra.modal.aprova')->with(['dados' => $dados]);
    }

    public function aprovarSugestao(SugestaoCompraAprovaRequest $request){

        $fields = $request->only(['id','motivo','produto_codigo','produto_descricao']);
  
        try{
            $id = decrypt( $request->id);
          
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
      
        $sugestaoCompraObj = SugestaoCompra::find($id);
          $sugestaoCompraObj->motivo = strtoupper($fields['motivo']);
          $sugestaoCompraObj->produto_codigo = strtoupper($fields['produto_codigo']);
          $sugestaoCompraObj->produto_descricao = strtoupper($fields['produto_descricao']);
    
        $sugestaoCompraObj->status =2;
        $sugestaoCompraObj->updated_by = Auth::id();
        $sugestaoCompraObj->save(); 
     
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
    public function reprovarSugestao(SugestaoCompraMotivoRequest $request){
        $fields = $request->only(['id','motivo']);
                
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
      
        $sugestaoCompraObj = SugestaoCompra::find($id);
        $sugestaoCompraObj->motivo = strtoupper($fields['motivo']);
        $sugestaoCompraObj->status = 3;
        $sugestaoCompraObj->updated_by = Auth::id();
        $sugestaoCompraObj->save(); 


        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
}
