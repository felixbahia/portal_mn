<?php

namespace App\Http\Controllers;

use App\Http\Requests\NovoUsuarioClienteRequest;
use App\Http\Requests\UsuarioClienteSalvarNovaSenhaRequest;

use App\CepEndereco;
use App\ClienteNasajon;
use App\ResetSenha;
use App\TipoUsuario;
use App\User;
use App\UsuarioClientePendente;
use App\VendedoresCadastroNasajon;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

use Hash;

use Auth;

class UsuarioClienteController extends Controller
{
    public function registrar(){
        return view('programs.usuario_cliente.registrar');
    }

    public function cnpjParaNome(Request $request){

        $cpf_cnpj = preg_replace('/[_\-\/\.]/','', $request->cpf_cnpj);

        if (!empty($cpf_cnpj)){
            $cliente = ClienteNasajon::where(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $cpf_cnpj)->first();
        }else{
            $cliente = null;
        }

        if (!empty($cpf_cnpj)){
            $representante = VendedoresCadastroNasajon::where(DB::Raw("replace(replace(replace(vendedor_cnpj, '.', ''), '-', ''), '/', '')"), $cpf_cnpj)->first();
        }else{
            $representante = null;
        }

        if(!is_null($cliente)){
            $return = [
                'status' => 'success',
                'message' => '',
                'response' => [
                    'nome' => $cliente->nome
                ],
                'error' => []
            ];

            $cod_http = 200;

        } else if(!is_null($representante)){
            $return = [
                'status' => 'success',
                'message' => '',
                'response' => [
                    'nome' => $representante->vendedor_nome
                ],
                'error' => []
            ];

            $cod_http = 200;
        }else{
            $return = [
                'status' => 'error',
                'message' => 'Nenhum cliente encontrado com este CPF/CNPJ',
                'response' => [],
                'error' => [
                    'cpf_cnpj' => 'Nenhum cliente encontrado com este CPF/CNPJ' 
                ]
            ];

            $cod_http = 422;
        }

        return response()->json($return, $cod_http);

    }

    public function salvar(NovoUsuarioClienteRequest $request){

        $fields = $request->only('cpf_cnpj', 'email', 'telefone');

        $clienteObj = ClienteNasajon::where(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), preg_replace('/[_\-\/\.]/','', $fields['cpf_cnpj']))->first();

        if($fields['email'] == $clienteObj->email){
            $user = new User;
            $user->username = preg_replace('/[_\-\/\.]/','', $fields['cpf_cnpj']);
            $user->name = $clienteObj->nome;
            $user->email = $fields['email'];
            $user->setor = 'Cliente';

            $tipoUsuarioObj = TipoUsuario::where('nome', 'Cliente')->first();

            $user->tipo_usuario_id = $tipoUsuarioObj->id;

            $alfanumericos = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $alfanumericos = str_shuffle($alfanumericos);
            $senha = substr($alfanumericos, 0, 8);

            $user->password = Hash::make($senha);

            if($user->save()){

                $user->assignRole('Cliente');

                UsuarioClientePendente::where('cpf_cnpj', $user->username)
                ->whereNull('deleted_at')
                ->update([
                    'status' => 'reprovado',
                    'updated_by' => 1,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);
                
                $emailControllerObj = new EmailController;
    
                $mail_result = $emailControllerObj->sendEmailToken('01', 'criacao_usuario_tecidos', [$fields['email']], ['nome' => $clienteObj->nome, 'usuario' => $user->username, 'senha' => $senha]);
    
                $return = [
                    'status' => 'success',
                    'message' => '',
                    'response' => [
                        'salvo' => 'usuario',
                        'email' => $mail_result
                    ],
                    'error' => []
                ];
    
                $cod_http = 200;

            }

        }
        else{

            $usuarioClientePendenteObj = new UsuarioClientePendente;

            $usuarioClientePendenteObj->cpf_cnpj = preg_replace('/[_\-\/\.]/','', $fields['cpf_cnpj']);
            $usuarioClientePendenteObj->novo_email = $fields['email'];
            $usuarioClientePendenteObj->telefone = $fields['telefone'];

            $usuarioClientePendenteObj->save();

            $return = [
                'status' => 'success',
                'message' => '',
                'response' => [
                    'salvo' => 'aprovacao'
                ],
                'errors' => []
            ];

            $cod_http = 220;
        }

        return response()->json($return, $cod_http);
    }

    public function resetar(){
        return view('programs.usuario_cliente.resetar');
    }

