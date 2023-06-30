<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\Produto;
use App\ProdutoEspecificacao;
use App\AliquotaPreco;

use App\Http\Controllers\BookVirtualController;
use App\Http\Controllers\ProdutoController;

class ConsultaEstoquePrecoController extends Controller
{
    public function index($book_virtual, Request $request) {
        $itens = $this->itens($book_virtual);

        $header_meses = [];
        $date = date("d");
        $month = intval(date("m"));
        $year = intval(date("Y"));
        if(intval($date) <= 15){
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        }
        $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        $month++;
        for ($i=0; $i < 3; $i++) {
            if($month > 12){
                $month = 1;
                $year++;
            }
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $month++;
        }

        $origem = $this->origem();

        $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\ListagemDePrecos');
        $campos_salvos = $UserCamposSalvoControllerObj->returnCamposSalvos();

        $bookVirtualObj = new BookVirtualController;
        $book = $bookVirtualObj->dadosBookVirtual($book_virtual);

        return view('programs.cartelas.index')->with(['book' => $book, 'itens' => $itens, 'header_meses' => $header_meses, 'origem' => $origem, 'campos_salvos' => $campos_salvos]);
    }

    public function book($book_virtual, Request $request) {
        $itens = $this->itens($book_virtual);

        $header_meses = [];
        $date = date("d");
        $month = intval(date("m"));
        $year = intval(date("Y"));
        if(intval($date) <= 15){
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        }
        $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quin. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
        $month++;
        for ($i=0; $i < 3; $i++) {
            if($month > 12){
                $month = 1;
                $year++;
            }
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1"] = "1 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $header_meses["{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2"] = "2 Quinz. <br />".parserNameMonth($month)."/".str_replace("20","",$year);
            $month++;
        }

        $origem = $this->origem();

        $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\ListagemDePrecos');
        $campos_salvos = $UserCamposSalvoControllerObj->returnCamposSalvos();

        $bookVirtualObj = new BookVirtualController;
        $book = $bookVirtualObj->dadosBookVirtual($book_virtual);

        return view('programs.cartelas.book')->with(['book' => $book, 'itens' => $itens, 'header_meses' => $header_meses, 'origem' => $origem, 'campos_salvos' => $campos_salvos]);
    }

    public function itens($book_virtual){
        $verificar_info_adicional = true;
        $composicao = '';
        $primeira_vez = true;
        $bookVirtualObj = new BookVirtualController;
        $produtoObj = new ProdutoController;

        $produtos = $bookVirtualObj->getItensBookVirtual($book_virtual);


        if(empty($produtos)){
            return abort(404);
        }

        $primeira = true;
        $totais = [];
        $return_produto = [];
        $codigos_produtos = "(";
        foreach($produtos as $produto){
            if($primeira_vez){
                $codigos_produtos = $codigos_produtos."'".$produto['cod_produto']."'";
                $primeira_vez = false;
            }else{
                $codigos_produtos = $codigos_produtos.",'".$produto['cod_produto']."'";
            }
        }
        $codigos_produtos = $codigos_produtos.")";
        $parametros = new Request ([
            'in_codigos' => $codigos_produtos,
            'estabel' => ''
        ]);
        $produtos_analise = $produtoObj->filterAnaliseTela($parametros, false, false, false);
        $return_produto =  $produtos_analise['data'];

        $return_info_adicional = ProdutoEspecificacao::with('produtoNasajon')
        ->whereIn('codigo_produto', $produtos)
        ->first()->toArray();
        if(!empty($return_info_adicional['produto_nasajon']['composicao'])){
            $composicao =  $return_info_adicional['produto_nasajon']['composicao'];  
        }
        foreach($produtos_analise['data'] as $key => $value){
            if($primeira){
                if(strcasecmp($key, 'pronta_entrega') == 0 || strcasecmp($key, 'futuro') == 0){
                    $totais[$key] = floatval(str_replace(",", ".", str_replace(".", "", $value)));
                }else if(strcasecmp($key, 'quinzenas') == 0){
                    foreach($value as $quinzena => $valor){
                        $totais['quinzenas'][$quinzena] = floatval(str_replace(",", ".", str_replace(".", "", $valor['quantidade'])));
                    }
                }
                
            }else{
                if(strcasecmp($key, 'pronta_entrega') == 0 || strcasecmp($key, 'futuro') == 0){
                    $totais[$key] += floatval(str_replace(",", ".", str_replace(".", "", $value)));
                }else if(strcasecmp($key, 'quinzenas') == 0){
                    foreach($value as $quinzena => $valor){
                        $totais['quinzenas'][$quinzena] += floatval(str_replace(",", ".", str_replace(".", "", $valor['quantidade'])));
                    }
                }

            }
            $primeira = false;
        }

        foreach($totais as $key => $value){
            if(strcasecmp($key, 'pronta_entrega') == 0 || strcasecmp($key, 'futuro') == 0){
                $totais[$key] = empty($value)?'':parserValor($value);
            }
        }
        if(!empty($totais['quinzenas'])){
            foreach($totais['quinzenas'] as $key => $value){
                $totais['quinzenas'][$key] = empty($value)?'':parserValor($value);
            }
        }

        $return = [
            'produtos' => $return_produto,
            'info_adicional' => $return_info_adicional,
            'composicao' => $composicao,
            'totais' => $totais
        ];
        return $return;
    }

    public function origem(){
        $origemObj = AliquotaPreco::with('origem_detalhe')
            ->orderBy('origem')
            ->get()
            ->unique('origem');

        foreach ($origemObj as $value){
            $origem[$value->origem] = $value->origem_detalhe->estado; 
        }

        return $origem;
    }

    public function quinzenas(){
        $header_meses = [];
        $date = date("d");
        $month = intval(date("m"));
        $year = intval(date("Y"));
        if(intval($date) <= 15){
            $header_meses [] = "{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1";
        }
        $header_meses [] = "{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2";
        $month++;
        for ($i=0; $i < 3; $i++) {
            if($month > 12){
                $month = 1;
                $year++;
            }
            $header_meses [] = "{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_1";
            $header_meses [] = "{$year}_".str_pad($month, 2, "0", STR_PAD_LEFT)."_2";
            $month++;
        }

        return $header_meses;
    }
}
