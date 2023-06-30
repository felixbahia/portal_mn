@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            
            <div class="col-lg-2">   
                <select name="estabelecimento" id="estabelecimento" class="form-control">
                    <option value=''>Estabelecimento</option>
                    @foreach ($estabelecimentos as $key => $value)
                    <option value='{{$key}}'>{{$value}}</option>
                    @endforeach
                </select>
            </div>

            @if(!empty($dropdown_diretores))
            <div class="col-lg-2">   
                {{ Form::select('diretor', $dropdown_diretores, '', ['id' => 'diretor', 'class' => 'form-controll busca_left', 'placeholder' => 'Diretores']) }}
            </div>
            @endif

            @if(!empty($dropdown_gerentes))
            <div class="col-lg-2">
                {{ Form::select('gerente', $dropdown_gerentes, '', ['id' => 'gerente', 'class' => 'form-controll busca_left', 'placeholder' => 'Gerentes']) }}
            </div>
            @endif

            @if(!empty($dropdown_usuarios))
            <div class="col-lg-2">
                {{ Form::select('vendedor', $dropdown_usuarios, '', ['id' => 'vendedor', 'class' => 'form-controll busca_left', 'placeholder' => 'Vendedores']) }}
            </div>
            @endif

        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <button type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear">Limpar busca</button>
        </div>
    </form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-entrada">
        <thead>
            <tr>
                <th rowspan=""></th>
                <th colspan='3' class='border-left text-center'>Dia</th>
                <th colspan='3' class='border-left text-center'>Mês</th>
                <th colspan='3' class='border-left text-center'>Ano</th>
            </tr>
            <tr>
                <th>Pronta-Entrega?</th>
                <th class='border-left'>Pedidos</th>
                <th>Clientes</th>
                <th>Valor</th>
                <th class='border-left'>Pedidos</th>
                <th>Clientes</th>
                <th>Valor</th>
                <th class='border-left'>Pedidos</th>
                <th>Clientes</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')

    $(document).ready( function () {

        $("#btn-filterform").on("click", function(){        
            filterAjax($("#form_filter").serialize());
        });
    });


    table_filters = $('#table-filters-entrada').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
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
            }        },
        "columnDefs": [
            {
                "targets": [1,2,3,4,5,6,7,8,9],
                "className": 'number_format',
                "width": '10%'
            },
            {
                'targets': 0,
                'width': '10%'
            }
        ],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });

    function detalhe(estabel, mes, coluna, criterio){
        var title = "Pedidos";
        $.ajax({
            url: "{{route('pedidos_orcamentos.pedidos')}}",
            data: {_token: '{{ csrf_token() }}', estabel: estabel, mes: mes, coluna: coluna, criterio: criterio},
            method: 'POST',
            success: function(body){
                createModal("show_detalhe", title, body, 'modal-lg');
            }
        });
    }

    function filterAjax(){
        var $return;
        var form = $("#form_filter");
        table_filters.clear().draw();
        var data_form = form.serialize();
        form.find('.error-message').remove();
        
        $.ajax({
            url: "{{ route('entrada_pedido.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){

                if(Object.keys(data).length > 0){
                    var fields_filter = [];
                    
                    for(var field in data){
                        
                        var temp_field = [
                            field,
                            createLinkDia(field, data[field].pedidos_dia, data[field].hash),
                            createLinkDia(field, data[field].clientes_dia, data[field].hash),
                            createLinkDia(field, data[field].valor_dia, data[field].hash),
                            createLinkMes(field, data[field].pedidos_mes, data[field].hash),
                            createLinkMes(field, data[field].clientes_mes, data[field].hash),
                            createLinkMes(field, data[field].valor_mes, data[field].hash),
                            createLinkAno(field, data[field].pedidos_ano, data[field].hash),
                            createLinkAno(field, data[field].clientes_ano, data[field].hash),
                            createLinkAno(field, data[field].valor_ano, data[field].hash),
                        ];

                        fields_filter.push(temp_field);
                    }

                    console.log(fields_filter);

                    table_filters.rows.add(fields_filter).draw();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                    });
                }
            },
            error: function(data){
                var errors = data.responseJSON.errors;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function createLinkDia(venda, valor, hash){

        if (valor != 0 && valor != '0,00'){
            return "<a href=\"#\" onclick=\"pedidos_periodo('dia', '"+venda+"', '"+hash+"')\">"+valor+"</a>";
        }
        else{
            return '';
        }

    }
    function createLinkMes(venda, valor, hash){
        
        if (valor.length != 0 && valor != '0,00'){
            return "<a href=\"#\" onclick=\"pedidos_periodo('mes', '"+venda+"', '"+hash+"')\">"+valor+"</a>";
        }
        else{
            return '';
        }
    }
    function createLinkAno(venda, valor, hash){
        
        if (valor.length != 0 && valor != '0,00'){
            return "<a href=\"#\" onclick=\"pedidos_periodo('ano', '"+venda+"', '"+hash+"')\">"+valor+"</a>";
        }
        else{
            return '';
        }
    }

    function pedidos_periodo(periodo, venda, hash){
        $.ajax({
            url: '{{ route('entrada_pedido.lista') }}',
            type: 'POST',
            data: {
                periodo: periodo, 
                venda: venda, 
                hash: hash,
                _token: '{{ csrf_token() }}'
            },
        })
        .done(function(data) {
            var title = 'Pedidos do período';
            createModal("show_detalhe", title, data, 'modal-lg');
        });
        
    }

    function limpaBusca(){
        $.ajax({
            url: '{{ route('entrada_pedido.reseta_busca') }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
        })
        .done(function(data) {
            
            $("#gerentes").html('<option value="">Gerente</option>');
            $("#supervisores").html('<option value="">Supervisor</option>');
            $("#vendedor_representante").html('<option value="">Vendedor</option>');

            $.each(data.gerentes, function(i, item) {

                $("#gerentes").append("<option value='"+ i +"'>" + item + "</option>")
                
            });

            $.each(data.supervisores, function(i, item) {

                $("#supervisores").append("<option value='"+ i +"'>" + item + "</option>")
                
            });

            $.each(data.vendedor_representante, function(i, item) {

                $("#vendedor_representante").append("<option value='"+ i +"'>" + item + "</option>")
                
            });

        });
        
    }

@endsection
</script>