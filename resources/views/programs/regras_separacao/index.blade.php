@extends('layouts.app')
@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2">
                <select name="estabelecimento" id="estabelecimento">
                    <option value=''>Estabelecimento</option>
                    @foreach($estabelecimentos as $key => $value)
                    <option value="{{$key}}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <button type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear">Limpar busca</button>
            <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
        </div>
    </form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-regras-separacao">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Destinatários do e-mail</th>
                <th>Editar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $("#btn-filterform").on("click", function(){        
            filterAjax($("#form_filter").serialize());
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
        $("#btn-create").on("click", function(){
            showModal();
        });
    });
    function createBtEdit($this){
        var html = "<a href=\"#\" data-id=\""+$this.estabelecimento+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Editar\" onclick=\"showModalEdit("+$this.estabelecimento+")\"></a>";
        return html;
    }
    function showModalEdit(estabelecimento){
        $.ajax({
            url: "{{ route('regras_separacao.modal.editar') }}",
            data: {_token: '{{ csrf_token() }}', estabelecimento:estabelecimento },
            method: 'POST',
            success: function(data){

                var $id = "editar";
                var $title = "Editar regras";
                var $body = data;

                createModal($id, $title, $body, '')

                $(document).find('.ui-widget-content').css('z-index', "2000 !important");
                ajaxForm($("#"+$id));

            }
        });
    }

    function showModal(){
        $.ajax({
            url: "{{ route('regras_separacao.modal.adicionar') }}",
            data: {_token: '{{ csrf_token() }}' },
            method: 'POST',
            success: function(data){

                var $id = "cadastrar";
                var $title = "Cadastrar regras";
                var $body = data;

                createModal($id, $title, $body, '')

                $(document).find('.ui-widget-content').css('z-index', "2000 !important");
                ajaxForm($("#"+$id));
            }
        });
    }

    table_filters = $('#table-filters-regras-separacao').DataTable({
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
            }        },
        "columnDefs": [

            {
                "targets": 0,
                "width": '10%'
            },
            {
                "targets": 2,
                "orderable": false,
                "width": '5%'
            },
        ],
        "order": [[ 0, 'asc' ]]
    });

    function showErrorsInputs(form, input, message){
        if (input == 'estabelecimento' || input == 'estado'){
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

    function filterAjax(data_form){
        var $return;
        var form = $("#form_filter");
        table_filters.clear().draw();

        form.find('.error-message').remove();
        
        $.ajax({
            url: "{{ route('regras_separacao.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabelecimento_nome,
                            data[field].emails,
                            createBtEdit(data[field]),

                        ];
                        fields_filter.push(temp_field);
                    }
                    
                    table_filters.rows.add(fields_filter).draw().nodes();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                    });
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

    function ajaxForm($model){
        $($model).find('[type="submit"]').on("click", function(event){
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
                    
                    $('#aliquota_modal').maskMoney({thousands:'.', decimal:','});

                }
            });
        });
    }

@endsection