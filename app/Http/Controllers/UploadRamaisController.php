<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

class UploadRamaisController extends Controller
{
    public function indexRamais(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\UploadRamais") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\UploadRamais');
        $retorno = " ";
        return view('programs.diversos.upload_ramais')->with(['return' => $retorno]);
    }

    public function filterUpload(Request $request){

       if($request->file("arquivo")->isValid()){
           if($request->file('arquivo')->extension()=='txt'){
               $nomeArquivo = 'ramais.txt';
               $request->file("arquivo")->storeAs("ramais",$nomeArquivo);

               $retorno = "Upload realizado com sucesso!";

           }
       }

      return view("programs.diversos.upload_ramais")->with(['return' => $retorno]);
   
    }
}
