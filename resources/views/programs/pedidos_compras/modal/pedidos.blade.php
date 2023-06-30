@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_analise_pedidos">
            <thead>
            <tr>
                <th>Estabel</th>
                <th>Pedido</th>
                @if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272]))
                    <th>Proforma</th>
                    <th>Fornecedor</th>
                @endif
                <th class="sort-date">Data Recebimento</th>
                <th>Grupo</th>
                <th class="tb_number">Preço Dolar</th>
                <th class="tb_number">Qtde Comprada</th>
                <th class="tb_number">Qtde Vendida</th>
                <th class="tb_number">% Compra/Venda</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        table_dialog_pedidos = [];
        table_dialog_pedidos = $('#table_analise_pedidos')
            .on( 'error.dt', function ( e, settings, techNote, men ) {
                hide_loader();
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }).DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "paging": true,
                "autoWidth": false,
                "orderMulti": false,
                "scrollY": "70vh",
                "scrollCollapse": true,
                "ordering": true,
                "pageLength": 20,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: ' ',
                        title: 'Análise de Compras',
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
                                    if(column === 3){
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
                        "targets": [6,7,8,9],
                    },
                    {
                        "width": "20%",
                        "targets": [3],
                    },
                    {
                        "width": "10%",
                        "targets": [0],
                    },
                    {
                        "class": "text_date",
                        "targets": [4],
                    },
                ]
            });

        showPedido();
        setTimeout(function(){
        }, 200);
    });


    function showPedido(){

        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.filter')}}',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: '{{$filter['estabelecimento']}}',
                fornecedor: '{{$filter['fornecedor']}}',
                grupos: '',
                pcmn: '',
                proforma: '',
                porcentage: '',
                data: '',
                export_excel: '',
            },
            method: 'POST',
            success: function(data){
                table_dialog_pedidos.clear().draw();
                produtos = [];
                for (var fields in data.response.retorno){
                    temp_array = [
                        data.response.retorno[fields].unidade,
                        createLinkPedido(data.response.retorno[fields], ''),
                        @if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272]))
                            data.response.retorno[fields].proforma,
                        createRepresentante(data.response.retorno[fields].fornecedor),
                        createLinkModificarData(data.response.retorno[fields], ''),
                        @else
                            data.response.retorno[fields].data_recebimento,
                        @endif
                            data.response.retorno[fields].grupo,
                        data.response.retorno[fields].preco_dolar,
                        data.response.retorno[fields].qtde_comprada,
                        data.response.retorno[fields].qtde_vendida,
                        data.response.retorno[fields].porcetagem
                    ];
                    produtos.push(temp_array)
                }
                table_dialog_pedidos.rows.add(produtos).draw();

            }
        });
    }

    function showComissaoDetalhes(id){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
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
                    message("Atenção", message);
                }
            }

        });
    }

    function createLinkPedido($this, $form){
        var html = "";
        if($this.id !== ""){
            html = "<a href='#' onclick=\"showComissaoDetalhes('"
                + $this.id + "', '"
                + $this.unidade + "', '"
                + $(document).find("#form_filter").find('#data').val() + ""
                + "')\">" + $this.pcmn + "</a>";
        }
        return html;
    }

    function createLinkModificarData($this, $form){
        var html = "";
        if($this.id !== ""){
            html = "<a href='#' onclick=\"showModalData('"
                + $this.id + "', '"
                + $this.unidade + "', '"
                + $(document).find("#form_filter").find('#data').val() + ""
                + "')\">" + $this.data_recebimento + "</a>";
        }
        return html;
    }

    function createRepresentante($this){
        var representante = "";

        representante = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + $this + "'>" + $this + "</div></div>";

        return representante;
    }
</script>
@endsection