    public function salvarRequisicaoNovaSenha(Request $request){
        $fields = $request->only('usuario', 'email');

        $usuarioQuery = User::whereHas('tipo_usuario', function($query){
            $query->where('nome', 'Cliente')
            ->orWhere('nome', 'Representante');
        });
        
        if(isset($fields['usuario']) && !empty($fields['usuario'])){   
            $usuarioQuery->where('username', $fields['usuario']);
        }
        else if(isset($fields['email']) && !empty($fields['email'])){   
            $usuarioQuery->where('email', $fields['email']);
        }
        else{
            return response()
                ->json([
                    'status' => 'error',
                    'message' => '',
                    'response' => [],
                    'errors' => []
                ], 422
            );
        }

        $usuario = $usuarioQuery->first();
        
        if(!is_null($usuario) && !empty($usuario)){

            $resetSenha = new ResetSenha;

            $resetSenha->user = $usuario->username;
            $resetSenha->hash = Crypt::encrypt($usuario->username . date('Y-m-d'));

            $resetSenha->save();

            $emailControllerObj = new EmailController;
    
            if($usuario->tipo_usuario_id !== 12){
                $mail_result = $emailControllerObj->sendEmailToken('01', 'reset_senha_usuario_cliente', [$usuario->email], ['nome' => $usuario->name, 'link' => route('usuario_cliente.nova_senha.index', ['hash' => $resetSenha->hash])]);
            }else if($usuario->tipo_usuario_id === 12){
                $mail_result = $emailControllerObj->sendEmailToken('00', 'cadastro:novo_representante', [$usuario->email], ['nome' => $usuario->name, 'usuario' => $usuario->username, 'link' => route('representante.trocar_senha', ['hash' => $resetSenha->hash])]);
            }

        }
        else{
            return response()
                ->json([
                    'status' => 'error',
                    'message' => '',
                    'response' => [],
                    'errors' => []
                ], 422
            );
        }

        if(strlen($usuario->username)==11){
            $cpf_cnpj = substr($usuario->username, 0, 3) . '.' . substr($usuario->username, 3, 3) . '.' . substr($usuario->username, 6, 3) . '-' . substr($usuario->username, 9);
        }
        else if(strlen($usuario->username)==14){
            $cpf_cnpj = substr($usuario->username, 0, 2) . '.' . substr($usuario->username, 2, 3) . '.' . substr($usuario->username, 5, 3) . '/' . substr($usuario->username, 8, 4) . '-' . substr($usuario->username, 12);

        }
        else{
            $cpf_cnpj = $usuario->username;
        }

        return response()->json(['status' => 'success',
                'message' => '',
                'response' => [
                    'email' => $usuario->email,
                    'empresa' => $usuario->name . ' - ' . $cpf_cnpj
                ],
                'errors' => []
            ], 200
        );

    }

    public function novaSenhaIndex(Request $request, $hash){

        $resetSenhaObj = ResetSenha::with('usuario')
            ->where('hash', $hash)
            ->where('recuperado', false)
            ->first();

        if(is_null($resetSenhaObj)){
            return abort(403);
        }
        else{
            return view('programs.usuario_cliente.nova_senha')->with(['hash' => $hash, 'user' => $resetSenhaObj->usuario]);
        }
    }

    public function salvarNovaSenha(UsuarioClienteSalvarNovaSenhaRequest $request){
        $fields = $request->only('senha', 'hash');
        
        $resetSenhaObj = ResetSenha::with('usuario')
            ->where('hash', $fields['hash'])
            ->first();

        $resetSenhaObj->usuario->password = Hash::make($fields['senha']);

        $resetSenhaObj->recuperado = true;
        $resetSenhaObj->push();

        $emailControllerObj = new EmailController;
    
        $mail_result = $emailControllerObj->sendEmailToken('01', 'mudanca_senha_usuario_tecidos', [$resetSenhaObj->usuario->email], ['nome' => $resetSenhaObj->usuario->name, 'usuario' => $resetSenhaObj->usuario->username, 'senha' => $fields['senha']]);

        
        return response()->json(['status' => 'success',
                'message' => '',
                'response' => [],
                'errors' => []
            ], 200
        );
    }

    public function indexAprovacao(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\UsuarioClientePendente") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\UsuarioClientePendente');
        return view('programs.usuario_cliente.index');
    }

