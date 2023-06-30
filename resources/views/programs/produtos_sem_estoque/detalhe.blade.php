@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link active" id='pedido-web-header-tab' data-toggle="tab" href="#pedido_web_header" role="tab" aria-controls="pedido_web_header" aria-selected="true">Dados do pedido</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="pedido-web-itens-tab" data-toggle="tab" href="#pedido_web_itens" role="tab" aria-controls="pedido_web_itens" aria-selected="false">Itens do pedido</a>
  </li>
</ul>
<div class="tab-content pt-3" id="PedidoHeaderContainer">
	<div class="tab-pane show active" id="pedido_web_header" role="tabpanel" aria-labelledby="dados-tab">
			
			<div class="row border-bottom">

				<div class="col-sm-1">
					<b>Estabelecimento:</b>
				</div>
				<div class="col-sm-2">
					{{$pedido['estabelecimento']}}
				</div>


		        <div class="col-sm-1">
					<b>Cliente:</b>
		    	</div>
		        <div class="col-sm-4">
		        	{{$pedido['cod_cliente'] }} - {{$pedido['NOME']}}
		    	</div>

		    </div>
		    	

		    <div class="row border-bottom">
				<div class="col-sm-2">
					<b>Condição de pagamento:</b>
		    	</div>
				<div class="col-sm-4">
					{{$pedido['condicao_pagamento_descr']}}
		    	</div>

			</div>

			<div class="row border-bottom">
				
				<div class="col-sm-2">
					<b>Transportadora:</b>
		    	</div>

				<div class="col-sm-2">
					 {{$pedido['transportadora_nome']}}
		    	</div>

		    
				<div class="col-sm-2">
					<b>Transportadora Redespacho:</b>
		    	</div>
				
				<div class="col-sm-2">
			    	{{$pedido['transportadora_redespacho_nome']}}
		    	</div>

		    	<div class="col-sm-2">
					<b>Tipo de Frete:</b>
				</div>
		    	<div class="col-sm-2">
			   		{{$pedido['tipo_frete']}}
				</div>

		    </div>

		    <div class="row border-bottom">
		        <div class="col-sm-3">
					<b>Nome do contato:</b>
		    	</div>    	
		
		        <div class="col-sm-3">
				     {{$pedido['nome_comprador']}}
		    	</div>    	
		
		    	<div class="col-sm-3 ">
					<b>Email do contato:</b>
		    	</div>
		
		    	<div class="col-sm-3 ">
					 {{$pedido['email_comprador']}}
		    	</div>
		    
		    </div>

			<div class="row border-bottom">
			
				<div class="col-sm-2">
					<b>Pedido futuro?</b>
				</div>

				<div class="col-sm-2">
					{{$pedido['pedido_futuro']}}
				</div>

				<div class="col-sm-2">
					<b>Data do pedido:</b>
				</div>

				<div class="col-sm-2">
					{{$pedido['data_pedido']}}
				</div>
			
				<div class="col-sm-2">
					<b>Previsão de entrega:</b> 
				</div>

				<div class="col-sm-2">
					{{$pedido['data_previsao_entrega']}}
				</div>

			</div>

			<div class="row border-bottom">
				
				<div class="col-sm-4">
					<b>Observação: </b> 			
				</div>
				<div class="col-sm-8">
					{{$pedido['observacao']}}
				</div>

			</div>

			<div class="row border-bottom">
				<div class="col-sm-2">
					<b>Aguardando aprovação de</b>
				</div>
				<div class="col-sm-2">
					{{$pedido['aprovacao']}}
				</div>

			</div>
		    
		    <div class="row border-bottoms">
				<div class="col-sm-12">
					<button type="button" class="btn btn-info troca-aba float-right">Ir para produtos >></button>
				</div>
			</div>

	</div>

	<div class="tab-pane" id="pedido_web_itens" role="tabpanel" aria-labelledby="dados-tab">

		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens" id="table-filters-pedidos-itens">
			        <thead>
			            <tr>
			                <th>Grupo</th>
			                <th>Código</th>
			                <th>Descrição</th>
			                <th>Marca</th>
			                <th>Linha</th>
			                <th>Quantidade</th>
			                <th>Preço unitário</th>
			                <th>Valor total</th>
			            </tr>
			        </thead>
			        <tbody>
			        	@foreach ($pedido['itens'] as $value)
			        	<tr>
			        		<td>{{$value['grupo']}}</td>
			        		<td>{{$value['cod_produto']}}</td>
			        		<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{$value['descricao']}}'>{{$value['descricao']}}</div></div></td>
			        		<td>{{$value['marca']}}</td>
			        		<td>{{$value['linha']}}</td>
			        		<td>{{parserValor($value['quantidade'])}}</td>
			        		<td>{{parserValor($value['preco_unitario'])}}</td>
			        		<td>{{parserValor($value['valor_total'])}}</td>
			        	</tr>
			        	@endforeach
			        </tbody>
			        <tfoot>
			        	<tr>
			        		<th colspan='7' class="text-right">Total:</th>
			        		<th></th>
			        	</tr>
			        </tfoot>
			    </table>
			</div>

			<div class="col-sm-12" id="button-bottom">
				<button type="button" class="btn troca-aba btn-info"><< Voltar para o cabeçalho</button>
			</div>

		</div>
		
		<div class="row">
		</div>

	</div>
</div>

<script>
    table_filters_produtos_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "scrollCollapse": true,
        "scrollY": "35vh",
        "drawCallback": function(settings) {
            $(document).find('.estoque, .preco').popover({
                container: 'body',
                html: true,
                show: true,
                trigger: 'manual'
            });


        },

        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
 
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\.]/g, '').replace(/[,]/g, '.') * 1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            var formato = { minimumFractionDigits: 2 }
 
            // Total over all pages
            total = api
                .column( 7 )
                .data()
                .reduce( function (a, b) {
                    return (intVal(a) + intVal(b));
            }, 0 );
            $( api.column( 7 ).footer() ).html(

                parseFloat(total.toFixed(2)).toLocaleString('pt-BR', formato)
            );
        },
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
            {
                "targets": [($('#table-filters-pedidos-itens thead th').length - 1), ($('#table-filters-pedidos-itens thead th').length - 2)],
                "orderable": false
            },
            {
                "targets": [5,6,7],
                "className": 'number_format',
            },
        ],
        "order": [[ 2, 'asc' ]]
    };

	table_filters_pedido_itens = $(document).find("#table-filters-pedidos-itens").DataTable(table_filters_produtos_options);

	$(document).ready(function(){
		$(".troca-aba, .nav-link").on("click", function(e){
            e.preventDefault();
            $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            table_filters_pedido_itens.draw();
            $(document).find(".popover").each(function(index, el) {
                $(document).find("[aria-describedby="+$(this).attr('id')+"]").popover('hide');
            });
        })
	})
</script>

@endsection