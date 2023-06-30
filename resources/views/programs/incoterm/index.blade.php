@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_incoterm" id="form_incoterm" onsubmit="return false">
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
            {{ Form::text('tipo', '', ['id' => 'incoterm', 'class' => 'form-control input-label', 'placeholder' => 'Tipo', 'maxlength' => '10']) }}
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
    <table class="table table-striped" id="table-filters-incoterm">
        <thead>
            <tr>
                <th>Tipo</th>
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

        $(document).find('#btn-filterform').on('click', function(event){
            event.stopPropagation();
            filtro();
        });
        $(document).find("#btn-create").off("click");
        $(document).find("#btn-create").on("click", function(){
            event.stopPropagation();
            showModalCreate();
        });
        table_filters_incoterm = $('#table-filters-incoterm')
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

        table_filters_incoterm.on('draw', function () {
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
        table_filters_incoterm.clear().draw();
        $.ajax({
            url: "{{ route('incoterm.filtro') }}", 
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}",
                tipo: $(document).find('#incoterm').val()
            },
            method: 'POST',
            success: function(callback){
                dados = callback.response.incoterm;
                table_filters_incoterm.clear().draw();
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].tipo,
                            createBtnEdit(dados[field]),
                            createBtnDelete(dados[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_incoterm.rows.add(fields_filter).draw().nodes();
                    
                }
            },
            error: function(callback){
                message('Atenção', 'Nenhuma Incoterm localizada.');
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

    function showModalCreate() {
        $.ajax({
            url: '{{ route("incoterm.modal.adicionar") }}',
            data: {_token: '{{ csrf_token() }}'},
            method: 'POST',
            success: function(body){
                createModal("modal_add_incoterm", 'Cadastro Incoterm', body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function showModalEdit($id) {
        $.ajax({
            url: '{{ route("incoterm.modal.editar") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                createModal("modal_edit_incoterm", 'Editar Incoterm', body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function showModalDelete($id) {
        $.ajax({
            url: '{{ route("incoterm.modal.deletar") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                createModal("modal_delete_incoterm", 'Excluir Incoterm', body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }
@endsection