    public function filter(Request $request){

        $fields = $request->only('nome_cliente');

        $usuarioClienteQuery = UsuarioClientePendente::with('cliente');

        if (isset($fields['nome_cliente']) && !empty($fields['nome_cliente'])){
            $clientesObj = ClienteNasajon::selectRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') as cpf_cnpj")->where(DB::Raw("TRIM(CONCAT(TRIM(nome), ' - ', cpf_cnpj))"), 'ilike', '%'. $fields['nome_cliente'] .'%');
            $usuarioClienteQuery->whereIn('cpf_cnpj', $clientesObj->pluck('cpf_cnpj'));
        }

        $usuarioClienteObj = $usuarioClienteQuery->get();

        $clientesObj = ClienteNasajon::whereIn(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"),$usuarioClienteObj->pluck('cpf_cnpj'))->get();

        $result = [];

        $usuarioClienteObj->each(function($cliente) use (&$result, $clientesObj){

            $clienteLinha = $clientesObj->first(function ($value, $key) use($cliente){
                return preg_replace('/[_\-\/\.]/','',$value->cpf_cnpj) == $cliente->cpf_cnpj;
            });

            $linha = [];
            $linha['id'] = $cliente->id;
            $linha['cliente'] = $clienteLinha->nome . ' - ' . $clienteLinha->cpf_cnpj;
            $linha['email'] = $cliente->novo_email;
            $linha['telefone'] = $cliente->telefone;
            $linha['data_solicitacao'] = parserData($cliente->created_at);
            $result[] = $linha;
        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'response' => [
                'resultado' => $result
            ],
            'errors' => []
        ], 200);

    }

    public function aprovarCliente(Request $request){

        $usuarioClienteObj = UsuarioClientePendente::find($request->id);
        $userObj = User::where('username', $usuarioClienteObj->cpf_cnpj)->first();

        if(!is_null($userObj)){
            $mensagem = 'Usuario já cadastrado para este CNPJ, com o e-mail: ' . $userObj->email.'. A requisição foi recusada';

            $usuarioClienteObj->status = 'reprovado';
            $usuarioClienteObj->updated_by = Auth::user()->id;
            $usuarioClienteObj->save();
            $usuarioClienteObj->delete();

            return response()->json([
                'status' => 'error',
                'message' => $mensagem,
                'response' => [],
                'errors' => ['username' => $mensagem]
            ], 422);
        }

        $clientesObj = ClienteNasajon::where(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"),$usuarioClienteObj->cpf_cnpj)->first();

        $enderecoObj = CepEndereco::with('cidadeBusca')->where('cep', str_replace('-', '', $clientesObj->cep))->first();

        $user = new User;
        $user->username = $usuarioClienteObj->cpf_cnpj;
        $user->name = $clientesObj->nome;
        $user->email = $usuarioClienteObj->novo_email;
        $user->setor = 'Cliente';

        $tipoUsuarioObj = TipoUsuario::where('nome', 'Cliente')->first();

        $user->tipo_usuario_id = $tipoUsuarioObj->id;

        $alfanumericos = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alfanumericos = str_shuffle($alfanumericos);
        $senha = substr($alfanumericos, 0, 8);

        $user->password = Hash::make($senha);

        if($user->save()){

            $user->assignRole('Cliente');

            preg_match('/(\(.*?\))(.*?),/', $clientesObj->telefones, $telefone);

            if(isset($telefone[1])){
                $ddd = preg_replace('/[\(\)]/', '', $telefone[1]);
            }
            else{
                $ddd = '';
            }

            if(isset($telefone[2])){
                $telefone = $telefone[2];
            }
            else{
                $telefone = '';
            }

            try {
                $result = DB::connection('nasajon')->select("SELECT * from integracoes.api_clientealterar(
                    '". $clientesObj->id ."',
                    '". $clientesObj->nome ."',
                    '". $clientesObj->nomefantasia ."',
                    '". $clientesObj->cpf_cnpj ."',
                    '". $clientesObj->inscricaoestadual ."',
                    '',
                    '". $usuarioClienteObj->novo_email ."',
                    0,
                    '". $clientesObj->tipologradouro ."',
                    '". $clientesObj->logradouro ."',
                    '". $clientesObj->numero ."',
                    '". $clientesObj->complemento ."',
                    '". $clientesObj->cep ."',
                    '". $clientesObj->bairro ."',
                    '". $clientesObj->uf ."',
                    '". $enderecoObj->cidadeBusca->cod_ibge ."',
                    '". $enderecoObj->cidadeBusca->cidade ."',
                    '',
                    '". $ddd ."',
                    '". $telefone ."',
                    '". $clientesObj->indicadorinscricaoestadual ."'
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

                $usuarioClienteObj->updated_by = Auth::user()->id;
                $usuarioClienteObj->status = 'aprovado';
                $usuarioClienteObj->save();
                $usuarioClienteObj->delete();
                
                $emailControllerObj = new EmailController;

                $mail_result = $emailControllerObj->sendEmailToken('01', 'criacao_usuario_tecidos', [$usuarioClienteObj->novo_email], ['nome' => $clientesObj->nome, 'usuario' => $user->username, 'senha' => $senha]);

                UsuarioClientePendente::where('cpf_cnpj', $user->username)
                    ->whereNull('deleted_at')
                    ->update([
                        'status' => 'reprovado',
                        'updated_by' => Auth::user()->id,
                        'deleted_at' => date('Y-m-d H:i:s')
                    ]);

                return response()->json([
                    'status' => 'success',
                    'message' => '',
                    'response' => [
                        'salvo' => 'usuario'
                    ],
                    'error' => []
                ], 200);
            }

        }
    }

    public function reprovarCliente(Request $request){

        $usuarioClienteObj = UsuarioClientePendente::with('cliente')->find($request->id);

        $usuarioClienteObj->updated_by = Auth::user()->id;
        $usuarioClienteObj->status = 'reprovado';
        $usuarioClienteObj->save();

        $usuarioClienteObj->delete();

        return response()->json([
            'status' => 'success',
            'message' => '',
            'response' => [

            ],
            'error' => []
        ], 200);
    }
}
