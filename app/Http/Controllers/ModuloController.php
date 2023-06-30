<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modulo;
use App\Programa;
use App\SubModulo;

use App\User;
use Auth;
use Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\ModuloRequest;

use App\Mensagem;
use App\Politica;
use Carbon\Carbon;
use Dcblogdev\MsGraph\Facades\MsGraph;

class ModuloController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $user = Auth::user()->load('tipo_usuario');
        if(strtolower($user['tipo_usuario']['nome']) === 'coletor'){
            return redirect()->route('home_coletor');
        }
        $modulos = Modulo::where('url', '!=', 'coletor')->orderBy('nome')->get()->toArray();
        $modulos_return = [];
        foreach ($modulos as $key => $modulo) {
            if($modulo['url'] == 'diversos'){
                continue;
            }
            if(Modulo::where("url", $modulo["url"])->exists()){
                if(Auth::user()->hasPermissionTo("modulos ".$modulo["url"]) === false){
                    continue;
                }
            }
            $route = route('modulo', ['modulo' => $modulo["url"]]);
            if(!empty($modulo["link"])){
                $route = $modulo["link"];
            }
            $modulos_return[strtolower($modulo["nome"])] = [
                "nome" => $modulo["nome"],
                "icon" => Storage::url($modulo["icon"]),
                "route" => $route
            ];
        }
        $modulos_return['favoritos'] = [
            "nome" => 'Favoritos',
            "icon" => Storage::url('public/icons/modulo/favorito-icon.png'),
            "route" => route('favoritos.index')
        ];
        if(Auth::user()->hasPermissionTo("programas App\HistoricoDeVendas") === true){
            $modulos_return['2ViaNotaeBoleto'] = [
                "nome" => '2 Via Nota e Boleto',
                "icon" => Storage::url('public/icons/modulo/2ViaNotaeBoleto.png'),
                "route" => route('historico_vendas.index')
            ];
        }
        $PoliticaObj = Politica::where(function($query){
                $query->orWhereHas('perfils', function($query){
                    $query->where('perfil_id', Auth::user()->roles[0]->id);
                });
                $query->orDoesnthave('perfils');
            })->exists();
		if($PoliticaObj === true){
            $modulos_return['politicas'] = [
                "nome" => 'politicas',
                "icon" => Storage::url('public/icons/modulo/2ViaNotaeBoleto.png'),
                "route" => route('politica.index')
            ];
		}
        ksort($modulos_return);
        $mensagem = '';
        return view('home_modulos')->with(['modulos' => $modulos_return, 'mensagem' => $mensagem]);
    }

    public function indexMensagem(){
        $user = Auth::user()->load('tipo_usuario');
        if(strtolower($user['tipo_usuario']['nome']) === 'coletor'){
            return redirect()->route('home_coletor');
        }
        $modulos = Modulo::where('url', '!=', 'coletor')->orderBy('nome')->get()->toArray();
        $modulos_return = [];
        foreach ($modulos as $key => $modulo) {
            if($modulo['url'] == 'diversos'){
                continue;
            }
            if(Modulo::where("url", $modulo["url"])->exists()){
                if(Auth::user()->hasPermissionTo("modulos ".$modulo["url"]) === false){
                    continue;
                }
            }
            $route = route('modulo', ['modulo' => $modulo["url"]]);
            if(!empty($modulo["link"])){
                $route = $modulo["link"];
            }
            $modulos_return[strtolower($modulo["nome"])] = [
                "nome" => $modulo["nome"],
                "icon" => Storage::url($modulo["icon"]),
                "route" => $route
            ];
        }
        $modulos_return['favoritos'] = [
            "nome" => 'Favoritos',
            "icon" => Storage::url('public/icons/modulo/favorito-icon.png'),
            "route" => route('favoritos.index')
        ];
        if(Auth::user()->hasPermissionTo("programas App\HistoricoDeVendas") === true){
            $modulos_return['2ViaNotaeBoleto'] = [
                "nome" => '2 Via Nota e Boleto',
                "icon" => Storage::url('public/icons/modulo/2ViaNotaeBoleto.png'),
                "route" => route('historico_vendas.index')
            ];
        }
        $PoliticaObj = Politica::where(function($query){
                $query->orWhereHas('perfils', function($query){
                    $query->where('perfil_id', Auth::user()->roles[0]->id);
                });
                $query->orDoesnthave('perfils');
            })->exists();
		if($PoliticaObj === true){
            $modulos_return['politicas'] = [
                "nome" => 'politicas',
                "icon" => Storage::url('public/icons/modulo/2ViaNotaeBoleto.png'),
                "route" => route('politica.index')
            ];
		}
        ksort($modulos_return);
        $mensagem = '';
        $MensagemObj = Mensagem::where(function($query){
                $query->orWhereHas('tipoUsuarios', function($query){
                    $query->where('tipo_usuario_id', Auth::user()->tipo_usuario_id);
                });
                $query->orDoesnthave('tipoUsuarios');
            })->
            whereRaw("'".Carbon::today()->format('Y-m-d')."' between data_inicio and data_fim")->
            inRandomOrder()->
            first();
        if(!empty($MensagemObj)){
            $mensagem = Storage::url($MensagemObj->arquivo);
        }
        return view('home_modulos')->with(['modulos' => $modulos_return, 'mensagem' => $mensagem]);
    }

    /**
     * Tela de módulo
     *
     * @param int $modulo
     * @return void
     */
    public function indexModulo($modulo){
        if(Auth::user()->hasPermissionTo("modulos ".$modulo) === false){
            return abort(403);
        }
        $modulo = Modulo::where("url", $modulo)->firstOrFail()->toArray();
        $nome = $modulo["nome"];
        $icon = Storage::url($modulo["icon"]);
        $rota = route('modulo', ['modulo' => $modulo["url"]]);
        $programas = [];

        $programas_temp = Programa::where("modulos_id", $modulo["id"])->whereNull("sub_modulos_id")->orderBy('nome')->get()->toArray();
        $sub_modulos = SubModulo::where("modulos_id", $modulo["id"])->whereNull("sub_modulos_id")->orderBy('nome')->get()->toArray();
        foreach ($sub_modulos as $key => $value) {
            if(Permission::where("name","sub_modulos ".$value["url"])->first() === null){
                continue;
            }
            if(Auth::user()->hasPermissionTo("sub_modulos ".$value["url"]) === false){
                continue;
            }
            if(empty($value["icon"])){
                $value["icon"] = $icon;
            }else{
                $value["icon"] = Storage::url($value["icon"]);
            }
            $programas[] = [
                "nome" => $value["nome"],
                "icon" => $value["icon"],
                "route" => route('sub_modulo', ['modulo' => $modulo["url"], "submodulo" => $value["url"]])
            ];
        }
        foreach ($programas_temp as $key => $value) {
            if(Permission::where("name","programas ".$value["model"])->first() === null){
                continue;
            }
            if(Auth::user()->hasPermissionTo("programas ".$value["model"]) === false){
                continue;
            }
            if(!Route::has($value["route_index"])){
                continue;
            }
            $icon_temp = $value["icon"];
            if(empty($icon_temp)){
                $icon_temp = $icon;
            }else{
                $icon_temp = Storage::url($value["icon"]);
            }
            $programas[] = [
                "route" => route($value["route_index"]),
                "nome" => $value["nome"],
                "icon" => $icon_temp,
            ];
        }

        return view('modulo', ['programas'=> $programas, "nome" => $nome, "icon" => $icon, "rota" => $rota]);
    }

    /**
     * Tela de sub modulos
     *
     * @param int $modulo
     * @param int $submodulo
     * @return void
     */
    public function indexSubModulo($modulo, $submodulo){
        if(Auth::user()->hasPermissionTo("sub_modulos ".$submodulo) === false){
            return abort(403);
        }
        $submodulo = SubModulo::where("url", $submodulo)->firstOrFail()->toArray();
        $modulo = Modulo::where("url", $modulo)->first()->toArray();

        $submodulo["rota"] = route('sub_modulo', ['modulo' => $modulo["url"], "submodulo" => $submodulo["url"]]);
        $icon = $submodulo["icon"];
        $modulo["rota"] = route('modulo', ['modulo' => $modulo["url"]]);
        if(empty($icon)){
            $icon = Storage::url($modulo["icon"]);
        }else{
            $icon = Storage::url($icon);
        }
        $programas = [];

        $programas_temp = Programa::where("sub_modulos_id", $submodulo["id"])->orderBy('nome')->get()->toArray();
        $sub_modulos = SubModulo::where("sub_modulos_id", $submodulo["id"])->orderBy('nome')->get()->toArray();
        foreach ($sub_modulos as $key => $value) {
            if(Permission::where("name","sub_modulos ".$value["url"])->first() === null){
                continue;
            }
            if(Auth::user()->hasPermissionTo("sub_modulos ".$value["url"]) === false){
                continue;
            }
            if(empty($value["icon"])){
                $value["icon"] = $icon;
            }else{
                $value["icon"] = Storage::url($value["icon"]);
            }
            $programas[] = [
                "nome" => $value["nome"],
                "icon" => $value["icon"],
                "route" => route('sub_modulo', ['modulo' => $modulo["url"], "submodulo" => $value["url"]])
            ];
        }

        foreach ($programas_temp as $key => $value) {
            if(Permission::where("name","programas ".$value["model"])->first() === null){
                continue;
            }
            if(Auth::user()->hasPermissionTo("programas ".$value["model"]) === false){
                continue;
            }
            if(!Route::has($value["route_index"])){
                continue;
            }
            $icon_temp = $value["icon"];
            if(empty($icon_temp)){
                $icon_temp = $icon;
            }else{
                $icon_temp = Storage::url($value["icon"]);
            }
            $programas[] = [
                "route" => route($value["route_index"]),
                "nome" => $value["nome"],
                "icon" => $icon_temp,
            ];
        }

        return view('sub_modulo', [ 'programas' => $programas, "icon" => $icon, "modulo" => $modulo, "submodulo" => $submodulo ]);
    }

    /**
     * Tela de cadastro inicial
     *
     * @return void
     */
    public function indexCadastro(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Modulo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Modulo');
        return view('programs.modulos.index');
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
            $where[] = ['LOWER(nome)', 'like', strtolower($fields['nome'])];
        }
        $query = DB::table("modulos");
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
            $return[$key]["edit"] = "<a href=\"#\" data-route=\"".route('modulos.edit',["id"=>$result["id"]])."\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
            $return[$key]["delete"] = "<a href=\"#\" data-route=\"".route('modulos.destroy',["id"=>$result["id"]])."\" data-nome=\"{$result["nome"]}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
        }
        return response()->json($return);
    }


    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create() {
        return view('programs.modulos.create');
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(ModuloRequest $request) {
        $icon = $request->icon;
        $name = $request->url.".".$icon->getClientOriginalExtension();
        $path_file = $icon->storeAs("public/icons/modulo", $name);
        
        $ModuloObj = new Modulo;
        $ModuloObj->nome = $request->nome;
        $ModuloObj->url = $request->url;
        $ModuloObj->icon = $path_file;
        $ModuloObj->save();

        $permission = new Permission();
        $permission->name = "modulos ".$request->url;
        $permission->save();

        return response()->json(['saved' => $ModuloObj ]);
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($codcad) {
        $dados = Modulo::findOrFail($codcad)->toArray();
        $imagem = Storage::url($dados["icon"]);
        return view('programs.modulos.edit')->with('dados', $dados)->with('Imagem', $imagem);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(ModuloRequest $request, $codcad) {
        $ModuloObj = Modulo::findOrFail($codcad);
        
        $permission = Permission::where("name", "modulos ".$ModuloObj->url)->first();
        $permission->name = "modulos ".$request->url;
        $permission->save();

        $ModuloObj->nome = $request->nome;
        $ModuloObj->url = $request->url;
        if(!empty($request->icon)){
            $icon = $request->icon;
            $path_file = $icon->storeAs("public/icons/modulo",$icon->getClientOriginalName());
            $ModuloObj->icon = $path_file;
        }

        $ModuloObj->save();

        return response()->json(['saved' => $ModuloObj ]);
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($codcad) {
        $ModuloObj = Modulo::findOrFail($codcad); 
        Storage::delete($ModuloObj->icon);
        Permission::where("name", "modulos ".$ModuloObj->url)->delete();
        $ModuloObj->delete();

        return response()->json(['saved' => $ModuloObj ]);
    }
}
