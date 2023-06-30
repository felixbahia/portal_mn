<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\TipoUsuario;
use App\Vendedor;
use App\VendedorNasajon;
use App\UserNajason;
use App\ClienteNasajon;
use App\CepEndereco;
use App\ResetSenha;

use Auth;
use App\Http\Requests\UserRequest;
use App\Http\Requests\UserEditarDadosRequest;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Session;

use Illuminate\Support\Facades\DB;

use Adldap\Laravel\Facades\Adldap;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

use Carbon\Carbon;

class UserController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(['auth'])->except('getPhoto');
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\User") === false){
            return abort(403);
        }
        $tipos = TipoUsuario::orderBy('nome')->get();
        $tipos_view = [];

        foreach($tipos as $value){
            $tipos_view[$value->id] = $value["nome"];
        }
        $representantes = [];
        $VendedorObj = VendedorNasajon::select('codigo')->orderBy('codigo')->get()->toArray();
        foreach ($VendedorObj as $key => $value) {
            $representantes[$value["codigo"]] = $value["codigo"];
        }
        unset($VendedorObj);


        $request->session()->flash('model', 'App\User');
        return view('programs.users.index')->with('tipos', $tipos_view)->with('vendedor', $representantes);
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create() {
        $tipos = TipoUsuario::orderBy('nome')->get(); 
        $tipos_view = [
            '' => ''
        ];
        foreach($tipos as $value){
            $tipos_view[$value->id] = $value["nome"];
        }

        $roles_array = Role::select('id', 'name')->get()->toArray();        
        $roles = [];

        foreach ($roles_array as $value) {
            $roles[$value['id']] = $value['name'];
        }
        asort($roles);
        $representantes = [""=>""];
        $VendedorObj = VendedorNasajon::select('codigo')->orderBy('codigo')->get()->toArray();
        foreach ($VendedorObj as $key => $value) {
            $representantes[$value["codigo"]] = $value["codigo"];
        }
        unset($VendedorObj);

        $supervisores_array = User::where('tipo_usuario_id',13)->select('id','name')->orderBy('name')->get()->toArray();
        $supervisores = [];

        foreach($supervisores_array as $supervisor){
            $supervisores[encrypt($supervisor['id'])] = $supervisor['name'];
        }
        
        unset($supervisores_array);

        $UserNajasonObj = UserNajason::orderBy('nome')->get();
        $usuarios_nasajon = [""=>""];
        foreach ($UserNajasonObj as $key => $user) {
            $usuarios_nasajon[$user->usuario] = ucwords(strtolower($user->nome));
        }

        $instituicoes = $this->getInstituicao();

        return view('programs.users.create', ['supervisores' => $supervisores,'tipos' => $tipos_view, 'roles' => $roles, 'representantes' => $representantes, 'usuarios_nasajon' => $usuarios_nasajon, 'instituicoes' => $instituicoes]);
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(UserRequest $request) {
        $fields = $request->only('folha_matricula', 'folha_lotacao', 'acrescimo_objetivo','supervisores');
        
        $UserObj = new User;
        $UserObj->name = $request->name;
        $UserObj->username = $request->username;
        $UserObj->email = strtolower($request->email);
        $UserObj->setor = $request->setor;
        $UserObj->celular = $request->celular;
        $UserObj->regiao_atuacao = $request->regiao_atuacao;
        $UserObj->tipo_usuario_id = $request->tipo_usuario_id;
        $UserObj->empresa_padrao_id = $request->empresa_padrao_id;
        $UserObj->cliente_padrao_id = $request->cliente_padrao_id;
        $UserObj->password = "";
        $UserObj->responsavel = $request->responsavel;
        $UserObj->codigo_representante = $request->codigo_representante;

        $UserObj->comissao_a = str_replace(",",".",$request->comissao_a);
        $UserObj->comissao_b = str_replace(",",".",$request->comissao_b);
        $UserObj->comissao_c = str_replace(",",".",$request->comissao_c);

        $UserObj->codigo_nasajon = $request->codigo_nasajon;

        if(!empty($fields['folha_matricula'])){
            $UserObj->folha_matricula = $fields['folha_matricula'];
        }
        if(!empty($fields['folha_lotacao'])){
            $UserObj->folha_lotacao = $fields['folha_lotacao'];
        }

        if(!empty($fields['acrescimo_objetivo'])){
            $UserObj->acrescimo_objetivo = str_replace(",",".",$fields['acrescimo_objetivo']);
        }else{
            $UserObj->acrescimo_objetivo = $fields['acrescimo_objetivo'];
        }

        if($request->tipo_usuario_id == 12){
            $verifica_representante = User::where('codigo_representante',$request->codigo_representante)
            ->where('email',strtolower($request->email))
            ->first();
            
            if(empty($verifica_representante)){
                $resetSenha = new ResetSenha;

                $resetSenha->user = $request->username;
                $resetSenha->hash = Crypt::encrypt($request->username . Carbon::now()->format('Y-m-d h:i:s'));

                $resetSenha->save();

                $emailControllerObj = new EmailController;
        
                $mail_result = $emailControllerObj->sendEmailToken('00', 'cadastro:novo_representante', [$request->email], ['nome' => $request->name, 'usuario' => $request->username, 'link' => route('representante.trocar_senha', ['hash' => $resetSenha->hash])]);
            }
        }
        
        if(!empty($fields['supervisores'])){
            try {
                $id_supervisor = Crypt::decrypt($fields['supervisores']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json([
                    "status" => "error",
                    'error' => ['supervisor' => 'Supervisor inválido'], 
                    "message" => "Supervisor inválido",
                    "response" => []
                ], 422);  
            }

            $UserObj->supervisor_id = $id_supervisor;
        }
        
        $UserObj->save();

        $UserObj->assignRole(Role::find($request->role));

        return response()->json(['saved' => $UserObj ]);
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit(Request $request) {
        $dados = User::findOrFail($request->id);
        $tiposObj = TipoUsuario::orderBy('nome')->get(); 
        $tipos = [
            '' => ''
        ];

        foreach($tiposObj as $value){
            $tipos[$value->id] = $value["nome"];
        }

        $dados->role_id = isset($dados->roles()->pluck('id')->toArray()[0])?$dados->roles()->pluck('id')->toArray()[0]:'';
        
        $roles_array = Role::select('id', 'name')->get()->toArray();        
        $roles = [];

        foreach ($roles_array as $value) {
            $roles[$value['id']] = $value['name'];
        }
        $responsaveis = [""=>""];
        asort($roles);
        $responsaveis_temp = $this->filterUserResponsavel(new Request(["user"=>$dados->id, "tipo_usuario"=>$dados->tipo_usuario_id]));
        $responsaveis_temp = (array) json_decode($responsaveis_temp->content());
        if($responsaveis_temp["status"] === "success"){
            $responsaveis_temp = $responsaveis_temp["data"];
            if($responsaveis_temp){
                foreach ($responsaveis_temp as $key => $value) {
                    $value = (array) $value;
                    $responsaveis[$value["id"]] = $value["name"]." - ".$value["tipo_usuario"];
                }
            }
        }

        $representantes = [""=>""];
        $VendedorObj = VendedorNasajon::select('codigo')->orderBy('codigo')->get()->toArray();
        foreach ($VendedorObj as $key => $value) {
            $representantes[$value["codigo"]] = $value["codigo"];
        }
        unset($VendedorObj);

        $UserNajasonObj = UserNajason::orderBy('nome')->get();
        $usuarios_nasajon = [""=>""];
        foreach ($UserNajasonObj as $key => $user) {
            $usuarios_nasajon[$user->usuario] = ucwords(strtolower($user->nome));
        }

        $instituicoes = $this->getInstituicao();

        $supervisores_array = User::where('tipo_usuario_id',13)->select('id','name')->orderBy('name')->get()->toArray();
        $supervisores = [];
        $supervisor_atual = '';

        foreach($supervisores_array as $supervisor){
            $crypt_id = encrypt($supervisor['id']);
            if($dados->supervisor_id == $supervisor['id']){
                $supervisor_atual = $crypt_id;
            }
            $supervisores[$crypt_id] = $supervisor['name'];
        }

        unset($supervisores_array);

        return view('programs.users.edit', compact('dados', 'tipos', 'roles', 'responsaveis', 'representantes', 'usuarios_nasajon', 'instituicoes','supervisores','supervisor_atual'));
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(UserRequest $request, $id) {
        $user = User::findOrFail($id);
        
        $input = $request->only(['name', 'username', 'email', 'setor', 'celular', 'regiao_atuacao', 'tipo_usuario_id', 'empresa_padrao_id', 'cliente_padrao_id', 'role', 'responsavel', 'codigo_representante', 'comissao_a', 'comissao_b', 'comissao_c', 'codigo_nasajon', 'folha_matricula', 'folha_lotacao', 'acrescimo_objetivo','supervisores']);
        $input["email"] = strtolower($input["email"]);

        $input['comissao_a'] = str_replace(",",".",$input['comissao_a']);
        $input['comissao_b'] = str_replace(",",".",$input['comissao_b']);
        $input['comissao_c'] = str_replace(",",".",$input['comissao_c']);
        if(!empty($input['acrescimo_objetivo'])){
            $input['acrescimo_objetivo'] = str_replace(",",".",$input['acrescimo_objetivo']);
        }
        
        $roles = $user->roles()->pluck('name');
        
        foreach ($roles as $key => $value) {
            $user->removeRole($value);
        }

        if(!empty($input['supervisores'])){
            try {
                $input['supervisor_id'] = Crypt::decrypt($input['supervisores']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json([
                    "status" => "error",
                    'error' => ['supervisor' => 'Supervisor inválido'], 
                    "message" => "Supervisor inválido",
                    "response" => []
                ], 422);  
            }
        }
       
        $user->fill($input);
        $user->save();
        $user->assignRole(Role::find($input['role']));
        return response()->json(['saved' => $user ]);
    }

    public function formExcluir(Request $request){
        $usuario = User::findOrFail($request->id)->toArray();

        $usuario['tipo'] = TipoUsuario::find($usuario['tipo_usuario_id']);

        return view('programs.users.destroy', compact('usuario'));
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy(Request $request) {
    //Find a user with a given id and delete
        $user = User::findOrFail($request->id); 
        $user->delete();

        return response()->json($user);
    }

    /**
     * Filtro da listagem
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request){
        $fields = $request->only(['nome', 'email', 'setor', 'tipo', 'empresa_padrao', 'cliente_padrao', 'codigo_vendedor']);
        $where = [];
        if(!empty($fields['nome'])){
            $where[] = ['LOWER(users.name)', 'like', strtolower($fields['nome'])];
        }
        if(!empty($fields['email'])){
            $where[] = ['email', 'like', $fields['email']];
        }
        if(!empty($fields['setor'])){
            $where[] = ['setor', 'like', $fields['setor']];
        }
        if(!empty($fields['tipo'])){
            $where[] = ['tipo_usuario_id', $fields['tipo']];
        }
        if(!empty($fields['empresa_padrao'])){
            $where[] = ['empresa_padrao_id', $fields['empresa_padrao']];
        }
        if(!empty($fields['cliente_padrao'])){
            $where[] = ['cliente_padrao_id', $fields['cliente_padrao']];
        }
        if(!empty($fields['codigo_vendedor'])){
            $where[] = ['codigo_representante', $fields['codigo_vendedor']];
        }
        $empresa_padrao = returnEmpresasNasajonView();
        $query = DB::table("users")->select('users.id', 'users.email', 'users.name', 'tipo_usuarios.nome as tipo_usuario', 'users.setor', 'users.empresa_padrao_id as empresa_padrao', 'users.cliente_padrao_id as cliente_padrao', 'responsavel', 'codigo_representante')->leftJoin('tipo_usuarios', 'tipo_usuarios.id', '=', 'users.tipo_usuario_id');
        foreach($where as $value){
            if(count($value) === 3){
                $query->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
            } else {
                $query->where($value[0], $value[1]);
            }
        }
        $query->whereNull("users.deleted_at");
        $query = $query->get();
        $results_user = $query->toArray();
        $key = 0;
        $results = [];
        foreach($results_user as $result){
            $result = (array) $result;
            if($result['id'] === 1){
                continue;
            }
            foreach($result as $k => $value){
                if(empty($value)){
                    $results[$key][$k] = " ";
                }else{
                    $results[$key][$k] = $value;
                }
            }
            $results[$key]['perfil_nome'] = User::find($result['id'])->roles()->pluck('name');
            $responsavel = "";
            $responsavel_temp = User::where("id", $result['responsavel'])->with(["tipo_usuario"])->first();
            if(!is_null($responsavel_temp)){
                $responsavel = $responsavel_temp->tipo_usuario->nome. " - ".$responsavel_temp->name;
                unset($responsavel_temp);
            }
            $results[$key]['responsavel'] = $responsavel;
            $results[$key]["codigo_representante"] = $result["codigo_representante"];
            $results[$key]["edit"] = "<a href=\"#\" data-id=\"".$result['id']."\" data-route=\"".route('usuario.edit')."\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
            if ($result['id'] != Auth::user()->id){
                $results[$key]["delete"] = "<a href=\"#\" data-id=\"".$result['id']."\" data-route=\"".route('usuario.formExcluir')."\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";            
            }
            else{
                $results[$key]['delete'] = "<i class='fas fa-ban' data-toggle=\"tooltip\" data-placement=\"top\" title=\"Você não pode se excluir\"></i>";
            }
            $results[$key]["login"] = "<a href=\"#\" data-id=\"".Crypt::encrypt($result["id"])."\" data-route=\"".route('usuario.logar')."\" class=\"bt-login\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Logar como Usuario\"><i class=\"fas fa-user\" style=\"color: #000;text-align: center;font-size: 14px;width: 30px;\"></i></a>";
            $key++;
        }
        return json_encode($results);
    }
    

    public function filterRole(Request $request){
        $fields = $request->only(['nome', 'email', 'setor', 'tipo', 'cliente', 'role_id']);
        $where = [];
        if(!empty($fields['nome'])){
            $where[] = ['LOWER(users.name)', 'like', strtolower($fields['nome'])];
        }
        if(!empty($fields['email'])){
            $where[] = ['email', 'like', $fields['email']];
        }
        if(!empty($fields['setor'])){
            $where[] = ['setor', 'like', $fields['setor']];
        }
        if(!empty($fields['tipo'])){
            $where[] = ['tipo_usuario_id', $fields['tipo']];
        }
        $query = DB::table("users")->select('users.id', 'users.email', 'users.name', 'tipo_usuarios.nome as tipo_usuario', 'users.setor', 'users.empresa_padrao_id as empresa_padrao', 'users.cliente_padrao_id as cliente_padrao', 'model_has_roles.*')->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')->leftJoin('tipo_usuarios', 'tipo_usuarios.id', '=', 'users.tipo_usuario_id');
        foreach($where as $value){
            if(count($value) === 3){
                $query->whereRaw("{$value[0]} {$value[1]} '%".trim(strtolower($value[2]))."%'");
            } else {
                $query->where($value[0], $value[1]);
            }
        }
        $query->whereNull("users.deleted_at");
        $query = $query->get();
        $results_user = $query->toArray();

        $usuarios_roles = [];
        $roles_users = DB::table('model_has_roles')->where('role_id', $fields["role_id"])->get()->toArray();
        foreach($roles_users as $value){
            $usuario_temp = User::where("id", $value->model_id)->first()->toArray();
            $usuarios_roles[] = $usuario_temp["id"];
        }

        $key = 0;
        foreach($results_user as $result){
            $result = (array) $result;
            if($result['id'] === 1){
                continue;
            }
            if(in_array($result['id'], $usuarios_roles)){
                continue;
            }
            foreach($result as $k => $value){
                if(empty($value)){
                    $results[$key][$k] = " ";
                }else{
                    $results[$key][$k] = $value;
                }
            }
            if(!empty($result["role_id"])){
                $results[$key]["role_exist"] = "1";
            } else {
                $results[$key]["role_exist"] = "0";
            }
            $key++;
        }
        return json_encode($results);
    }
    /**
     * Alteração de EmpresaPadrão de Usuário logado
     *
     * @param Request $request
     * @return void
     */
    public function empresaPadraoUpdate(Request $request){
        $fields = $request->only(["empresa"]);
        $empresa_padrao = intval($fields["empresa"]);
        $empresas = returnEmpresasNasajonView();
        if(!array_key_exists($empresa_padrao, $empresas)){
            return response()->json(["status"=>"error"]);
        }

        Auth::user()->fill(["empresa_padrao_id"=>$empresa_padrao])->update();
        return response()->json(["status"=>"success"]);
    }
    

    public function filterAjax(){
        $tipos = TipoUsuario::orderBy('nome')->get();
        $tipos_view = [];
        foreach($tipos as $value){
            $tipos_view[$value->id] = $value["nome"];
        }
        return view('programs.users.filter-ajax')->with('tipos', $tipos_view);
    }

    /**
    * Show the form
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id) {
        $dados = User::findOrFail($id);
        $tiposObj = TipoUsuario::orderBy('nome')->get();
        foreach($tiposObj as $value){
            $tipos[$value->id] = $value["nome"];
        }
        return view('programs.users.view', compact('dados', 'tipos'));
    }

    public function filterUserResponsavel(Request $request){
        $fields = $request->only(["user", "tipo_usuario"]);
        $TipoUsuarioObj = TipoUsuario::find($fields["tipo_usuario"]);
        
        $user = "";
        if(isset($fields["user"])){
            $user = User::find($fields["user"]);
            $user = $user->id;
        }
        if((int) $TipoUsuarioObj->nivel === 0){
            $return = [
                "status" => "error",
                "message" => ""
            ];
        }else{
            $representantes = [];
            if(strtolower($TipoUsuarioObj->nome) == "representante" || strtolower($TipoUsuarioObj->nome) == "supervisor"){
                $representantes = [];
                $VendedorObj = VendedorNasajon::select('codigo')->orderBy('codigo')->get()->toArray();
                foreach ($VendedorObj as $key => $value) {
                    $representantes[] = $value["codigo"];
                }
                unset($VendedorObj);
            }

            if($TipoUsuarioObj->id = 13){
                $nivel = 2;
            }else{
                $nivel = $TipoUsuarioObj->nivel;
            }

            $TipoUsuarioObj = TipoUsuario::select("id")->where("id", "<>", $TipoUsuarioObj->id)->where("nivel", "<", $nivel)->get()->toArray();
            $TipoUsuario = [];
            foreach ($TipoUsuarioObj as $key => $value) {
                $TipoUsuario[] = (int) $value["id"];
            }
            if(isset($fields["user"])){
                $users = User::with(["tipo_usuario"])->whereIn("tipo_usuario_id", $TipoUsuario)->where("id", "<>", $user)->orderBy('name')->get()->toArray();
            }else{
                $users = User::with(["tipo_usuario"])->whereIn("tipo_usuario_id", $TipoUsuario)->orderBy('name')->get()->toArray();
            }
            $return_users = [];
            foreach ($users as $key => $value) {
                $return_users[] = [
                    "id" => $value["id"],
                    "name" => $value["name"],
                    "tipo_usuario" => $value["tipo_usuario"]["nome"],
                ];
            }
            if(!empty($representantes)){
                $return = [
                    "status" => "success",
                    "data" => $return_users,
                    "representantes" => $representantes
                ];
            }else{
                $return = [
                    "status" => "success",
                    "data" => $return_users
                ];
            }
        }
        return response()->json($return);
    }

    static public function varreSubordinados($id){

        $array[] = $id;

        $userObj = User::with('subordinados')->find($id);

        if(!empty($userObj)){
            foreach ($userObj->subordinados as $value){

                $array2 = UserController::varreSubordinados($value->id);
    
                if (is_array($array2)){
                    $array = array_merge($array, $array2);
                }
            }
        }

        return $array;

    }

    public function getPhoto(Request $request, $id){
        $id = Crypt::decryptString($id);
        $user = User::find($id);
        header("Content-type: image");
        return Storage::download($user->photo);
    }

    public function getDadosSubordinados(Request $request){
        $field = $request->only(['user']);
        $response = [];
        if(!empty($field['user'])){
            $user = Crypt::decrypt($field['user']);
            $user = User::find($user);
            $user_get = User::with(['tipo_usuario'])->where('responsavel', $user->id)->whereHas('tipo_usuario', function($query) use ($user) {
                if(strtolower($user->tipo_usuario->nome) === "gerente comercial"){
                    $query->where('nivel', 3);
                }else{
                    $query->where('nivel', $user->tipo_usuario->nivel + 1);
                }
            })->orderBy('name')->get()->toArray();
            $response = [];
            foreach ($user_get as $key => $user_query) {
                $response[] = [
                    "name" => $user_query['name'],
                    "id" => Crypt::encrypt($user_query['id'])];
            }
        }else{
            $user_get = User::whereNotNull('codigo_representante')->orderBy('name')->get()->toArray();
            foreach ($user_get as $key => $user_query) {
                $response[] = [
                    "name" => $user_query['name'],
                    "id" => Crypt::encrypt($user_query['id'])];
            }
        }
        unset($user_get);
        if(count($response) > 0){
            return response()->json([
                "status" => "success",
                "message" => "",
                "response" => $response
            ]);
        }else{
            return response()->json([
                "status" => "error",
                "message" => "Nenhum registro encontrado"
            ]);
        }
    }

    function retornaGerentesEVendedores(Request $request){

        $diretor = $request->diretor;

        if (empty($diretor)){
            $users = User::all();
        }
        else{

            $array = $this->varreSubordinados($diretor);

            $users = User::whereIn('id', $array)->get();
            
        }


        $gerentes = [];
        $vendedores = [];

        foreach ($users as $value) {

            if ($value->tipo_usuario_id == 14){
                $gerentes[$value->id] = $value->name;
            }

            if (!empty($value->codigo_representante)){
                $vendedores[$value->id] = $value->name;
            }
        }
        
        return response()->json(['gerentes' => $gerentes, 'vendedores' => $vendedores]);

    }

    function retornaVendedores(Request $request){

        $gerente = $request->gerente;

        if (empty($gerente)){
            $users = User::all();
        }
        else{
            $array = $this->varreSubordinados($gerente);
            $users = User::whereIn('id', $array)->get();
        }


        $vendedores = [];

        foreach ($users as $value) {

            if (!empty($value->codigo_representante)){
                $vendedores[$value->id] = $value->name;
            }
        }

        return response()->json(['vendedores' => $vendedores]);

    }

    public static function validaAgenteVenda($codigo_representante){
        $user = User::where('codigo_representante', $codigo_representante)->first();
        if(!empty($user) && isset($user->tipo_usuario_id)){
            if($user->tipo_usuario_id == 22){
                return $user->id;
            }
        }
        return false;
    }

    public static function validaPromotorVenda($codigo_representante){
        $user = User::where('codigo_representante', $codigo_representante)->first();
        if(!empty($user) && isset($user->tipo_usuario_id)){
            if($user->tipo_usuario_id == 23){
                return $user->id;
            }
        }
        return false;
    }

    public function autoComplete(Request $request){
        $fields = $request->only(["term"]);
        $return = [];

        $query = User::select('name')->withTrashed()
            ->limit("15")
            ->orderBy('name', "ASC")
            ->where("name",  'ilike', "%". $fields["term"] ."%")
            ->whereNotIn('tipo_usuario_id', [17, 21])
            ->get()
            ->toArray();

        foreach ($query as $value){
            $value = (array) $value;
            $return[] = $value['name'];
        }
        
        return response()->json($return);
    }

    public function autoCompleteCodigoRepresentante(Request $request){
        $fields = $request->only(["term"]);
        $return = [];

        $query = User::select('codigo_representante','name')->withTrashed()
            ->limit("15")
            ->orderBy('codigo_representante', "ASC")
            ->whereRaw("TRIM(CONCAT(TRIM(codigo_representante), ' - ', name)) ilike '%".trim($fields['term'])."%'")
            ->whereNotIn('tipo_usuario_id', [17, 21])
            ->get()
            ->toArray();
        foreach ($query as $value){
            $value = (array) $value;
            $return[] = $value['codigo_representante'].' - '.$value['name'];
        }
        
        return response()->json($return);
    }

    public function modalBuscar(){
        $tipos = TipoUsuario::orderBy('nome')->get();
        $tipos_view = [];
        foreach($tipos as $value){
            $tipos_view[$value->id] = $value["nome"];
        }
        return view('programs.users.buscar')->with('tipos', $tipos_view);
    }

    public function filtroBuscar(Request $request){
        $fields = $request->only(['nome', 'email', 'setor', 'tipo']);

        $query = User::select()->withTrashed()->with(['tipo_usuario']);
        if(!empty($fields['nome'])){
            $query->where('name', 'ilike', '%'.$fields['nome'].'%');
        }
        if(!empty($fields['email'])){
            $query->where('email', 'ilike', '%'.$fields['email'].'%');
        }
        if(!empty($fields['setor'])){
            $query->where('setor', 'ilike', '%'.$fields['setor'].'%');
        }
        if(!empty($fields['tipo'])){
            $query->where('tipo_usuario_id', $fields['tipo']);
        }
        $query->whereNotIn('tipo_usuario_id', [17, 21]);
        $result = $query->get();
        
        $usuarios = [];
        foreach($result as $usuario){
            $usuarios[] = [
                "nome" => $usuario->name,
                "email" => $usuario->email,
                "setor" => $usuario->setor,
                "codigo_representante" => empty($usuario->codigo_representante)? '' : $usuario->codigo_representante,
                "tipo_usuario" => $usuario->tipo_usuario->nome,
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $usuarios
        ];
        return response()->json($response);
    }

    public function getDadosSubordinadosOutros(Request $request){
        $field = $request->only(['user']);
        $response = [];

        if(isset($field['user'])){
            try {
                $user = Crypt::decrypt($field['user']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json([
                    "status" => "error",
                    'error' => ['gerentes' => 'Gerente inválido'], 
                    "message" => "Gerente inválido",
                    "response" => []
                ], 422);  
            }
        }
        
        if(isset($user) && $user === 0){
            $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

            $user_get = User::with(['tipo_usuario'])
                ->whereNotNull('codigo_representante')
                ->where(function ($query) use($gerentes){
                    $query->whereNotIn('responsavel', $gerentes)
                        ->orWhereDoesntHave('tipo_usuario', function($query){
                            $query->where('nivel', 3);
                        });
                })
                ->orderBy('name')->get()->toArray();

            $response = [];
            foreach ($user_get as $key => $user_query) {
                $response[] = [
                    "name" => $user_query['name'],
                    "id" => Crypt::encrypt($user_query['id'])];
            }
            unset($user_get);
        }
        else{
            if(!empty($field['user'])){
                $user = Crypt::decrypt($field['user']);
                $user = User::find($user);
                $user_get = User::with(['tipo_usuario'])->where('responsavel', $user->id)->whereHas('tipo_usuario', function($query) use ($user) {
                    if(strtolower($user->tipo_usuario->nome) === "gerente comercial"){
                        $query->where('nivel', 3);
                    }else{
                        $query->where('nivel', $user->tipo_usuario->nivel + 1);
                    }
                })->orderBy('name')->get()->toArray();
                
                $response[] = [
                    "name" => $user->name,
                    "id" => Crypt::encrypt($user->id)];
                    
                foreach ($user_get as $key => $user_query) {
                    $response[] = [
                        "name" => $user_query['name'],
                        "id" => Crypt::encrypt($user_query['id'])];
                }

            }else{
                $user_get = User::whereNotNull('codigo_representante')->orderBy('name')->get()->toArray();
                foreach ($user_get as $key => $user_query) {
                    $response[] = [
                        "name" => $user_query['name'],
                        "id" => Crypt::encrypt($user_query['id'])];
                }
            }
        }
        if(count($response) > 0){
            return response()->json([
                "status" => "success",
                'error' => [],
                "message" => "Vendedores recuperados com sucesso",
                "response" => $response
            ], 220);
        }else{
            return response()->json([
                "status" => "error",
                'error' => ['gerentes' => 'Nenhum vendedor encontrado para este gerente'], 
                "message" => "Nenhum registro encontrado",
                "response" => []
            ], 422);
        }
    }

    public function logarId(Request $request){
        if(Auth::user()->tipo_usuario_id === 1){
            $filtro = $request->only(['usuario']);
            $usuario = $filtro['usuario'];
            $usuario = Crypt::decrypt($filtro['usuario']);
            Auth::loginUsingId($usuario);
            return redirect()->route('home');
        }else{
            return redirect()->route('home');
        }
    }

    public function getInstituicao(){
        $instituicoes = [];
        $instituicoes['0000005'] = '5 - TEXTIL MN COM TECIDOS E CONF LTDA';
        $instituicoes['0000001'] = '25-IDARA SERVIÇOS ADMINISTRATIVOS EIRELI';

        return $instituicoes;
    }

    public function editarDados(){
        $user = Auth::user();
        $dados = ['email' => ''];
        $dados['email'] = $user->email;
        $dados['telefone'] = $user->celular;
        $dados['telefone_emergencia'] = $user->telefone_emergencia;
        $dados['contato_emergencia'] = $user->contato_emergencia;
        if($user->tipo_usuario_id == 21){
            $ClienteNasajonObj = ClienteNasajon::where(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $user->username)->first();
            $dados['email'] = $ClienteNasajonObj->email;
            $dados['telefone'] = $ClienteNasajonObj->telefones;
        }
        if($user->tipo_usuario_id == 12){
            $dados['usar_carteira'] = $user->usar_clientes_carteira;
        }
        $dados['email_pessoal'] = $user->email_pessoal;
        return view('programs.users.editar_dados')->with(['dados' => $dados]);
    }

    public function salvarEditarDados(UserEditarDadosRequest $request){
        $fields = $request->only('email', 'telefone', 'usar_carteira', 'telefone_emergencia', 'contato_emergencia', 'contato_emergencia','email_pessoal');
        $user = Auth::user();
        
        if($user->tipo_usuario_id == 21){
            $UserObj = User::find(Auth::id());
            $ClienteNasajonObj = ClienteNasajon::where(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $user->username)->first();
            $enderecoObj = CepEndereco::with('cidadeBusca')->where('cep', str_replace('-', '', $ClienteNasajonObj->cep))->first();

            $cidade = $enderecoObj->cidadeBusca->cidade;
            $cidade = str_replace("'", "", $cidade);

            $telefone = '';
            $ddd = '';
            if(!empty($fields['telefone'])){
                $re = '/(\([0-9]{2}\)\ ).+/';
                
                preg_match_all($re, $fields['telefone'], $result_regex);
                if(!empty($result_regex) && isset($result_regex[1])){
                    $result_regex = reset($result_regex[1]);
                    $ddd = trim(str_replace(['(', ')'],'',$result_regex));
                    $telefone = str_replace($result_regex,'', $fields['telefone']);
                }

                $UserObj->telefone_emergencia = $telefone;
            }
            
            $UserObj->contato_emergencia = $fields['contato_emergencia'];
            $UserObj->updated_at = Carbon::now();
            $UserObj->save();
            try {
                $result = DB::connection('nasajon')->select("SELECT * from integracoes.api_clientealterar(
                    '". $ClienteNasajonObj->id ."',
                    '". $ClienteNasajonObj->nome ."',
                    '". $ClienteNasajonObj->nomefantasia ."',
                    '". $ClienteNasajonObj->cpf_cnpj ."',
                    '". $ClienteNasajonObj->inscricaoestadual ."',
                    '',
                    '". $fields['email'] ."',
                    0,
                    '". $ClienteNasajonObj->tipo_logradouro ."',
                    '". $ClienteNasajonObj->logradouro ."',
                    '". $ClienteNasajonObj->numero ."',
                    '". $ClienteNasajonObj->complemento ."',
                    '". $ClienteNasajonObj->cep ."',
                    '". $ClienteNasajonObj->bairro ."',
                    '". $ClienteNasajonObj->uf ."',
                    '". $enderecoObj->cidadeBusca->cod_ibge ."',
                    '". $cidade ."',
                    '',
                    '". $ddd ."',
                    '". $telefone ."',
                    '". $ClienteNasajonObj->indicadorinscricaoestadual ."'
                )");

            } catch (\Illuminate\Database\QueryException $th) {

                return response()->json([
                    'status' => 'success',
                    'message' => 'Erro na transação!',
                    'error' => [ 
                        "mensagem" => $th->getMessage()
                    ],
                    'response' => []
                ], 422);
            }

            $result = json_decode($result[0]->mensagem);
            
            $mensagem = $result->mensagem;

            if($result->codigo == 'OK'){
        
                return response()->json([
                    'status' => 'success',
                    'message' => 'Dados atualizados com sucesso!',
                    'error' => [],
                    'response' => []
                ], 200);
            }
            else{
                return response()->json([
                    'status' => 'erro',
                    'message' => 'Ocorreu um erro!',
                    'error' => [
                        "mensagem" => $mensagem
                    ],
                    'response' => []
                ], 422);
            }
        }else{
            $UserObj = User::find(Auth::id());
            $UserObj->email = $fields['email'];
            $UserObj->celular = $fields['telefone'];
            $UserObj->contato_emergencia = $fields['contato_emergencia'];
            $UserObj->telefone_emergencia = $fields['telefone_emergencia'];
            if($user->tipo_usuario_id == 12){
                $UserObj->usar_clientes_carteira = isset($fields['usar_carteira']) ? true : false;
            }
            $UserObj->updated_at = Carbon::now();
            $UserObj->email_pessoal = $fields['email_pessoal'];
            $UserObj->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Dados atualizados com sucesso!',
                'error' => [],
                'response' => []
            ], 200);
        }
    }

}