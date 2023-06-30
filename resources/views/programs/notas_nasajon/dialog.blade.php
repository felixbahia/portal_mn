@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs" id="NotasTab" role="tablist">
  	<li class="nav-item">
    	<a class="nav-link active" id='nota-header-tab' data-toggle="tab" href="#nota-header" role="tab" aria-controls="nota-header" aria-selected="true">Dados da nota</a>
  	</li>
	<li class="nav-item">
    	<a class="nav-link" id="nota-itens-tab" data-toggle="tab" href="#nota-itens" role="tab" aria-controls="nota-itens" aria-selected="false">Itens da nota</a>
	</li>
	<li class="nav-item">
    	<a class="nav-link" id="nota-documento-tab" data-toggle="tab" href="#nota-documento" role="tab" aria-controls="nota-documento" aria-selected="false">Documentos da nota</a>
	</li>
</ul>
<div class="tab-content" id="NotasTabContent">
	<div class="tab-pane show active" id="nota-header" role="tabpanel" aria-labelledby="nota-header-tab">
		<div class="container border col-sm-12">
			<div class="row">
				<div class='col-sm-2'>
					<b>Nota Nº/Série:</b>
				</div>
				<div class='col-sm-3'>
					{{ $header_nota_array['nota_serie'] }} @if($header_nota_array['pre_pago'] === true)  - <b>Título Pré Pago</b> @endif
				</div>
				<div class="col-sm-2">
					<b>Vendedor:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['vendedor_codigo'] }} - {{ $header_nota_array['vendedor_nome'] }}
				</div>
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
					<a href='#' onclick="showItens('{{ $header_nota_array['pedido_id'] }}', '{{ $header_nota_array['origem'] }}', '{{ $header_nota_array['estabelecimento'] }}')">
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
			<div class="row">
				<div class="col-sm-2">
					<b>Forma de Pagamento:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['forma_pagamento'] }}
				</div>
				<div class="col-sm-2">
					<b>Média Pagamento:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['forma_pagamento_media'] }}
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
					<b>Transportadora:</b>
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
				@if($header_nota_array['peso_confirmado'] != '')
				<div class="col-sm-2">
					<b>Peso Confirmado:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array['peso_confirmado'] }}
				</div>
				@endif
			</div>
			@if(!empty($header_nota_array["frete_pedido"]))
			<div class="row">
				<div class="col-sm-2">
					<b>Frete aplicado no preço:</b>
				</div>
				<div class="col-sm-3">
					{{ $header_nota_array["frete_pedido"] }}
				</div>
			</div>
			@endif
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
			                <th class='tb_number' rowspan="2">Quantidade</th>
			                <th class='tb_number' rowspan="2">Valor Unitário</th>
			                <th class='tb_number' rowspan="2">Valor Total</th>
			                <th class='tb_number' rowspan="2">Base ICMS</th>
			                <th class='tb_number' rowspan="2">Valor ICMS</th>
			                <th class='tb_number' rowspan="2">Valor IPI</th>
			                <th colspan="2">Alíquotas</th>
			            </tr>
			            <tr>
			                <th class='tb_number'>ICMS</th>
			                <th class='tb_number'>IPI</th>
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
			<div class="col-sm-12">
				<button type="button" class="btn troca-aba btn-info" id='ir_para_cabecalho'><< Voltar para o cabeçalho</button>
			</div>
			<div class="col-sm-12">
				<button type="button" class="btn btn-info troca-aba float-right" id="ir_para_documentos">Ir para documentos >></button>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="nota-documento" role="tabpanel" aria-labelledby="nota-documento-tab">
		<div class="container border col-sm-12">
			<ul>
				@if(!empty($header_nota_array["nota_id"]))
					<li>
						<a href='#' onclick="downloadDanfe()"><i class='btn-nota-pdf'></i>PDF</a>
					</li>
					<li>
						<a href='#' onclick="downloadXML()"><i class='btn-download'></i>XML</a>
					</li>
				@endif
				@if(!empty($header_nota_array["foto_canhoto"]))
					<li>
						<a href="{{ $header_nota_array["foto_canhoto"] }}"  class="thumb" data-toggle="popover" data-trigger="hover" title="Foto Canhoto" data-content="<img src='{{ $header_nota_array["foto_canhoto"] }}' width='250' class='rounded mx-auto d-block' alt='Canhoto'>"><i class='btn-foto-canhoto'></i>Canhoto</a>
					</li>
				@endif
			</ul>
		</div>
		<div class="col-sm-12" id="button-bottom">
			<button type="button" class="btn troca-aba btn-info" id='ir_para_itens'><< Voltar para itens</button>
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
		$(document).find('#nota-documento').hide();
		$(document).find('#nota-itens').show();
		$(document).find('#nota-header-tab').removeClass('active');
		$(document).find('#nota-documento-tab').removeClass('active');
		$(document).find('#nota-itens-tab').addClass('active');
	}
	else if($(this).attr("id") === "ir_para_cabecalho"){
		$(document).find('#nota-itens').hide();
		$(document).find('#nota-documento').hide();
		$(document).find('#nota-header').show();
		$(document).find('#nota-itens-tab').removeClass('active');
		$(document).find('#nota-documento-tab').removeClass('active');
		$(document).find('#nota-header-tab').addClass('active');
	}
	else if($(this).attr("id") === "ir_para_documentos"){
		$(document).find('#nota-itens').hide();
		$(document).find('#nota-header').hide();
		$(document).find('#nota-documento').show();
		$(document).find('#nota-header-tab').removeClass('active');
		$(document).find('#nota-itens-tab').removeClass('active');
		$(document).find('#nota-documento-tab').addClass('active');
	}
});

