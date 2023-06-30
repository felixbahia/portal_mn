@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs" id="FaturasTab" role="tablist">
  	<li class="nav-item">
    	<a class="nav-link active" id='fatura-header-tab' data-toggle="tab" href="#fatura-header" role="tab" aria-controls="fatura-header" aria-selected="true">Dados da fatura</a>
  	</li>
	<li class="nav-item">
    	<a class="nav-link" id="fatura-itens-tab" data-toggle="tab" href="#fatura-itens" role="tab" aria-controls="fatura-itens" aria-selected="false">Itens da fatura</a>
	</li>
</ul>
<div class="tab-content" id="FaturasTabContent">
	<div class="tab-pane show active" id="fatura-header" role="tabpanel" aria-labelledby="fatura-header-tab">
		<div class="container border col-sm-12">
			<div class="row">
				<div class='col-sm-2'>
					<b>Fatura Nº/Série:</b>
				</div>
				<div class='col-sm-3'>
					{{ $header_fatura_array['documento_cobranca_serie'] }}
				</div>
				<div class="col-sm-2">
					<b>Tipo de Cobrança</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['tipo_documento_cobranca'] }} - {{ $header_fatura_array['tipo_cobranca'] }}
				</div>
			</div>	
			<div class="row">
				<div class="col-sm-2">
					<b>Banco:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['codigo_banco'] }}  {{ $header_fatura_array['nome_banco'] }}
				</div>
				<div class="col-sm-2">
					<b>Agencia - C/C:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['numero_agencia'] }}  {{ $header_fatura_array['agencia_digito'] }} - {{ $header_fatura_array['conta_corrente'] }}  {{ $header_fatura_array['conta_corrente_digito'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Natereza da Operação:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['cfop'] }}
				</div>
				<div class="col-sm-2">
					<b>Data Limite Desconto:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['data_limite_pagamento_desconto'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Data da emissão:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['data_emissao'] }}
				</div>
				<div class="col-sm-2">
					<b>Data Vencimento</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['data_vencimento'] }}
				</div>
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Transportadora:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['transportadora_nome'] }} - {{ $header_fatura_array['filial_emissora_documento'] }}
				</div>
				<div class="col-sm-2">
					<b>CPF/CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['transportadora_cnpj'] }}
				</div>
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Base de cálculo de ICMS</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['base_calculo_icms'] }}
				</div>
				<div class="col-sm-2">
					<b>Valor do ICMS</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['valor_total_icms'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Base de cálculo de ICMS ST</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['base_calculo_icms_st'] }}
				</div>
				<div class="col-sm-2">
					<b>Valor do ICMS ST</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['valor_total_icms_st'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor Total do Frete</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['valor_total'] }}
				</div>
				<div class="col-sm-2">
					<b>Alíquota ICMS</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['aliquota_icms'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Desconto</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['valor_desconto'] }}
				</div>
				<div class="col-sm-2">
					<b>Juros</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['valor_juros_dia_atraso'] }}
				</div>				
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Total de Notas:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['total_nota'] }}
				</div>
				<div class="col-sm-2">
					<b>Valor Total das Notas:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['valor_total_nota'] }}
				</div>
			</div>
		</div>	
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Numero Protocolo:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['numero_protocolo_nf'] }}
				</div>
				<div class="col-sm-2">
					<b>Ação Documento:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['acao_documento'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Identificação Fatura Cliente:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['identificacao_pre_fatura_cliente'] }}
				</div>
				<div class="col-sm-2">
					<b>Percentual Juros:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['percentual_multa_atraso'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Peso Líquido:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['peso'] }} KG
				</div>
				<div class="col-sm-2">
					<b>Identificação Fatura Cliente Complementar:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_fatura_array['identificacao_complementar_pre_fatura_cliente'] }}
				</div>
			</div>
		</div>	
	    <div class="row">
			<div class="col-sm-12">
				<button type="button" class="btn btn-info troca-aba float-right" id="ir_para_itens-fatura">Ir para notas >></button>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="fatura-itens" role="tabpanel" aria-labelledby="fatura-itens-tab">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped" id="table-filters-fatura-itens">
			        <thead>
			            <tr>
							<th rowspan="2">Cliente / Fornecedor</th>
							<th rowspan="2">Destinatario / Emissor</th>
							<th rowspan="2">Nota</th>
							<th rowspan="2">Tipo</th>
			                <th rowspan="2">Valor NF Fatura</th>
							<th rowspan="2">Peso Fatura</th>
							<th rowspan="2">Valor Nota</th>
			                <th rowspan="2">Peso Nota</th>
							<th rowspan="2">Valor Frete</th>
			            </tr>
			        </thead>
			        <tbody>
			        	@foreach ($itens_array as $value)
			        	<tr>
							<td><div><div data-toggle="tooltip" data-html="true" data-original-title="{{ $value['cliente_fornecedor'] }}">{{ $value['cliente_fornecedor'] }}</div></div></td>
							<td><div><div data-toggle="tooltip" data-html="true" data-original-title="{{ $value['estabelecimento'] }}">{{ $value['estabelecimento'] }}</div></div></td>
							@if(!empty($value['nota_id_saida']))
								<td class="tb_number">
									<a href="#" class="modal-nota-saida" data-title="DETALHES DA NOTA: {{ $value["nota"] }}" data-route="{{ route('notas_nasajon.modal.exibir') }}" data-nota_id='{{ $value["nota_id_saida"] }}'>{{ $value['nota'] }}</a>
								</td>
							@elseif (!empty($value['nota_id_entrada']))
								<td class="tb_number">
									<a href="#" class="modal-nota-entrada" data-title="DETALHES DA NOTA: {{ $value["nota"] }}" data-route="{{ route('notas_entradas_nasajon.nota') }}" data-nota_id='{{ $value["nota_id_entrada"] }}'>{{ $value['nota'] }}</a>
								</td>
							@else
								<td class="tb_number">{{ $value["nota"] }}</td>
							@endif
								<td class="text-center"><div><div data-toggle="tooltip" data-html="true" data-original-title="{{ $value['tipo_descricao'] }}">{{ $value['tipo'] }}</div></div></td>
								<td class="tb_number"> {{ $value['valor_fatura'] }}</td>
								<td class="tb_number"> {{ $value['peso_fatura'] }}</td>
							@if(substr($value['valor_fatura'],0,3) == substr($value['valor_nota'],0,3))
								<td class="tb_number"> 
									{{ $value['valor_nota'] }} &nbsp&nbsp&nbsp&nbsp <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>
								</td>
							@else
								<td class="tb_number">
									{{ $value['valor_nota'] }} &nbsp&nbsp&nbsp&nbsp <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>
								</td>
							@endif
							@if(substr($value['peso_fatura'],0,3) == substr($value['peso_nota'],0,3))
								<td class="tb_number"> 
									{{ $value['peso_nota'] }} &nbsp&nbsp&nbsp&nbsp <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>
								</td>
							@else
								<td class="tb_number">
									{{ $value['peso_nota'] }} &nbsp&nbsp&nbsp&nbsp <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>
								</td>
							@endif
							<td class="tb_number">
								{{ $value['frete'] }}
							</td>
			        	</tr>
			        	@endforeach
			        </tbody>
			        <tfoot>
			        	<tr>
							<th>Total:</th>
							<th></th>
							<th></th>
							<th class="text-right"> {{ $quantidade_total['nota'] }}</th>
							<th class="text-right"> {{ parserValor($quantidade_total['valor_fatura']) }}</th>
							<th class="text-right"> {{ parserQtd($quantidade_total['peso_fatura']) }}</th>
							<th class="text-right"> {{ parserValor($quantidade_total['valor_nota']) }}</th>
							<th class="text-right"> {{ parserQtd($quantidade_total['peso_nota']) }}</th>
							<th class="text-right"> {{ $quantidade_total['frete'] }}</th>
			        	</tr>
			        </tfoot>
			    </table>
			</div>
			<div class="col-sm-12">
				<button type="button" class="btn troca-aba btn-info" id='ir_para_cabecalho-fatura'><< Voltar para o cabeçalho</button>
			</div>
		</div>
	</div>
