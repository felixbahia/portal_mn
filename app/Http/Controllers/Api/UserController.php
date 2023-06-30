<?php

namespace App\Http\Controllers\Api;

use Hash;
use Auth;
use DateTime;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests\Api\LoginApiRequest;
use App\Http\Controllers\Controller;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;


class UserController extends Controller 
{

    public $successStatus = 200;
    public $errorStatus = 403;

    protected $server;
    protected $tokens;

    public function __construct(ResourceServer $server, TokenRepository $tokens) {
        $this->server = $server;
        $this->tokens = $tokens;
    }

    /** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function login(LoginApiRequest $request){
        header('Access-Control-Allow-Origin: *');
        if(User::where('username', strtolower($request->username))->first() === null){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "O usuário ou a senha informado no login não correspondeu a nenhum registro",
                        "user" => "Usuário ou senha inválido"
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }else if(Auth::attempt(['username' => strtolower($request->username), 'password' => $request->password])){ 
            $user = Auth::user();
            $url = '';
            if(!empty($user->photo)){
                $url = route('usuario.photo',['id'=>Crypt::encryptString(Auth::id())]);
            }
            $success = [
                'error' =>[
                    "error" => false,
                    "msg" => [
                        "dev" => "",
                        "user" => ""
                    ]
                ],
                'request' => new \stdClass(),
                "response" => [
                    'token' =>  $user->createToken('portal_app')->accessToken,
                    'user' => [
                        'id' =>  $user->id,
                        'name' =>  $user->name,
                        "email" => $user->email,
                        "phone" => !empty($user->celular) ? $user->celular : '',
                        "perfil" => $user->tipo_usuario_id,
                        "photo" => $url
                    ]
                ]
            ];
            return response()->json($success, $this->successStatus); 
        } else {
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "O usuário ou a senha informado no login não correspondeu a nenhum registro",
                        "user" => "Usuário ou senha inválido"
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
    }

    public function logout(Request $request){
        header('Access-Control-Allow-Origin: *');
        if (Auth::check()) {
            $user = Auth::user();
            $user->token()->revoke();
            $user->token()->delete();
            $success = [
                "error" => [
                    "error" => false,
                    "msg" => [
                        "dev" => "",
                        "user" => ""
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($success, $this->successStatus);  
        } else {
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Ocorreu um erro no manuseio do token",
                        "user" => "Credencial expirada, favor efetue o login novamente."
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
    }

    public function updadateInfo(Request $request){
        header('Access-Control-Allow-Origin: *');
        ini_set('max_execution_time', 500);
        ini_set('post_max_size', '50M');
        ini_set('upload_max_filesize', '50M');
        ini_set('memory_limit', '2024M');
        $filters = $request->only(['email', 'phone', 'photo']);
        if(!isset($filters['email']) || empty($filters['email']) || !isset($filters['phone']) || empty($filters['phone'])) {
            $error = [
                "error" => [
                    "error" => true,
                    'msg' => [
                        'dev' => 'Paramentro(s) informado(s) inválido(s)',
                        'user' => 'Paramentro(s) informado(s) inválido(s)'
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
        if(!filter_var($filters['email'], FILTER_VALIDATE_EMAIL)){
            $error = [
                "error" => [
                    "error" => true,
                    'msg' => [
                        'dev' => 'E-mail inválido',
                        'user' => 'Informe um e-mail válido'
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
        $file_name = '';
        $user = Auth::user();
        $user->email = strtolower($filters['email']);
        $user->celular = $filters['phone'];
        if(!empty($filters['photo'])){
            $file_name = 'users/user_'.Auth::user()->id.'_'.time().'.jpeg';
            $file_data = str_replace('data:image/jpeg;base64,', '', $filters['photo']);
            $file_data = str_replace(' ', '+', $file_data);
            if($file_data!=""){
                Storage::put($file_name, base64_decode($file_data)); 
            }
            Storage::put(str_replace("jpeg", 'txt', $file_name), $filters['photo']);
            $user->photo = $file_name;
        }
        $user->updated_at = date('Y-m-d H:i:s');
        if($user->save()){
            $success = [
                'error' =>[
                    "error" => false,
                    "msg" => [
                        "dev" => "",
                        "user" => ""
                    ]
                ],
                'request' => new \stdClass(),
                'response' => 'Atualizado com sucesso'
            ];
            return response()->json($success, $this->successStatus);
        } else {
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Ocorreu um erro! Tente novamente.",
                        "user" => "Ocorreu um erro! Tente novamente."
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
    }

    /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function getSession(){
        header('Access-Control-Allow-Origin: *');
        $user = Auth::user();
        $url = '';
        if(!empty($user->photo)){
            $url = route('usuario.photo',['id'=>Crypt::encryptString(Auth::id())]);
        }
        $success = [
            'error' =>[
                "error" => false,
                "msg" => [
                    "dev" => "",
                    "user" => ""
                ]
            ],
            'request' => new \stdClass(),
            'response' => [
                "nome" => $user->name,
                "email" => $user->email,
                "phone" => !empty($user->celular) ? $user->celular : '',
                "perfil" => $user->tipo_usuario_id,
                "photo" => $url,
            ]
        ];
        return response()->json($success, $this->successStatus);
    }
}