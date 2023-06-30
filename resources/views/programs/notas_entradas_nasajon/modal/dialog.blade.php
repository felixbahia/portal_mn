@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs" id="NotasTab" role="tablist">
  	<li class="nav-item">
    	<a class="nav-link active" id='nota-header-tab' data-toggle="tab" href="#nota-header" role="tab" aria-controls="nota-header" aria-selected="true">Dados da nota</a>
  	</li>
	<li class="nav-item">
    	<a class="nav-link" id="nota-itens-tab" data-toggle="tab" href="#nota-itens" role="tab" aria-controls="nota-itens" aria-selected="false">Itens da nota</a>
	</li>
	@if(!empty($faturas))
		<li class="nav-item">
			<a class="nav-link" id="faturas-tab" data-toggle="tab" href="#faturas-link" role="tab" aria-controls="faturas" aria-selected="false">Fatura</a>
		</li>
	@endif
</ul>
<div class="tab-content">
	<div class="tab-pane show active" id="nota-header" role="tabpanel" aria-labelledby="nota-header">
		<div class="container border col-sm-12">
			<div class="row">
				<div class='col-sm-2'>
					<b>Nota Nº:</b>
				</div>
				<div class='col-sm-3'>
					{{$header_nota_array['nota_numero']}}
				</div>
				<div class="col-sm-2">
					<b>Natureza da operação:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['natureza'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Data da emissão:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['data_emissao'] }}
				</div>
				<div class="col-sm-2">
					<b>Data de entrada</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['data_entrada'] }}
				</div>

			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Forma de Pagamento:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['forma_pagamento'] }}
				</div>
				<div class="col-sm-2">
					<b>Valor do Pagamento:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['pagamento_valor'] }}
				</div>
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Cliente:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['nome']}}
				</div>
				<div class="col-sm-2">
					<b>CPF/CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['cpf_cnpj']}}
				</div>
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Valor do ICMS ST</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_icms_st']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor Total do Frete</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_total_frete']}}
				</div>
				<div class="col-sm-2">
					<b>Valor do Seguro</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_seguro']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Desconto</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_desconto']}}
				</div>
				<div class="col-sm-2">
					<b>Outras despesas</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_outras_despesas']}}
				</div>				
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor AFRAMM</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['valor_aframm'] }}
				</div>
				<div class="col-sm-2">
					<b>Valor II</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['valor_ii'] }}
				</div>				
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor PIS</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['valor_pis'] }}
				</div>
				<div class="col-sm-2">
					<b>Valor COFINS</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['valor_cofins'] }}
				</div>				
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Total da Nota:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['valor_total'] }}
				</div>
				<div class="col-sm-2">
					<b>Total de produtos:</b>
				</div>
				<div class="col-sm-3">
					{{ (isset($header_nota_array['valor_total_produtos'])) ? $header_nota_array['valor_total_produtos'] : '' }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Impostos:</b>
				</div>
				<div class="col-sm-3">
					{{ (isset($header_nota_array['valor_impotos'])) ? $header_nota_array['valor_impotos'] : '' }}
				</div>
			</div>
		</div>	
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Fornecedor:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['transportadora_nome']}}
				</div>
				<div class="col-sm-2">
					<b>CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['transportadora_cnpj'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Quantidade de Volumes:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['qtd_volumes']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Peso Líquido:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['peso_liquido']}}
				</div>
				<div class="col-sm-2">
					<b>CHAVE:</b>
				</div>
				<div class="col-sm-3">
					{{ (isset($header_nota_array['chave'])) ? $header_nota_array['chave'] : '' }}
				</div>
			</div>
		</div>	
	    <div class="row">
			<div class="col-sm-12">
				<button type="button" class="btn btn-info troca-aba float-right" id="ir_para_itens">Ir para produtos >></button>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="nota-itens" role="tabpanel" aria-labelledby="nota-itens">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-nota-itens" id="table-filters-notas-itens">
			        <thead>
			            <tr>
			                <th rowspan="2">Código</th>
			                <th rowspan="2">Descrição</th>
			                <th rowspan="2">NCM/SH</th>
			                <th rowspan="2">CST</th>
			                <th rowspan="2">CFOP</th>
			                <th rowspan="2">UN</th>
			                <th class="td_number" rowspan="2">Quantidade</th>
			                <th class="td_number" rowspan="2">Valor Unitário</th>
			                <th class="td_number" rowspan="2">Valor Total</th>
			                <th class="td_number" rowspan="2">Base ICMS</th>
			                <th class="td_number" rowspan="2">Valor ICMS</th>
			                <th class="td_number" rowspan="2">Valor IPI</th>
			                <th colspan="2">Alíquotas</th>
			                <th class="td_number" rowspan="2">Outras Despesas</th>
			                <th class="td_number" rowspan="2">AFRAMM</th>
			                <th class="td_number" rowspan="2">PIS</th>
			                <th class="td_number" rowspan="2">COFINS</th>
			                <th class="td_number" rowspan="2">Valor II</th>
			            </tr>
			            <tr>
			                <th class="td_number">ICMS</th>
			                <th class="td_number">IPI</th>
			            </tr>
			        </thead>
			        <tbody>
			        	@foreach ($itens_array as $value)
							<tr>
								<td> {{ $value['codigo'] }}</td>
								<td><div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $value['descricao'] }}">{{ $value['descricao'] }}</div></div></td>
								<td> {{ $value['ncm'] }}</td>
								<td> {{ $value['cst'] }}</td>
								<td> {{ $value['cfo'] }}</td>
								<td> {{ $value['un'] }}</td>
								<td> {{ $value['quantidade'] }}</td>
								<td> {{ $value['preco_unitario'] }}</td>
								<td> {{ $value['valor_total'] }}</td>
								<td> {{ $value['base_icms'] }}</td>
								<td> {{ $value['valor_icms'] }}</td>
								<td> {{ $value['valor_ipi'] }}</td>
								<td> {{ $value['aliquota_icms'] }}</td>
								<td> {{ $value['aliquota_ipi'] }}</td>
								<td> {{ $value['outras_despesas'] }}</td>
								<td> {{ $value['aframm'] }}</td>
								<td> {{ $value['pis'] }}</td>
								<td> {{ $value['cofins'] }}</td>
								<td> {{ $value['valor_ii'] }}</td>
							</tr>
			        	@endforeach
			        </tbody>
			        <tfoot>
			        	<tr>
			        		<th colspan='9' class="text-right">Total: {{ $header_nota_array['valor_total'] }}</th>
			        		<th></th>
			        	</tr>
			        </tfoot>
			    </table>
			</div>
			<div class="row mt-1">
				<div class="col-sm-6" id="button-bottom">
					<button type="button" class="btn troca-aba btn-info" id='ir_para_cabecalho'><< Voltar para o cabeçalho</button>	
				</div>
				@if(!empty($faturas))
					<div class="col-sm-6">
						<button type="button" class="btn btn-info troca-aba float-right" id="ir_para_faturas">Ir para Faturas >></button>
					</div>
				@endif
			</div>
		</div>
	</div>
	@if(!empty($faturas))
		<div class="tab-pane" id="faturas-link" role="tabpanel" aria-labelledby="faturas-link">
			<div class="content-dialog-table">
				<div class="content-table">
					<table class="table table-striped table-filter-nota-itens" id="table-filters-faturas">
						<thead>
							<tr>
								<th>Fatura</th>
								<th>Duplicata</th>
								<th class="sort-date">Vencimento</th>
								<th class="td_number">Valor</th>
							</tr>
						</thead>
						<tbody>
							@foreach ($faturas as $fatura)
								<tr>
									<td> {{ $fatura['fatura'] }}</td>
									<td>{{ $fatura['duplicata'] }}</td>
									<td> {{ $fatura['vencimento'] }}</td>
									<td> {{ $fatura['valor'] }}</td>
								</tr>
							@endforeach
						</tbody>
						<tfoot>
							<tr>
								<th colspan='4' class="text-right">Total: {{ $total_fatura }}</th>
							</tr>
						</tfoot>
					</table>
				</div>
			
			<div class="col-sm-12" id="button-bottom">
				<button type="button" class="btn troca-aba btn-info" id='voltar_para_itens'><< Voltar para os Itens</button>	
			</div>
		</div>
	@endif
