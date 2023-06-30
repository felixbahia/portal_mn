@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-analise-grupo">
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>Marca</th>
                        <th>Linha</th>
                        <th>Grupo</th>
                        <th class="tb_number">Estoque</th>
                        <th class="tb_number">Compras</th>
                        <th class="tb_number" id="vendas">Vendas @if(isset($vendas)) ({{ $vendas }} Meses) @endif</th>
                        <th class="tb_number" id="remessas">Remessas @if(isset($vendas)) ({{ $vendas }} Meses) @endif</th>
                        <th class="tb_number" id="mediavendas">Média @if(isset($vendas)) ({{ $vendas }} Meses) @endif</th>
                        <th class="tb_number" id="necessidade">Necessidade @if(isset($estoque)) ({{ $estoque }} Meses) @endif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dados as $dado)
                        <tr>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['estabelecimento'] }}">{{ $dado['estabelecimento'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['marca'] }}">{{ $dado['marca'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['linha'] }}">{{ $dado['linha'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['grupo'] }}">{{ $dado['grupo'] }}</div></div></td>
                            <td>{{ $dado['estoque'] }}</td>
                            <td>{{ $dado['compras'] }}</td>
                            <td>{{ $dado['vendas'] }}</td>
                            <td>{{ $dado['remessas'] }}</td>
                            <td>{{ $dado['media'] }}</td>
                            <td>{{ $dado['necessidade'] }}</td>
                            <td>
                                <a href="#" class="bt-view bt-open-view-analise-produto" data-title='ANALITÍCO POR PRODUTO' data-route="{{ route('analise_compras_mes.modal.index') }}" data-filter="{{ $dado['filter'] }}" ></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td>{{ (!empty($totalEstoque)) ? parserValor($totalEstoque) : '' }}</td>
                    <td>{{ (!empty($totalCompra)) ? parserValor($totalCompra) : '' }}</td>
                    <td>{{ (!empty($totalVenda)) ? parserValor($totalVenda) : '' }}</td>
                    <td>{{ (!empty($totalRemessa)) ? parserValor($totalRemessa) : '' }}</td>
                    <td>{{ (!empty($totalMedia)) ? parserValor($totalMedia) : ''}}</td>
                    <td>{{ (!empty($totalNecessidade)) ? parserValor($totalNecessidade) : '' }}</td>
                    <td></td>
                </tfoot>  
            </table>
        </div>
</div>
<script>
    $(document).ready( function () {
        table_analise_grupo = $('#table-analise-grupo')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
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
                            if(column === 4 || column === 5 || column === 6 || column === 7 || column === 8 || column === 9){
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
                "targets": "tb_number"
            },
        ]
        });

        table_analise_grupo.on('draw', function (event) {
            $(document).find(".bt-open-view-analise-produto").off("click");
            $(document).find(".bt-open-view-analise-produto").on("click", function(event){
                event.stopPropagation();
                showModalAberturaProduto($(this));
            });
        });

        table_analise_grupo.draw();
    });

    function showModalAberturaProduto($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters: filter},
            method: 'POST',
            success: function(body){
                createModal("model_analise_produto_modal", title, body, 'modal-lg');
            }
        });
    }

</script>
@endsection        
