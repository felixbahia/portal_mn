<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;

use App\FaturamentoOnline;
use App\FaturamentoNasajon;
use App\FaturamentoNotaNasajon;
use App\AliquotaPreco;
use App\FaturamentoOnlineCondicaoDePagamento;

class ImportDadosPrologos extends Controller{

	public function FaturamentoDia(){
        ini_set('memory_limit', '1024M');
		$this->importDadosNajasonDia();
		return false;
	}

	public function FaturamentoCompleto(){
		ini_set('memory_limit', '1024M');
		$data_final = Carbon::now()->setTime(23,59,59);
		$data_ini = Carbon::now()->subYear()->setTime(0,0,0)->firstOfMonth();
		$data_end = Carbon::now()->subYear()->lastOfMonth()->setTime(23,59,59);
		echo $data_ini;
		echo "\n";
		echo $data_end;
		echo "\n";
		echo $data_final->gte($data_ini);

		while($data_final->gte($data_ini)){
			$this->importDadosNajasonTudo($data_ini->format('Y-m-d 00:00:00'), $data_end->format('Y-m-d 00:00:00'));
			echo "\n";
			$data_ini = $data_ini->addMonth();
			echo $data_ini;
			echo "\n";
			$data_end = $data_end->addMonth();
			echo $data_end;
		}

		return false;
	}

