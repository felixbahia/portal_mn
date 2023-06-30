<?php

namespace App\Http\Controllers;

use App\FichaTecnicaProduto;
use App\Http\Controllers\FichaTecnicaCadastroController;
use Exception;

class IntegracaoFichaTecnicaProdutoNasajon extends Controller
{

    public function exportarFichaTecnica(){
        ini_set('memory_limit','1024M');
        ini_set('max_execution_time', 1800);

        $fichaTecnicaObj = FichaTecnicaProduto::select('id')->get();

        $id_ficha_Tecnica_Array = $fichaTecnicaObj->pluck('id');
        $ficha_tecnica_cadastro_objeto = new FichaTecnicaCadastroController;

        foreach($id_ficha_Tecnica_Array as $id){
            $saida = $ficha_tecnica_cadastro_objeto->cadastroFichaTecnicaNasajon($id);

            if($saida['status'] == 'error'){
                throw new Exception($saida['message']);
            }
        }

    }
    
}
