<?php

namespace App\Http\Controllers;

use App\Role;
use App\Video;
use App\Politica;
use App\VideoPerfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\VideoEditarRequest;
use App\Http\Requests\VideoAdicionarRequest;
use App\Modulo;

class VideoController extends Controller
{
    public $path = 'public/tutorial_video/'; 
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Video") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Video');

        return view('programs.video.index');
    }

    public function indexTutorial(Request $request){
        $request->session()->flash('model', 'App\Video');

        $saida = [];
      

            $VideoObj = Video::select('videos.id as id_video','*')->join('modulos','videos.modulos_id', '=', 'modulos.id')
             ->where(function($query){
			$query->orWhereHas('perfils', function($query){
				$query->where('perfil_id', Auth::user()->roles[0]->id);
			});
			$query->orDoesnthave('perfils');
		})->orderBy('modulos.nome')->orderBy('videos.descricao')->
		get();

        foreach($VideoObj as $video){
            $modulo= !empty($video->modulos) ? $video->modulos->nome :'TODOS';
           
            $modulo_atual='';
            if(!isset($chave[$modulo])){
                $chave[$modulo] = [
                    'chave' =>$modulo];
                   $modulo_atual=$modulo;
       
                       
            }
            $saida[] = [
                'id' => encrypt($video->id_video),
                'descricao' => $video->descricao,
                'caminho' => $video->caminho,
                'modulo' =>  $modulo_atual,
                'chave'=>  $modulo .'-'.$video->descricao,
    
 
            ];
        }


        return view('programs.tutorial.index')->with('dados',$saida);;
    }

    public function filtro(Request $request){


        $fields = $request->only(['perfil','descricao']);

        $VideoObj = Video::with(['perfils', 'perfils.perfil']);
        if(!empty($fields['descricao'])){
            $VideoObj->where('descricao', 'ilike', '%'.$fields['descricao'].'%');
        }

        try{
            $field_perfil = (!empty($fields['perfil'])) ? decrypt($fields['perfil']) : '';
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consulta, tente novamente mais tarde.',
                'error' => $e->getMessage(),
                'response' => '',
            ]);
        }

        if($field_perfil != '' && $field_perfil != 'todos'){
            $VideoObj->whereHas('perfils', function($query) use ($field_perfil){
                $query->where('perfil_id', $field_perfil);
            });
        }

        $saida = [];
        $Videos = $VideoObj->get();
        $Videos->each(function($mensagem) use(&$saida){
            $perfil_retorno = '';
            $mensagem->perfils->each(function($perfil) use(&$perfil_retorno){
				if(!empty($perfil->perfil)){
					$perfil_retorno .= $perfil->perfil->name .', ';
				}
            });
            if(empty($perfil_retorno)){
                $perfil_retorno = 'Todos';
            }else{
                $perfil_retorno = substr($perfil_retorno, 0, -2);
            }
            $saida[] = [
                'id' => encrypt($mensagem->id),
                'descricao' => $mensagem->descricao,
                'perfil' => $perfil_retorno
            ];
        });


        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $saida
        ];
        return response()->json($response, 200);
   

    
    }



    public function modalAdicionar() {
        $modulos =$this->modulosPortal();

        return view('programs.video.modal.adicionar')->with(["modulos" => $modulos]);
    }

    public function salvar(VideoAdicionarRequest $request){
        ini_set('memory_limit','2024M');
        $campo = $request->only('arquivo','descricao','caminho','perfils','modulos');
     
        $perfils = [];
        if(isset($campo['perfils'])){
            $perfils = $campo['perfils'];
        }
        foreach($perfils as $key => $value){
            try{
                $perfils[$key] = decrypt($value);
            }catch(Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao consulta, tente novamente mais tarde.',
                    'error' => $e->getMessage(),
                    'response' => '',
                ]);
            }
        }
    
        $VideoObj = new Video;

        $VideoObj->descricao = $campo['descricao'];
      

        $VideoObj->created_by = Auth::id();
   

        if(!empty($campo['arquivo'])){
        
        
            foreach($campo['arquivo'] as $key => $arq){

         
               
                $name_arquivo_video = $arq->getClientOriginalName();
                      
                 $VideoObj->arquivo = $name_arquivo_video ;
                $path_file =  $arq->storeAs($this->path, $name_arquivo_video);

                $VideoObj->caminho = $path_file ;
            }
        }
        if(!empty($campo['modulos'])){
            $VideoObj->modulos_id = $campo['modulos'];
        }

        $VideoObj->save();


        foreach($perfils as $perfil){
            $VideoPerfilObj  = new VideoPerfil;
            $VideoPerfilObj->videos_id = $VideoObj->id;
            $VideoPerfilObj->perfil_id = $perfil;
            $VideoPerfilObj->created_by = Auth::id();
            $VideoPerfilObj->save();
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

        $VideoObj = Video::find($id);
        if(is_null($VideoObj)){
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

        $dados = [];
        $perfils = [];

        $modulos =$this->modulosPortal();

   
        if(!empty($VideoObj)){
			foreach($VideoObj->perfils as $perfil){
				if(!empty($perfil->perfil)){
					$perfils[] = [
						'nome' => $perfil->perfil->name,
						'id' => encrypt($perfil->perfil_id),
					];
				}
            }
            $dados['link_arquivo'] = $VideoObj->caminho;
            $dados['descricao'] = $VideoObj->descricao;
            $dados['id'] = encrypt($VideoObj->id);
            $dados['perfil'] = $perfils;
            $dados['nome_arquivo'] = $VideoObj->arquivo;
            $dados['modulos_id'] = $VideoObj->modulos_id;

      
    
         
			return view('programs.video.modal.editar')->with(["dados" => $dados,"modulos" => $modulos]);
        }else{
			return view('programs.video.modal.adicionar')->with(["modulos" => $modulos]);;
		}

   
   

    }

    public function editar(VideoEditarRequest $request){
    

        $fields = $request->only('id', 'arquivo','descricao','perfils','modulos');
        $perfils = [];
        if(isset($fields['perfils'])){
            $perfils = $fields['perfils'];
        }
        foreach($perfils as $key => $value){
            try{
                $perfils[$key] = decrypt($value);
            }catch(Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao consulta, tente novamente mais tarde.',
                    'error' => $e->getMessage(),
                    'response' => '',
                ]);
            }
        }
        try{
            $id = decrypt($fields['id']);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consulta, tente novamente mais tarde.',
                'error' => $e->getMessage(),
                'response' => '',
            ]);
        }

        $VideoObj = Video::find($id);
    
        $VideoObj->descricao = $fields['descricao'];
    
  
        $VideoObj->updated_by = Auth::id();
 

        if(!empty($fields['arquivo'])){
        
        
            foreach($fields['arquivo'] as $key => $arq){
               

                       
               $name_arquivo_video = $arq->getClientOriginalName();
             
               $VideoObj->arquivo = $name_arquivo_video ;
                if(Storage::exists($this->path ."/", $name_arquivo_video)){
                    Storage::delete($this->path."/", $name_arquivo_video);
             
                }
            

                $path_file =  $arq->storeAs($this->path."/", $name_arquivo_video);
            
                $VideoObj->caminho = $path_file ;
            }
        }

        if(!empty($fields['modulos'])){
            $VideoObj->modulos_id = $fields['modulos'];
        }
        $VideoObj->save(); 

        $VideoObj->perfils->whereNotIn('perfil_id', array_values($perfils))->each(function($perfil){
			$perfil->delete();
		});
        foreach($perfils as $perfil){
            if($VideoObj->perfils->where('perfil_id', $perfil)->count() !== 0){
                $pefil = $VideoObj->perfils->where('perfil_id', $perfil)->first();
                $pefil->updated_by = Auth::id();
                $pefil->save();
            }else{
                $VideoPerfilObj  = new VideoPerfil;
                $VideoPerfilObj->videos_id = $VideoObj->id;
                $VideoPerfilObj->perfil_id = $perfil;
                $VideoPerfilObj->created_by = Auth::id();
                $VideoPerfilObj->save();
            }
        }
        if(empty($perfils)){
			$VideoObj->perfils->each(function($perfil){
				$perfil->delete();
			});
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

        $VideoObj = Video::find($id);
        if(is_null($VideoObj)){
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
            'id' => encrypt($VideoObj->id),
            'descricao' => $VideoObj->descricao
        ];
        return view('programs.video.modal.deletar')->with(['dados' => $dados]);
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

        $VideoObj = Video::find($id);
        $VideoObj->deleted_by = Auth::id();
        $VideoObj->save();
        $VideoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalVideo(Request $request){
   
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
      $videos =[];

        $tuttorialVideos =  Video::where('id',$id)->get();

       foreach($tuttorialVideos as $tutorialVideo){

               $videos[] = [
                    'video' =>  Storage::url($tutorialVideo->caminho),
                ];

        }
      
      
        if(is_null($videos)){
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

         return view('programs.video.modal.video')->with(['videos' => $videos]);
    }

    public function modalVideoTutorial(Request $request){
   
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
      $videos =[];

        $tuttorialVideos =  Video::where('id',$id)->get();

       foreach($tuttorialVideos as $tutorialVideo){

               $videos[] = [
                    'video' =>  Storage::url($tutorialVideo->caminho),
                ];

        }
      

        if(is_null($videos)){
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

         return view('programs.tutorial.modal.video')->with(['videos' => $videos]);
    }
    public function modalTutorial(Request $request){
        $campo = $request->only('grupo');

        $VideoObj = Video::select();

             
        $VideoObj->where('grupo',  $campo['grupo']);

        $VideoObjs = $VideoObj->get();
        $dados=[];
        foreach($VideoObjs as $video){
        $dados[] = [
            'id' => encrypt($video->id),
            'arquivo' => $video->arquivo,
            'descricao' => $video->descricao,
            'link_arquivo' => $video->caminho,
        ];
    }
   

        return view('programs.tutorial.modal.dialog')->with(['dados' => $dados]);
    }
	private function perfils(){
		$RoleObj = Role::orderBy('name')->get();
		$perfis[null] = 'Perfil';

		foreach($RoleObj as $tipo){
			$perfis[encrypt($tipo->id)] = $tipo->name;
		}

		return $perfis;
	}

    private function modulosPortal(){
		$moduloObj = Modulo::orderBy('nome')->get();
      

		foreach($moduloObj as $modulo){
			$modulos[$modulo->id] = $modulo->nome;
		}

		return $modulos;
	}


    public function modalAssinatura() {
        $dados=[];
        $dados['nome'] = Auth::user()->name;
        $dados['setor'] = Auth::user()->setor;
        $dados['telefone'] =Auth::user()->celular;
        $dados['celular'] =Auth::user()->celular;
        $dados['email'] =Auth::user()->email;
   

        return view('programs.video.modal.assinatura')->with(["dados" => $dados]);
    }

}