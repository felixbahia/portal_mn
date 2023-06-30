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
        <div class="form-group col-lg-2 col-xl-3">
            {{ Form::text("data_inicio", date("d/m/Y"), ["id" => "data_inicio", "class" => "form-control data", "placeholder" => "Data Inicial"]) }}
        </div>
        <div class="form-group col-lg-2 col-xl-3">
            {{ Form::text("data_fim", date("d/m/Y"), ["id" => "data_fim", "class" => "form-control data", "placeholder" => "Data Final"]) }}
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
    <table class="table table-striped" id="table-filters-pagamentos-stone">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento</th>
                <th rowspan="2" class="tb_number">Pedido</th>
                <th rowspan="2" class="tb_number">Nota</th>
                <th rowspan="2" class="tb_number">RA</th>
                <th rowspan="2">Cliente</th>
                <th rowspan="2" class="sort-date">Data</th>
                <th rowspan="2" class="tb_number">Valor</th>
                <th rowspan="2">Status</th>
                <th colspan="3">Tempo</th>
            </tr>
            <tr>
                <th class="sort-date">Início Declarado</th>
                <th class="sort-date">Fim Declarado</th>
                <th class="sort-date">Finalização RA</th>
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
        table_filters_maquinihas.clear().draw();
    });
});

table_filters_maquinihas = $("#table-filters-pagamentos-stone").DataTable({
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
    table_filters_maquinihas.clear().draw();
    form = $(document).find('#form_filter');
    $('label.error-message').remove();

    $.ajax({
        url: '{{ route("tempo_espera_pedido.filtro") }}',
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
                    createBtnNota(response[field].nota_id,response[field].nota_numero),
                    response[field].ra,
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-placement='right' data-original-title='" + response[field].cliente + "''>" + response[field].cliente + "</div></div>",
                    response[field].data,
                    response[field].valor,
                    status(response[field].status,response[field].pedido),
                    response[field].tempo_manual_inicio,
                    response[field].tempo_manual_fim,
                    response[field].finalizacao_ra
                ];

                linhas.push(titulo_linha);

            }

            table_filters_maquinihas.rows.add(linhas).nodes().draw();

        },
        error: function (data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

function createBtnNota($id_nota,$numero_nota){
    var $html = "<a href=\"#\" class=\"btn-create\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"abrirNota('"+$id_nota+"','"+$numero_nota+"');\">"+$numero_nota+"</a>";
    return $html;
}

function createBtnPedido($id){
    var $html = "<a href=\"#\" class=\"btn-create\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"abrirPedido('"+$id+"');\">"+$id+"</a>";
    return $html;
}

function status($status,$id){
    if($status == 'em_andamento'){
        $html = '<center><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Separação em Andamento">'+
            '<i class="fas fa-clock" aria-hidden="true"></i>'+
        '</div></center>';
    }else if($status == 'ra_finalizada'){
        $html = '<center><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Separação Concluída">'+
                    '<i class="fa fa-check check-icon" aria-hidden="true"></i>'+
                '</div></center>';
    }else if($status == 'tempo_manual_finalizado'){
        $html = '<center><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Separação Concluída">'+
                    '<i class="fa fa-check check-icon" aria-hidden="true"></i>'+
                '</div></center>';
    }else if($status == ''){
        $html = '<center><div class="pr-5" data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="Separação não concluída">'+
                    '<i class="fas fa-times-circle"></i>'+
                '</div>'+
                '<div class="ml-4" data-toggle="tooltip" data-html="true" title="" data-placement="right" data-original-title="Cadastrar Nova Separação">'+
                    "<a href=\"#\" class=\"text-decoration-none\" onclick=\"lancarTempo('"+$id+"');\">"+
                        '<i class="far fa-plus-square"></i>'+
                    "</a>"+
                '</div></center>';
    }

    return $html;
}

function lancarTempo($id){
    $.ajax({
        url: "{{ route("tempo_espera_pedido.modal.cadastrar_tempo_espera") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Associar Tempo de Espera no Pedido: "+$id; 
            $body = callback;
            $class = "modal-lg";
            createModal("registrar-tempo-espera", $title, $body, '');
        }
    });
}

function lancarPagamentoRestante($id,$cliente,$valor,$valor_faturado,$id_pedido,$valor_pago,$valor_restante){
    $.ajax({
        url: "{{ route("stone_pagamentos.modal.registrar_pagamento_restante") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Associar Pagamento no Pedido: "+$id_pedido+" - Cliente: "+$cliente + " - Valor Pedido: "+$valor+ " - Valor Separação: "+$valor_faturado+ " - Valor pago: "+$valor_pago+ " - Valor restante: "+$valor_restante; 
            $body = callback;
            $class = "modal-lg";
            createModal("registrar-pagamentos", $title, $body, $class);
        }
    });
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

function abrirNota($id, $nota){
    $.ajax({
        url: '{{ route('notas_nasajon.modal.exibir')}}',
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            id_nota: $id,
            link_pedido: true,
            origem: 'NASAJON'
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da nota: " + $nota, body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })

        }
    });
}

function showErrorsInputs(form, input, messagen){
    var $input = $(form).find("input[name='"+input+"']");
    message('Atenção',messagen);
    $input.parent().children().addClass('error-input');
}
@endsection