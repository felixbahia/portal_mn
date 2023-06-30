@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_artigo_analise_compras_abertura">
            <thead>
                <tr>
                    <th>EStabelecimento</th>
                    <th>Nota</th>
                    <th>Fornecedor</th>
                    <th>Pedido</th>
                    <th>Proforma</th>
                    <th>Entrada</th>
                    <th>Código</th>
                    <th class="tb_number">Quantidade</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td>{{ $item['estabelecimento'] }}</td>
                    <td>@if(!empty($item['nota_id']))
                        <a href="#" onclick="showNotaEntradaDetalhes('{{ $item['nota_id'] }}')">{{ $item['nota'] }}</a>
                        @else
                        {{ $item['nota'] }}
                        @endif
                    </td>
                    <td>{{ $item['fornecedor'] }}</td>
                    <td>{{ $item['pedido'] }}</td>
                    <td>{{ $item['proforma'] }}</td>
                    <td>{{ $item['entrada'] }}</td>
                    <td>{{ $item['codigo'] }}</td>
                    <td>{{ $item['quantidade'] }}</td>
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
                    <td></td>
                    <td>Total</td>
                    <td style="font-size: 12px;">{{ $total['comprado'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
    table_dialog_comprados = [];
    table_dialog_comprados = $('#table_artigo_analise_compras_abertura')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "orderMulti": false,
        "ordering": true,
        "scrollX": false,
        "scrollY": "50vh",
        "scrollCollapse": true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                text: 'Analise de Compras - Comprado',
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
                            console.log(column, data)
                            return (column === 7) ?
                                data.replace( /[$.]/g, '' ).replace( /[$,]/g, '.' ).toLocaleString('pt-BR') :
                                data;
                        }
                    }
                }
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
                "targets": [7],
            },
            { 
                "class": "tb_date", 
                "targets": [5] 
            },
        ]
    });
    setTimeout(function(){
        table_dialog_comprados.draw();
    }, 200);

    function showNotaEntradaDetalhes($id){
        var url = '{{ route('notas_entradas_nasajon.nota')}}';
        var title = 'Detalhes da nota';
        var id = $id;
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", id: id},
            method: 'POST',
            success: function(body){
                if(body.status === 'error'){
                    message('Erro',body.message,'');
                }else{
                    createModal("modal_nota_entrada", title, body, 'modal-lg');
                }
            }
        });
    }

</script>
@endsection