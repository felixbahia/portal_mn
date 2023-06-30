@extends('layouts.page-dialog')

@section('content')

<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view table-comissao" id="table-dialog2">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="sort-date">Data Emissão</th>
                <th class="tb_number_html">Pedido</th>
                <th class="tb_number_html ">Nota</th>
                <th>Cliente</th>
                <th class="tb_number_column">Valor Vendido</th>
                <th class="tb_number_column">Devolução</th>
                <th class="tb_number_column">Total</th>
                <th class="tb_number_column">Comissão %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas['titulos'] as $titulos)
            <tr>
                <td><div><div data-toggle="tooltip" data-trigger="hover" data-placement="top" title="{{ $titulos['estabelecimento'] }}">{{ $titulos['estabelecimento'] }}</div></div></td>
                <td>{{ $titulos['data_emissao'] }}</td>
                <td>
                {{ $titulos['numero_documento'] }}
                <td>
                    <a href='#' onclick="showNotasDetalhes('{{ $titulos['id_nota'] }}', '{{ $titulos['estabelecimento_not_parse'] }}')">{{ $titulos['numero_nota'] }}</a>
                <td><div><div data-toggle="tooltip" data-trigger="hover" data-placement="top" title="{{ $titulos['cliente'] }}">{{ $titulos['cliente'] }}</div></div></td>
                <td> {{ $titulos['valor_nota'] }}</td>
                <td> {{ $titulos['valor_devolucao'] }}</td>
                <td> {{ $titulos['total'] }}</td>
                <td> {{ $titulos['comissao'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"></td>
                <td>Total:</td>
                <td class="tb_number" id='total_basecomissao'>{{ $notas['total']['valor_nota'] }}</td>
                <td class="tb_number" id='total_comissao'>{{ $notas['total']['devolucao'] }}</td>
                <td class="tb_number" id='total_total'>{{ $notas['total']['total'] }}</td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $(document).find('#table-dialog2').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column > 1){
                                    if(data != ''){
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number_column" },
                { "class": "tb_number", type: 'html-num', targets: "tb_number_html" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
        
        $(document).find('#table-dialog-creddeb').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,

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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
    });


    function showNotasDetalhes($id_nota, estabelecimento){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
                estabelecimento: estabelecimento,
                origem: 'NASAJON',
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }
        });
    }
</script>
@endsection
