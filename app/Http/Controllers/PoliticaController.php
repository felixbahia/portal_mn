<?php

namespace App\Http\Controllers;

use Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Role;
use App\Politica;
use App\PoliticaPerfil;

use App\Http\Requests\PoliticaAdicionarRequest;
use App\Http\Requests\PoliticaEditarRequest;

class PoliticaController extends Controller
{
	public function index(Request $request){
		$request->session()->flash('model', 'App\PoliticaCadastro');
        $arquivos = [];
        $PoliticaObj = Politica::where(function($query){
			$query->orWhereHas('perfils', function($query){
				$query->where('perfil_id', Auth::user()->roles[0]->id);
			});
			$query->orDoesnthave('perfils');
		})->
		get();
        $PoliticaObj->each(function($politica) use(&$arquivos){
            $arquivos[] = [
                'nome' => $politica->descricao,
                'url' => $politica->url_arquivo
            ];
        });
        return view('programs.politica.index')->with('arquivos', $arquivos);
	}

	private function perfils(){
		$RoleObj = Role::orderBy('name')->get();
		$perfis[null] = 'Perfil';

		foreach($RoleObj as $tipo){
			$perfis[encrypt($tipo->id)] = $tipo->name;
		}

		return $perfis;
	}

	public function indexCadastro(Request $request){
		if(Auth::user()->hasPermissionTo("programas App\PoliticaCadastro") === false){
			return abort(403);
		}
		$request->session()->flash('model', 'App\PoliticaCadastro');

		$perfis = $this->perfils();

		return view('programs.politica.cadastro.index')->with(['perfis' => $perfis]);
	}

	public function modalAdicionar(){        
		return view('programs.politica.cadastro.modal.adicionar');
	}

    public function adicionar(PoliticaAdicionarRequest $request){
        ini_set('post_max_size', '20M');
        ini_set('upload_max_filesize', '20M');
        ini_set('memory_limit', '22M');
        $fields = $request->only(['descricao', 'arquivo', 'perfils']);

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

        $PoliticaObj = new Politica;
        $PoliticaObj->descricao = $fields['descricao'];
        $PoliticaObj->created_by = Auth::id();
        $PoliticaObj->save();

        if($request->hasFile('arquivo')){
            $nome_arquivo = $PoliticaObj->id . '.' . $request->file('arquivo')->getClientOriginalExtension();
            $request->file('arquivo')->storeAs($PoliticaObj->caminho, $nome_arquivo);
            $PoliticaObj->arquivo = $nome_arquivo;
            $PoliticaObj->save();
        }


        foreach($perfils as $perfil){
            $PoliticaPerfilObj  = new PoliticaPerfil;
            $PoliticaPerfilObj->politicas_id = $PoliticaObj->id;
            $PoliticaPerfilObj->perfil_id = $perfil;
            $PoliticaPerfilObj->created_by = Auth::id();
            $PoliticaPerfilObj->save();
        }

        if(!$PoliticaObj){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao gravar',
                'error' => [],
                'response' => [],
            ]);
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => 'Politica gravada com sucesso',
                'error' => [], 
                'response' => [],
            ]);
        }
    }
    public function modalEditar(Request $request){
        $fields = $request->only('id');

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

        $PoliticaObj = Politica::find($id);

        $dados = [];
        $perfils = [];

        if(!empty($PoliticaObj)){
			foreach($PoliticaObj->perfils as $perfil){
				if(!empty($perfil->perfil)){
					$perfils[] = [
						'nome' => $perfil->perfil->name,
						'id' => encrypt($perfil->perfil_id),
					];
				}
            }
            $dados['link_arquivo'] = $PoliticaObj->url_arquivo;
            $dados['descricao'] = $PoliticaObj->descricao;
            $dados['id'] = encrypt($PoliticaObj->id);
            $dados['perfil'] = $perfils;
            $dados['nome_arquivo'] = $PoliticaObj->arquivo;
			return view('programs.politica.cadastro.modal.editar')->with(["dados" => $dados]);
        }else{
			return view('programs.politica.cadastro.modal.adicionar');
		}
    }

    public function editar(PoliticaEditarRequest $request){
        ini_set('post_max_size', '20M');
        ini_set('upload_max_filesize', '20M');
        ini_set('memory_limit', '22M');
        $fields = $request->only('id', 'arquivo','descricao','perfils');
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

        $PoliticaObj = Politica::find($id);
        $PoliticaObj->descricao = $fields['descricao'];
        $PoliticaObj->updated_by = Auth::id();
        $PoliticaObj->save();

        if($request->hasFile('arquivo')){
            Storage::delete($PoliticaObj->caminho_arquivo);
            $nome_arquivo = $PoliticaObj->id . '.' . $request->file('arquivo')->getClientOriginalExtension();
            $request->file('arquivo')->storeAs($PoliticaObj->caminho, $nome_arquivo);
            $PoliticaObj->arquivo = $nome_arquivo;
            $PoliticaObj->save();
        }
		$PoliticaObj->perfils->whereNotIn('perfil_id', array_values($perfils))->each(function($perfil){
			$perfil->delete();
		});
        foreach($perfils as $perfil){
            if($PoliticaObj->perfils->where('perfil_id', $perfil)->count() !== 0){
                $pefil = $PoliticaObj->perfils->where('perfil_id', $perfil)->first();
                $pefil->updated_by = Auth::id();
                $pefil->save();
            }else{
                $PoliticaPerfilObj  = new PoliticaPerfil;
                $PoliticaPerfilObj->politicas_id = $PoliticaObj->id;
                $PoliticaPerfilObj->perfil_id = $perfil;
                $PoliticaPerfilObj->created_by = Auth::id();
                $PoliticaPerfilObj->save();
            }
        }
        if(empty($perfils)){
			$PoliticaObj->perfils->each(function($perfil){
				$perfil->delete();
			});
        }
        if(!$PoliticaObj){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao editar',
                'error' => $e->getMessage(),
                'response' => [],
            ]);
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => 'Mensagem Editada com Sucesso',
                'error' => [],
                'response' => [],
            ]);
        }
    }

    public function excluir(Request $request){
        $filter = $request->only(['id']);

        try{
            $id = decrypt($filter['id']);
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
            $PoliticaObj = Politica::findOrFail($id);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'erro',
                'message' => 'Ocorreu um erro!',
                'error' => $e->getMessage(),
                'response' => []
            ]);
        }
        $PoliticaObj->deleted_by = Auth::user()->id;
        $PoliticaObj->save();
        $PoliticaObj->delete();

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => []
        ]);
    }

    public function filtro(Request $request){
        $fields = $request->only(['perfil']);

        $PoliticaObj = Politica::with(['perfils', 'perfils.perfil']);

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
            $PoliticaObj->whereHas('perfils', function($query) use ($field_perfil){
                $query->where('perfil_id', $field_perfil);
            });
        }

        $response = [];
        $Politicas = $PoliticaObj->get();
        $Politicas->each(function($mensagem) use(&$response){
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
            $response[] = [
                'id' => encrypt($mensagem->id),
                'descricao' => $mensagem->descricao,
                'perfil' => $perfil_retorno
            ];
        });

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $response],
        ]);

    }
}
