@extends('layouts.page-dialog')

@section('content')
<h4>Filtros</h4>
<div class="content-fields">
    <div class="row">
        <div class="form-group col-lg-3 col-xl-2">
            <b>{{ Form::label('grupolabel', 'Grupo:', array('class' => 'awesome', 'for' => 'grupo')) }}</b>
            {{ Form::label('grupo', $fields['grupo'], array('class' => 'awesome')) }}
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            <b>{{ Form::label('codigolabel', 'Código:', array('class' => 'awesome', 'for' => 'codigo')) }}</b>
            {{ Form::label('codigo', $fields['codigo'], array('class' => 'awesome')) }}
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            <b>{{ Form::label('nomelabel', 'Nome:', array('class' => 'awesome', 'for' => 'nome')) }}</b>
            {{ Form::label('nome', $fields['nome'], array('class' => 'awesome')) }}
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            <b>{{ Form::label('marcalabel', 'Marca:', array('class' => 'awesome', 'for' => 'marca')) }}</b>
            {{ Form::label('marca', $fields['marca'], array('class' => 'awesome')) }}
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            <b>{{ Form::label('linhalabel', 'Linha:', array('class' => 'awesome', 'for' => 'linha')) }}</b>
            {{ Form::label('linha', $fields['linha'], array('class' => 'awesome')) }}
        </div>
    </div>
</div>
<div class="content-dialog-table">
    <div class="content-table">
        <table class="order-column table-striped" id="table-mes-a-mes-p">
            <thead>
                <tr>
                    <th class="width-table-align-120">Data</th>
                    @foreach ($th as $dado)
                        <th class="tb_date width-table-align-120">{{ $dado }}</th>
                    @endforeach
                    <th class="width-table-align-120">Total</th>
                    <th class="width-table-align-120"></th>
                    
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="width-table-align-120">Compras</td>
                    @foreach ($th as $key => $data)
                        <td class="tb_number width-table-align-120">
                            @foreach ($dados as $dado)
                                @if($dado['data'] == $key)
                                    {{ $dado['compra'] }}
                                @endif
                            @endforeach
                        </td>   
                    @endforeach
                    <td class="tb_number">
                        @if(!isset($artigo))
                            <a href="#" data-route="{{ route('compras_analise.modal.analise.compras') }}" data-filter="{{ (isset($dado['filter'])) ? $dado['filter'] : null  }}" class="view-compras-ultimos-meses" data-title='Análise de Compras Mensal por Produto'>
                                {{ $total['comprado'] }}
                            </a>
                        @else
                            {{ $total['comprado'] }}
                        @endif                            
                    </td>
                    <td class="tb_number">  
                    </td>    
                </tr>
                <tr>    
                    <td class="width-table-align-120">Vendas</td>
                    @foreach ($th as $key => $data)
                    <td class="tb_number width-table-align-120">
                        @foreach ($dados as $dado)
                            @if($dado['data'] == $key)
                                {{ $dado['venda'] }}
                            @endif
                        @endforeach
                    </td>
                    @endforeach
                    <td class="tb_number width-table-align-120">
                        @if(!isset($artigo))
                            <a href="#" data-route="{{ route('compras_analise.modal.analise.vendas') }}" data-filter="{{ (isset($dado['filter'])) ? $dado['filter'] : null }}" class="view-vendas-ultimos-meses" data-title='Análise de Vendas Mensal por Produto'>
                                {{ $total['vendido'] }}
                            </a>
                        @else
                            {{ $total['vendido'] }}
                        @endif    
                    </td>
                    <td>
                        @if(!isset($artigo))
                            <a href="#" data-route="{{ route('compras_analise.modal.analise.produto') }}" data-filter="{{ (isset($dado['filter'])) ? $dado['filter'] : null  }}" data-title='Análise por Produto Mensal' class="bt-view view-vendas-ultimos-meses-abertura-modal"></a>
                        @endif   
                    </td>
                </tr>
                <tr>    
                    <td class="width-table-align-120">Remessas</td>
                    @foreach ($th as $key => $data)
                    <td class="tb_number width-table-align-120">
                        @foreach ($dados as $dado)
                            @if($dado['data'] == $key)
                                {{ $dado['remessas'] }}
                            @endif
                        @endforeach
                    </td>
                    @endforeach
                    <td class="tb_number width-table-align-120">
                        @if(!isset($artigo))
                            <a href="#" data-route="{{ route('compras_analise.modal.analise.analise_mes_mes_remessas') }}" data-filter="{{ (isset($dado['filter'])) ? $dado['filter'] : null }}" class=" view-vendas-ultimos-meses" data-title='Análise de Remessas Mensal por Produto'>
                                {{ $total['remessas'] }}
                            </a>
                        @else
                            {{ $total['remessas'] }}
                        @endif    
                    </td>
                    <td></td>
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
        paging: false,
        'rowsGroup': [{{ count($th) + 2}}],
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
                            if(column >= 1){
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

function showModalUltimosMesesCompras($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_analise_ultimos_meses_compras", title, body, 'modal-lg');
        }
    });
}

function showModalUltimosMesesVendas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_analise_ultimos_meses_vendas", title, body, 'modal-lg');
        }
    });
}

function showModalUltimosMesesAberturaModal($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_analise_ultimos_meses_abertura_modal", title, body, 'modal-lg');
        }
    });
}
</script>
@endsection        
