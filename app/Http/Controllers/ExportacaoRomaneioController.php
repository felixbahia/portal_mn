<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\ExportacaoRomaneioRequest;

class ExportacaoRomaneioController extends Controller
{
    public function index(Request $request){

        set_time_limit(300);
        ini_set('memory_limit','1024M');
        
        if(Auth::user()->hasPermissionTo("programas App\ExportacaoRomaneio") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ExportacaoRomaneio');

        return view('programs.exportacao_romaneio.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function exportar(Request $request){
        $fields = $request->only([
            "estabelecimento",
            "numero_nota",
            "emissao"
        ]);
        $numero_nota = str_pad($fields["numero_nota"], 6, '0', STR_PAD_LEFT);
        $produto = array();
        $data = Carbon::createFromFormat('m/Y', $fields["emissao"])->format('ym');
        $table_dum = "DUM0".$fields["estabelecimento"]."_".$data."2";
        if($this->checkTableDUM($table_dum, $numero_nota)){
            switch ($fields["estabelecimento"]) {
                case '1':
                    $produto = $this->getProdutoAlmirante($table_dum, $numero_nota);
                    break;
                case '2':
                    $produto = $this->getProdutoBotelho($table_dum, $numero_nota);
                    break;
                case '3':
                    $produto = $this->getProdutoArmazen($table_dum, $numero_nota, 3);
                    break;
                case '4':
                    $produto = $this->getProdutoArmazen($table_dum, $numero_nota, 4);
                    break;
                case '5':
                    $produto = $this->getProdutoXavantes($table_dum, $numero_nota);
                    break;
            }
            if(!empty($produto)){
                $produto = $this->arrayProdutoCodVol($produto);
                $csv = $this->gerarDadosCsv($produto);
                $this->gerarArquivoCsv($csv,$fields["numero_nota"]);
                exit;
            }else{ 
                exit;
            }
        }else{
            exit;
        }
    }

    private function checkTableDUM($table_dum, $numero_nota){
        if(DB::connection('srv_prologos')->getSchemaBuilder()->hasTable($table_dum)){
            $query = DB::connection('srv_prologos')->table($table_dum)
                ->select('NF_NUMNF')
                ->where('NF_NUMNF', $numero_nota)
                ->where('TIPOPER','like', 'sv%')
                ->first();
            if(!empty($query)){
                return true;
            }
        }
        return false;
    }

    private function getProdutoAlmirante($table_dum, $numero_nota){
        $query = DB::connection('srv_almirante')->table('tbvol1')
            ->select()
            ->where('NF_SAIDA', $numero_nota)
            ->get()
            ->toArray();
        return $query;
    }

    private function getProdutoBotelho($table_dum, $numero_nota){
        $query = DB::connection('srv_botelho')->table('tbvol1')
            ->select()
            ->where('NF_SAIDA', $numero_nota)
            ->get()
            ->toArray();
        return $query;
    }

    private function getProdutoArmazen($table_dum, $numero_nota, $estabelecimento){
        $dono = [3 => "0063112740002", 4 => '0063112740003'];
        $queryPedido = DB::connection('srv_prologos')->table($table_dum)
            ->select('MLD_GUERRA')
            ->where('NF_NUMNF', $numero_nota)
            ->where('TIPOPER','like', 'sv%')
            ->first();
        $num_pedido = preg_replace('/[a-zA-Z]{3}\:\ [0-9]{2}\-/', '', $queryPedido->MLD_GUERRA);
        $query = DB::connection('srv_armazen')->table('tbvol3')
            ->select()
            ->where('NUMPED', $num_pedido)
            ->where("DONO", $dono[$estabelecimento])
            ->get()
            ->toArray();
        return $query;
    }

    private function getProdutoXavantes($table_dum, $numero_nota){
        $query = DB::connection('srv_xavantes')->table('tbvol1')
            ->select()
            ->where('NF_SAIDA',$numero_nota)
            ->get()
            ->toArray();
        return $query;
    }

    private function arrayProdutoCodVol($query){
        $return = array();
        foreach($query as $produto){
            $produto = (array) $produto;
            $return[$produto["CODPRD"]][] = $produto["QTDE_NO_VOLUME"];
        }
        return $return;
    }

    private function gerarDadosCsv($array){
        $csv = "";
        foreach($array as $key => $value){
            $csv = $csv.'#'.$key."\n";
            foreach($value as $qtd){
                $strQtd = str_replace('.', ',', $qtd);
                $csv = $csv.$strQtd."\n";
            }
        }
        $csv = substr($csv, 0, -1);
        return $csv;
    }

    private function gerarArquivoCsv($csv, $numero_nota){
        $arquivo = 'romaneio_'.$numero_nota.'.csv';
        header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header ("Cache-Control: no-cache, must-revalidate");
        header ("Pragma: no-cache");
        header ("Content-type: txt/csv; charset=UTF-8");
        header ("Content-Disposition: attachment; filename=\"{$arquivo}\"" );
        header ("Content-Transfer-Encoding: BINARY");
        header ("Content-Description: MN Tecidos" );
        echo utf8_decode($csv);
    }

    public function validar(ExportacaoRomaneioRequest $request){
        $fields = $request->only([
            "estabelecimento",
            "numero_nota",
            "emissao"            
        ]);
        $numero_nota = str_pad($fields["numero_nota"], 6, '0', STR_PAD_LEFT);
        $produto = 0;
        $data = Carbon::createFromFormat('m/Y', $fields["emissao"])->format('ym');
        $table_dum = "DUM0".$fields["estabelecimento"]."_".$data."2";
        if($this->checkTableDUM($table_dum, $numero_nota)){
            switch($fields["estabelecimento"]){
                case "1":
                    $produto = $this->verificarProdutoAlmirante($table_dum, $numero_nota);
                    break;
                case "2":
                    $produto = $this->verificarProdutoBotelho($table_dum, $numero_nota);
                    break;
                case '3':
                    $produto = $this->verificarProdutoArmazen($table_dum, $numero_nota, 3);
                    break;
                case '4':
                    $produto = $this->verificarProdutoArmazen($table_dum, $numero_nota, 4);
                    break;
                case '5':
                    $produto = $this->verificarProdutoXavantes($table_dum, $numero_nota);
                    break;
            }
            if($produto > 0){
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => ''
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => '',
                    'error' => ['numero_nota' => 'Não existe peça romaneio do produto'],
                    'response' => ''
                ],422);
            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => ['numero_nota' => 'Nota não encontrada'] ,
                'response' => ''
            ], 422);
        }
    }

    private function verificarProdutoAlmirante($table_dum, $numero_nota){
        $query = DB::connection('srv_almirante')->table('tbvol1')
            ->select('CODPRD')
            ->where('NF_SAIDA', $numero_nota)
            ->count('CODPRD');
        return $query;
    }

    private function verificarProdutoBotelho($table_dum, $numero_nota){
        $query = DB::connection('srv_botelho')->table('tbvol1')
            ->select('CODPRD')
            ->where('NF_SAIDA', $numero_nota)
            ->count('CODPRD');
        return $query;
    }

    private function verificarProdutoArmazen($table_dum, $numero_nota, $estabelecimento){
        $dono = [3 => "0063112740002", 4 => '0063112740003'];
        $queryPedido = DB::connection('srv_prologos')->table($table_dum)
            ->select('MLD_GUERRA')
            ->where('NF_NUMNF', $numero_nota)
            ->where('TIPOPER','like', 'sv%')
            ->first();
        $num_pedido = preg_replace('/[a-zA-Z]{3}\:\ [0-9]{2}\-/', '', $queryPedido->MLD_GUERRA);
        $query = DB::connection('srv_armazen')->table('tbvol3')
            ->select("CODPRD" , "QTDE_NO_VOLUME")
            ->where('NUMPED', $num_pedido)
            ->where("DONO", $dono[$estabelecimento])
            ->count('CODPRD');
        return $query;
    }

    private function verificarProdutoXavantes($table_dum, $numero_nota){
        $query = DB::connection('srv_xavantes')->table('tbvol1')
            ->select('CODPRD')
            ->where('NF_SAIDA', $numero_nota)
            ->count('CODPRD');
        return $query;
    }

    private function estabelecimentosPrologos(){
        $estabelecimentos = returnEmpresasPrologusView();
        $estabelecimentos = array_merge(["" => "Estabelecimento"], $estabelecimentos);
        unset($estabelecimentos[5]);
        return $estabelecimentos;
    }

    public function exportarEtq(Request $request){
        $fields = $request->only([
            "estabelecimento",
            "numero_nota",
            "emissao"
        ]);
        
        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);

        $numero_nota = str_pad($fields["numero_nota"], 6, '0', STR_PAD_LEFT);
        $produto = array();
        $data = Carbon::createFromFormat('m/Y', $fields["emissao"])->format('ym');
        $table_dum = "DUM0".$fields["estabelecimento"]."_".$data."2";

        $numero_nota_inteiro = str_pad($fields["numero_nota"], 9, '0', STR_PAD_LEFT);
        $cabecalho = "VOLUMES GERADOS PELO ESTABEL: ".$estabelecimento." REF NF ".$numero_nota." PARA LOJA 0050758840001\n";

        if($this->checkTableDUM($table_dum, $numero_nota)){
            switch ($fields["estabelecimento"]) {
                case '1':
                    $produto = $this->getProdutoAlmirante($table_dum, $numero_nota);
                    break;
                case '2':
                    $produto = $this->getProdutoBotelho($table_dum, $numero_nota);
                    break;
                case '3':
                    $produto = $this->getProdutoArmazen($table_dum, $numero_nota, 3);
                    break;
                case '4':
                    $produto = $this->getProdutoArmazen($table_dum, $numero_nota, 4);
                    break;
                case '5':
                    $produto = $this->getProdutoXavantes($table_dum, $numero_nota);
                    break;
            }
            if(!empty($produto)){
                $arquivo = $this->gerarDadosOK($cabecalho, $produto, $numero_nota_inteiro, $fields["estabelecimento"]);
                $this->gerarArquivoEtq($arquivo,$fields["numero_nota"], $estabelecimento);
                exit;
            }else{ 
                exit;
            }
        }else{
            exit;
        }
    }

    private function gerarDadosOK($cabecalho, $produto, $numero_nota, $codigo_estabelecimento){
        $dono = [0 => "0063112740001", 1 => "0050758840001", 2 => '0050758840002'];
        $text = "";
        foreach($produto as $value){
            if($codigo_estabelecimento == 3 || $codigo_estabelecimento == 4){
                if(empty(trim($value->ID_ORIGINAL))){
                    $text = $text.$value->DONO.";".$numero_nota.";".$value->CODPRD.";".$value->NF_ENTRADA.".".$value->NUMVOL.";".str_replace(".", ",", $value->QTDE_NO_VOLUME)."\r\n";
                }else{
                    $text = $text.$value->DONO.";".$numero_nota.";".$value->CODPRD.";".$value->ID_ORIGINAL.";".str_replace(".", ",", $value->QTDE_NO_VOLUME)."\r\n";
                }
            }else{
                $text = $text.$dono[$codigo_estabelecimento].";".$numero_nota.";".$value->CODPRD.";".$value->ID_VOLUME.";".str_replace(".", ",", $value->QTDE_NO_VOLUME)."\r\n";
            }
        }
        $text = substr($text, 0, -2);
        return $text;
    }

    private function gerarArquivoEtq($csv, $numero_nota, $estabelecimento){
        $arquivo = 'TEXTILMN_'.$estabelecimento.$numero_nota.'.ETQ';
        header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header ("Cache-Control: no-cache, must-revalidate");
        header ("Pragma: no-cache");
        header ("Content-type: txt; charset=UTF-8");
        header ("Content-Disposition: attachment; filename=\"{$arquivo}\"" );
        header ("Content-Transfer-Encoding: BINARY");
        header ("Content-Description: MN Tecidos" );
        echo utf8_decode($csv);
    }
}