	public function importDadosNajasonTudo($data_ini, $data_end){
		$chaves_iguinorar = FaturamentoOnline::select('chave_validacao', 'tabela_data')->whereBetween('data',[$data_ini,$data_end])->where('nasajon', true)->get()->toArray();
		$chaves_iguinorar = [];
		if(count($chaves_iguinorar)){
			$temp_chaves = $chaves_iguinorar;
			$chaves_iguinorar = [];
			foreach ($temp_chaves as $chave) {
				$chaves_iguinorar[] = $chave['chave_validacao'];
			}

			$faturamento_nasajon = FaturamentoNotaNasajon::
				whereRaw('case
					when "TIPO" ilike \'DEV%\' then
						"Data Lançamento" between \''.$data_ini.'\' and \''.$data_end.'\'
					else
						"Data de Emissão" between \''.$data_ini.'\' and \''.$data_end.'\'
					end')
				->whereNotIn(DB::raw("CONCAT('', \"Número Documento\", '', \"Código da Operação\", '', '0', '', REPLACE( REPLACE( REPLACE( to_char( \"Valor Documento\", '999,999,999,999.0000' ), '.', '' ), ',', '' ), ' ', '' ), '' , REPLACE( REPLACE( REPLACE( to_char( \"Data de Emissão\", 'YYYY MM DD 00 00 00' ), '-', '' ), ' ', '' ), ':', '' ) )"), $chaves_iguinorar)
				->with(['pedidoNasajon.pedido_pre_pago', 'cliente', 'estabelecimentoDetalhes', 'estabelecimentoDetalhes.cidadeDetalhes', 'pedidoNasajon.pedido_portal', 'detalhesDeCondicoesPagamentos'])
				->whereNotIn('Código da Operação', ['DEVOLUCAOCOMPRA','DEVOLUCAODECOMPRA'])
				->whereHas('cliente', function($query){
					$query
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '06311274%'")
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '05075884%'");
				})
				->get();
		}else{
			$faturamento_nasajon = FaturamentoNotaNasajon::whereRaw('case
					when "TIPO" ilike \'DEV%\' then
						"Data Lançamento" between \''.$data_ini.'\' and \''.$data_end.'\'
					else
						"Data de Emissão" between \''.$data_ini.'\' and \''.$data_end.'\'
					end')
				->with(['pedidoNasajon.pedido_pre_pago', 'cliente', 'estabelecimentoDetalhes', 'estabelecimentoDetalhes.cidadeDetalhes', 'pedidoNasajon.pedido_portal', 'detalhesDeCondicoesPagamentos'])
				->whereNotIn('Código da Operação', ['DEVOLUCAOCOMPRA','DEVOLUCAODECOMPRA'])
				->whereHas('cliente', function($query){
					$query
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '06311274%'")
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '05075884%'");
				})
				->get();
		}
		$aliquotasObj = AliquotaPreco::select(
			'origem',
			'estado',
			'frete_adicional'
		)
		->get();
		$faturamento_nasajon->each(function($faturamento) use ($aliquotasObj){
			$chave = ($faturamento["Número Documento"] . $faturamento["Código da Operação"] . '0' . number_format($faturamento["Valor Documento"],4,'','') . date("YmdHis", strtotime($faturamento["Data de Emissão"])));
			$FaturamentoOnlineObj = FaturamentoOnline::where('chave_validacao', $chave)->first();
			if(empty($FaturamentoOnlineObj)){
				$valor_prepago = 0;
				if(
					isset($faturamento->pedidoNasajon) &&
					isset($faturamento->pedidoNasajon->pedido_pre_pago) &&
					!empty($faturamento->pedidoNasajon->pedido_pre_pago)
				){
					$valor_prepago = floatval($faturamento["Valor Documento"]);
				}
				$data = '';
				if(substr_count($faturamento['TIPO'], "DEV") > 0){
					$data = $faturamento["Data Lançamento"];
				}else{
					$data = $faturamento["Data de Emissão"];
				}
				$FaturamentoOnlineObj = new FaturamentoOnline();
				$FaturamentoOnlineObj->estabelecimento = $faturamento["Estabelecimento"];
				$FaturamentoOnlineObj->numero_documento = $faturamento["Número Documento"];
				$FaturamentoOnlineObj->codigo_cadastro = !empty($faturamento["Cliente"]) ? $faturamento["Cliente"] : '000';
				$FaturamentoOnlineObj->codigo_vendedor = '0';
				$FaturamentoOnlineObj->tipo_operacao = $faturamento["Código da Operação"];
				$FaturamentoOnlineObj->data = $data;
				$FaturamentoOnlineObj->valor_compra = floatval($faturamento["Valor Documento"]);
				$FaturamentoOnlineObj->valor_frete = 0.0;
				$FaturamentoOnlineObj->valor_ipi = 0.0;
				$FaturamentoOnlineObj->valor_troco = 0.0;
				$FaturamentoOnlineObj->valor_prepago = $valor_prepago;
				$FaturamentoOnlineObj->chave_validacao = $chave;
				$FaturamentoOnlineObj->tabela_data = 'vw_faturamento';
				$FaturamentoOnlineObj->numero_nota = $faturamento["Número Documento"];
				$FaturamentoOnlineObj->serie_nota = '';
				$FaturamentoOnlineObj->nome_cliente = !empty($faturamento['Nome do Cliente']) ? $faturamento['Nome do Cliente'] : 'Não encontrado';
				$FaturamentoOnlineObj->nasajon = true;

				$valor_frete = 0;
				$tipo_frete = '';
				
				if(isset($faturamento->pedidoNasajon) && isset($faturamento->pedidoNasajon->pedido_portal)){
					$aliquota = $aliquotasObj->first(function($aliquota) use ($faturamento){
						if(
							isset($faturamento->cliente->uf) && ($faturamento->estabelecimentoDetalhes->cidadeDetalhes->uf)
						){
							if(
								$aliquota->origem == strtoupper($faturamento->estabelecimentoDetalhes->cidadeDetalhes->uf) &&
								$aliquota->estado == strtoupper($faturamento->cliente->uf)
							){
								return true;
							}
						}
					});
					if(
						$faturamento['Frete - Modalidade'] == 'Por conta do emitente' &&
						!empty($aliquota)
					){
						$tipo_frete = 'CIF';
						if($data < '2021-02-01'){
							$aliquota_porcentagem = $aliquota->frete_adicional / 1.1;
						} else {
							$aliquota_porcentagem = $aliquota->frete_adicional;
						}
						$valor_frete = round(floatval($faturamento["Valor Documento"]) - (floatval($faturamento["Valor Documento"]) / (1 + ($aliquota_porcentagem/ 100))), 2);
					} else {
						$tipo_frete = 'FOB';
						$valor_frete = 0;
					}
				}
	

				$FaturamentoOnlineObj->valor_frete_cobrado = $valor_frete;
				$FaturamentoOnlineObj->transportador_uuid = $faturamento["Transportadora - Id"];
				$FaturamentoOnlineObj->transportador_codigo = $faturamento["Transportadora - Código"];
				$FaturamentoOnlineObj->tipo_frete = $tipo_frete;
				$FaturamentoOnlineObj->nota_uuid = $faturamento["Id_Nota"];


				$FaturamentoOnlineObj->save();
			}else{
				$data = '';
				if(substr_count($faturamento['TIPO'], "DEV") > 0){
					$data = $faturamento["Data Lançamento"];
				}else{
					$data = $faturamento["Data de Emissão"];
				}
				$valor_frete = 0;
				$tipo_frete = '';
				
				if(isset($faturamento->pedidoNasajon) && isset($faturamento->pedidoNasajon->pedido_portal)){
					$aliquota = $aliquotasObj->first(function($aliquota) use ($faturamento){
						if(
							isset($faturamento->cliente->uf) && ($faturamento->estabelecimentoDetalhes->cidadeDetalhes->uf)
						){
							if(
								$aliquota->origem == strtoupper($faturamento->estabelecimentoDetalhes->cidadeDetalhes->uf) &&
								$aliquota->estado == strtoupper($faturamento->cliente->uf)
							){
								return true;
							}
						}
					});
					if(
						$faturamento['Frete - Modalidade'] == 'Por conta do emitente' &&
						!empty($aliquota)
					){
						$tipo_frete = 'CIF';
						if($data < '2021-02-01'){
							$aliquota_porcentagem = $aliquota->frete_adicional / 1.1;
						} else {
							$aliquota_porcentagem = $aliquota->frete_adicional;
						}
						$valor_frete = round(floatval($faturamento["Valor Documento"]) - (floatval($faturamento["Valor Documento"]) / (1 + ($aliquota_porcentagem/ 100))), 2);
					} else {
						$tipo_frete = 'FOB';
						$valor_frete = 0;
					}
				}
	

				$FaturamentoOnlineObj->valor_frete_cobrado = $valor_frete;
				$FaturamentoOnlineObj->tipo_frete = $tipo_frete;

				$FaturamentoOnlineObj->save();
			}
			echo $FaturamentoOnlineObj->numero_documento;
			
			if(!empty($FaturamentoOnlineObj->detalhesCondicoesPagamentos)){
				foreach($FaturamentoOnlineObj->detalhesCondicoesPagamentos as $value){
					$value->deleted_by = 1;
					$value->deleted_at = Carbon::now();
					$value->save();
				}
			}			

			foreach($faturamento->detalhesDeCondicoesPagamentos as $condicao_pagamento){
				$FaturamentoOnlineCondicaoDePagamentoObj = new FaturamentoOnlineCondicaoDePagamento;
				$FaturamentoOnlineCondicaoDePagamentoObj->created_by = 1;				
				$FaturamentoOnlineCondicaoDePagamentoObj->faturamento_online_id = $FaturamentoOnlineObj->id;
				$FaturamentoOnlineCondicaoDePagamentoObj->formapagamento_codigo = $condicao_pagamento->formapagamento_codigo;
				$FaturamentoOnlineCondicaoDePagamentoObj->formapagamento_descricao = $condicao_pagamento->formapagamento_descricao;
				$FaturamentoOnlineCondicaoDePagamentoObj->valor = $condicao_pagamento->formapagamento_valor;
				$FaturamentoOnlineCondicaoDePagamentoObj->save();
			}
		});
	}
	public function importDadosNajasonDia(){
		// $data_ini = date('Y-m-d', strtotime('-3 days'));
		$data_ini = date('Y-m-d', strtotime('-12 days'));
		$data_end = date('Y-m-d');
		$chaves_iguinorar = FaturamentoOnline::select('chave_validacao', 'tabela_data')->whereBetween('data',[$data_ini.' 00:00:00',$data_end.' 23:59:59'])->where('nasajon', true)->get()->toArray();
		if(count($chaves_iguinorar)){
			$temp_chaves = $chaves_iguinorar;
			$chaves_iguinorar = [];
			foreach ($temp_chaves as $chave) {
				$chaves_iguinorar[] = $chave['chave_validacao'];
			}

			$faturamento_nasajon = FaturamentoNotaNasajon::
				whereRaw('case
					when "TIPO" ilike \'DEV%\' then
						"Data Lançamento" between \''.$data_ini.' 00:00:00\' and \''.$data_end.' 23:59:59\'
					else
						"Data de Emissão" between \''.$data_ini.' 00:00:00\' and \''.$data_end.' 23:59:59\'
					end')
				->whereNotIn(DB::raw("CONCAT('', \"Número Documento\", '', \"Código da Operação\", '', '0', '', REPLACE( REPLACE( REPLACE( to_char( \"Valor Documento\", '999,999,999,999.0000' ), '.', '' ), ',', '' ), ' ', '' ), '' , REPLACE( REPLACE( REPLACE( to_char( \"Data de Emissão\", 'YYYY MM DD 00 00 00' ), '-', '' ), ' ', '' ), ':', '' ) )"), $chaves_iguinorar)
				->with(['pedidoNasajon.pedido_pre_pago', 'cliente', 'estabelecimentoDetalhes', 'estabelecimentoDetalhes.cidadeDetalhes', 'pedidoNasajon.pedido_portal', 'detalhesDeCondicoesPagamentos'])
				->whereNotIn('Código da Operação', ['DEVOLUCAOCOMPRA','DEVOLUCAODECOMPRA'])
				->whereHas('cliente', function($query){
					$query
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '06311274%'")
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '05075884%'");
				})
				->get();
		}else{
			$faturamento_nasajon = FaturamentoNotaNasajon::whereRaw('case
					when "TIPO" ilike \'DEV%\' then
						"Data Lançamento" between \''.$data_ini.' 00:00:00\' and \''.$data_end.' 23:59:59\'
					else
						"Data de Emissão" between \''.$data_ini.' 00:00:00\' and \''.$data_end.' 23:59:59\'
					end')
				->with(['pedidoNasajon.pedido_pre_pago', 'cliente', 'estabelecimentoDetalhes', 'estabelecimentoDetalhes.cidadeDetalhes', 'pedidoNasajon.pedido_portal', 'detalhesDeCondicoesPagamentos'])
				->whereNotIn('Código da Operação', ['DEVOLUCAOCOMPRA','DEVOLUCAODECOMPRA'])
				->whereHas('cliente', function($query){
					$query
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '06311274%'")
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '05075884%'");
				})
				->get();
		}
		$aliquotasObj = AliquotaPreco::select(
			'origem',
			'estado',
			'frete_adicional'
		)
		->get();

		$faturamento_nasajon->each(function($faturamento) use ($aliquotasObj){
			$chave = ($faturamento["Número Documento"] . $faturamento["Código da Operação"] . '0' . number_format($faturamento["Valor Documento"],4,'','') . date("YmdHis", strtotime($faturamento["Data de Emissão"])));

			if(FaturamentoOnline::where('chave_validacao', $chave)->doesntExist()){
				$valor_prepago = 0;
				if(
					isset($faturamento->pedidoNasajon) &&
					isset($faturamento->pedidoNasajon->pedido_pre_pago) &&
					!empty($faturamento->pedidoNasajon->pedido_pre_pago)
				){
					$valor_prepago = floatval($faturamento["Valor Documento"]);
				}
				$data = '';
				if(substr_count($faturamento['TIPO'], "DEV") > 0){
					$data = $faturamento["Data Lançamento"];
				}else{
					$data = $faturamento["Data de Emissão"];
				}
				$FaturamentoOnlineObj = new FaturamentoOnline();
				$FaturamentoOnlineObj->estabelecimento = $faturamento["Estabelecimento"];
				$FaturamentoOnlineObj->numero_documento = $faturamento["Número Documento"];
				$FaturamentoOnlineObj->codigo_cadastro = !empty($faturamento["Cliente"]) ? $faturamento["Cliente"] : '000';
				$FaturamentoOnlineObj->codigo_vendedor = '0';
				$FaturamentoOnlineObj->tipo_operacao = $faturamento["Código da Operação"];
				$FaturamentoOnlineObj->data = $data;
				$FaturamentoOnlineObj->valor_compra = floatval($faturamento["Valor Documento"]);
				$FaturamentoOnlineObj->valor_frete = 0.0;
				$FaturamentoOnlineObj->valor_ipi = 0.0;
				$FaturamentoOnlineObj->valor_troco = 0.0;
				$FaturamentoOnlineObj->valor_prepago = $valor_prepago;
				$FaturamentoOnlineObj->chave_validacao = $chave;
				$FaturamentoOnlineObj->tabela_data = 'vw_faturamento';
				$FaturamentoOnlineObj->numero_nota = $faturamento["Número Documento"];
				$FaturamentoOnlineObj->serie_nota = '';
				$FaturamentoOnlineObj->nome_cliente = !empty($faturamento['Nome do Cliente']) ? $faturamento['Nome do Cliente'] : 'Não encontrado';
				$FaturamentoOnlineObj->nasajon = true;

				$valor_frete = 0;
				$tipo_frete = '';
				if(isset($faturamento->pedidoNasajon) && isset($faturamento->pedidoNasajon->pedido_portal)){
					$aliquota = $aliquotasObj->first(function($aliquota) use ($faturamento){
						if(
							isset($faturamento->cliente->uf) && ($faturamento->estabelecimentoDetalhes->cidadeDetalhes->uf)
						){
							if(
								$aliquota->origem == strtoupper($faturamento->estabelecimentoDetalhes->cidadeDetalhes->uf) &&
								$aliquota->estado == strtoupper($faturamento->cliente->uf)
							){
								return true;
							}
						}
					});
					if(
						$faturamento['Frete - Modalidade'] == 'Por conta do emitente' &&
						!empty($aliquota)
					){
						$tipo_frete = 'CIF';
						if($data < '2021-02-01'){
							$aliquota_porcentagem = $aliquota->frete_adicional / 1.1;
						} else {
							$aliquota_porcentagem = $aliquota->frete_adicional;
						}
						$valor_frete = round(floatval($faturamento["Valor Documento"]) - (floatval($faturamento["Valor Documento"]) / (1 + ($aliquota_porcentagem/ 100))), 2);
					} else {
						$tipo_frete = 'FOB';
						$valor_frete = 0;
					}
				}
	

				$FaturamentoOnlineObj->valor_frete_cobrado = $valor_frete;
				$FaturamentoOnlineObj->transportador_uuid = $faturamento["Transportadora - Id"];
				$FaturamentoOnlineObj->transportador_codigo = $faturamento["Transportadora - Código"];
				$FaturamentoOnlineObj->tipo_frete = $tipo_frete;
				$FaturamentoOnlineObj->nota_uuid = $faturamento["Id_Nota"];

				$FaturamentoOnlineObj->save();

				foreach($faturamento->detalhesDeCondicoesPagamentos as $condicao_pagamento){
					$FaturamentoOnlineCondicaoDePagamentoObj = new FaturamentoOnlineCondicaoDePagamento;
					$FaturamentoOnlineCondicaoDePagamentoObj->faturamento_online_id = $FaturamentoOnlineObj->id;
					$FaturamentoOnlineCondicaoDePagamentoObj->formapagamento_codigo = $condicao_pagamento->formapagamento_codigo;
					$FaturamentoOnlineCondicaoDePagamentoObj->formapagamento_descricao = $condicao_pagamento->formapagamento_descricao;
					$FaturamentoOnlineCondicaoDePagamentoObj->valor = $condicao_pagamento->formapagamento_valor;
					$FaturamentoOnlineCondicaoDePagamentoObj->created_by = 1;
					$FaturamentoOnlineCondicaoDePagamentoObj->save();
				}
			}
		});
	}

	public function checkFaturamentoCanceladosDiaNasajon(){
        ini_set('memory_limit', '1024M');

		$ids = [];
		$data_ini = date('Y-m-d', strtotime('-3 days'));
		$data_fim = date('Y-m-d');

		$faturamento_nasajon = FaturamentoNotaNasajon::whereRaw('case
				when "TIPO" ilike \'DEV%\' then
					"Data Lançamento" between \''.$data_ini.' 00:00:00\' and \''.$data_fim.' 23:59:59\'
				else
					"Data de Emissão" between \''.$data_ini.' 00:00:00\' and \''.$data_fim.' 23:59:59\'
				end')
			->whereHas('cliente', function($query){
				$query
					->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '06311274%'")
					->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '05075884%'");
			})->
			// where('Cfop', '!=', '5051')->
			get()->
			toArray();
		$chaves = [];
		foreach ($faturamento_nasajon as $key => $faturamento) {
			$faturamento = (array) $faturamento;
			$chave = ($faturamento["Número Documento"] . $faturamento["Código da Operação"] . '0' . str_replace(".", "", str_replace(",", "", number_format($faturamento["Valor Documento"],4,',','.') ) ) . date("YmdHis", strtotime($faturamento["Data de Emissão"])));
			$FaturamentoOnlineObj = FaturamentoOnline::where('chave_validacao', $chave)->first();
			if(!is_null($FaturamentoOnlineObj)){
				$chaves[] = $chave;
			}
		}
		unset($faturamento_nasajon);
		$FaturamentoOnlineObj = FaturamentoOnline::
			whereBetween('data',[$data_ini.' 00:00:00', $data_fim.' 23:59:59'])->
			where('nasajon', true)->
			whereNotIn('chave_validacao', $chaves)->
			get();
		foreach($FaturamentoOnlineObj as $faturamento){
			foreach($faturamento->detalhesCondicoesPagamentos as $condicao){
				$condicao->delete();
			}
			$faturamento->delete();
		}
	}

	public function checkFaturamentoCanceladosCompletoNasajon(){
        ini_set('memory_limit', '1024M');
		$data_ini = '2018-06-01 00:00:00';
		//$data_ini = '2018-11-01 00:00:00';
		$data_end = date('Y-m-d 23:59:59');

		$data = date('Y-m-d', strtotime("-1 months", strtotime($data_ini)));
		$faturamento = [];
		do{
			$data = date('Y-m-d', strtotime("+1 months", strtotime($data)));
			$faturamento_nasajon = FaturamentoNotaNasajon::whereRaw('case
				when "TIPO" ilike \'DEV%\' then
					"Data Lançamento" between \''.$data_ini.' 00:00:00\' and \''.$data_end.' 23:59:59\'
				else
					"Data de Emissão" between \''.$data_ini.' 00:00:00\' and \''.$data_end.' 23:59:59\'
				end')->
				// where('Cfop', '!=' ,'5051')->
				whereHas('cliente', function($query){
					$query
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '06311274%'")
						->WhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '05075884%'");
				})->
				get()->
				toArray();
			foreach ($faturamento_nasajon as $key => $faturamento) {
				$faturamento = (array) $faturamento;
				$chave = ($faturamento["Número Documento"] . $faturamento["Código da Operação"] . '0' . str_replace(".", "", str_replace(",", "", number_format($faturamento["Valor Documento"],4,',','.') ) ) . date("YmdHis", strtotime($faturamento["Data de Emissão"])));
				$FaturamentoOnlineObj = FaturamentoOnline::where('chave_validacao', $chave)->first();
				if(is_null($FaturamentoOnlineObj)){
					continue;
				}
				$FaturamentoOnlineObj->delete();
			}
			unset($faturamento_nasajon);
		}while(strtotime($data) <= strtotime($data_end));
	}

}
