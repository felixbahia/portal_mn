<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

class DiversosController extends Controller
{
    public function indexRamais(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Ramais") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Ramais');
        $arquivo = file('../storage/app/ramais/ramais.txt');

        $num_linhasTotal = (isset($arquivo)) ?  count($arquivo) : 0;
       
        $colunas = 3;

        $num_linhasColuna = $num_linhasTotal / $colunas;
        $num_linhasColuna = intval(round($num_linhasColuna));

        //dd($num_linhasColuna);

        $primeiraColuna   = 0;
        $segundaColuna    = 0;
        $terceiraColuna   = 0;
        
        $retorno = [];
        foreach($arquivo as $linha){
            $linha  = trim($linha);
            $valor  = explode(';',$linha);
            
            $primeiraColuna ++;
            
            if($primeiraColuna <= $num_linhasColuna){
               $coluna = 'Primeira';
               $retorno[] = [
                'coluna' => $coluna,
                'departamento' => $valor[0],
                'nome' => $valor[1],
                'ramal' => $valor[2], 
            ]; 

            }else{

                $segundaColuna ++;     
                if($segundaColuna <= $num_linhasColuna){
                    $coluna = 'Segunda';
                    $retorno[] = [
                        'coluna' => $coluna,
                        'departamento' => $valor[0],
                        'nome' => $valor[1],
                        'ramal' => $valor[2],
                    ]; 
                }elseif(($primeiraColuna>$num_linhasColuna)||($segundaColuna>$num_linhasColuna)){
                    $terceiraColuna ++;   
                    $coluna = 'Terceira';
                    $retorno[] = [
                        'coluna' => $coluna,
                        'departamento' => $valor[0],
                        'nome' => $valor[1],
                        'ramal' => $valor[2],
                    ]; 

                }

            }
        }       
            

        return view('programs.diversos.ramais')->with(['response' => $retorno]);
    }

   
}
