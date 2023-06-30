<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProdutoNasajon;

use Illuminate\Support\Facades\DB;

class ProdutoNasajonController extends Controller
{
    public function modalBusca(){
    	return view('programs.produto_nasajon.modal_busca');
    }


    public function autocomplete(Request $request){

        $produtosObj = ProdutoNasajon::select('produto', 'especificacao')
            ->distinct('especificacao')
            ->whereRaw("UPPER(especificacao) like '%" .  strtoupper($request->term) . "%'")
            ->orderBy('especificacao', "ASC")
            ->limit(15);

        if(isset($request->exclude)){
            $ids_query = DB::table($request->exclude)
                ->select('produto')
                ->distinct('produto');

            if (isset($request->id)){
                $ids_query->where('produto', '!=', $request->id);
            } 
                
            $ids = $ids_query->get();

            $produtosObj->whereNotIn('produto', $ids->only('produto'));

        }

        $produtos = $produtosObj->get();

    	$return = [];

    	foreach ($produtos as $value) {

    		$return[] = 
    		[
    			'label' => $value->especificacao,
    			'value' => $value->especificacao
    		]; 
    		
    	}

    	return response()->json($return);
    }
}