$('[data-toggle="popover"]').popover({
		container: 'body',
		html: true,
		show: true,
		template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
	});
	$('[data-toggle="popover"]').on('show.bs.popover', function () {
		var $this = $(this);
		$('.popover').not($this).each(function(){
			$("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
		});
		$("body").on("keyup", function(e){
			if(e.keyCode == 27){
				$($this).popover('hide');
			}
		});
	});

	$(document).find("a.thumb").fancybox(
		{
			onComplete: function(){
				$('#fancybox-content')
					.on('mouseover', function(){
						$(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
					})
					.on('mouseout', function(){
						$(this).children('#fancybox-img').css({'transform': 'scale(1)'});
					})
					.on('mousemove', function(e){
						$(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
				});
			}
		}
	);
});

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
			"targets": 'tb_number',
			"className": 'number_format'
		}
	],
	"order": [[ 2, 'desc' ]]
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

function downloadDanfe(){
	var erro = false;

	$.ajax({
		url: '{{ route('notas_nasajon.testar_danfe') }}',
		data: {
			'_token': '{{ csrf_token() }}',
			'id': '{{ $header_nota_array['nota_id'] }}'
		},
		type: 'POST',
		error: function(){
			message('Atenção', 'DANFE não disponível!')
			erro = true;
		}, success: function(){
			$('<form action="{{ route('notas_nasajon.modal.documentos.pdf') }}" method="POST" target="_blank">\
				<input type="hidden" name="_token" value="{{ csrf_token() }}">\
				<input type="hidden" name="id" value="{{ $header_nota_array['nota_id'] }}">\
			</form>').appendTo('body').submit().remove();
		}

	});

}

function downloadXML(){
	var erro = false;
	$.ajax({
		url: '{{ route('notas_nasajon.testar_xml') }}',
		data: {
			'_token': '{{ csrf_token() }}',
			'id': '{{ $header_nota_array['nota_id'] }}'
		},
		type: 'POST',
		error: function(){
			message('Atenção', 'XML não disponível!')
			erro = true;
		},
		success: function(){
			$('<form action="{{ route('notas_nasajon.modal.documentos.xml') }}" method="POST" target="_blank">\
				<input type="hidden" name="_token" value="{{ csrf_token() }}">\
				<input type="hidden" name="id" value="{{ $header_nota_array['nota_id'] }}">\
			</form>').appendTo('body').submit().remove();
		}
	});

}

</script>
@endsection
