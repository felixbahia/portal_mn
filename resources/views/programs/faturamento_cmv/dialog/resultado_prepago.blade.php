@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-filters-dialog">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="sort-date">Data</th>
                <th class="tb_number">Documento</th>
                <th>Fornecedor/Cliente</th>
                <th>CFOP</th>
                <th class="tb_number">Valor</th>
                <th class="tb_number">Valor pré-pago</th>
                <th class="tb_number">Valor Composição</th>
                <th class="tb_number">Aliquota</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Unidade</th>
                <th class="tb_number">IPI</th>
                <th class="tb_number">Seguro</th>
                <th class="tb_number">Desconto</th>
                <th class="tb_number">Frete</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($movimentos as $movimento)
                <tr>
                    <td>{!! $movimento['estabelecimento'] !!}</td>
                    <td class="sort-date">{{ $movimento['data'] }}</td>
                    <td>{!! $movimento['documento'] !!}</td>
                    <td>{{ $movimento['cliente_fornecedor'] }}</td>
                    <td>{{ $movimento['cfop'] }}</td>
                    <td class="tb_number">{{ $movimento['preco'] }}</td>
                    <td class="tb_number">{{ $movimento['prepago'] }}</td>
                    <td class="tb_number">{{ $movimento['preco_composicao'] }}</td>
                    <td class="tb_number">{{ $movimento['aliquota'] }}</td>
                    <td class="tb_number">{{ $movimento['quantidade'] }}</td>
                    <td>{{ $movimento['unidade'] }}</td>
                    <td class="tb_number">{{ $movimento['ipi'] }}</td>
                    <td class="tb_number">{{ $movimento['seguro'] }}</td>
                    <td class="tb_number">{{ $movimento['desconto'] }}</td>
                    <td class="tb_number">{{ $movimento['frete'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
    table_dialog = [];
    $(document).ready(function () {
        setTimeout(function(){
            $height = $(document).find(".modal-body").height() - 130;
            $.fn.dataTable.moment('DD/MM/YYYY');
            table_dialog = $(document).find("#table-filters-dialog").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "scrollY": $height,
                "scrollCollapse": true,
                "paging": false,
                "dom": 'Bfrtip',
                "autowidth": false,
                "buttons": [
                    {
                        extend: 'excelHtml5',
                        footer: true,
                        customize: function ( xlsx ) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        },
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            },
                            format: {
                                body: function ( data, row, column, node ) {
                                    data = $('<p>' + data + '</p>').text();
                                    if (column === 5 || column === 6 || column === 8 || column === 10 || column === 11 || column === 12 || column === 13) {
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
                    { "class": "tb_number", "targets": "tb_number" },
                    { "class": "tb_date", "targets": "sort-date" },
                    { "targets": [0], "width": '150px' }
                ],
            });
            $(document).find('[data-toggle="tooltip"]').tooltip();
        }, 250);

        $(document).find('a.exibir-nota-entrada').on('click', function(){
            showNotasEntradaDetalhesNasajon($(this).data('documento'),$(this).data('estabelecimento'));
        });
        $(document).find('a.exibir-nota').on('click', function(){
            showNotasDetalhesNasajon($(this).data('documento'),$(this).data('estabelecimento'));
        });
        $(document).find('a.exibir-nota-prologos').on('click', function(){
            showNotasDetalhesPrologos($(this).data('estabelecimento'), $(this).data('documento'), $(this).data('data_movimentacao'))
        });
    });

    function showNotasEntradaDetalhesNasajon($documento, $estabelecimento){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.modal.exibir_busca')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                numero: $documento,
                estabelecimento: $estabelecimento,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota: "+$documento, body, 'modal-lg');
                $(document).find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function showNotasDetalhesNasajon($documento, $estabelecimento){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir_busca')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                numero: $documento,
                estabelecimento: $estabelecimento,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota: "+$documento, body, 'modal-lg');
                $(document).find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }
    function showNotasDetalhesPrologos(estabelecimento, nota_fiscal, data){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                documento: nota_fiscal,
                link_pedido: true,
                data: data,
                origem: 'PROLOGOS'
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(document).find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            }

        });
    }
</script>
@endsection 
