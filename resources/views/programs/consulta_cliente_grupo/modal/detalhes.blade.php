@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped" id="table-filters-prd">
            <thead>
                <tr>
                    <th>Estabelecimento</th>
                    <th class="tb_date">Data Emissão</th>
                    <th>Nota</th>
                    <th>Cliente</th>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th class="tb_number">Quantidade</th>
                    <th class="tb_number">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itens as $item)
                    <tr>
                        <td>{{ $item['estabelecimento'] }}</td>
                        <td class="tb_date">{{ $item['data'] }}</td>
                        @if(empty($item['id_nota']))
                        <td>{{ $item['nota_codigo'] }}/td>
                        @else
                        <td><a href="#" id="bt_link" onclick="showNotasDetalhes('{{ $item['id_nota'] }}')">{{ $item['nota_codigo'] }}</a></td>
                        @endif
                        <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $item['cliente'] }}">{{ $item['cliente'] }}</div></div></td>
                        <td>{{ $item['codigo'] }}</td>
                        <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $item['descricao'] }}">{{ $item['descricao'] }}</div></div></td>
                        <td class="tb_number">{{ $item['quantidade'] }}</td>
                        <td class="tb_number">{{ $item['preco_total'] }}</td>
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
                    <td class="tb_number">Total:</td>
                    <td class="tb_number">{{ $total['quantidade'] }}</td>
                    <td class="tb_number">{{ $total['valor'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
    $(document).ready( function () {
        table_modal_produto = $('#table-filters-prd').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": true,
            "pageLength": 20,
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
                                if(column >= 4){
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
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
    });

    function showItensDialog($pedido, $origem, $estabelecimento){
        var title = "Dados do pedido: "+$pedido;
        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {
                _token: "{{ csrf_token() }}",
                origem: $origem,
                pedido: $pedido,
                estabelecimento: $estabelecimento
            },
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })
            }
        });
    }

    function showNotasDetalhes($id_nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
                link_pedido: true,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }

        });

    }
</script>
@endsection 
