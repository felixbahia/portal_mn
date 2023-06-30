@extends('layouts.page-dialog')
@section('content')

<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog" id="form_filter_dialog" onsubmit="return false;">
        @csrf
        {{ Form::hidden('filtro', $fields, ['id' => 'filtro']) }}
        <div class="content-fields">
            <div class="col-lg-2">
                {{ Form::text('data_de', '', ['id' => 'data_de', 'class' => 'form-control data', 'placeholder' => 'Data de (DD/MM/YYYY)', 'maxlength' => '20']) }}
            </div>
            <div class="col-lg-3">
                {{ Form::text('data_ate', '', ['id' => 'data_ate', 'class' => 'form-control data', 'placeholder' => 'Data até (DD/MM/YYYY)', 'maxlength' => '20']) }}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
	<div class="content-table">
		<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-acompanhamento_pedido">
			<thead>
				<tr>
					<th>Estabelecimento</th>
					<th>Presencial</th>
					<th class="td_pedido">Pedido</th>
					<th>Cliente</th>
					<th>numero do terminal</th>
					<th>codigo autorização</th>
					<th>NSU</th>
					<th>tipo pagamento</th>
					<th>forma pagamento</th>
					<th>Numero Cartão</th>
					<th>bandeira Cartão</th>
					<th class="tb_number">Valor total</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>
<script>
    table_filters_acompanhamento_pedido_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "page": false,
        "pageLength": -1,
        "autoWidth": false,
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
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Pedidos Pagos',
                footer: true,
                autoFilter: true,
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    columns: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 11){
                                if(data != ''){
                                    numero = data.replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        }
                    }
                }
            },
        ],
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
        ],
        "order": [[ 2, 'asc' ]]
    };
	table_filters_acompanhamento_pedido = $(document).find("#table-filters-acompanhamento_pedido").DataTable(table_filters_acompanhamento_pedido_options);
	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_acompanhamento_pedido.draw();
		}, 500);

        $(document).find('#form_filter_dialog').find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        $(document).find('#form_filter_dialog').find('.data').mask('00/00/0000');

        $(document).find('#form_filter_dialog').find('#btn-filterform').off('click');
        $(document).find('#form_filter_dialog').find('#btn-filterform').on('click', function(){
            filterPagos();
        });
	});

    function filterClear(){
        table_filters_acompanhamento_pedido.clear().draw();
    }
    function filterPagos(){
        filterClear();
        form = $(document).find("#form_filter_dialog");
        data_form = form.serialize();
        filterClear();
        $.ajax({
            url: '{{ route('acompanhamento_cielo.filtro_pagos')}}',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status === 'success')
                linhas = [];
                for (var linha in callback.response){
                    temp_array = [
                        callback.response[linha].estabelecimento,
                        callback.response[linha].presencial,
                        createBtnPedido(callback.response[linha].pedido),
                        callback.response[linha].cliente,
                        callback.response[linha].terminal_numero,
                        callback.response[linha].codigo_autorizacao,
                        callback.response[linha].nsu,
                        callback.response[linha].tipo_pagamento,
                        callback.response[linha].forma_pagamento,
                        callback.response[linha].numero_cartao,
                        callback.response[linha].bandeira_cartao,
                        callback.response[linha].valor_total,
                    ];
                    linhas.push(temp_array);
                }
                table_filters_acompanhamento_pedido.rows.add(linhas).draw();
            }
        });
    }
    function createBtnPedido($pedido){
        var html = '';
        html = '<a href="#" onclick="abrirPedido('+$pedido+')">'+$pedido+'</a>'
        return html;
    }
    function abrirPedido($id){
        $.ajax({
            url: "{{ route("pedido_portal.detalhes") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido_id: $id
            },
            success: function(data){
                $id = "view-pedido";
                $title = "Detalhes do pedido"; 
                $body = data;
                $class = "modal-lg";
                createModal($id, $title, $body, $class);
            }
        });
    }
</script>
@endsection