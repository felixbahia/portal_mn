@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_motivo_divergencia" id="form_motivo_divergencia" onsubmit="return false">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
            {{ Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control input-label', 'placeholder' => 'Descrição', 'maxlength' => '250']) }}
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
    <table class="table table-striped" id="table-filters-motivo_divergencia">
        <thead>
            <tr>
                <th class="descricao">Descrição</th>
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
            modalAdicionar();
        });
        table_filters_motivo_divergencia = $('#table-filters-motivo_divergencia')
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
            },
            "columnDefs":[
                {
                    "targets": "descricao",
                    "orderable": true
                }
            ]
        });

        table_filters_motivo_divergencia.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(){
                modalEditar($(this).data('id'));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(){
                modalDeletar($(this).data('id'));
            });
        });
    });

    function filtro(){
        table_filters_motivo_divergencia.clear().draw();
        $.ajax({
            url: "{{ route('motivo_divergencia.filtro') }}", 
            dataType: 'json',
            data: $(document).find('#form_motivo_divergencia').serialize(),
            method: 'POST',
            success: function(callback){
                dados = callback.response;
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].descricao,
                            criarBtnEditar(dados[field]),
                            criarBtnDeletar(dados[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_motivo_divergencia.rows.add(fields_filter).draw().nodes();
                }
            },
            error: function(callback){
                message('Atenção', 'Nenhuma Família localizada.');
            }
        });
    }

    function criarBtnEditar($dados){

        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }


    function criarBtnDeletar($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }

    function modalAdicionar() {
        $.ajax({
            url: '{{ route("motivo_divergencia.modal.salvar") }}',
            data: {_token: '{{ csrf_token() }}'},
            method: 'POST',
            success: function(body){
                var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal("modal_add_motivo_divergencia", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function modalEditar($id) {
        $.ajax({
            url: '{{ route("motivo_divergencia.modal.editar") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Editar {{ CustomView::programaName() }}';
                createModal("modal_edit_motivo_divergencia", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function modalDeletar($id) {
        $.ajax({
            url: '{{ route("motivo_divergencia.modal.excluir") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Deletar {{ CustomView::programaName() }}';
                createModal("modal_delete_motivo_divergencia", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }
@endsection