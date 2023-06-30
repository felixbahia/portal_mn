@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_analise_artigos_abertura">
            <thead>
                <tr>
                    <th rowspan="2">Marca</th>
                    <th rowspan="2">Linha</th>
                    <th rowspan="2">Grupo</th>
                    <th rowspan="2">Produto</th>
                    <th class="tb_number" rowspan="2" class="tb_number">Saldo Anterior</th>
                    <th class="tb_date" colspan="7"><center>Ultimos {{ $mes }} meses</center></th>
                    <th class="tb_number" rowspan="2" class="tb_number">Estoque</th>
                    <th class="tb_number" colspan="3"><center>A receber</center></th>
                </tr>
                <tr>
                    <th class="tb_number">Comprado</th>
                    <th class="tb_date">Último Compra</th>
                    <th class="tb_number">Remessas</th>
                    <th class="tb_number">Média de Remessas</th>
                    <th class="tb_number">Vendido</th>
                    <th class="tb_number" >Média de venda</th>
                    <th></th>
                    <th class="tb_number">{{ $meses["atual"] }}</th>
                    <th class="tb_number">{{ $meses["mes_1"] }}</th>
                    <th class="tb_number" >{{ $meses["mes_2"] }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['marca'] }}">{{ $item['marca'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['linha'] }}">{{ $item['linha'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['grupo'] }}">{{ $item['grupo'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['produto'] }}">{{ $item['produto'] }}</div></div></td>
                    <td>{{ $item['saldo_anterior'] }}</td>
                    <td>
                        <a href="#" data-route="{{ route('compras_analise.modal.comprado') }}" data-filter="{{ $item['filter'] }}" data-title='Analise de Notas de Compra ' class='bt-view-comprado-abertura'>{{ $item['comprado'] }}</a>
                    </td>
                    <td>{{ $item['data_entrada'] }}</td>
                    <td>
                        <a href="#" data-route="{{ route('compras_analise.modal.analise.remessas') }}" data-filter="{{ $item['filter'] }}" data-title='Analise de Remessas Mensalmente ' class='bt-view-vendido-abertura'>{{ $item['remessas'] }}</a>
                    </td>
                    <td>{{ $item['media_remessas'] }}</td>
                    <td>
                       <a href="#" data-route="{{ route('compras_analise.modal.vendido') }}" data-filter="{{ $item['filter'] }}" data-title='Analise de Vendas Mensalmente ' class='bt-view-vendido-abertura'>{{ $item['vendido'] }}</a>
                    </td>
                    <td>{{ $item['media_venda'] }}</td>
                    <td>
                        <a href="#" data-route="{{ route('compras_analise.modal.analise.mes') }}" data-artigo="artigo" data-filter="{{ $item['filter'] }}" data-title='Análise de Vendas/Compras últimos {{ $mes }} Meses' class='bt-view-abertura-ultimos-meses bt-view'></a>
                    </td>
                    <td>{{ $item['estoque'] }}</td>
                    <td>
                        <a href="#" data-route="{{ route('compras_analise.modal.receber') }}" data-mes='atual' data-filter="{{ $item['filter'] }}" data-title='Analise a Receber Mês {{ parserNameMonthFull(date("m"))}} ' class='bt-view-mes-atual-abertura'>{{ $item['a_receber_atual'] }}</a>
                    </td>
                    <td>
                        <a href="#" data-route="{{ route('compras_analise.modal.receber') }}" data-mes='um' data-filter="{{ $item['filter'] }}" data-title='Analise a Receber Mês {{ parserNameMonthFull(date("m", strtotime("+1 months")))}} ' class='bt-view-mes-um-abertura'>{{ $item['a_receber_mes_1'] }}</a>
                    </td>
                    <td>
                        <a href="#" data-route="{{ route('compras_analise.modal.receber') }}" data-mes='dois' data-filter="{{ $item['filter'] }}" data-title='Analise a Receber Mês {{ parserNameMonthFull(date("m", strtotime("+2 months")))}} ' class='bt-view-mes-dois-abertura'>{{ $item['a_receber_mes_2'] }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="font-size: 12px;">{{ $total['saldo_anterior'] }}</td>
                    <td style="font-size: 12px;">{{ $total['comprado'] }}</td>
                    <td></td>
                    <td style="font-size: 12px;">{{ $total['remessas'] }}</td>
                    <td style="font-size: 12px;">{{ $total['media_remessas'] }}</td>
                    <td style="font-size: 12px;">{{ $total['vendido'] }}</td>
                    <td style="font-size: 12px;">{{ $total['media_venda'] }}</td>
                    <td></td>
                    <td style="font-size: 12px;">{{ $total['estoque'] }}</td>
                    <td style="font-size: 12px;">{{ $total['a_receber_atual'] }}</td>
                    <td style="font-size: 12px;">{{ $total['a_receber_mes_1'] }}</td>
                    <td style="font-size: 12px;">{{ $total['a_receber_mes_2'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_dialog_artigos = [];
    table_dialog_artigos = $('#table_analise_artigos_abertura')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "orderMulti": false,
        "scrollY": "50vh",
        "scrollCollapse": true,
        "ordering": true,
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
                            if(column === 4 || column === 5 || column === 6 || column === 7 || column === 8 || column === 9 || column === 10 || column === 11 || column === 12 || column === 13 || column === 14){
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
            }
        },
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number",
            },
            {
                "class": "tb_date", 
                "targets": "tb_date",
            },
        ]
    });
    table_dialog_artigos.on('draw', function () {
        $(document).find(".bt-view-comprado-abertura").off("click");
            $(document).find(".bt-view-comprado-abertura").on("click", function(event){
                event.stopPropagation();
                showModalArtigosCompradoAbertura($(this));
            });
            $(document).find(".bt-view-vendido-abertura").off("click");
            $(document).find(".bt-view-vendido-abertura").on("click", function(event){
                event.stopPropagation();
                showModalArtigosVendidoAbertura($(this));
            });

            $(document).find(".bt-view-abertura-ultimos-meses").off("click");
            $(document).find(".bt-view-abertura-ultimos-meses").on("click", function(event){
                event.stopPropagation();
                showModalArtigosUltimosMesesAbertura($(this));
            });

            $(document).find(".bt-view-mes-atual-abertura").off("click");
            $(document).find(".bt-view-mes-atual-abertura").on("click", function(event){
                event.stopPropagation();
                showModalArtigosMesAtualAbertura($(this));
            });

            $(document).find(".bt-view-mes-um-abertura").off("click");
            $(document).find(".bt-view-mes-um-abertura").on("click", function(event){
                event.stopPropagation();
                showModalArtigosMesUmAbertura($(this));
            });

            $(document).find(".bt-view-mes-dois-abertura").off("click");
            $(document).find(".bt-view-mes-dois-abertura").on("click", function(event){
                event.stopPropagation();
                showModalArtigosMesDoisAbertura($(this));
            });
    });
    setTimeout(function(){
        table_dialog_artigos.draw();
    }, 200);
});

function showModalArtigosCompradoAbertura($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var mes = $($this).data('mes');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_comprado_abertura", title, body, 'modal-lg');
        }
    });
}

function showModalArtigosVendidoAbertura($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var mes = $($this).data('mes');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_vendido_abertura", title, body, 'modal-lg');
        }
    });
}

function showModalArtigosUltimosMesesAbertura($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var mes = $($this).data('mes');
    var artigo = $($this).data('artigo');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes, artigo : artigo},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_ultimos_meses_abertura", title, body, 'modal-lg');
        }
    });
}

function showModalArtigosMesAtualAbertura($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var mes = $($this).data('mes');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_mes_atual_abertura", title, body, 'modal-lg');
        }
    });
}

function showModalArtigosMesUmAbertura($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var mes = $($this).data('mes');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_mes_um_abertura", title, body, 'modal-lg');
        }
    });
}

function showModalArtigosMesDoisAbertura($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var mes = $($this).data('mes');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_mes_dois_abertura", title, body, 'modal-lg');
        }
    });
}
</script>
@endsection