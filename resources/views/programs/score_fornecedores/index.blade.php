@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                <div class="input-group">
                    {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Fornecedor', 'maxlength' => '200']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route('fornecedor.busca.index') }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {{ Form::select("score", ['score' => 'Todos','sem_score' => 'Sem Score','com_score' => 'Com Score'], 'frete', ["id" => "score", "class"=>"form-control"]) }}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-dialog-table notas-importadas">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-index">
            <thead>
            <tr>
                <th class="fornecedor">Fornecedor<br></th>
                <th>Estado</th>
                <th>Regime de Tributação</th>
                <th class="score">Formulário Score</th>
            </tr>
            </thead>
            <tbody>
            </tbody> 
            <tfoot>
                <td></td>
                <td class="tb_number"></td>
                <td></td>
                <td class="tb_number"></td>
            </tfoot>  
        </table>
    </div>
@endsection

@section('script-footer')
$(document).ready( function () {
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });

    $('#btn-filterform').on('click', function(){
        filtro();
    });

    $(document).find("#fornecedor").val('');
    $(document).find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor());
    $(document).find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });

    table_score_fornecedores = $('#table-filters-index')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 15,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Consulta Fatura Transportadora',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
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
        "columnDefs": [
            {
                "width": "35%",
                "targets": "fornecedor"
            },
            {
                "width": "5%",
                "targets": "score"
            },
            {
                "class": "tb_number",
                "targets": "tb_number"
            },
        ]
    });
    table_score_fornecedores.on('draw', function () {
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });
});

function filtro(){
    $(table_score_fornecedores.column(0).footer()).html('');
    $(table_score_fornecedores.column(1).footer()).html('');
    $(table_score_fornecedores.column(2).footer()).html('');
    $(table_score_fornecedores.column(3).footer()).html('');
    table_score_fornecedores.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('score_fornecedores.filtro')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var out = [];
            for (var fields in data.response.retorno){
                out.push([
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.retorno[fields].fornecedor+"\">"+data.response.retorno[fields].fornecedor+"</div></div>",
                    data.response.retorno[fields].estado,
                    data.response.retorno[fields].regime_tributacao,
                    createBtViewFormularioScore(data.response.retorno[fields]),
                ]);
            }

            $(table_score_fornecedores.column(0).footer()).html('Total Realizado');
            $(table_score_fornecedores.column(1).footer()).html(data.response.contador_Score.realizado);
            $(table_score_fornecedores.column(2).footer()).html('Total Não Realizado');
            $(table_score_fornecedores.column(3).footer()).html(data.response.contador_Score.nao_realizado);
            
            table_score_fornecedores.rows.add(out).draw();
        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $form.find('input.error').eq(0).focus();
            }
        }
    }).always(function() {
        hide_loader();
    });
}

function createBtViewFormularioScore($this){
    if($this.score == false){
        var html = "<center><a href=\"#\" data-route=\"{{ route('score_fornecedores.modal.formulario_score') }}\" data-id=\""+$this.id+"\" data-title='SCORE - "+$this.fornecedor+"' class='bt-modal bt-formulario-score'></a></center>"
    }else if($this.score == true){
        var html = "<center><a href=\"#\" data-route=\"{{ route('score_fornecedores_consulta.modal.formulario_respondido_editar') }}\" data-id=\""+$this.id+"\" data-title='SCORE - "+$this.fornecedor+"' class='bt-modal'><i class='bt-edit'></i></a></center>"
    }

    return html;
}

function showModal($this){
    var url = $($this).data("route");
    var $id = $($this).data("id");
    var title = $($this).data("title");

    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id: $id},
        success: function(body){
            createModal('modal_formulario_score', title, body, 'modal-lg');
        }
    });
}

    
function returnDados($dados){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_atrasos_show").modal("hide");
    $("#form_filter").find("#fornecedor").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $.ajax({
        url: '{{ route('cliente.salvaClientePadrao') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codcad: $dados.find("td").eq(0).text()
        }
    });
}
    
function optionsAutoCompleteFornecedor(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('fornecedor.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum fornecedor encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#fornecedor").val(ui.item.label);
            $.ajax({
                url: '{{ route('cliente.salvaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    codcad: ui.item.value
                }
            });
            return false;
        }
    };
}

function showModalFornecedor(url, title){
    xhr = $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_atrasos_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_atrasos_show").find("tbody").find("tr").off("click");
                    $(document).find("#fornecedor_atrasos_show").find("tbody").find("tr").on("click", function(){
                        returnDados($(this));
                    });
                });
                $(document).find("#fornecedor_atrasos_show").find(".bt-selected").on("click", function(){
                    returnDados($(this));
                });
            });
        }
    });
}
@endsection