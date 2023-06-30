@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs" id="NotasTab" role="tablist">
  	<li class="nav-item">
    	<a class="nav-link active" id='nota-header-tab' data-toggle="tab" href="#nota-header-cte" role="tab" aria-controls="nota-header-tab" aria-selected="true">Dados da nota</a>
  	</li>
	<li class="nav-item">
		<a class="nav-link" id="nota-itens-tab" data-toggle="tab" href="#nota-itens-cte" role="tab" aria-controls="nota-itens-tab" aria-selected="false">Itens da nota</a>
	</li>
</ul>
<div class="tab-content" id="NotasTabContent">
	<div class="tab-pane show active" id="nota-header-cte" role="tabpanel" aria-labelledby="nota-header-tab">
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
					{{$header_nota_array['natureza']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Data da emissão:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['data_emissao']}}
				</div>
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Emitente:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['emitente']}}
				</div>
				<div class="col-sm-2">
					<b>CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['emitente_cpf_cnpj']}}
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
				<div class="col-sm-2">
					<b>Valor do ICMS BASE CÁLCULO</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_icms_st']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor do ICMS ALIQUOTA</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_icms_aliquota']}}
				</div>
				<div class="col-sm-2">
					<b>Valor do ICMS</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_icms_valor']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor do IPI</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_ipi']}}
				</div>
				<div class="col-sm-2">
					<b>Valor do IPI DEVOLUÇÃO</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_ipi_devolucao']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor do PIS</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_pis']}}
				</div>
				<div class="col-sm-2">
					<b>Valor do COFINS</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_cofins']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor Total de Tributos</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_total_tributos']}}
				</div>
				<div class="col-sm-2">
					<b>Valor de Caixas e ETC</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_etc']}}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<b>Valor de Pedágio</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['valor_pedagio']}}
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
					{{ $header_nota_array['valor_total_frete'] }}
				</div>
			</div>
		</div>	
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Remetente:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['remetente']}}
				</div>
				<div class="col-sm-2">
					<b>CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['remetente_cnpj'] }}
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
					<b>Peso Bruto:</b>
				</div>
				<div class="col-sm-3">
					{{$header_nota_array['peso_bruto']}}
				</div>
			</div>
		</div>
		<div class="container border col-sm-12">
			<div class="row">
				<div class="col-sm-2">
					<b>Expedidor:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['expedidor'] }}
				</div>
				<div class="col-sm-2">
					<b>CNPJ:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['expedidor_cnpj'] }}
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<a class="btn btn-info troca-aba float-right" id="nota-itens-tab-button" data-toggle="tab" href="#nota-itens-cte" role="tab" aria-controls="nota-itens-tab-button" aria-selected="false">Ir para produtos >></a>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="nota-itens-cte" role="tabpanel" aria-labelledby="nota-itens-tab">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-nota-itens" id="table-filters-notas-itens-cte">
					<thead>
					<tr>
						<th>Cliente</th>
						<th>Nota</th>
						<th>Valor da Nota</th>
						<th>Peso da Nota</th>
						<th>Peso Real</th>
						<th>Valor Frete</th>
						<th>Pedágio</th>
						<th>GRIS</th>
					</tr>
					</thead>
					<tbody>
						@foreach ($itens_array as $value)
							<tr>
								<td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $value['cliente'] }}"> {{ $value['cliente'] }}</div></div></td>
								<td> 
									@if($value['tipo'] == 'nasajon')
										<a href='#' onclick="showNotasDetalhesNasajon('{{ $value['id'] }}')">{{ $value['nota'] }}</a>
									@elseif($value['tipo'] == 'importada')
										<a href='#' onclick="mostrarNota('{{ $value['id'] }}')">{{ $value['nota'] }}</a>
									@elseif($value['tipo'] == 'entrada')
										<a href='#' onclick="showNotasDetalhesEntrada('{{ $value['id'] }}')">{{ $value['nota'] }}</a>
									@else
										{{ $value['nota'] }}
									@endif
								</td>
								<td> {{ $value['valor_nota'] }}</td>
								<td> {{ $value['peso_nota'] }}</td>
								<td> {{ $value['peso_real'] }}</td>
								<td> {{ $value['valor_frete'] }}</td>
								<td> {{ $value['pedagio'] }}</td>
								<td> {{ $value['gris'] }}</td>
							</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<th>Total</th>
							<th colspan="2" class="text-right">{{ $total['valor_nota'] }}</th>
							<th class="text-right">{{ $total['peso_nota'] }}</th>
							<th class="text-right">{{ $total['peso_real'] }}</th>
							<th class="text-right">{{ $total['valor_frete'] }}</th>
							<th class="text-right">{{ $total['pedagio'] }}</th>
							<th class="text-right">{{ $total['gris'] }}</th>
						</tr>
					</tfoot>
				</table>
			</div>
			<div class="col-sm-12" id="button-bottom">
				<a class="btn troca-aba btn-info" id='nota-header-tab-button' data-toggle="tab" href="#nota-header-cte" role="tab" aria-controls="nota-header-tab-button" aria-selected="true"><< Voltar para o cabeçalho</a>
			</div>
		</div>
	</div>
</div>
<script>
	table_filters_itens = $("#table-filters-notas-itens-cte").DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"autoWidth": false,
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
				"targets": [1,2,3,4,5,6,7],
				"className": 'tb_number'
			},
			{
				"targets": [0],
				"width": '20%'
			}
		],
	});
	setTimeout(function(){
		table_filters_itens.draw();
	}, 200);

	function showNotasDetalhesNasajon($id_nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota
            },
            success: function(body){
                createModal("nota_detalhes_nfe", "Detalhes da nota", body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }

        });

    }

	function mostrarNota($id){
		$.ajax({
			url: '{{ route('modal.notas.importadas')}}',
			type: 'POST',
			data: {
				_token: '{{csrf_token()}}',
				id: $id
			},
			success: function(body){
				createModal("nota_detalhes_importada", "Detalhes da nota", body, 'modal-lg');
				$(".troca-aba").on("click", function(e){
					e.preventDefault();
					$(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
				})
			}
		});
	}

	function showNotasDetalhesEntrada($id){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.nota')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(body){
                createModal("nota_detalhes_entrada", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }
</script>
@endsection