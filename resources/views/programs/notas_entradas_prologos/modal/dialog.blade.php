@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs" id="NotasTab" role="tablist">
  	<li class="nav-item">
    	<a class="nav-link active" id='nota-header-tab' data-toggle="tab" href="#nota-header" role="tab" aria-controls="nota-header-tab" aria-selected="true">Dados da nota</a>
  	</li>
	<li class="nav-item">
    	<a class="nav-link" id="nota-itens-tab" data-toggle="tab" href="#nota-itens" role="tab" aria-controls="nota-itens-tab" aria-selected="false">Itens da nota</a>
	</li>
</ul>
<div class="tab-content" id="NotasTabContent">
	<div class="tab-pane show active" id="nota-header" role="tabpanel" aria-labelledby="nota-header-tab">
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
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Nome:</b>
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
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Total da Nota:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['valor_total'] }}
				</div>
			</div>
		</div>	
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Transportadora:</b>
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
			</div>
		</div>	
	    <div class="row">
			<div class="col-sm-12">
				<button type="button" class="btn btn-info troca-aba float-right" id="ir_para_itens">Ir para produtos >></button>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="nota-itens" role="tabpanel" aria-labelledby="nota-itens-tab">
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
			                <th rowspan="2">Quantidade</th>
			                <th rowspan="2">Valor Unitário</th>
			                <th rowspan="2">Valor Total</th>
			                <th rowspan="2">Base ICMS</th>
			                <th rowspan="2">Valor ICMS</th>
			                <th rowspan="2">Valor IPI</th>
			                <th colspan="2">Alíquotas</th>
			            </tr>
			            <tr>
			                <th>ICMS</th>
			                <th>IPI</th>
			            </tr>
			        </thead>
			        <tbody>
			        	@foreach ($itens_array as $value)
			        	<tr>
							<td> {{ $value['codigo'] }}</td>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $value['descricao'] }}">{{ $value['descricao'] }}</div></div></td>
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
			<div class="col-sm-12" id="button-bottom">
				<button type="button" class="btn troca-aba btn-info" id='ir_para_cabecalho'><< Voltar para o cabeçalho</button>	
			</div>
		</div>
	</div>
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
				"targets": [6,7,8,9,10,11,12,13],
				"className": 'number_format'
			}
		],
		"order": [[ 2, 'desc' ]]
	});
	$(document).find(".troca-aba").off("click");
	$(document).find(".troca-aba").on("click", function(e){
		e.preventDefault();
		if($(this).attr("id") === "ir_para_itens"){
			$(document).find('#nota-header').hide();
			$(document).find('#nota-itens').show();
			$(document).find('#nota-header-tab').removeClass('active');
			$(document).find('#nota-itens-tab').addClass('active');
		}
		else if($(this).attr("id") === "ir_para_cabecalho"){
			$(document).find('#nota-itens').hide();
			$(document).find('#nota-header').show();
			$(document).find('#nota-itens-tab').removeClass('active');
			$(document).find('#nota-header-tab').addClass('active');
		}
	});
	
</script>
@endsection