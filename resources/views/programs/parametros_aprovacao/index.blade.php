@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
	        <select name="estabelecimento" id="estabelecimento">
	            <option value=''>Estabelecimento</option>
	            @foreach(returnEmpresasNasajonView() as $key => $value)
	            <option value="{{ str_pad($key, 2, "0", STR_PAD_LEFT) }}">{{ $value }}</option>
	            @endforeach
	        </select>
        </div>
        <div class="col-lg-2">
            <select name="tipo_usuario_id" id="tipo_usuario_id">
                <option value=''>Tipo de Usuário</option>
                @foreach($tipo_usuarios as $value)
                <option value="{{ $value->id }}">{{ $value->nome }}</option>
                @endforeach
            </select>
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
    <table class="table table-striped" id="table-filters-precos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Tipo de Usuário</th>
                <th class='number_format'>Desconto Limite</th>
                <th class='number_format'>Prazo Limite</th>
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

    $(document).ready( function () {


        $("#coluna").on("change",function(){
            table_filters.clear().draw();
        });

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        $("#btn-create").on("click", function(){
            showModalParametros( '{{ route('parametros_aprovacao.formCreate') }}', 'Criar novos parâmetros');
        });

        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                createModal($(this));
            });
        });

	});

    function showModalParametros(url, title){
        $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}"}, 
            method: 'POST',
            success: function(body){
                createModal("modal_create_param", title, body, '');
                $(document).ready( function () {
                    ajaxForm($("#modal_create_param"));
                    $('#percentual_desconto').mask('#.##0,00', {reverse: true});
                    $('#prazo_adicional').mask('0#');
                });
            }
        });
    }

    function showModalEdit(url, title, id){
        $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", id:id}, 
            method: 'POST',
            success: function(body){
                createModal("modal_edit_param", title, body, '');
                $(document).ready( function () {
                    ajaxForm($("#modal_edit_param"));
                    $('#percentual_desconto').mask('#.##0,00', {reverse: true});
                    $('#prazo_adicional').mask('0#');
                });
            }
        });
    }

    function showModalDelete(url, title, id){
        $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", id:id}, 
            method: 'POST',
            success: function(body){
                createModal("modal_edit_param", title, body, '');
                $(document).ready( function () {
                    ajaxForm($("#modal_edit_param"));
                    $('#percentual_desconto').mask('#.##0,00', {reverse: true});
                    $('#prazo_adicional').mask('0#');
                });
            }
        });
    }

    function ajaxForm($model){
        $($model).find('.modal-body').find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $($model).modal('hide');
                    filterAjax($("#form_filter").serialize());
                    message("Atenção", "Dados salvos com sucesso!");
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            });
        });
    }

    table_filters = $('#table-filters-precos').DataTable({
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
            },
        },
        columnDefs: [{
            'targets': 'number_format',
            'class': 'number_format'
        }],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });

    function showErrorsInputs(form, input, message){
        
        if (input == 'empresa' || input == 'estado'){
            var $input = $(form).find("select[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
        else{
            var $input = $(form).find("input[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
    }

    function createBtEdit($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"popover\" data-trigger='hover' title=\"Editar\" onclick=\"showModalEdit('{{route('parametros_aprovacao.formEdit')}}', 'Editar parâmetros', "+$this.id+")\"></a>";
        return html;
    }

    function createBtDelete($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-delete\" data-toggle=\"popover\" data-trigger='hover' title=\"Apagar\" onclick=\"showModalDelete('{{route('parametros_aprovacao.formDelete')}}', 'Excluir parâmetros', "+$this.id+")\"></a>";

        return html;
    }

    function filterAjax(data_form){
        var $return;	    
        table_filters.clear().draw();
	    var form = $("#form_filter");

        form.find('.error-message').remove();

        $.ajax({
            url: "{{ route('parametros_aprovacao.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                    
                var linhas = data;

                if(linhas.length > 0){

                    var fields_filter = [];

                    for(var field in data){

                        var temp_field = [
                            linhas[field].estabelecimento,
                            linhas[field].tipo_usuario_id,
                            linhas[field].percentual_desconto,
                            linhas[field].prazo_adicional,
                            createBtEdit(linhas[field]),
                            createBtDelete(linhas[field]),
                        ];

                        fields_filter.push(temp_field);

                    }


                    table_filters.rows.add(fields_filter).draw().nodes();

                    var coluna = new Array("coluna_a", "coluna_b", "coluna_c");
                    var prazo = new Array("prazo_vista", "prazo_15", "prazo_30", "prazo_45", "prazo_60");

                    if(coluna.indexOf($("#coluna").val()) != -1){

                        table_filters.columns([5,6,7]).visible(false);
                        table_filters.columns([8,9,10,11,12,13]).visible(true);

                    }
                    else if (prazo.indexOf($("#coluna").val()) != -1){

                        table_filters.columns([8,9,10,11,12]).visible(false);
                        table_filters.columns([5,6,7,13]).visible(true);

                    }
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
@endsection
