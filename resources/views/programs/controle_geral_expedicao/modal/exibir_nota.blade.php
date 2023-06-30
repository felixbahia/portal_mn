@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs" id="NotasTab" role="tablist">
  	<li class="nav-item">
    	<a class="nav-link active" id='nota-header-tab' data-toggle="tab" href="#nota-header" role="tab" aria-controls="nota-header" aria-selected="true">Dados da nota</a>
  	</li>
	<li class="nav-item">
    	<a class="nav-link" id="nota-itens-tab" data-toggle="tab" href="#nota-itens" role="tab" aria-controls="nota-itens" aria-selected="false">Itens da nota</a>
	</li>
</ul>
<div class="tab-content" id="NotasTabContent">
	<div class="tab-pane show active" id="nota-header" role="tabpanel" aria-labelledby="nota-header-tab">
		<div class="container border col-sm-12">
			<div class="row">
				<div class='col-sm-2'>
					<b>Nota:</b>
				</div>
				<div class='col-sm-3'>
					{{ $header_nota_array['numero'] }}
				</div>
			</div>	
			<div class="row">
				<div class="col-sm-2">
					<b>Natureza da operação:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['natureza_operacao'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Data da emissão:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['emissao'] }}
				</div>
				<div class="col-sm-2">
					<b>Data de Entrada:</b>
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
					{{ $header_nota_array['nome_cliente'] }}
				</div>
				<div class="col-sm-2">
					<b>CPF/CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['cnpj_cliente'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Fornecedor:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['nome_fornecedor'] }}
				</div>
				<div class="col-sm-2">
					<b>CPF/CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['cnpj_fornecedor'] }}
				</div>
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Valor do Frete:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['frete'] }}
				</div>
				<div class="col-sm-2">
					<b>Valor do Seguro:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['seguro'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Desconto:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['desconto'] }}
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
					<b>Quantidade de Volumes:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['quantidade_volume'] }}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Peso Volume:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['peso_volume'] }}
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
				<table class="table table-striped" id="table-filters-nota-itens">
			        <thead>
			            <tr>
			                <th rowspan="2">Código</th>
			                <th rowspan="2">Descrição</th>
			                <th rowspan="2">Quantidade</th>
			            </tr>
			        </thead>
			        <tbody>
			        	@foreach ($itens_array as $value)
			        	<tr>
							<td> {{ $value['codigo'] }}</td>
							<td><div><div data-toggle="tooltip" data-html="true" data-original-title="{{ $value['descricao'] }}">{{ $value['descricao'] }}</div></div></td>
							<td class="tb_number"> {{ $value['quantidade'] }}</td>
			        	</tr>
			        	@endforeach
			        </tbody>
			        <tfoot>
			        	<tr>
			        		<th colspan='9' class="text-right">Total: {{ parserQtd($quantidade_total) }}</th>
			        	</tr>
			        </tfoot>
			    </table>
			</div>
			<div class="col-sm-12">
				<button type="button" class="btn troca-aba btn-info" id='ir_para_cabecalho'><< Voltar para o cabeçalho</button>
			</div>
		</div>
	</div>
</div>
<script>

$(document).ready(function (){
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
});

table_filters_itens = $("#table-filters-nota-itens").DataTable({
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

</script>
@endsection