<?php

namespace App\Http\Controllers;

use App\UserCamposSalvo;
use App\User;
use App\Programa;
use Illuminate\Http\Request;
use Auth;

class UserCamposSalvoController extends Controller
{
    private $programa_id;

    public function __construct($model){
        $this->programa_id = Programa::select('id')->where("model", $model)->pluck('id')->first();
    }

    public function salvarCampo($campo, $valor){
        $campo_check = $this->checkCampoSalvo($campo);
        if(is_numeric($campo_check) && intval($campo_check) > 0){
            $UserCamposSalvoObj = UserCamposSalvo::find($campo_check);
            $UserCamposSalvoObj->valor = $valor;
            $UserCamposSalvoObj->save();
        }else{
            $UserCamposSalvoObj = new UserCamposSalvo();
            $UserCamposSalvoObj->user_id = Auth::id();
            $UserCamposSalvoObj->programa_id = $this->programa_id;
            $UserCamposSalvoObj->campo = $campo;
            $UserCamposSalvoObj->valor = $valor;
            $UserCamposSalvoObj->save();
        }
    }

    private function checkCampoSalvo($campo){
        $campo = UserCamposSalvo::where('user_id', Auth::id())->where('programa_id', $this->programa_id)->where('campo', $campo)->pluck('id')->first();
        return $campo;
    }

    public function returnCamposSalvos(){
        $camposBusca = UserCamposSalvo::select('campo', 'valor')->where('user_id', Auth::id())->where('programa_id', $this->programa_id)->get()->toArray();
        $campos = [];
        foreach ($camposBusca as $key => $value) {
            if(empty($value['valor'])){
                $value['valor'] = '';
            }
            $campos[$value['campo']] = $value['valor'];
        }
        return $campos;
    }
}
