@extends('layouts.app')

@section('content-filter')
<form action="{{ route('produtos_sem_estoque.filtro') }}" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <select name="estabelecimento" id="estabelecimento">
                <option value="">Estabelecimento</option>

                @foreach ($estabelecimento as $key => $value)
                  <option value="{{$key}}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <input type="text" name="data_inicio" id="data_inicio" value="" placeholder="Início do período" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="data_fim" id="data_fim" value="" placeholder="Fim do período" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <div class="form-check">
                {{ Form::checkbox('outlet', 'value',false, ['id' => 'outlet', 'class' => 'form-check-input']) }}
                {{ Form::label('outlet', 'Outlet',['class' => 'form-check-label', 'for' => 'outlet']) }}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit" id="table-filters-sem_estoque">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Código</th>
                <th>Descrição</th>
                <th>Cliente</th>
                <th class="tb_date">Data do pedido</th>
                <th class="tb_date">Previsão de Chegada</th>
                <th>Pedido Futuro?</th>
                <th class="tb_number">Quantidade não atendida</th>
                <th class="tb_number">Quantidade Atendida</th>
                <th>Vendedor</th>
                <th></th>
     

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

        $("#btn-filterform").on('click', function(){
            filterAjax();
        });

        initTable();

    });
    
    function initTable(){
        table_filters = $('#table-filters-sem_estoque').DataTable({
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
            "columnDefs": [
                { "class": "tb_date", targets: "tb_date"},
                { "class": "tb_number", targets: "tb_number"},
            ],
        });
    }

    $(document).ready(function (){
        datepicker_options = {format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            endDate: new Date()};

        $('#data_inicio').datepicker(datepicker_options);
        $('#data_fim').datepicker(datepicker_options);
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

    function filterAjax(){
        
        var form = $("#form_filter");
        table_filters.clear().draw();

        var url = form.attr('action');

        data_form = form.serialize();

        form.find('.error-message').remove();
        
        $.ajax({
            url: url,
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){

                    var fields_filter = [];

                    for(var field in data){

                        var temp_field = [
                            data[field].estabelecimento,
                            data[field].codigo,
                            data[field].descricao,
                            data[field].cliente,
                            data[field].data_pedido,
                            data[field].data_entrega,
                            data[field].pedido_futuro,
                            data[field].qtd,
                            data[field].qtd_atentida,
                            data[field].user,
                            data[field].detalhes_link
                      
                        ];
                        
                        fields_filter.push(temp_field);
                    
                    }

                    table_filters.rows.add(fields_filter).draw().nodes();
                    
                    $(document).find("#table-filters-sem_estoque").find(".bt-edit").off("click");
                    
                    $(document).find("#table-filters-sem_estoque").find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                    });

                }

            },

            error: function(data){

                var errors = data.responseJSON.errors;

                form.find('.error-message').remove();

                for(var field in errors){

                    showErrorsInputs(form, field, errors[field]);

                }

            }

        });

    }

    function showModal($element) {

        $.ajax({
            url: $($element).data('url'),
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                pedido_id: $($element).data('id'),
            },
            success: function(data){
                createModal('detalhes_modal', 'Detalhes do pedido', data, 'modal-lg')
            }
        })        
    }

@endsection
</script>