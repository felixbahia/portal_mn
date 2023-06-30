@extends('layouts.page-dialog')
@section('content')
<div id="nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}">
	<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id='pedido-web-header-tab' data-toggle="tab" href="#pedido_web_header_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}" role="tab" aria-controls="pedido_web_header_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}" aria-selected="true">Dados da nota</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="pedido-web-itens-tab" data-toggle="tab" href="#pedido_web_itens_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}" role="tab" aria-controls="pedido_web_itens_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}" aria-selected="false">Itens da nota</a>
	</li>
	</ul>
	<div class="tab-content pt-3" id="PedidoHeaderContainer">
		<div class="tab-pane show active" id="pedido_web_header_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}" role="tabpanel" aria-labelledby="dados-tab">
			<div class="container border col-sm-12">
				<div class="row">
					<div class='col-sm-2'>
						<b>Nota Nº/Série:</b>
					</div>
					<div class='col-sm-3'>
						{{ $header_nota_array['nota_serie'] }}@if(isset($header_nota_array['prepago']) && $header_nota_array['prepago'] === true) - <b>Título Pré Pago</b>@endif
					</div>
					@if(isset($header_nota_array['nota_original']))
					<div class='col-sm-2'>
						<b>Nota Nº/Série Original:</b>
					</div>
					<div class='col-sm-3'>
						<a href="#" onclick="showNotasDetalhesOriginal('{{ $header_nota_array['nota_original']['estabelecimento'] }}', '', '{{ $header_nota_array['nota_original']['data_emissao'] }}', 'NASAJON', '{{ $header_nota_array['nota_original']['numero_documento'] }}')">
							{{ $header_nota_array['nota_original']['numero_documento'] }}
						</a>
					</div>
					@endif
				</div>	
				<div class="row">
					
					<div class="col-sm-2">
						<b>Natureza da operação:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['natureza'] }}
					</div>
					@if(isset($header_nota_array['pedido']))
					<div class="col-sm-2">
						<b>Pedido:</b>
					</div>
					<div class="col-sm-3">
						<a href='#' onclick="showItens('{{ $header_nota_array['pedido'] }}', '{{ $header_nota_array['origem'] }}', '{{ $header_nota_array['estabelecimento'] }}')">
							{{ $header_nota_array['pedido'] }}
						</a>
					</div>
					@endif
				</div>
				<div class="row">
					<div class="col-sm-2">
						<b>Data da emissão:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['data_emissao'] }}
					</div>
					
					<div class="col-sm-2">
						<b>Data da Saída</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['data_saida'] }}
					</div>
				</div>	
			</div>
			<div class="container border col-sm-12">
				<div class="row">
					<div class="col-sm-2">
						<b>Nome:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['nome'] }}
					</div>
					<div class="col-sm-2">
						<b>CPF/CNPJ:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['cpf_cnpj'] }}
					</div>
				</div>
			</div>
			<div class="container border col-sm-12">
				<div class="row">
					<div class="col-sm-2">
						<b>Base de cálculo de ICMS</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['base_calculo_icms'] }}
					</div>
					<div class="col-sm-2">
						<b>Valor do ICMS</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['valor_icms'] }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-2">
						<b>Base de cálculo de ICMS ST</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['base_calculo_substituicao'] }}
					</div>
					<div class="col-sm-2">
						<b>Valor do ICMS ST</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['valor_substituicao'] }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-2">
						<b>Valor Total do Frete</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['valor_total_frete'] }}
					</div>
					<div class="col-sm-2">
						<b>Valor do Seguro</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['valor_seguro'] }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-2">
						<b>Desconto</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['valor_desconto'] }}
					</div>
					<div class="col-sm-2">
						<b>Outras despesas</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['valor_outras_despesas'] }}
					</div>				
				</div>
			</div>
			<div class="container border col-sm-12">
				<div class="row">
					<div class="col-sm-2">
						<b>Total dos Produtos:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['valor_total_produtos'] }}
					</div>
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
						<b>Vendedor:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['vendedor'] }}
					</div>
					<div class="col-sm-2">
						<b>Comissão:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['comissao'] }}
					</div>
				</div>
			</div>	
			<div class="container border col-sm-12">
				<div class="row">
					<div class="col-sm-2">
						<b>Transporadora:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['transportadora_nome'] }}
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
						{{ $header_nota_array['qtd_volumes'] }}
					</div>
					<div class="col-sm-2">
						<b>Espécie:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['volume_especie'] }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-2">
						<b>Peso Líquido:</b>
					</div>
					<div class="col-sm-3">
						{{ $header_nota_array['peso_liquido'] }}
					</div>
				</div>
			</div>	
			<div class="row">
				<div class="col-sm-12">
					<button type="button" class="btn btn-info troca-aba float-right">Ir para produtos >></button>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="pedido_web_itens_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="content-table">
					@if(isset($header_nota_array['nota_original']))
					<table class="table table-striped table-filter-pedido-original-itens" id="table-filters-pedidos-original-itens">
					@else
					<table class="table table-striped table-filter-pedido-original-itens" id="table-filters-pedidos-itens">
					@endif
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
								<th colspan='7' class="text-right">Total: {{ $header_nota_array['valor_total'] }}</th>
								<th></th>
							</tr>
						</tfoot>
					</table>
				</div>
				<div class="col-sm-12" id="button-bottom">
					<button type="button" class="btn troca-aba btn-info"><< Voltar para o cabeçalho</button>	
				</div>
			</div>
		</div>
	</div>
</div>
	<script>
	$(document).ready( function(){
	    $(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find(".troca-aba").on("click", function(e){
	        e.preventDefault();
	        $(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find(".nav-link").not(".active, .dropdown-toggle").tab("show");
	    });
	});
	@if(isset($header_nota_array['nota_original']))
	table_filters_itens = $(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find("#table-filters-pedidos-original-itens").DataTable({
	@else
	table_filters_itens = $(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find("#table-filters-pedidos-itens").DataTable({
	@endif
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
        "order": [[ 1, 'desc' ]]
	});
	
    function showItens($pedido, $origem, $estabelecimento){
        var title = "Dados do pedido"
        
        if($origem!='nasajon'){
            title+=": "+ $pedido;
        }
        $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {_token: "{{ csrf_token() }}", origem: $origem, pedido: $pedido, estabelecimento: $estabelecimento},
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
            }
        });
	}
	@if(isset($header_nota_array['nota_original']))
	function showNotasDetalhesOriginal(estabelecimento, nota_fiscal, data, origem, num_nota){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                documento: nota_fiscal,
                data: data,
                origem:origem,
                num_nota: num_nota
            },
            success: function(body){
                createModal("nota_detalhes_original", "Detalhes da nota", body, 'modal-lg');
				$(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find('#nota_detalhes_original').css('z-index', $(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find('#nota_detalhes').css('z-index') +1);
				$(document).ready(function(){
					$(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find('#nota_detalhes_original').find(".troca-aba").on("click", function(e){
						e.preventDefault();
						$(document).find("#nota_{{ str_replace(['/', ' '], '', $header_nota_array['nota_serie']) }}").find('#nota_detalhes_original').find(".nav-link").not(".active, .dropdown-toggle").tab("show");
					})
				});
            }
        });
    }
	@endif
	</script>
@endsection