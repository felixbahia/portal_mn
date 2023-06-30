<?php

namespace App\Http\Controllers;

use App\TitulosAbertosNasajonVirada;
use App\User;
use App\LancamentoDebCredVendedor;
use App\ComissaoGerentesVendedores;

use Illuminate\Http\Request;

use Carbon\Carbon;
use PDF;

use Illuminate\Support\Facades\DB;

use App\Exports\ComissaoDuplicataXLSXExport;

use Maatwebsite\Excel\Facades\Excel;

use Auth;

class DescontoRepresentanteController extends Controller
{
    public function exibicao(Request $request){
        $fields = $request->only('representante');
        
        $representanteObj = User::find($fields['representante']);

        $titulosEmAbertoQuery = TitulosAbertosNasajonVirada::
            select('created_at',DB::Raw(
                'CASE
                    WHEN banco_codigo = \'Juridico Ragazzi\' THEN ("valor" * (1/100))
                    ELSE ("valor" * ("percentual_comissao"/100))
                END as comissao,
                CASE 
                    WHEN vendedor_codigo is null THEN \'001\'
                    ELSE vendedor_codigo
                END as vendedor_codigo'))
            ->where('vendedor_codigo', $representanteObj->codigo_representante);

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $titulosEmAbertoQuery->where('codigo', $fields['estabelecimento']);
        }

        $query_virada = str_replace(['?'], ['\'%s\''], $titulosEmAbertoQuery->toSql()); 
        $query_virada = vsprintf($query_virada, $titulosEmAbertoQuery->getBindings());
        
        $titulos_virada = collect(DB::select(
            'with virada as ('.$query_virada.')
            select
                sum(comissao) as comissao,
                max(created_at) as created_at,
                vendedor_codigo 
            from
                virada
            group by vendedor_codigo'
        ))->first();

        $totalComissao = $titulos_virada->comissao;
        
        $representante = $representanteObj->codigo_representante . ' - ' . $representanteObj->name;
        $virada = Carbon::parse($titulos_virada->created_at)->format('Y-m-d');

        $codigo_representante = $representanteObj->codigo_representante;

        $lancamentoDebCredVendedorObj = LancamentoDebCredVendedor::
            where('codigo_vendedor', $representanteObj->id)
            ->where('codigo_motivo', 37)
            ->get();

        $mostrar_botoes_lancamentos = false;

        $total_sem_bonus = parserValor($totalComissao);
        $bonus = '';

        if($lancamentoDebCredVendedorObj->isEmpty()){
            if($representanteObj->tipo_usuario_id == 16){            

                if($representanteObj->responsavel != 17){
                    $totalComissao = $totalComissao + 600;
                    $bonus = '600,00';
                }

                $parcelas = 10;
                $valor = round(($totalComissao) / 10, 2);
                $valor_total = $totalComissao;
            }
            else{
                if($totalComissao < 500){
                    $parcelas = 1;
                    $valor = $totalComissao;
                }
                else if(floor($totalComissao/500) > 9){
                    $parcelas = 10;
                    $valor = round($totalComissao / 10, 2);
                }
                else{
                    $parcelas = floor($totalComissao/500);
                    $valor = round($totalComissao / ($parcelas>0?$parcelas:1), 2);
                }

                $valor_total = $totalComissao;
            }

            $result = [];

            $data = Carbon::parse('2021-03-01');

            for($x = 0; $x < $parcelas; $x++){
                $linha = [];
                $linha['parcela'] = parserNameMonth($data->format('m')) . '/' . $data->format('Y');
                $linha['data_sql'] = $data->format('Y-m-d');
                $linha['valor'] = parserValor($valor);

                $result[] = $linha;

                $data->addMonthNoOverflow();
            }

            if($x < 8 && ($valor % $x) > 0){
                $result[] = [
                    'parcela' => parserNameMonth($data->addMonthNoOverflow()->format('m')) . '/' . $data->format('Y'),
                    'data_sql' => $data->format('Y-m-d'),
                    'valor' => parserValor($valor % $x)
                ];
            }

            if((Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('ADM - Comissoes'))){
                $mostrar_botoes_lancamentos = true;
            }
        }
        else{
            $valor_total = $lancamentoDebCredVendedorObj->sum('valor');

            if($representanteObj->tipo_usuario_id == 16 && $representanteObj->responsavel != 17){
                $totalComissao = $totalComissao + 600;
                $bonus = '600,00';
            }

            $lancamentoDebCredVendedorObj->each(function($lancamento) use(&$result){
                
                $data = Carbon::parse($lancamento->data_lancamento);

                $result[] = [
                    'parcela' => $data->format('m/Y'),
                    'data_sql' => $data->format('Y-m-d'),
                    'valor' => parserValor($lancamento->valor)
                ];
            });
        }
        return view('programs.desconto_representante.modal.modal')->with(['representante' => $representante,'result' => $result, 'total_sem_bonus' => $total_sem_bonus, 'total' => parserValor($totalComissao), 'data' => parserData($virada), 'codigo_representante' => $codigo_representante, 'valor_total' => parserValor($valor_total), 'tipo_usuario' => $representanteObj->tipo_usuario_id, 'mostrar_botoes_lancamentos' => $mostrar_botoes_lancamentos, 'bonus' => $bonus]);

    }

    public function titulosDetalhes(Request $request){

        ini_set('memory_limit', '2M');
        ini_set('max_execution_time', 300);

        $fields = $request->only('representante');

        $representanteObj = User::where('codigo_representante', $fields['representante'])->first();
        $representante = $representanteObj->codigo_representante . ' - ' . $representanteObj->name;

        $comissaoGerentesVendedoresObj = ComissaoGerentesVendedores::get();

        $titulosEmAbertoObj = TitulosAbertosNasajonVirada::with('cliente')
            ->select('*',DB::raw(
                'CASE 
                    WHEN banco_codigo = \'Juridico Ragazzi\' THEN 1
                     ELSE percentual_comissao
                END as percentual_comissao'
            ))
            ->where('vendedor_codigo', $representanteObj->codigo_representante)
            ->get();

        $titulos = [];
        $totais = [
            'valor_total' => 0,
            'comissao' => 0,
            'valor_com_desconto' => 0,
            'desconto' => 0
        ];

        $estabelecimentos = returnEmpresasNasajonView();

        $titulosEmAbertoObj->each(function($titulo) use (&$titulos, &$totais, $estabelecimentos, $representanteObj, $comissaoGerentesVendedoresObj){
            $linha = [];

            if($representanteObj->codigo_representante == $titulo->vendedor_codigo){
                $comissao = $titulo->percentual_comissao;
            }
            else{
                $comissao = $comissaoGerentesVendedoresObj->first(function ($comissao) use($titulo, $representanteObj){
                    return $comissao->ano . '-' . str_pad($comissao->mes, 2, '0', STR_PAD_LEFT) == Carbon::parse($titulo->titulo_emissao)->format('Y-m') &&
                        $comissao->codigo_representante == $representanteObj->codigo_representante;
                })->porcentagem ?? 0;
            }

            $linha['estabelecimento'] = $estabelecimentos[intval($titulo->codigo)];
            $linha['estabelecimento_not_parse'] = intval($titulo->codigo);
            $linha['cliente'] = $titulo->cliente->nome . ' - ' . $titulo->cliente->cpf_cnpj;
            $linha['duplicata'] = $titulo->numero;
            $linha['parcela'] = $titulo->parcela;
            $linha['numero_documento'] = $titulo->nota_numero;
            $linha['nota_id'] = $titulo->nota_id;
            $linha['emissao'] = $titulo->titulo_emissao;
            $linha['vencimento'] = $titulo->vencimento;
            $linha['valor_total'] = parserValor($titulo->valor);
            $linha['desconto'] = parserValor($titulo->desconto);
            $linha['valor_com_desconto'] = parserValor($titulo->valor - $titulo->desconto);
            $linha['porcentagem'] = parserValor($comissao) . '%';
            $linha['comissao'] = parserValor(($titulo->valor) * ($comissao / 100));

            $titulos[] = $linha;

            $totais['valor_total'] += $titulo->valor;
            $totais['comissao'] += $titulo->valor * ($comissao / 100);
            $totais['desconto'] += $titulo->desconto;
            $totais['valor_com_desconto'] += $titulo->valor - $titulo->desconto;
        });

        $totais['valor_total'] = parserValor($totais['valor_total']);
        $totais['comissao'] = parserValor($totais['comissao']);
        $totais['desconto'] = parserValor($totais['desconto']);
        $totais['valor_com_desconto'] = parserValor($totais['valor_com_desconto']);

        $exportar = [
            'titulos' => $titulos,
            'total' => $totais,
			'codigo' => $fields['representante'],
            'nome' => $representante,
        ];
        
		$exportar = encrypt($exportar);

        return view('programs.desconto_representante.modal.abertos')->with(['representante' => $representante, 'totais' => $totais, 'titulos' => $titulos, 'exportar' => $exportar]);

    }

    public function gerarArquivoAbertosPdf(Request $request){

        ini_set('memory_limit', '2M');
        ini_set("pcre.backtrack_limit", "5000000");
        set_time_limit(300);

		$field = $request->only('exportar');
		try{
            $exportar = decrypt($field['exportar']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => $e,
                'response' => []
            ]);
		}
		$pdfFilePath = 'rep_'.$exportar['codigo'].'.pdf';
		$pdf = PDF::loadView(
			'pdf.comissao_vencidos_desconto', 
			[
				'representante' => $exportar['nome'],
				'cod_representante' => $exportar['codigo'],
                'titulos' => $exportar['titulos'],
                'total' => $exportar['total'],
			], 
			[], 
			['title' => 'Desconto de comissão por títulos vencidos - ' . $exportar['nome'], 'custom_font_dir' => '', 'custom_font_data' => [],'margin_bottom' => 1]
		);
        $pdf->save($pdfFilePath);

        return view('pdf.leitura')->with(['caminho' => '/'.$pdfFilePath]);
    }

