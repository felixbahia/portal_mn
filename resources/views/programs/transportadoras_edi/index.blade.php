@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_transportadora_edi" id="form_transportadora_edi" onsubmit="return false">
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group col-sm-4 col-xl-3">
            <div class="input-group">
                {{ Form::text('transportadora_edi', '', ['id' => 'transportadora_edi', 'class' => 'form-control input-label', 'placeholder' => 'Transportadora', 'maxlength' => '250']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora-busca" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>

@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-transportadoras">
        <thead>
            <tr>
                <th>Nome Transportadora</th>
                <th>CNPJ</th>
                <th>Email</th>
                <th>Editar</th>
                <th>Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready(function(){

        $(document).find("#transportadora_edi").autocomplete(optionsAutoCompleteTransportador());

        $(document).find('#btn-filterform').on('click', function(event){
            event.stopPropagation();
            filtro();
        });
        $(document).find("#btn-create").off("click");
        $(document).find("#btn-create").on("click", function(){
            event.stopPropagation();
            showModalCreate();
        });
        $(document).find("#bt-view-transportadora").off("click");
        $(document).find("#bt-view-transportadora").on("click", function(event){
            event.stopPropagation();
            modalTransportador();
        });

        table_filters_transportadoras = $('#table-filters-transportadoras')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
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
            }
        });

        table_filters_transportadoras.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(){
                showModalEdit($(this).data('id'));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(){
                showModalDelete($(this).data('id'));
            });
        });
    });

    function filtro(){
        table_filters_transportadoras.clear().draw();
        $.ajax({
            url: "{{ route('transportadoras_edi.filtro') }}", 
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}",
                transportadora_edi: $(document).find('#transportadora_edi').val()
            },
            method: 'POST',
            success: function(callback){
                dados = callback.response.transportadoras;
                table_filters_transportadoras.clear().draw();
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].transportadora_nome,
                            dados[field].transportadora_cnpj,
                            dados[field].email,
                            createBtnEdit(dados[field]),
                            createBtnDelete(dados[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_transportadoras.rows.add(fields_filter).draw().nodes();
                    
                }
            },
            error: function(callback){
                message('Atenção', 'Nenhuma Transportadora localizada.');
            }
        });
    }

    function createBtnEdit($dados){

        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }


    function createBtnDelete($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }

    function optionsAutoCompleteTransportador(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#form_transportadora_edi').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_edi").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportadora_edi").val(ui.item.label);
                return false;
            }
        };
    }

    function showModalCreate() {
        $.ajax({
            url: '{{ route("transportadoras_edi.modal.adicionar") }}',
            data: {_token: '{{ csrf_token() }}'},
            method: 'POST',
            success: function(body){
                createModal("modal_add_transportadoras_edi", 'Cadastro Transportadora EDI', body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function showModalEdit($id) {
        $.ajax({
            url: '{{ route("transportadoras_edi.modal.editar") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                createModal("modal_edit_transportadoras_edi", 'Editar Transportadora EDI', body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function showModalDelete($id) {
        $.ajax({
            url: '{{ route("transportadoras_edi.modal.deletar") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                createModal("modal_delete_transportadora_edi", 'Excluir Transportadora EDI', body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function modalTransportador(){
        $.ajax({
            url: '{{ Route("transportador.index.dialog") }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(data){
                $(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
                            returnDadosTransportador($(this), modal);
                        });
                    });
                });
            }
        });
    }



    function returnDadosTransportador($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#transportadora_edi").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
        modal.modal('hide');
    }
@endsection