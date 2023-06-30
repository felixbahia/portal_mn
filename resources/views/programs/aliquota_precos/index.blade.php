@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2">
                <select name="origem" id="origem">
                    <option value=''>Origem</option>
                    @foreach($origem as $key => $value)
        
                        <option value="{{$key}}">{{ $value }}</option>

                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="estado" id="estado">
                    <option value=''>Estado</option>
                    @foreach($estados as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="internacional" id="internacional">
                    <option value=''>Filtrar por origem?</option>
                    <option value="0">Nacional</option>
                    <option value="1">Importado</option>
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
    <table class="table table-striped" id="table-filters-aliquota">
        <thead>

            <tr>
                <th rowspan='2'>Origem</th>
                <th rowspan='2'>Estado</th>
                <th rowspan='2'>Frete adicional</th>
                <th colspan=2>ICMS para venda</th>
                <th rowspan='2'>Origem</th>
                <th rowspan='2'>Editar</th>
            </tr>
            <tr>
                <th>Pessoa jurídica</th>
                <th>Cliente Isento</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
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
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Editar\" onclick=\"showModalEdit("+$this.id+", '"+$this.nome+"')\"></a>";
        return html;
    }



    function showModalEdit(id){
        $.ajax({
            url: "{{ route('aliquota_preco.formEdit') }}",
            data: {_token: '{{ csrf_token() }}', id:id },
            method: 'POST',
            success: function(data){

                var $id = "editar";
                var $title = "Editar alíquota";
                var $body = data;

                createModal($id, $title, $body, '')

                $(document).find('.ui-widget-content').css('z-index', "2000 !important");

            }
        });
    }

    function showModal(){
        $.ajax({
            url: "{{ route('aliquota_preco.formCadastro') }}",
            data: {_token: '{{ csrf_token() }}' },
            method: 'POST',
            success: function(data){

                var $id = "cadastrar";
                var $title = "Cadastrar alíquota";
                var $body = data;

                createModal($id, $title, $body, '')

                $(document).find('.ui-widget-content').css('z-index', "2000 !important");

            }
        });
    }

    table_filters = $('#table-filters-aliquota').DataTable({
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
                "targets": -1,
                "orderable": false,
            },
            {
                "targets": [2,3,4],
                "className": 'number_format',
                "width": '10% !important'
            }
        ],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
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
            url: "{{ route('aliquota_preco.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].origem,
                            data[field].estado,
                            data[field].frete_adicional,
                            data[field].icms_venda,
                            data[field].icms_venda_cliente_isento,
                            data[field].internacional,
                            createBtEdit(data[field]),

                        ];
                        fields_filter.push(temp_field);
                    }
                    console.log(fields_filter);
                    
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

@endsection
</script>