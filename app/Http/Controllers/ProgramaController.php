<?php

namespace App\Http\Controllers;

use App\Modulo;
use App\Programa;
use App\SubModulo;

use Auth;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\ProgramaRequest;

class ProgramaController extends Controller
{
    public static function returnDadosPrograma($model){

        $programa = Programa::where("model", $model)->first()->toArray();
        $dados = [
            "id" => $programa["id"],
            "nome" => $programa["nome"],
            "modulo" => [
                "nome" => "",
                "url" => "",
                "icon" => "icon"
            ],
            "submodulo" => []
        ];

        $modulo = Modulo::find($programa["modulos_id"])->toArray();
        $dados["modulo"]["nome"] = $modulo["nome"];
        $dados["modulo"]["url"] = route('modulo', ['modulo' => $modulo["url"]]);
        $dados["modulo"]["icon"] = Storage::url($modulo["icon"]);

        if(!empty(intval($programa["sub_modulos_id"]))){
            $sub_modulo = SubModulo::find($programa["sub_modulos_id"])->toArray();
            if(!empty($sub_modulo['sub_modulos_id'])){
                $sub_modulo_sub = SubModulo::find($sub_modulo['sub_modulos_id'])->toArray();
                $dados["submodulo"][] = [
                    "nome" => $sub_modulo_sub["nome"],
                    "url" => route('sub_modulo', ['modulo' => $modulo["url"], "submodulo" => $sub_modulo_sub["url"]]),
                    "icon" => (empty($sub_modulo_sub["icon"]) ? Storage::url($modulo["icon"]) : Storage::url($sub_modulo_sub["icon"]))
                ];
            }
            $dados["submodulo"][] = [
                "nome" => $sub_modulo["nome"],
                "url" => route('sub_modulo', ['modulo' => $modulo["url"], "submodulo" => $sub_modulo["url"]]),
                "icon" => (empty($sub_modulo["icon"]) ? Storage::url($modulo["icon"]) : Storage::url($sub_modulo["icon"]))
            ];
        }

        return $dados;

    }

    /**
     * Tela de cadastro inicial
     *
     * @return void
     */
    public function indexCadastro(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Programa") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Programa');
        $modulos = Modulo::all();
        $sub_modulos = SubModulo::all();
        return view('programs.programas.index')->with("modulos", $modulos)->with("sub_modulos", $sub_modulos);
    }

    /**
     * Filtro da listagem
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request){
        $fields = $request->only(['nome', "modulo", "submodulo"]);
        $where = [];
        if(!empty($fields['nome'])){
            $where[] = ['LOWER(nome)', 'like', strtolower($fields['nome'])];
        }
        if(!empty($fields["modulo"])){
            $where[] = ["modulos_id", $fields["modulo"]];
        }
        if(!empty($fields["submodulo"])){
            $where[] = ["sub_modulos_id", $fields["submodulo"]];
        }
        $query = DB::table("programas");
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
            $submodulo = SubModulo::find($result["sub_modulos_id"]);

            if(empty($modulo)){
                $return[$key]["modulo"] = "";
            }else{
                $return[$key]["modulo"] = $modulo->nome;
            }
            if(empty($submodulo)){
                $return[$key]["submodulo"] = "";
            }else{
                $return[$key]["submodulo"] = $submodulo->nome;
            }
            unset($modulo);
            unset($submodulo);
            $return[$key]["edit"] = "<a href=\"#\" data-route=\"".route('programas.edit',["id"=>$result["id"]])."\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
        }
        return response()->json($return);
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id) {
        $dados = Programa::findOrFail($id)->toArray();
        $imagem = Storage::url($dados["icon"]);
        $modulosTemp = Modulo::all();
        $modulos = [""=>""];
        foreach ($modulosTemp as $key => $value) {
            $modulos[$value["id"]] = $value["nome"];
        }
        $subModulosTemp = SubModulo::where("modulos_id", $dados["modulos_id"])->get()->toArray();
        $submodulos = [""=>""];
        foreach ($subModulosTemp as $key => $value) {
            $submodulos[$value["id"]] = $value["nome"];
        }
        return view('programs.programas.edit')->with('dados', $dados)->with('Imagem', $imagem)->with("modulos", $modulos)->with("sub_modulos", $submodulos);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(ProgramaRequest $request, $id) {
        $ProgramaObj = Programa::findOrFail($id);
        
        $ProgramaObj->nome = $request->nome;
        $ProgramaObj->modulos_id = $request->modulos_id;
        $ProgramaObj->sub_modulos_id = $request->sub_modulos_id;
        if(!empty($request->icon)){
            $icon = $request->icon;
            $path_file = $icon->storeAs("public/icons/programas",$icon->getClientOriginalName());
            $ProgramaObj->icon = $path_file;
        }

        $ProgramaObj->save();

        return response()->json(['saved' => $ProgramaObj ]);
    }

}
