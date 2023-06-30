@extends('layouts.page-dialog')
@section('content')
<form action="#" method="post" id="editar_mensagem_form" name="editar_mensagem_form" class="editar_mensagem_form" onsubmit="return false">
    @csrf
	{{ Form::hidden('id',$dados['id'],['id' => 'id']) }}
	<div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('titulo_modal', 'Títudo da Campanha', []) }}
            {{ Form::text('titulo', $dados['titulo'], ['class' => 'form-control', 'id' => 'titulo']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            {{ Form::label('data_inicio', 'De:') }}
            {{ Form::text('data_inicio', $dados['data_inicio'],['id' => 'data_inicio', 'class' => 'form-control data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-6">
            {{ Form::label('data_final', 'Até:') }}
            {{ Form::text('data_final', $dados['data_fim'],['id' => 'data_final', 'class' => 'form-control data', 'placeholder' => 'Data Final DD/MM/AAAA','maxlength' => '10']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('arquivo', 'Arquivo') }}
            {{ Form::file('arquivo', ['id'=>'arquivo', 'class' => 'btn btn-sm btn-light form-control', 'accept' => 'image/*']) }}
            <a href='{{ $dados['link_arquivo'] }}' target="blank" title="Documento HTML"><i class="btn-download thumb ml-2 mt-1"></i> {{ $dados['nome_arquivo'] }}</a>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('', 'Adicionar Tipo usuario') }}
            <div class="input-group">
                {{ Form::text('tipo_usuario', '', ['id' => 'tipo_usuario', 'class' => 'form-control', 'disabled' => 'disabled']) }}
                <input type='hidden'name='tipo_usuario_retorno' id='tipo_usuario_retorno' value=''>
                <span class="input-group-addon border" id="bt-search-tipo_usuario"><i class="bt-view m-2"></i></span>
                <div class="input-group-append" id="success_button_cliente">
                    <button class="input-group-addon btn btn-success" id="add-tipo_usuario-button">+</button>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row add-clientes">
        <div class="col-sm-12 table-container">
            <table class="table table-striped content-dialog-table" id="table-modal-editar">
                <thead>
                    <tr>
                        <th>Tipo usuario</th>
                        <th class="display_none"></th>
                        <th>Excluir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados['tipo_usuario'] as $tipo_usuario)
                        <tr>
                            <td>{{ $tipo_usuario['nome'] }}</td>
                            <td>{{ $tipo_usuario['id'] }}</td>
                            <td><a href='#' class="bt-delete" data-toggle="popover" data-trigger='hover' title="Apagar" onclick="deletarLinha(this)"></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-12 mt-4">
            {{ Form::submit('Editar', array('id' => 'editar', 'class' => 'btn btn-primary float-right')) }}
        </div>
    </div>
</form>
<script>
	$(document).ready( function(){
        $(document).find('#editar_mensagem_form').find('#table-modal-editar').DataTable(table_editar_tipo_usuario).draw();

        $(document).find("#editar_mensagem_form").find("#editar").on("click", function(){
            editar();
        });

        form_modal_editar = $(document).find('#editar_mensagem_form');
        initMaskCamposEditar(form_modal_editar);

        $(document).find('#editar_mensagem_form').find("#add-tipo_usuario-button").on("click", function(){
			adicionarTipoUsuario();
		});

        $(document).find('#editar_mensagem_form').find("#bt-search-tipo_usuario").on("click", function(){
			showModalTipoUsuario();
		});
	});

    function adicionarTipoUsuario(){
        if($(document).find('#tipo_usuario').val() ==  ''){
            message('Atenção','Nenhum tipo_usuario Selecionado.','error');
            return false;
        }
        
        nova_linha = [$(document).find('#tipo_usuario').val(),$(document).find('#tipo_usuario_retorno').val(), createBtDeleteLista()];

        if($(document).find("#table-modal-editar").find("td").filter(function() { return $(this).text() == $(document).find('#tipo_usuario').val(); }).length == 0 ){
            $(document).find("#table-modal-editar").DataTable().row.add(nova_linha).draw();
            $(document).find('#tipo_usuario').val('');
        }else{
            $form.find('#table-modal-editar').eq(0).parent().append('<label class="error-message error-">tipo_usuario já adicionado</label>'); 
            $form.find('#table-modal-editar').eq(0).addClass('error');
        }
    }

    function createBtDeleteLista(){
        var html = "<a href='#' class=\"bt-delete\" data-toggle=\"popover\" data-trigger='hover' title=\"Apagar\" onclick=\"deletarLinha(this)\"></a>";
        return html;
    }

    function deletarLinha(element){
        $(element).parents('table').DataTable().row( $(element).parents('tr') ).remove().draw();
    }

    table_editar_tipo_usuario = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        'paging': false,
        "orderMulti": false,
        "scrollY": "20vh",
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
            }        },
        "columnDefs": [
            {
                "targets": -1,
                "orderable": false,
            },
            {
                "targets": [ 'display_none' ],
                "class": "display_none"
            },
        ],
        "order": [[ 0, 'asc' ]]
    };

    function showModalTipoUsuario(){
        $.ajax({
            url: '{{ route('tipo_usuario.modal.busca') }}',
            method: 'GET',
            success: function(body){
                createModal("tipo_usuario_searsh_show_modal", "Tipo de usuários", body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog_tipo_usuario.on('draw', function () {
                        $(document).find("#tipo_usuario_searsh_show_modal").find('tbody').find("tr").off("click");
                        $(document).find("#tipo_usuario_searsh_show_modal").find('tbody').find("tr").on("click", function(event){
                            returnDados($(this), event);
                        });
                    });
                    $(document).find("#tipo_usuario_searsh_show_modal").find(".bt-selected").on("click", function(){
                        returnDados($(this));
                    });
                });
            }
        });
    }

    function returnDados($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#tipo_usuario_searsh_show_modal").modal("hide");
        $(document).find("#tipo_usuario").val($dados.find("td").eq(0).text());
        $(document).find("#tipo_usuario_retorno").val($dados.find("td").eq(1).text());
    }


    function editar(){
        var $form = $(document).find("#editar_mensagem_form");
        var vencimentos = [];
		var tipo_usuario = [];
		var data = new FormData($form[0]);
        $($form).find("#table-modal-editar > tbody").find("tr").each( function(){
            if (!$(this).find("td:eq(0)").hasClass('dataTables_empty')){
                tipo_usuario.push($(this).find("td:eq(1)").text());
            }
        });
        if(tipo_usuario.length  > 0){
            $.each(tipo_usuario, function(k, value){
                data.append('tipo_usuarios[]', value);
            });
        }

        $('label.error-message').remove();

        $.ajax({
            url: '{{ route('mensagem.editar')}}',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function(data){
                if(data.status === 'sucess'){
                    filter($("#form_filter").serialize());
                    message('Atenção',data.message,'success');
                    $(document).find('#modal_editar_mensagem').modal('hide');
                }else if(data.status === 'error'){
                    message('Atenção',data.message,'error');
                }   
            },
            error: function(callback){
                var retorno = callback.responseJSON.error;
                $.each(retorno, function(index, el) {
                    if(index != 'tipo_usuarios'){
                        $form.find('#'+index).eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('#'+index).eq(0).addClass('error');
                    }else{
                        $form.find('#table-modal-editar').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('#table-modal-editar').eq(0).addClass('error');
                    }
                });
                $form.find('input.error').eq(0).focus();
            }
        });
	}

    function initMaskCamposEditar(form_modal_editar){
        form_modal_editar.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        });
        form_modal_editar.find('.data').mask('00/00/0000');
    }
</script>
@endsection
