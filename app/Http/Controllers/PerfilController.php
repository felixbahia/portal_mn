<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\User;
use App\Modulo;
use App\Programa;
use App\SubModulo;
use App\DevolucaoNotaStatus;

use Illuminate\Support\Facades\DB;

class PerfilController extends Controller
{
    public function __construct() {
        $this->middleware(['auth']);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\Profile") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Profile');
        return view('programs.perfis.index');
    }

    /**
     * Filtro da listagem
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request){
        $fields = $request->only(['nome']);
        $where = [];
        if(!empty($fields['nome'])){
            $where[] = ['LOWER(name)', 'like', strtolower($fields['nome'])];
        }
        $query = DB::table("roles");
        foreach($where as $value){
            if(count($value) === 3){
                $query->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
            } else {
                $query->where($value[0], $value[1]);
            }
        }
        $query = $query->get();
        $results = $query->toArray();
        $return = [];
        foreach($results as $key => $result){
            $result = (array) $result;
            $return[] = [
                "name" => $result["name"],
                "usuario_vinculados" => "<a href=\"#\" data-route=\"".route('perfil.lista_usuarios',["id"=>$result["id"]])."\" data-nome=\"{$result["name"]}\" data-id=\"{$result["id"]}\" class=\"bt-view bt-view-users\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visuálizar Usuários\"></a>",
                "edit" => "<a href=\"#\" data-route=\"".route('perfil.edit',["id"=>$result["id"]])."\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>",
                "delete" => "<a href=\"#\" data-route=\"".route('perfil.destroy',["id"=>$result["id"]])."\" data-nome=\"{$result["name"]}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>"
            ];
        }
        return response()->json($return);
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create() {
        return view('programs.perfis.create')->with("permissoes", $this->returnPermissoesView());
    }

    public function returnPermissoesView($dados = []){
        $checks = [];
        if(!empty($dados)){
            foreach ($dados as $key => $value) {
                $checks[] = $value["id"];
            }
        }
        $permissoes = [];
        $permissoes_temp = Permission::orderby('name')->get()->toArray();
        $temp_modulo = [];
        $temp_sub_modulo = [];
        $temp_programa = [];
        $temp_action = [];

        foreach ($permissoes_temp as $key => $value) {
            if(strpos($value["name"], "sub_modulos ") !== false){
                $submodulo = str_replace("sub_modulos ","", $value["name"]);
                $submodulo = SubModulo::where("url", $submodulo)->first();
                if(!is_null($submodulo)){
                    $submodulo = $submodulo->toArray();
                }
                $temp_sub_modulo[intval($value["id"])] = $submodulo;
            } else if(strpos($value["name"], "modulos ") !== false){
                $modulo = str_replace("modulos ","", $value["name"]);
                $modulo = Modulo::where("url", $modulo)->first();
                if(!is_null($modulo)){
                    $temp_modulo[intval($value["id"])] = $modulo->toArray();
                }
            } else if(strpos($value["name"], "programas ") !== false){
                $programa = str_replace("programas ","", $value["name"]);
                $programa = Programa::where("model", $programa)->first();
                if(!is_null($programa)){
                    $temp_programa[intval($value["id"])] = $programa->toArray();
                }
            } else if(strpos($value["name"], "action ") !== false){
                $string = str_replace("action ","", $value["name"]);
                $array = explode(' ', $string);
                $devolucaoNotaStatusObj = '';
                if($array[0] == 'App\DevolucaoNotaAprovacao'){
                    $devolucaoNotaStatusObj = DevolucaoNotaStatus::where('chave', $array[1])->first();
                    $programa = Programa::where("model", 'App\DevolucaoNotaAprovacao')->first();
                }
                if(!empty($devolucaoNotaStatusObj)){
                    $temp_action[intval($value["id"])] = [
                        "modulos_id" => $programa['modulos_id'],
                        "sub_modulos_id" => $programa['sub_modulos_id'],
                        "nome" => $devolucaoNotaStatusObj->descricao,
                        "programa" => $programa['id'],
                    ];
                }
            }

        }

        foreach ($temp_modulo as $key => $value) {
            $permissoes[intval($value["id"])]["modulos"] = [
                "permission_id" => $key,
                "id" => intval($value["id"]),
                "nome" => $value["nome"],
                "check" => (in_array(intval($key), $checks) ? 1 : 0)
            ];
            $permissoes[intval($value["id"])]["submodulos"] = [];
            $permissoes[intval($value["id"])]["programas"] = [];
            unset($temp_modulo[$key]);
        }
        foreach ($temp_sub_modulo as $key => $value) {

            if(intval($value["sub_modulos_id"]) === 0) {
                if(isset($permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["id"])])){
                        $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["id"])]["permission_id"] = $key;
                        $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["id"])]["id"] = intval($value["id"]);
                        $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["id"])]["nome"] = $value["nome"];
                        $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["id"])]["check"] = (in_array(intval($key), $checks) ? 1 : 0);
                        $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["id"])]['programas'] = [];
                }else{
                    $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["id"])] = [
                        "permission_id" => $key,
                        "id" => intval($value["id"]),
                        "nome" => $value["nome"],
                        "check" => (in_array(intval($key), $checks) ? 1 : 0),
                        'programas' => [],
                        'submodulos' => []
                    ];
                }
            }
            else{
                //$permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["sub_modulos_id"])]["submodulos"] = [];
                $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value["sub_modulos_id"])]["submodulos"][intval($value["id"])] = [
                    "permission_id" => $key,
                    "id" => intval($value["id"]),
                    "nome" => $value["nome"],
                    "check" => (in_array(intval($key), $checks) ? 1 : 0),
                    'programas' => [],
                    'submodulos' => []
                ];
            }
            unset($temp_sub_modulo[$key]);
        }
        foreach ($temp_programa as $key => $value) {
            if(intval($value["sub_modulos_id"]) === 0) {
                $permissoes[intval($value["modulos_id"])]["programas"][intval($value["id"])] = [
                    "permission_id" => $key,
                    "id" => intval($value["id"]),
                    "nome" => $value["nome"],
                    "check" => (in_array(intval($key), $checks) ? 1 : 0),
                    "action" => [],
                ];
            }
            elseif (isset($permissoes[intval($value["modulos_id"])]["submodulos"][$value["sub_modulos_id"]])){
                $permissoes[intval($value["modulos_id"])]["submodulos"][$value["sub_modulos_id"]]["programas"][intval($value["id"])] = [
                    "permission_id" => $key,
                    "id" => intval($value["id"]),
                    "nome" => $value["nome"],
                    "check" => (in_array(intval($key), $checks) ? 1 : 0),
                    "action" => [],
                ];
            }
            else{
                $submodulo = SubModulo::where('id', $value['sub_modulos_id'])->first()->toArray();

                $permissoes[intval($value["modulos_id"])]["submodulos"][intval($submodulo['sub_modulos_id'])]['submodulos'][$value["sub_modulos_id"]]['programas'][intval($value["id"])] = [
                    "permission_id" => $key,
                    "id" => intval($value["id"]),
                    "nome" => $value["nome"],
                    "check" => (in_array(intval($key), $checks) ? 1 : 0),
                    "action" => [],
                ];
            }
            unset($temp_programa[$key]);
        }
        foreach ($temp_action as $key => $value) {
            
            if(isset($permissoes[intval($value["modulos_id"])]["programas"][intval($value["programa"])])){

                $permissoes[intval($value["modulos_id"])]["programas"][intval($value["programa"])]['check'] = 0;

                $permissoes[intval($value["modulos_id"])]["programas"][intval($value["programa"])]['action'][] = [
                    "permission_id" => $key,
                    "nome" => $value["nome"],
                    "check" => (in_array(intval($key), $checks) ? 1 : 0)
                ];
            }
            else if(isset($permissoes[intval($value["modulos_id"])]["submodulos"][intval($value['sub_modulos_id'])]['submodulos'][$value["sub_modulos_id"]]['programas'][intval($value["programa"])])){

                $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value['sub_modulos_id'])]['submodulos'][$value["sub_modulos_id"]]['programas'][intval($value["programa"])]['check'] = 0;

                $permissoes[intval($value["modulos_id"])]["submodulos"][intval($value['sub_modulos_id'])]['submodulos'][$value["sub_modulos_id"]]['programas'][intval($value["programa"])]['action'][] = [
                    "permission_id" => $key,
                    "nome" => $value["nome"],
                    "check" => (in_array(intval($key), $checks) ? 1 : 0)
                ];
            }
            else if($permissoes[intval($value["modulos_id"])]["submodulos"][$value["sub_modulos_id"]]["programas"][intval($value["programa"])]){
                $permissoes[intval($value["modulos_id"])]["submodulos"][$value["sub_modulos_id"]]["programas"][intval($value["programa"])]['check'] = 0;

                $permissoes[intval($value["modulos_id"])]["submodulos"][$value["sub_modulos_id"]]["programas"][intval($value["programa"])]['action'][] = [
                    "permission_id" => $key,
                    "nome" => $value["nome"],
                    "check" => (in_array(intval($key), $checks) ? 1 : 0)
                ];
            }

        }
        return $permissoes;
    }
    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request) {
        set_time_limit(300);
        $validatedData = $request->validate([
            'nome' => 'required|max:100|unique:roles,name',
        ], [
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'nome.unique' => __('validation.unique', ['attribute' => 'Nome']),
            'nome.max' => __('validation.max.string', ['attribute' => 'Nome']),
        ]);
        $RoleObj = new Role;
        $RoleObj->name = $request->nome;
        $RoleObj->guard_name = "web";
        $RoleObj->save();

        $permissoes_temp = $request->permissoes;
        $permissoes = ["modulos" => [] ,"submodulos" => [],"programas" => []];
        $permissoesObj = Permission::whereIn("id", $permissoes_temp)->orderBy("name")->get()->toArray();
        foreach ($permissoesObj as $key => $value) {
            if(strpos($value["name"], "sub_modulos ") !== false){
                $submodulo = str_replace("sub_modulos ","", $value["name"]);
                $temp = SubModulo::where("url", $submodulo)->first()->toArray();
                $permissoes["modulos"][] = $temp["modulos_id"];
                if(intval($temp["sub_modulos_id"]) > 0){
                    $permissoes["submodulos"][] = $temp["sub_modulos_id"];
                }
            } else if(strpos($value["name"], "modulos ") !== false){
                $modulo = str_replace("modulos ","", $value["name"]);
                $temp = Modulo::where("url", $modulo)->first()->toArray();
                $permissoes["modulos"][] = $temp["id"];
            } else if(strpos($value["name"], "programas ") !== false){
                $programa = str_replace("programas ","", $value["name"]);
                $temp = Programa::where("model", $programa)->first()->toArray();
                $permissoes["modulos"][] = $temp["modulos_id"];
                $permissoes["submodulos"][] = $temp["sub_modulos_id"];
                $permissoes["programas"][] = $temp["id"];
            } else if(strpos($value["name"], "action ") !== false){
                $action[] = $value['id'];
            }
        }
        unset($permissoesObj);
        $modulos = Modulo::wherein("id",$permissoes["modulos"])->get()->toArray();
        $submodulos = SubModulo::wherein("id",$permissoes["submodulos"])->get()->toArray();
        $programas = Programa::wherein("id",$permissoes["programas"])->get()->toArray();
        $permissoes = [];
        foreach ($modulos as $key => $value) {
            $permissoes[] = Permission::where("name", "modulos ".$value["url"])->first()->toArray()["id"];
        }
        foreach ($submodulos as $key => $value) {
            $permissoes[] = Permission::where("name", "sub_modulos ".$value["url"])->first()->toArray()["id"];
        }
        foreach ($programas as $key => $value) {
            $permissoes[] = Permission::where("name", "programas ".$value["model"])->first()->toArray()["id"];
        }
        $permissoes = array_merge($permissoes, $action);

        unset($modulos, $submodulos, $programas, $action);

        foreach($permissoes as $value){
            $RoleObj->givePermissionTo($value);
        }
        
        return response()->json(['saved' => $RoleObj ]);
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id) {
        

        $rolesObj = Role::with("permissions")->find($id);

        $excluir = [];
        
        $rolesObj->permissions->each(function($permission) use (&$excluir){
            $temp = explode(' ', $permission->name);
            if($temp[0] == 'action')
            $excluir[] = 'programas ' . $temp[1];
        });

        $dados = $rolesObj->toArray();
        $permissoes = $this->returnPermissoesView($dados["permissions"]);
        $permissoes_programs = [];
        foreach ($dados["permissions"] as $key => $value) {
            if(in_array($value["name"], $excluir) || (strpos($value["name"], "programas") === false && strpos($value["name"], "action") === false && strpos($value["name"], "modulos power_bi") === false)){
                continue;
            }

            $permissoes_programs[] = $value["id"];

        }

        return view('programs.perfis.edit')->with('dados', $dados)->with('permissoes', $permissoes)->with('permissoes_programs', $permissoes_programs);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(Request $request, $id) {
        set_time_limit(300);

        $RoleObj = Role::findOrFail($id);

        $action = [];
        
        $permissoes_temp = $request->permissoes;
        $permissoes = ["modulos" => [] ,"submodulos" => [],"programas" => []];
        $permissoesObj = Permission::whereIn("id", $permissoes_temp)->orderBy("name")->get()->toArray();
        foreach ($permissoesObj as $key => $value) {
            if(strpos($value["name"], "sub_modulos ") !== false){
                $submodulo = str_replace("sub_modulos ","", $value["name"]);
                $temp = SubModulo::where("url", $submodulo)->first()->toArray();
                $permissoes["modulos"][] = $temp["modulos_id"];
                if(intval($temp["sub_modulos_id"]) > 0){
                    $permissoes["submodulos"][] = $temp["sub_modulos_id"];
                }
            } else if(strpos($value["name"], "modulos ") !== false){
                $modulo = str_replace("modulos ","", $value["name"]);
                $temp = Modulo::where("url", $modulo)->first()->toArray();
                $permissoes["modulos"][] = $temp["id"];
            } else if(strpos($value["name"], "programas ") !== false){
                $programa = str_replace("programas ","", $value["name"]);
                $temp = Programa::where("model", $programa)->first()->toArray();
                $permissoes["modulos"][] = $temp["modulos_id"];
                $permissoes["submodulos"][] = $temp["sub_modulos_id"];
                $permissoes["programas"][] = $temp["id"];
            } else if(strpos($value["name"], "action ") !== false){
                $action[] = $value['id'];
            }
        }
        unset($permissoesObj);
        $modulos = Modulo::wherein("id",$permissoes["modulos"])->get()->toArray();
        $submodulos = SubModulo::wherein("id",$permissoes["submodulos"])->get()->toArray();
        $programas = Programa::wherein("id",$permissoes["programas"])->get()->toArray();
        $permissoes = [];
        foreach ($modulos as $key => $value) {
            $permissoes[] = Permission::where("name", "modulos ".$value["url"])->first()->toArray()["id"];
        }
        foreach ($submodulos as $key => $value) {
            $permissoes[] = Permission::where("name", "sub_modulos ".$value["url"])->first()->toArray()["id"];
        }
        foreach ($programas as $key => $value) {
            $permissoes[] = Permission::where("name", "programas ".$value["model"])->first()->toArray()["id"];
        }

        $permissoes = array_merge($permissoes, $action);

        unset($modulos, $submodulos, $programas, $action);
        
        $validatedData = $request->validate([
            'nome' => 'required|max:100|unique:roles,name,'.$id,
        ], [
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'nome.unique' => __('validation.unique', ['attribute' => 'Nome']),
            'nome.max' => __('validation.max.string', ['attribute' => 'Nome']),
        ]);

        $RoleObj->name = $request->nome;
        $RoleObj->save();
        
        foreach ($RoleObj->permissions->toArray() as $key => $value) {
            $RoleObj->revokePermissionTo($value["id"]);
        }

        foreach($permissoes as $value){
            $RoleObj->givePermissionTo($value);
        }
        
        return response()->json(['saved' => $RoleObj ]);
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id) {
        $RoleObj = Role::findOrFail($id); 
        $RoleObj->delete();

        return response()->json(['saved' => $RoleObj ]);
    }

    public function listUsers($id) {
        $dados = Role::findOrFail($id)->toArray();
        $usuarios = [];
        $roles_users = DB::table('model_has_roles')->where('role_id',$id)->get()->toArray();
        foreach($roles_users as $value){
            if(intval($value->model_id) === 1){
                continue;
            }
            $user = User::where("id", $value->model_id)->first();
            if(is_null($user)){
                continue;
            }
            $usuario_temp = $user->toArray();
            $usuario = [
                "nome" => $usuario_temp["name"],
                "usuario" => $usuario_temp["username"],
                "id" => $usuario_temp["id"],
                "view" => "<a href=\"#\" data-route=\"".route('usuario.show',["id"=>$usuario_temp["id"]])."\" data-nome=\"{$usuario_temp["name"]}\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\"></a>"
            ];
            $usuarios[] = $usuario;
        }
        return view('programs.perfis.usuarios')->with('usuarios', $usuarios);
    }

    public function addUser(Request $request){
        $fields = $request->only(['role', 'user']);
        $RoleObj = Role::findOrFail($fields["role"]); 
        $UserObj = User::findOrFail($fields["user"]);
        $roles = $UserObj->roles()->pluck('name');
        foreach ($roles as $key => $value) {
            $UserObj->removeRole($value);
        }
        $UserObj->assignRole($RoleObj);

        return response()->json(['saved' => $UserObj ]);
    }

    public function getTableUser(Request $request){
        $fields = $request->only(['role']);
        $dados = Role::findOrFail($fields["role"])->toArray();
        $usuarios = [];
        $roles_users = DB::table('model_has_roles')->where('role_id', $fields["role"])->get()->toArray();
        foreach($roles_users as $value){
            if(intval($value->model_id) === 1){
                continue;
            }
            $usuario_temp = User::where("id", $value->model_id)->first()->toArray();
            $usuario = [
                "nome" => $usuario_temp["name"],
                "usuario" => $usuario_temp["username"],
                "view" => "<a href=\"#\" data-route=\"".route('usuario.show',["id"=>$usuario_temp["id"]])."\" data-nome=\"{$usuario_temp["name"]}\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\"></a>"
            ];
            $usuarios[] = $usuario;
        }
        
        return response()->json($usuarios);
    }

    public function modalBusca(){
        return view('programs.perfis.modal.busca');
    }

    public function filtroModal(Request $request){
        $fields = $request->only(['nome']);

        $RoleObj = Role::query();

        if(!empty($fields['nome'])){
            $RoleObj->where('name', 'ilike', '%'.$fields['nome'].'%');
        }

        $retorno = [];
        $roles = $RoleObj->get();
        unset($RoleObj);
        $roles->each(function($role) use (&$retorno){
            $retorno[] = [
                'name' => $role->name,
                'id' => encrypt($role->id)
            ];
        });
        unset($roles);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno,
        ]);
    }


}
