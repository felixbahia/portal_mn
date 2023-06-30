@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem para {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimentos'])}}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::select('status', ['saldo_aberto' => 'SALDO EM ABERTO','saldo_encerrado' => 'SALDO ENCERRADO'], '', ['id' => 'status', 'class' => 'form-control', 'placeholder' => 'Status'])}}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                {{ Form::text('data_inicio_entrega','',['id' => 'data_inicio_entrega', 'class' => 'data', 'placeholder' => 'Data Início Entrega DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim_entrega', '',['id' => 'data_fim_entrega', 'class' => 'data', 'placeholder' => 'Data Fim Entrega DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio_previsao', '',['id' => 'data_inicio_previsao', 'class' => 'data', 'placeholder' => 'Data Início Previsão DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim_previsao', '',['id' => 'data_fim_previsao', 'class' => 'data', 'placeholder' => 'Data Fim  Previsão DD/MM/AAAA','maxlength' => '20']) }}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::select('industrializacao', ['geral' => 'Geral','sem_industrializacao' => 'COMPRAS SEM INDUSTRIALIZAÇÃO','somente_industrializacao' => 'SOMENTE INDUSTRIALIZAÇÃO'], 'geral', ['id' => 'industrializacao', 'class' => 'form-control'])}}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>	

@endsection

@section('content')

<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters">
        <thead>
            <tr>
                <th>MARCA</th>
                <th>GRUPO</th>
                <th>CÓDIGO PRODUTO</th>
                <th>DESCRIÇÃO</th>
                <th>PEDIDO</th>
                <th>EMISSÃO</th>
                <th>PREV. DE ENTREGA</th>
                <th>ENTREGA</th>
                <th>VALOR COMPRA</th>
                <th>QTD COMPRADA</th>
                <th>QTD RECEBIDA</th>
                <th>SALDO</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
        </tfoot>    
    </table>
</div>

@endsection

@section('script-footer')
    $(document).ready( function () {
        $('.number').mask('0000');
        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            autoHide: true
        });
        $('#btn-filterform').on('click', function(){
			filtro();
		});
        table_filters.destroy();
        table_filters = $('#table-filters')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "autoWidth": false,
            "info": false,
            "paging": true,
            "orderMulti": false,
            "pageLength": 10,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: 'Análise de Produto',
                    footer: true,
                    customize: function( xlsx ) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        $('row c[r^="C"]', sheet).attr( 's', '2' );
                    },
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 8 || column === 9 || column === 10 || column === 11){
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
                            },
                            footer: function(data) {
                                data = $('<p>' + data + '</p>').text();
                                return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                            }
                        }
                    },
                },
            ],
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
                {
                    "class": "tb_number", 
                    "targets": [4,8,9,10,11]
                },
                {
                    "width": "5%", 
                    "targets": [8,9,10,11]
                },
                {
                    "class": "tb_date", 
                    "targets": [5,6,7]
                },
            ]
        });

        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#grupo").autocomplete(optionsAutoComplete("grupo"));

    });

    function filtro(){
        $(table_filters.column(7).footer()).html('');
        $(table_filters.column(8).footer()).html('');
        $(table_filters.column(9).footer()).html('');
        $(table_filters.column(10).footer()).html('');
        $(table_filters.column(11).footer()).html('');
        table_filters.clear().draw();
        $form = $("#form_filter");
        $data = $form.serialize();
        $('label.error-message').remove();
		$.ajax({
			url: '{{ route('recebimentos_compras.filtro')}}',
			type: 'POST',
			data: $data,
			success: function(datas){
                var data = datas.response.retorno;
                var total = datas.response.total;
                var out = [];
				for (var fields in data){
                    out.push([
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[fields].marca + "'>" + data[fields].marca + "</div></div>",
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[fields].grupo + "'>" + data[fields].grupo + "</div></div>",
                        data[fields].codigo_produto,
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[fields].descricao + "'>" + data[fields].descricao + "</div></div>",
                        createLinkPedido(data[fields],''),
                        data[fields].emissao,
                        data[fields].previsao_entreda,
                        data[fields].data_entrega,
                        data[fields].preco_compra,
                        data[fields].quantidade,
                        data[fields].quantidade_recebida.length > 0 ? createBtViewNotasCompras(data[fields]) : data[fields].quantidade_recebida,
                        data[fields].saldo,
                    ]);
                }
                $(table_filters.column(7).footer()).html('total');
                $(table_filters.column(8).footer()).html(total.preco_compra);
                $(table_filters.column(9).footer()).html(total.quantidade);
                $(table_filters.column(10).footer()).html(total.quantidade_recebida);
                $(table_filters.column(11).footer()).html(total.saldo);
                table_filters.rows.add(out).draw();
                
			},
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $form.find('#'+index).eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('#'+index).eq(0).addClass('error');
                    });
                    $form.find('input.error').eq(0).focus();
                }
            }
		}).always(function() {
            hide_loader();
        });
    }

    function createLinkPedido($this, $form){
        var html = "";
        if($this.id_nota !== ""){
            html = "<a href='#' onclick=\"showComissaoDetalhes('" 
            + $this.id_nota + "', '"
            + "', '"
            + "" 
            + "')\">" + $this.pedido + "</a>";
        }
        return html;
    }

    function showComissaoDetalhes(id, unidade, data){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
                unidade: unidade,
                data: data
            },
            success: function(body){
                createModal("1", "Detalhe do Pedido Compras", body, 'modal-lg');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    console.log(message);
                }
            }
    
        });
    }

    function createBtViewNotasCompras($this){
        var html = "<a href=\"#\" onclick=showListaNotasEntradas('"+$this.id_nota+"','"+$this.codigo_produto+"')>"+$this.quantidade_recebida+"</a>"
        return html;
    }

    function showListaNotasEntradas($id_nota,$codido_produto){  
        $.ajax({
            url: '{{ route('busca_notas_pedido.abertura')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
                codido_produto: $codido_produto,
            },
            success: function(body){
                createModal("notas_entradas_lista", "Lista de Notas", body, 'modal-lg');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    console.log(message);
                }
            }

        });
    }

    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3
        };
    }

@endsection