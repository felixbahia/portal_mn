<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\ClienteNasajon;
use App\VendedoresCadastroNasajon;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\LoginRequest;
use Dcblogdev\MsGraph\Facades\MsGraph;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        //return view('auth.login_manutencao');
        return view('auth.login');
    }

    public function login(LoginRequest $request){
        $dados = $request->only(['username', 'password']);
        $user = User::with('tipo_usuario')->where('username', $dados['username'])->first();
        if(!is_object($user) || intval($user->id) === 1 ){
            return redirect(route('login'))->withErrors(__('auth.failed'));
        }
        
        if(!empty($user->tipo_usuario_id)){
            if(strtolower($user->tipo_usuario->nome) === 'cliente'){
                $clienteObj = ClienteNasajon::where(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), preg_replace('/[_\-\/\.]/','', $dados['username']))->where('bloqueado', 'false')->first();
                if(Hash::check($dados['password'], $user->password) && !is_null($clienteObj) ) {
                    Auth::loginUsingId($user->id);
                    return redirect()->route('home_message');
                }
            }else if(strtolower($user->tipo_usuario->nome) === 'representante'){
                $representanteObj = VendedoresCadastroNasajon::where('vendedor_pessoa', $user->codigo_representante)->where('vendedor_bloqueado', 'false')->first();
                if(Hash::check($dados['password'], $user->password) && !is_null($representanteObj)){
                    Auth::loginUsingId($user->id);
                    return redirect()->route('home_message');
                }
            }else{
                if (Auth::attempt($request->only(['username', 'password']))) {
                    if(strtolower($user['tipo_usuario']['nome']) === 'coletor'){
                        return redirect(route('login'))->withErrors('Usuário para acesso no coletor');
                    }else{
                        Auth::loginUsingId($user->id);
                        return redirect()->route('home_message');
                        //return redirect()->route('auth20');  
                    }
                }else if(Hash::check($dados['password'], $user->password) ) {
                    Auth::loginUsingId($user->id);
                    return redirect()->route('home_message');
                }
            }
        } else {
            if(Hash::check($dados['password'], $user->password)) {
                Auth::loginUsingId($user->id);
                return redirect()->route('home_message');
            }
        }
        return redirect(route('login'))->withErrors(__('auth.failed'));
    }
    
}
