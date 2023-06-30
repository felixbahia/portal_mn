@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_contato_emergencia" id="form_contato_emergencia" onsubmit="return false">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
        
            <div class=" col-lg-2">
            {{ Form::hidden('id', '', ['id' => 'id']) }}
            {{ Form::text('nome', '', ['id' => 'nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Usuário', 'maxlength' => '250']) }}
            </div>
            <div class="col-lg-1">
                    <div class="form-check">
       

                    {!! Form::checkbox('inativo', 'false', false, ['id' => 'inativo', 'class' => 'form-check-input']) !!}
                    {!! Form::label('inativo', 'Inativo', ['class' => 'form-check-label']) !!}
        
                    </div>
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
    <table class="table table-striped" id="table-filters-contato-emergencia">
        <thead>
            <tr>
                <th rowspan='2'>Usuário</th>    
                <th colspan='3' class="border-right">Dados do Usuário</th>
                <th colspan='3'>Contato de Emergência</th>
             
            </tr>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>Setor</th>

                <th>Nome</th>
                <th>Telefone</th>
                <th>Editar</th>
                <th>Desativar</th>
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

        $(document).find("#nome").autocomplete(optionsAutoCompleteUsuario());
        $(document).find("#btn-create").off("click");
        $(document).find("#btn-create").on("click", function(){
            event.stopPropagation();
            modalAdicionar();
        });
        table_filters_contato_emergencia = $('#table-filters-contato-emergencia')
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

        table_filters_contato_emergencia.on('draw', function () {
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
        $(document).find('#form_contato_emergencia').find('.error-message').remove();
        $.ajax({
            url: "{{ route('contato_emergencia.filtro') }}", 
            dataType: 'json',
            data: $(document).find('#form_contato_emergencia').serialize(),
            method: 'POST',
            success: function(callback){
                dados = callback.response.usuario;
                table_filters_contato_emergencia.clear().draw();
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].usuario,
                            dados[field].nome,
                            dados[field].telefone,
                            dados[field].setor,
                            dados[field].contato_emergencia,
                            dados[field].telefone_emergencia,
                            criarBtnEditar(dados[field]),
                            criarBtnDeletar(dados[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_contato_emergencia.rows.add(fields_filter).draw().nodes();
                    
                }
            },
            error: function(callback){
                message('Atenção', 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!');
            }
        });
    }

    function optionsAutoCompleteUsuario(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('usuario.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum usuário encontrado');
                    event.stopPropagation();
                    return false;
                };
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#id").val(ui.item.id);
                $(document).find("#nome").val(ui.item.label);
                return false;
            }
        };
    }

    function criarBtnEditar($dados){

        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }
    function criarBtnDeletar($dados){
        var $html = '';
        if($dados.ativo == true){
             var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        } 
     return $html;
    }
    function modalAdicionar() {
        $.ajax({
            url: '{{ route("contato_emergencia.modal.salvar") }}',
            data: {_token: '{{ csrf_token() }}'},
            method: 'POST',
            success: function(body){
                var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal("modal_add_contato_emergencia", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function modalEditar($id) {
        $.ajax({
            url: '{{ route("contato_emergencia.modal.editar") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Editar {{ CustomView::programaName() }}';
                createModal("modal_edit_contato_emergencia", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }
    function modalDeletar($id) {
        $.ajax({
            url: '{{ route("contato_emergencia.modal.excluir") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Deletar {{ CustomView::programaName() }}';
                createModal("modal_delete_contato_emergencia", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }
@endsection