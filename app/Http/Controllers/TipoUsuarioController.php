<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\TipoUsuario;

class TipoUsuarioController extends Controller
{
    public function modalBusca(){
        return view('programs.tipo_usuario.modal.busca');
    }

    public function filtroModal(Request $request){
        $fields = $request->only(['nome']);

        $TipoUsuarioObj = TipoUsuario::query();

        if(!empty($fields['nome'])){
            $TipoUsuarioObj->where('nome', 'ilike', '%'.$fields['nome'].'%');
        }

        $retorno = [];
        $TipoUsuarioObj->get()->each(function($tipo) use (&$retorno){
            $retorno[] = [
                'nome' => $tipo->nome,
                'id' => encrypt($tipo->id)
            ];
        });
        unset($TipoUsuarioObj);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno,
        ]);
    }
}
