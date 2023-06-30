@extends('layouts.page-dialog')

@section('content')
    <div class="content-dialog-table" style='float: none;'>
        <table class='table table-striped table-filter-notas table-not-edit table-not-view' id='table-filter-notas'>
            <thead>
                <tr>
                    <th>Estabelecimento</th>
                    <th class='tb_number'>Número</th>
                    <th>Cliente</th>
                    <th class='tb_date'>Emissão</th>
                    <th>Natureza de operação</th>
                    <th class='tb_number'>Valor</th>
                    <th class='tb_number'>Frete</th>
                    <th class='tb_number'>Alíquota do Frete</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultado as $nota)
                <tr>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $nota['estabelecimento'] }}'>{{ $nota['estabelecimento'] }}</div></div></td>
                    @if(!empty($nota['route']))
                        <td><a href="#" data-title="DETALHES DA NOTA: {{ $nota['numero'] }}" data-route="{{ $nota['route'] }}" data-nota_id='{{ $nota['id'] }}' data-id='{{ $nota['id'] }}' onclick='showModalNota($(this));'>{{ $nota['numero'] }}</a></td>
                    @else
                        <td>{{ $nota['numero'] }}</td>
                    @endif
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $nota['cliente'] }}'>{{ $nota['cliente'] }}</div></div></td>
                    <td>{{ $nota['emissao'] }}</td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $nota['natureza'] }}'>{{ $nota['natureza'] }}</div></div></td>
                    <td>{{ $nota['valor'] }}</td>
                    <td>{{ $nota['frete'] }}</td>
                    <td>{{ $nota['aliquota_porcentagem'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">TOTAL:</td>
                    <td>{{ $total['valor'] }}</td>
                    <td>{{ $total['frete'] }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        table_filters_nota = $('#table-filter-notas').DataTable({
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
                {
                    "class": "tb_number", 
                    "type": 'numeric-comma-ftm',
                    "targets": "tb_number",
                    "width": '10vw'
                },
                { "class": "tb_date", targets: "tb_date" }
            ],
            "order": [ 0, 'asc' ]
        });

        function showModalNota($this){
            var $url = $($this).data("route");
            var $nota_id = $($this).data("nota_id");
            var $title = $($this).data("title");

            $.ajax({
                url: $url,
                method: 'POST',
                data: {_token: "{{ csrf_token() }}", id_nota : $nota_id, id: $nota_id},
                success: function(body){
                    createModal("table-itens-nota-fatura", $title, body, 'modal-lg');
                },
                error: function(erro){
                    message('Erro', erro.responseJSON.message, 'erro_nota');
                }
            });
        }
    </script>
@endsection