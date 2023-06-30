<?php

namespace App\Http\Controllers;

use Hash;
use App\ResetSenha;

use Illuminate\Http\Request;
use App\Http\Requests\UsuarioClienteSalvarNovaSenhaRequest;

class RepresentanteController extends Controller
{
    public function novaSenhaIndex($hash){
        
        $resetSenhaObj = ResetSenha::with('usuario')
            ->where('hash', $hash)
            ->where('recuperado', false)
            ->first();
            
        if(is_null($resetSenhaObj)){
            return abort(403);
        }
        else{
            return view('programs.representante.trocar_senha')->with(['hash' => $hash, 'user' => $resetSenhaObj->usuario]);
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
}
