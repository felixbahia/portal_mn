@extends('layouts.page-dialog')

@section('content')
<h4>Análise de Compras dos Últimos 6 Meses</h4>
<div class="content-fields">
    <div class="row">
        <div class="form-group col-lg-3 col-xl-2">
            <b>{{ Form::label('grupolabel', 'Pedido:', array('class' => 'awesome', 'for' => 'pedido')) }}</b>
            {{ Form::label('pedido', $pedido, array('class' => 'awesome')) }}
        </div>
    </div>
</div>
<div class="content-dialog-table">
    <div class="content-table">
        <table class="order-column table-striped" id="table-mes-a-mes-p">
            <thead>
                 <tr>
                    @foreach ($heads as $head)
                            <th colspan="2" class="tb_date width-table-align-120">{{ $head }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($heads as $head)
                        <th>Percentual</th>
                        <th>Vendas</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach ($heads as $key => $data)
                        <td class="tb_number width-table-align-120">
                            @foreach ($dados as $dado)
                                @if($dado['data'] == $key)
                                    {{$dado['percentual']}}
                                @endif
                            @endforeach
                        </td>   
                        <td class="tb_number width-table-align-120">
                            @foreach ($dados as $dado)
                                @if($dado['data'] == $key)
                                    {{$dado['media']}}
                                @endif
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
$('[data-toggle="tooltip"]').tooltip();

$(document).ready( function () {
    var table_mes_mes = $('#table-mes-a-mes-p').DataTable({
        "scrollX": true,
        "searching": false,
        "info": false,
        paging:         false,
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
    });
    $('.dataTables_length').addClass('bs-select');

    table_mes_mes.on('draw', function () {
        $(document).find(".view-compras-ultimos-meses").off("click");
        $(document).find(".view-compras-ultimos-meses").on("click", function(event){
            event.stopPropagation();
            showModalUltimosMesesCompras($(this));
        });
        $(document).find(".view-vendas-ultimos-meses").off("click");
        $(document).find(".view-vendas-ultimos-meses").on("click", function(event){
            event.stopPropagation();
            showModalUltimosMesesVendas($(this));
        });
        $(document).find(".view-vendas-ultimos-meses-abertura-modal").off("click");
        $(document).find(".view-vendas-ultimos-meses-abertura-modal").on("click", function(event){
            event.stopPropagation();
            showModalUltimosMesesAberturaModal($(this));
        });
    });

    
$('[data-toggle="tooltip"]').tooltip();
setTimeout(function(){
    table_mes_mes.draw();
}, 200);
});

</script>
@endsection        