    public function gerarArquivoAbertosExcel(Request $request){
		$field = $request->only('exportar');
		try{
            $exportar = decrypt($field['exportar']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => $e,
                'response' => []
            ]);
        }
        
        $comissaoXLSX = new ComissaoDuplicataXLSXExport(
            [
                'representante' => $exportar['nome'],
                'cod_representante' => $exportar['codigo'],
                'titulos' => $exportar['titulos'],
                'total' => $exportar['total'],
            ]
        );

		$pdfFilePath = 'rep_'.$exportar['codigo'].'_desconto.xlsx';
		return Excel::download(
            $comissaoXLSX, 'descontos_'.$exportar['codigo'].'.xlsx'
        );
    }

    public function lancarDebitos(Request $request){

        $fields = $request->only('representante', 'parcelamento');

        $representanteObj = User::where('codigo_representante', $fields['representante'])->first();

        $titulosEmAbertoQuery = TitulosAbertosNasajonVirada::
            select(DB::Raw('sum("valor" * ("percentual_comissao"/100)) as comissao'))
            ->where('vendedor_codigo', $representanteObj->codigo_representante);

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $titulosEmAbertoQuery->where('codigo', $fields['estabelecimento']);
        }

        $titulosEmAbertoObj = $titulosEmAbertoQuery->first();

        $totalComissao = $titulosEmAbertoObj->comissao;
    
        $representante = $representanteObj->codigo_representante . ' - ' . $representanteObj->name;
        $virada = Carbon::parse($titulosEmAbertoObj->max('created_at'))->format('Y-m-d');

        $codigo_representante = $representanteObj->codigo_representante;

        $parcelas = 1;

        if($representanteObj->tipo_usuario_id == 16){
            
            if($representanteObj->responsavel != 17){
                $totalComissao = $totalComissao + 600;
            }

            if($fields['parcelamento'] == 'parcelado'){
                $parcelas = 10;

                $valor = round(($totalComissao) / 10, 2);
                $valor_total = $totalComissao;
            }
            else{
                $valor = $totalComissao;
                $valor_total = $totalComissao;
            }
        }
        else{
            if($totalComissao < 500 || $fields['parcelamento'] != 'parcelado'){
                $parcelas = 1;
                $valor = $totalComissao;
            }
            else if(floor($totalComissao/500) > 9 && $fields['parcelamento'] == 'parcelado'){
                $parcelas = 10;
                $valor = round($totalComissao / 10, 2);
            }
            else{
                $parcelas = floor($totalComissao/500);
                $valor = round($totalComissao / ($parcelas>0?$parcelas:1), 2);
            }

            $valor_total = $totalComissao;
        }

        $data = Carbon::parse('2021-03-01');

        for($x = 0; $x < $parcelas; $x++){

            $lancamentoObj = new LancamentoDebCredVendedor;
            
            $lancamentoObj->parcela = $x+1;
            $lancamentoObj->data_lancamento = $data->format('Y-m-d');
            $lancamentoObj->valor = round($valor, 2);
            $lancamentoObj->num_documento = '000';
            $lancamentoObj->codigo_vendedor = $representanteObj->id;
            $lancamentoObj->codigo_motivo = 37;
            $lancamentoObj->tipo = 'D';
            $lancamentoObj->created_by = Auth::id();
            $lancamentoObj->updated_by = Auth::id();

            $lancamentoObj->save();

            $data->addMonthNoOverflow();
        }

        if($x < 8 && ($valor % $x) > 0){
            $lancamentoObj = new LancamentoDebCredVendedor;
            
            $lancamentoObj->parcela = $x+1;
            $lancamentoObj->data_lancamento = $data->format('Y-m-d');
            $lancamentoObj->valor = round($valor % $x, 2);
            $lancamentoObj->num_documento = '000';
            $lancamentoObj->codigo_vendedor = $representanteObj->id;
            $lancamentoObj->codigo_motivo = 37;
            $lancamentoObj->tipo = 'D';
            $lancamentoObj->created_by = Auth::id();
            $lancamentoObj->updated_by = Auth::id();

            $lancamentoObj->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lançamentos cadastrados com sucesso!',
            'error' => [],
            'response' => []
        ]);
    }
}
