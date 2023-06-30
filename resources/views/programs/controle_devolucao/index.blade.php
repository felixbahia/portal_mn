@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select("estabelecimento", $estabelecimentos, 'Estabelecimentos', ["id" => "estabelecimento", "class"=>"form-control", 'placeholder' => 'Estabelecimentos']) }}
            </div>
            <div class="col-lg-2">
                <div class="input-group">
                    {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio', date('01/m/Y'),['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', date('d/m/Y'),['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar Busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table notas-importadas">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-index-devolucao">
        <thead>
            <tr>
                <th>Estabelecimento<br></th>
                <th class="tb_number">Notas de Devolução</th>
                <th class="tb_number">Com Processo</th>
                <th class="tb_number">Sem Processo</th>
                <th class="tb_number">Com Crítica</th>
                <th class="tb_number">Sem Crítica</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
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
        filtroBusca();
    });
    $(document).find("#cliente_nome").val('');
    $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());
    $(document).find("#bt-search-cliente-busca").on("click", function(){
        showModalCliente($(this).data("route"), "Lista de Clientes");
    });
    table_notas_devolucao = $('#table-filters-index-devolucao')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('c[r=G7] t', sheet).attr( 's', '0' );
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
            {
                className: "tb_number",
                "targets": "tb_number"
            },
        ]
    });
    table_notas_devolucao.on('draw', function () {
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModalNotas($(this));
        });
        $(document).find(".bt-modal-total-notas").off("click");
        $(document).find(".bt-modal-total-notas").on("click", function(event){
            event.stopPropagation();
            showModalNotas($(this));
        });
    });
});

function showModalCliente(url, title){
    xhr = $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            $(document).find('#cliente_modal_show').remove();
            createModal("cliente_modal_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_modal_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDados($(this), event);
                    });
                });
            });
        }
    });
}

function returnDados($dados){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente_modal_show").modal("hide");
    $("#form_filter").find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $.ajax({
        url: '{{ route('cliente.salvaClientePadrao') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codcad: $dados.find("td").eq(0).text()
        }
    });
}

function optionsAutoCompleteCliente(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('clientes.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum cliente encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#cliente_nome").val(ui.item.label);
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

function filtroBusca(){
    $(table_notas_devolucao.column(0).footer()).html('');
    $(table_notas_devolucao.column(1).footer()).html('');
    $(table_notas_devolucao.column(2).footer()).html('');
    $(table_notas_devolucao.column(3).footer()).html('');
    $(table_notas_devolucao.column(4).footer()).html('');
    $(table_notas_devolucao.column(5).footer()).html('');
    table_notas_devolucao.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('controle_devolucao.filtro')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var dados = data.response.retorno;
            var total = data.response.total;
            if(dados){
                var out = [];
                for (var fields in dados){
                    out.push([
                        dados[fields].estabelecimento,
                        createBtViewNotas(dados[fields]),
                        createBtViewNotasComProcesso(dados[fields]),
                        createBtViewNotasSemProcesso(dados[fields]),
                        createBtViewNotasComCritica(dados[fields]),
                        createBtViewNotasSemCritica(dados[fields]),
                    ]);
                }
                $(table_notas_devolucao.column(0).footer()).html('total');
                $(table_notas_devolucao.column(1).footer()).html(createBtViewTotalNotas(total));
                $(table_notas_devolucao.column(2).footer()).html(createBtViewTotalNotasComProcesso(total));
                $(table_notas_devolucao.column(3).footer()).html(createBtViewTotalNotasSemProcesso(total));
                $(table_notas_devolucao.column(4).footer()).html(createBtViewTotalNotasComCritica(total));
                $(table_notas_devolucao.column(5).footer()).html(createBtViewTotalNotasSemCritica(total));
                table_notas_devolucao.rows.add(out).draw();
            }
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

function showModalNotas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("notas_importadas-modal-notas", title, body, 'modal-lg');
        }
    });
}

function showModalTotalNotas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("notas_importadas-modal-notas", title, body, 'modal-lg');
        }
    });
}

function showModalNotasComPorcesso($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    var total = $($this).data('total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter, total: total},
        method: 'POST',
        success: function(body){
            createModal("notas_importadas-modal-notas", title, body, 'modal-lg');
        }
    });
}

function showModalTotalNotasComProcesso($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("notas_importadas-modal-notas", title, body, 'modal-lg');
        }
    });
}

function createBtViewNotas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_entrada') }}\" data-filter=\""+$this.filter+"\" data-title='NOTAS POR ESTABELECIMENTO - "+$this.estabelecimento+"' class='bt-modal'>"+$this.notas_importadas+"</a>"
    return html;
}

function createBtViewTotalNotas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_entrada') }}\" data-filter=\""+$this.filter+"\" data-title='TOTAL DE NOTAS' class='bt-modal-total-notas'>"+$this.notas_importadas+"</a>"
    return html;
}

function createBtViewNotasComProcesso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_com_processo') }}\" data-filter=\""+$this.filter+"\" data-title='NOTAS COM PROCESSO POR ESTABELECIMENTO - "+$this.estabelecimento+"' class='bt-modal'>"+$this.com_processo+"</a>"
    return html;
}

function createBtViewTotalNotasComProcesso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_com_processo') }}\" data-filter=\""+$this.filter+"\" data-title='TOTAL DE NOTAS COM PROCESSO' class='bt-modal'>"+$this.com_processo+"</a>"
    return html;
}

function createBtViewNotasSemProcesso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_sem_processo') }}\" data-filter=\""+$this.filter+"\" data-title='NOTAS SEM PROCESSO POR ESTABELECIMENTO - "+$this.estabelecimento+"' class='bt-modal'>"+$this.sem_processo+"</a>"
    return html;
}

function createBtViewTotalNotasSemProcesso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_sem_processo') }}\" data-filter=\""+$this.filter+"\" data-title='TOTAL DE NOTAS SEM PROCESSO' class='bt-modal'>"+$this.sem_processo+"</a>"
    return html;
}

function createBtViewNotasSemCritica($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_sem_critica') }}\" data-filter=\""+$this.filter+"\" data-title='NOTAS SEM CRÍTICA POR ESTABELECIMENTO - "+$this.estabelecimento+"' class='bt-modal'>"+$this.sem_critica+"</a>"
    return html;
}

function createBtViewTotalNotasSemCritica($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_sem_critica') }}\" data-filter=\""+$this.filter+"\" data-title='TOTAL DE NOTAS SEM CRÍTICA' class='bt-modal'>"+$this.sem_critica+"</a>"
    return html;
}

function createBtViewNotasComCritica($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_com_critica') }}\" data-filter=\""+$this.filter+"\" data-title='NOTAS COM CRÍTICA POR ESTABELECIMENTO - "+$this.estabelecimento+"' class='bt-modal'>"+$this.com_critica+"</a>"
    return html;
}

function createBtViewTotalNotasComCritica($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_devolucao.modal.notas_com_critica') }}\" data-filter=\""+$this.filter+"\" data-title='TOTAL DE NOTAS COM CRÍTICA' class='bt-modal'>"+$this.com_critica+"</a>"
    return html;
}

@endsection