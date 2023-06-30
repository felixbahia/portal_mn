<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Auth;
use PDF;

use Carbon\Carbon;

use App\ProdutosEstoque;
use App\ClienteNasajon;
use App\ComprasNasajon;
use App\ComprasRondonia;

use App\Http\Requests\OrdemFaturamentoServicoArmazenagemRequest;

class OrdemFaturamentoServicoArmazenagemController extends Controller
{
    public function index(Request $request){

        return abort(404);
        
        if(Auth::user()->hasPermissionTo("programas App\OrdemFaturamentoServicoArmazenagem") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\OrdemFaturamentoServicoArmazenagem');
        
        return view('programs.ordem_faturamento_servico_armazenagem.index');
    }

    public function gerar(Request $request){
        $fields = $request->only('valor', 'estabelecimento', 'periodo');
        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
        $peso_total = 0.0;
        $peso_entrada = 0.0;

        $cliente = ['03' => '06311274000269', '04' => '06311274000340'];

        $query_produto_estoque = ProdutosEstoque::select();
        $query_produto_estoque->where('estabelecimento', $estabelecimento);
        $result_produto_estoque = $query_produto_estoque->get();
        
        foreach($result_produto_estoque as $produto){
            $peso_total = $peso_total + ($produto->peso * $produto->estoque);
        }

        $valor_unitario = str_replace(",", ".", str_replace(".", "", $fields['valor']));
        $valor_total = parserValor($peso_total * floatval($valor_unitario));

        list($newMes, $newAno) = explode("/", $fields['periodo']);
        $data_inicial = Carbon::createFromFormat('d/m/Y','01/'.($newMes).'/'.$newAno)->setTime(0,0,0);
        $data_final = Carbon::createFromFormat('d/m/Y', $this->ultimoDiaMes(date('d/m/Y')))->setTime(23,59,59);
        
        $query_compras = ComprasNasajon::select();
        $query_compras->where('estabelecimento', $estabelecimento);
        $query_compras->whereBetween('previsao_entrega', [$data_inicial, $data_final]);
        $result_compras = $query_compras->get();

        foreach($result_compras as $produto){
            $query_peso = ProdutosEstoque::select();
            $query_peso->where('estabelecimento', $estabelecimento);
            $query_peso->where('codigo_produto', $produto->cod_produto);
            $result_peso = $query_peso->first();

            if(!empty($result_peso->peso)){
                $peso_entrada = $peso_entrada + ($produto->quantidade * $result_peso->peso);
            }
        }

        //$peso_total = $peso_total - $peso_entrada;
        $data_inicial = Carbon::createFromFormat('d/m/Y','01/'.($newMes).'/'.$newAno)->setTime(0,0,0);
        $data_final = Carbon::createFromFormat('d/m/Y', $this->ultimoDiaMes(date('d/m/Y')))->setTime(23,59,59);

        $table_dum = "DUM".$estabelecimento."_19062";
        $table_dui = "DUI".$estabelecimento."_19062";

        $query_compras = DB::connection('srv_prologos')->table($table_dum);
        $query_compras->select();
        $query_compras->where('TIPOPER', 'like', '%EC%');
        $query_compras->whereNotIn('TIPOPER', ['EC-', 'EC@', 'EC3', 'EC6', 'EC7', 'ECD', 'ECF', 'ECI','ECJ', 'ECP', 'ECS', 'ECT', 'ECO']);

        $result_compras = $query_compras->get();

        $num_docs = [];
        foreach($result_compras as $num_doc){
            $num_docs [] = $num_doc->NUMDOC; 
        }

        $query_dui = DB::connection('srv_prologos')->table($table_dui);
        $query_dui->select('CODPRD', 'QTDE');
        $query_dui->whereIn('NUMDOC', $num_docs);
        $result_dui = $query_dui->get();
 
        foreach($result_dui as $produto){
            $query_peso = ProdutosEstoque::select();
            $query_peso->where('estabelecimento', $estabelecimento);
            $query_peso->where('codigo_produto', $produto->CODPRD);
            $result_peso = $query_peso->first();
            if(!empty($result_peso->peso)){
                $peso_entrada = $peso_entrada + ($produto->QTDE * $result_peso->peso);
            }
        }

        $peso_total = $peso_total - $peso_entrada;

        $query_estabelecimento = ClienteNasajon::select();
        $query_estabelecimento->where('codigo', $cliente[$estabelecimento]);
        $result_estabelecimento = $query_estabelecimento->first();

        $pdfFilePath = 'ordem_faturamento_servico_armazenagem'.$fields['periodo'].'_estabelecimento_'.$estabelecimento.'.pdf';
		$pdf = PDF::loadView(
			'pdf.ordem_faturamento_servico_armazenagem', 
			[
                'data_atual' => date('d/m/Y'),
                'empresa' => $result_estabelecimento->nome,
                'cep' => $this->mascaraCep($result_estabelecimento->cep),
                'endereco' => $result_estabelecimento->logradouro,
                'cnpj' => $result_estabelecimento->cpf_cnpj,
                'cidade' => strtoupper($result_estabelecimento->cidade),
                'estado' => $result_estabelecimento->uf,
                'inscricao_estatual' => $result_estabelecimento->inscricaoestadual,
                'data' => $fields['periodo'],
                'peso_total' => parserQtd($peso_total),
                'valor_unitario' => parserValor($valor_unitario),
                'valor_total' => $valor_total,
                'peso_inicial' => parserQtd($peso_total - $peso_entrada),
                'peso_entrada' => parserQtd($peso_entrada)
			], 
			[], 
			['title' => 'Ordem Faturamento Serviço Armanagem - '.$fields['periodo'], 'custom_font_dir' => '', 'custom_font_data' => [],'margin_bottom' => 1]
		);
		$pdf->stream($pdfFilePath);
        
        return $result_produto_estoque;
    }

    public function validar(OrdemFaturamentoServicoArmazenagemRequest $request){
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ''
        ]);
    }

    function ultimoDiaMes($newData){
        list($newDia, $newMes, $newAno) = explode("/", $newData);
        return date("d/m/Y", mktime(0, 0, 0, $newMes+1, 0, $newAno));
    }

    function mascaraCep($str){
        $str = str_replace(" ","",$str);
        $str = str_replace("-","",$str);
        $str = str_pad($str, 8, '0', STR_PAD_LEFT);

        $mask = '#####-###';

        for($i=0;$i<strlen($str);$i++){
            $mask[strpos($mask,"#")] = $str[$i];
        }

        return $mask;
    }
}
