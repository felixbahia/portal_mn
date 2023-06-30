<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Modulo;
use App\SubModulo;

use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


use App\Http\Requests\SubModuloRequest;

class SubModuloController extends Controller
{
    
    /**
     * Tela de cadastro inicial
     *
     * @return void
     */
    public function indexCadastro(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\SubModulo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\SubModulo');
        $modulos = Modulo::all();
        return view('programs.sub_modulos.index')->with("modulos", $modulos);
    }

    /**
     * Filtro da listagem
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request){
        $fields = $request->only(['nome', "modulo"]);
        $where = [];
        if(!empty($fields['nome'])){
            $where[] = ['LOWER(nome)', 'like', strtolower($fields['nome'])];
        }
        if(!empty($fields["modulo"])){
            $where[] = ["modulos_id", $fields["modulo"]];
        }
        $query = SubModulo::select("*");
        foreach($where as $value){
            if(count($value) === 3){
                $query->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
            } else {
                $query->where($value[0], $value[1]);
            }
        }
        $query->whereNull("deleted_at");
        $query = $query->get();
        $results = $query->toArray();
        $return = [];
        foreach($results as $key => $result){
            $result = (array) $result;
            $return[$key] = [];
            foreach($result as $k => $value){
                if(empty($value)){
                    $return[$key][$k] = "";
                }else{
                    $return[$key][$k] = $value;
                }
            }
            $modulo = Modulo::find($result["modulos_id"]);

            $return[$key]["modulo"] = $modulo->nome;
            if(!empty($result["sub_modulos_id"])){
                $submodulo = SubModulo::find($result["sub_modulos_id"]);
                $return[$key]["submodulo"] = $submodulo->nome;
            }else{
                $return[$key]["submodulo"] = " ";
            }
            unset($modulo);
            $return[$key]["edit"] = "<a href=\"#\" data-route=\"".route('submodulos.edit',["id"=>$result["id"]])."\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
            $return[$key]["delete"] = "<a href=\"#\" data-route=\"".route('submodulos.destroy',["id"=>$result["id"]])."\" data-nome=\"{$result["nome"]}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
        }
        return response()->json($return);
    }


    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create() {
        $modulosTemp = Modulo::all();
        $modulos = [""=>""];
        foreach ($modulosTemp as $key => $value) {
            $modulos[$value["id"]] = $value["nome"];
        }
        return view('programs.sub_modulos.create')->with("modulos", $modulos);
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(SubModuloRequest $request) {
        $icon = $request->icon;
        $name = $request->url.".".$icon->getClientOriginalExtension();
        $path_file = $icon->storeAs("public/icons/sub_modulos", $name);
        
        $SubModuloObj = new SubModulo;
        $SubModuloObj->nome = $request->nome;
        $SubModuloObj->modulos_id = $request->modulos_id;
        $SubModuloObj->sub_modulos_id = $request->sub_modulos_id;
        $SubModuloObj->url = str_replace(" ","_",trim(strtolower($request->url)));
        $SubModuloObj->icon = $path_file;
        $SubModuloObj->save();

        $permission = new Permission();
        $permission->name = "sub_modulos ".str_replace(" ","_", trim(strtolower($request->url)));
        $permission->save();

        return response()->json(['saved' => $SubModuloObj ]);
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id) {
        $dados = SubModulo::findOrFail($id)->toArray();
        $imagem = Storage::url($dados["icon"]);
        $modulosTemp = Modulo::all();
        $modulos = [""=>""];
        foreach ($modulosTemp as $key => $value) {
            $modulos[$value["id"]] = $value["nome"];
        }
        $SubModulosTemp = SubModulo::where("modulos_id", $dados["modulos_id"])->get();
        $sub_modulos = [""=>""];
        foreach ($SubModulosTemp as $key => $value) {
            $sub_modulos[$value["id"]] = $value["nome"];
        }
        
        return view('programs.sub_modulos.edit')->with('dados', $dados)->with('Imagem', $imagem)->with("modulos", $modulos)->with("sub_modulos", $sub_modulos);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(SubModuloRequest $request, $id) {
        $SubModuloObj = SubModulo::findOrFail($id);
        
        $permission = Permission::where("name", "sub_modulos ".$SubModuloObj->url)->first();
        $permission->name = "sub_modulos ".str_replace(" ","_", trim(strtolower($request->url)));
        $permission->save();

        $SubModuloObj->nome = $request->nome;
        $SubModuloObj->modulos_id = $request->modulos_id;
        $SubModuloObj->sub_modulos_id = $request->sub_modulos_id;
        $SubModuloObj->url = str_replace(" ","_", trim(strtolower($request->url)));
        if(!empty($request->icon)){
            $icon = $request->icon;
            $path_file = $icon->storeAs("public/icons/sub_modulos",$icon->getClientOriginalName());
            $SubModuloObj->icon = $path_file;
        }

        $SubModuloObj->save();

        return response()->json(['saved' => $SubModuloObj ]);
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id) {
        $SubModuloObj = SubModulo::findOrFail($id); 
        Storage::delete($SubModuloObj->icon);
        Permission::where("name", "sub_modulos ".$SubModuloObj->url)->delete();
        $SubModuloObj->delete();

        return response()->json(['saved' => $SubModuloObj ]);
    }
    
    /**
     * Retorna sub-modulos por id de módulo
     *
     * @param int $modulos_id
     * @return void
     */
    public function returnToModulos(Request $request){
        $fields = $request->only(["modulo"]);
        $modulos_id = intval($fields["modulo"]);
        $modulos = Modulo::findOrFail($modulos_id);
        $SubModulosTemp = SubModulo::where("modulos_id", $modulos_id)->get()->toArray();
        $SubModulos = [];
        foreach($SubModulosTemp as $key=>$value){
            $SubModulos[] = [
                "id" => $value["id"],
                "nome" => $value["nome"]
            ];
        }
        return response()->json(['submodulos' => $SubModulos ]);
    }
}
