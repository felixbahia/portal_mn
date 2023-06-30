@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-modal-produto-terceiro">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Código</th>
                    <th>Grupo</th>
                    <th>Linha</th>
                    <th>Subgrupo</th>
                    <th>Marca</th>
                    <th>Kg</th>
                    <th>Metros</th>
                    <th>Outras Un.</th>
                    <th>Custo Contábil Portal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['produto'] }}">{{ $dado['produto'] }}</div></div></td>
                    <td>
                        {{ $dado['codigo'] }}
                        <a href="#" class="bt-estoque text-right" data-url="{{ route('produto.movimento_estoque.movimento_portal_terceiro_fornecedor') }}" data-title="Movimento de Estoque - {{ $dado['codigo'] }} - {{ $dado['grupo'] }} - {{ $dado['produto'] }}" data-estabel="" data-codigo="{{ $dado['codigo'] }}" data-inicial="2018-07-01" data-fim="{{ date('Y-m-d') }}" data-fornecedor_codigo="{{$fornecedor_codigo}}" data-toggle="tooltip" data-placement="top" title="Movimento de Estoque" onclick="showModalMovimentoEstoque($(this))"></a> 
                    </td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['grupo'] }}">{{ $dado['grupo'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['linha'] }}">{{ $dado['linha'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['subgrupo'] }}">{{ $dado['subgrupo'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['marca'] }}">{{ $dado['marca'] }}</div></div></td>
                    <td class='tb_number'>{{ $dado['kg'] }}</td>
                    <td class='tb_number'>{{ $dado['metros'] }}</td>
                    <td class='tb_number'>{{ $dado['outras_unidades'] }}</td>
                    <td class='tb_number'>{{ $dado['custo_medio_contabil_portal'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total:</td>
                    <td class='tb_number'>{{ $total['KG'] }}</td>
                    <td class='tb_number'>{{ $total['metros'] }}</td>
                    <td class='tb_number'>{{ $total['outras_unidades'] }}</td>
                    <td class='tb_number'>{{ $total['custo_medio_contabil_portal'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
    $(document).ready( function () {
        table_estoque_terceiro_modal = $('#table-modal-produto-terceiro')
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
            "autoWidth": false,
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
                                if(column === 6 || column === 7 || column === 8){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 4);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }else if(column === 9){
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
                },{ targets: 0, "orderable": true, width: '15%'},
            ],
        });    
        table_estoque_terceiro_modal.draw();
    });
    function showModalMovimentoEstoque($this){
        var url = $($this).data("url");
        var $estabel = $($this).data("estabel");
        var $codigo = $($this).data("codigo");
        var $inicial = $($this).data("inicial");
        var $fim = $($this).data("fim");
        var title = $($this).data("title");
        var $fornecedor_codigo = $($this).data("fornecedor_codigo");
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                estabel: $estabel,
                codigo: $codigo,
                inicial: $inicial,
                fim: $fim,
                fornecedor_codigo: $fornecedor_codigo
            },
            success: function(body){
                createModal('modal_movimento_estoque', title, body, "modal-lg");
                var modal = $("#modal_movimento_estoque");
            }
        });
    }
</script>
@endsection  