</div>
<script>

$(document).ready(function (){
	$(document).find(".troca-aba").off("click");
	$(document).find(".troca-aba").on("click", function(e){
		e.preventDefault();
		if($(this).attr("id") === "ir_para_itens-fatura"){
			$(document).find('#fatura-header').hide();
			$(document).find('#fatura-itens').show();
			$(document).find('#fatura-header-tab').removeClass('active');
			$(document).find('#fatura-itens-tab').addClass('active');
		}
		else if($(this).attr("id") === "ir_para_cabecalho-fatura"){
			$(document).find('#fatura-itens').hide();
			$(document).find('#fatura-header').show();
			$(document).find('#fatura-itens-tab').removeClass('active');
			$(document).find('#fatura-header-tab').addClass('active');
		}
	});
	$(document).find(".modal-nota-saida").off("click");
	$(document).find(".modal-nota-saida").on("click", function(event){
		event.stopPropagation();
		showModalNotaSaida($(this));
	});
	$(document).find(".modal-nota-entrada").off("click");
	$(document).find(".modal-nota-entrada").on("click", function(event){
		event.stopPropagation();
		showModalNotaEntrada($(this));
	});

	table_filters_itens = $("#table-filters-fatura-itens").DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"language": {
			"decimal":        ",",
			"emptyTable":     "Nenhum registro encontrado",
			"infoPostFix":    "",
			"thousands":      ".",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum registro encontrado",
			"paginate": {
				"first":      "<<",
				"last":       ">>",
				"next":       ">",
				"previous":   "<"
			}
		},
		"columnDefs": [
			{ "class": "tb_number", type: 'num-fmt', targets: "tb_number" }
		],
	});
	table_filters_itens.draw();
});

function showModalNotaSaida($this){
    var $url = $($this).data("route");
    var $nota_id = $($this).data("nota_id");
    var $title = $($this).data("title");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id_nota : $nota_id},
        success: function(body){
            createModal("table-itens-nota-fatura", $title, body, 'modal-lg');
        }
    });
}

function showModalNotaEntrada($this){
    var $url = $($this).data("route");
    var $nota_id = $($this).data("nota_id");
    var $title = $($this).data("title");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id : $nota_id},
        success: function(body){
            createModal("table-itens-nota-fatura", $title, body, 'modal-lg');
        }
    });
}

</script>
@endsection