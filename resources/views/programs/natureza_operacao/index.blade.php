@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2">
                <select name="estabelecimento" id="estabelecimento">
                    <option value=''>Origem</option>
                    @foreach($estabelecimentos as $key => $value)
                        <option value="{{$key}}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="estado_destino" id="estado_destino">
                    <option value=''>Estado</option>
                    @foreach($estados as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
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
    <table class="table table-striped table-not-view" id="table-filters-natureza">
        <thead>
            <tr>
                <th rowspan="2">Origem</th>
                <th rowspan="2">Estado</th>
                <th colspan="2">CFOP</th>
                <th rowspan="2">Editar</th>                
            </tr>
            <tr>
                <th>Pessoa Jurídica</th>
                <th>Pessoa Física</th>
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
                editModal($(this));
            });
        });
        
        $("#btn-create").on("click", function(){
            addModal();
        });

    });

    function createBtEdit($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Editar\" onclick=\"editModal($(this))\"></a>";
        return html;
    }


    table_filters = $('#table-filters-natureza').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
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
            }        },
        "columnDefs": [
        ],
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
            url: "{{ route('natureza_operacao.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                if(callback.status){
                    var data = callback.response;
                    if(data.length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].estabelecimento,
                                data[field].estado_destino,
                                data[field].cfop_pj,
                                data[field].cfop_pf,
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

    function ajaxForm($modal){
        $($modal).find('[type="submit"]').on("click", function(event){
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
                    $($modal).modal('hide');
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

    function addModal(){
        $.ajax({
            url: '{{ route('natureza_operacao.modal.add')}}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(data){
                createModal('adicionar-natureza', "Adicionar Nova Natureza de Operação", data);
                ajaxForm('#adicionar-natureza');
            }
        });
        
    }

    function editModal(elemen){
        $.ajax({
            url: '{{ route('natureza_operacao.modal.edit')}}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}',
            id: elemen.data('id')},
            success: function(data){
                createModal('editar-natureza', "Editar Natureza de Operação", data);
                ajaxForm('#editar-natureza');
            }
        });
        
    }
@endsection