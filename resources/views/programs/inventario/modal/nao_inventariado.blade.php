@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_produto_nao_inventariado">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Unidade de medida</th>
                    <th class="tb_number">Estoque</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($retorno as $produto)
                <tr>
                    <td>{{ $produto['codigo'] }} - {{ $produto['descricao'] }}</td>
                    <td>{{ $produto['unidade'] }}</td>
                    <td data-order="{{ $produto['estoque_notFormat'] }}"><a href="#" class="btn-view-estoque" data-title="Estoque - {{ $produto['codigo'] }} - {{ $produto['descricao'] }}" data-url="{{ route('produto.estoque') }}" data-estabel="{{ $estabelecimento }}" data-codigo="{{ $produto['codigo'] }}">{{ $produto['estoque'] }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>

$(document).find('.btn-view-estoque').off("click");
$(document).find('.btn-view-estoque').on('click', function(){
    showModalPecaPeca($(this));
});

table_dialog4 = [];
table_dialog4 = $('#table_produto_nao_inventariado')
.on( 'error.dt', function ( e, settings, techNote, men ) {
    hide_loader();
    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
}).DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "pageLength": 15,
    "scrollCollapse": true,
    dom: 'Bfrtip',
    buttons: [
        {
            extend: 'excelHtml5',
            text: ' ',
            title: 'Itens não inventariados',
            exportOptions: {
                modifier: {
                    page: 'all',
                    search: 'applied',
                    order: 'applied'
                },
                format: {
                    body: function (data, row, column, node ) {
                        if(column === 2){
                            data = $(data).text();
                            numero = data.replace('.','').replace(',','');
                            inteiro = Math.floor(numero.length - 2);
                            decimal = Math.floor(numero.length);
                            data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                        }
                        return data;
                    }
                }
            },
        },
    ],
    "language": {
        "decimal":        ".",
        "emptyTable":     "Nenhum pedido encontrado",
        "infoPostFix":    "",
        "thousands":      ",",
        "loadingRecords": "Carregando...",
        "processing":     "Processando...",
        "zeroRecords":    "Nenhum pedido encontrado",
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
        }
    ]
});
$(document).ready( function () {
    setTimeout(function(){
        table_dialog4.draw();
    }, 400);
});
function showModalPecaPeca($this){
    var url = $($this).data("url");
    var codigo = $($this).data("codigo");
    var estabel = $($this).data("estabel");
    $('[data-toggle="popover"]').popover('hide');
    $('[data-toggle="tooltip"]').tooltip('hide');
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel},
        method: 'POST',
        success: function(body){
            createModal("model_pecapeca_view", title, body, 'modal-lg');
            var modal = $(document).find("#model_pecapeca_view");
        }
    });

}
</script>
@endsection