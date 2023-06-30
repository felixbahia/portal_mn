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
            {{ Form::label('codigo', (isset($fields['codigo'])) ? 'Todos' : '', array('class' => 'awesome')) }}
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
        <table class="order-column table-striped" id="table-mes-a-mes-produto">
            <thead>
                <tr>
                    <th class="width-table-align-250">Produto</th>
                    <th class="width-table-align-120">TIPO</th>
                    @foreach ($th as $dado)
                        <th class="tb_date width-table-align-120">{{ $dado }}</th>
                    @endforeach
                    <th class="width-table-align-120">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dados as $dado)
                    <tr>
                        <td class="width-table-align-250"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['codigo'] }}">{{ $dado['codigo'] }}</div></div></td>
                        <td class="width-table-align-120">Compras</td>
                        @foreach($th as $key => $value)
                            @if(isset($dado['datacompra'][$key]))
                                <td class="tb_number width-table-align-120">{{ parserValor($dado['datacompra'][$key]) }}</td>
                            @else
                                <td></td>
                            @endif 
                        @endforeach
                        <td class="width-table-align-120 tb_number">{{ ($dado['total_compras'] > 0) ? parserValor($dado['total_compras']) : '' }}</td>
                    </tr>
                    <tr>
                        <td class="width-table-align-250"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['codigo'] }}">{{ $dado['codigo'] }}</div></div></td>
                        <td class="width-table-align-120">Vendas</td>
                        @foreach($th as $key => $value)
                            @if(isset($dado['datavenda'][$key]))
                                <td class="tb_number width-table-align-120">{{ parserValor($dado['datavenda'][$key]) }}</td>
                            @else
                                <td></td>
                            @endif 
                        @endforeach
                        <td class="width-table-align-120 tb_number">{{ ($dado['total_vendas'] > 0) ? parserValor($dado['total_vendas']) : '' }}</td>
                    </tr>
                    <tr>
                        <td class="width-table-align-250"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['codigo'] }}">{{ $dado['codigo'] }}</div></div></td>
                        <td class="width-table-align-120">Remessas</td>
                        @foreach($th as $key => $value)
                            @if(isset($dado['dataremessa'][$key]))
                                <td class="tb_number width-table-align-120">{{ parserValor($dado['dataremessa'][$key]) }}</td>
                            @else
                                <td></td>
                            @endif 
                        @endforeach
                        <td class="width-table-align-120 tb_number">{{ ($dado['total_remessas'] > 0) ? parserValor($dado['total_remessas']) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
$('[data-toggle="tooltip"]').tooltip();

$(document).ready( function () {
    var table_mes_mes_produto = $('#table-mes-a-mes-produto').DataTable({
        "searching": false,
        "info": false,
        scrollY: "50vh",
        scrollX: true,
        scrollCollapse: false,
        "ordering" : false,
        'rowsGroup': [0],
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

    
$('[data-toggle="tooltip"]').tooltip();
setTimeout(function(){
    table_mes_mes_produto.draw();
}, 200);
});

</script>
@endsection        
