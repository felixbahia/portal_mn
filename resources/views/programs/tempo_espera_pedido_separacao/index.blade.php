@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group col-lg-3 col-xl-3">   
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form form-control', 'placeholder' => 'Estabelecimento']) !!}
        </div>
        <div class="form-group col-lg-3 col-xl-3">
            {{ Form::text("numero_pedido", "", ["id" => "numero_pedido", "class" => "form-control", "placeholder" => "Pedido Portal"]) }}
        </div>
        <div class="form-group col-lg-1 col-xl-2">
            {{ Form::text("data_inicio", date("d/m/Y"), ["id" => "data_inicio", "class" => "form-control data", "placeholder" => "Data Inicial"]) }}
        </div>
        <div class="form-group col-lg-1 col-xl-2">
            {{ Form::text("data_fim", date("d/m/Y"), ["id" => "data_fim", "class" => "form-control data", "placeholder" => "Data Final"]) }}
        </div>
        <div class="form-group col-lg-1 col-xl-2">
                {!! Form::select('situacao', $situacao, '',  ['id' => 'situacao', 'class' => 'form form-control', 'placeholder' => 'STATUS']) !!}
         </div>
    </div>
    
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-pedidos">
        <thead>
            <tr>
                <th class="th-dados">Estabelecimento</th>
                <th  class="tb_number">Pedido</th>
                <th class="th-dados">Cliente</th>
                <th class="sort-date">Entrada</th>
                <th class="sort-date">Estimada</th>
                <th class="sort-date">Separaçâo</th>
                <th class="sort-date">Faturamento</th>
                <th class="sort-date">Retirada</th>
                <th class="th-dados">Status</th>
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

$(document).ready(function(){
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });

    $(document).find('#btn-filterform').on('click', function(){
        buscarPedidos();
    });


    $(document).find("#btn-clearform").on("click", function(){
        table_filters_separacao.clear().draw();
    });
});

table_filters_separacao = $("#table-filters-pedidos").DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "scrollX": false,
    "scrollCollapse": true,
    "paging": false,
    "autoWidth": true,
    "language": {
        "emptyTable":     "Nenhum registro encontrado",
        "infoPostFix":    "",
        "thousands":      ".",
        "decimal":        ",",
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
            "type": 'num-fmt', 
            "targets": "tb_number"
        },
        { "targets": [-1, -2], "orderable": false},
        { "class": "tb_date", targets: "sort-date" }
    ],
    "order": [[ 0, 'asc' ]]
    }
);

function buscarPedidos(){
    table_filters_separacao.clear().draw();
    form = $(document).find('#form_filter');
    $('label.error-message').remove();

    $.ajax({
        url: '{{ route("tempo_espera_pedido_separacao.filtro") }}',
        dataType: 'json',
        method: 'POST',
        data: form.serialize(),
        success: function(data){

            var response = data.response.retorno;
   
            var linhas = [];

            for(var field in response){
                
                titulo_linha = [
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-placement='right' data-original-title='" + response[field].estabelecimento + "''>" + response[field].estabelecimento + "</div></div>",
                    createBtnPedido(response[field].pedido),
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-placement='right' data-original-title='" + response[field].cliente + "''>" + response[field].cliente + "</div></div>", 
                    response[field].data_entrada,
                    response[field].data_estimada,
                    response[field].data_separacao,
                    response[field].data_faturamento,
                    response[field].data_retirada,
                    status(response[field].status),
                    response[field].tempo_total,


                ];
              
                linhas.push(titulo_linha);

            }

            table_filters_separacao.rows.add(linhas).nodes().draw();

        },
        error: function (data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}



function createBtnPedido($id){
    var $html = "<a href=\"#\" class=\"btn-create\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"abrirPedido('"+$id+"');\">"+$id+"</a>";
    return $html;
}

function status($status){
    if($status == 'OK'){
        $html = '<center><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Separação OK">'+
            '<i class="fa fa-check check-icon text-success" aria-hidden="true"></i>'+
        '</div></center>';
    }else if($status == 'ATENÇÃO'){
        $html = '<center><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Separação em Atenção">'+
                    '<i class="fas fa-clock text-warning" aria-hidden="true"></i>'+
                '</div></center>';
    }else if($status == 'ATRASO'){
        $html = '<center><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Separação em Atrazo">'+
                    '<i class="fa fa-times error-icon  text-danger" aria-hidden="true"></i>'+
                '</div></center>';
    }
    return $html;
}




function abrirPedido($id){
    $.ajax({
        url: "{{ route("pedido_portal.detalhes") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Detalhes do pedido: "+$id; 
            $body = callback;
            $class = "modal-lg";
            createModal("view_pedido", $title, $body, $class);
        }
    });
}



function showErrorsInputs(form, input, messagen){
    var $input = $(form).find("input[name='"+input+"']");
    message('Atenção',messagen);
    $input.parent().children().addClass('error-input');
}
@endsection