</div>
<script>
	table_filters_itens = $("#table-filters-notas-itens").DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"language": {
			"decimal":        ".",
			"emptyTable":     "Nenhum registro encontrado",
			"infoPostFix":    "",
			"thousands":      ",",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum registro encontrado",
			"paginate": {
				"first":      "<<",
				"last":       ">>",
				"next":       ">",
				"previous":   "<"
			}        },
		"columnDefs": [
			{
				"targets": 'td_number',
				"className": 'number_format'
			}
		],
		"order": [[ 2, 'desc' ]]
	});

	table_filters_faturas = $("#table-filters-faturas").DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"language": {
			"decimal":        ".",
			"emptyTable":     "Nenhum registro encontrado",
			"infoPostFix":    "",
			"thousands":      ",",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum registro encontrado",
			"paginate": {
				"first":      "<<",
				"last":       ">>",
				"next":       ">",
				"previous":   "<"
			}        },
		"columnDefs": [
			{
				"targets": 'td_number',
				"className": 'number_format'
			},
            {
                "class": "text_date", 
                "targets": "sort-date",
            },
		],
		"order": [[ 2, 'desc' ]]
	});

	$(document).find(".troca-aba").off("click");
	$(document).find(".troca-aba").on("click", function(e){
		e.preventDefault();
		if($(this).attr("id") === "ir_para_itens"){
			$('#NotasTab a[href="#nota-itens"]').tab('show');
		}
		else if($(this).attr("id") === "ir_para_cabecalho"){
			$('#NotasTab li:first-child a').tab('show');
		}
		else if($(this).attr("id") === "ir_para_faturas"){
			$('#NotasTab a[href="#faturas-link"]').tab('show');
		}
		else if($(this).attr("id") === "voltar_para_itens"){
			$('#NotasTab a[href="#nota-itens]').tab('show');
		}
	});
	
</script>
@endsection
