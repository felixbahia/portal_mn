@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
        </div>
        <div class="content-buttons">
        </div>
    </form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-param">
        <thead>

            <tr>
                <th>Estabelecimento</th>
                <th class='number_format'>Desconto Máximo</th>
                <th class='number_format'>Valor adicional Máximo</th>
                <th class='number_format'>Dias maximo para integração</th>
                <th>Editar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')

    $(document).ready(function (){

        lerInformacoesTabela();
        
        $(".bt-edit").on('click', function(){
            showModalEdit($(this).data('estabelecimento'));
        });

    });

    function showModalEdit($estabelecimento){
        $.ajax({
            url: '{{ route('parametros_pedido.modal')}}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                estabelecimento: $estabelecimento
            },
            success: function(data){
                createModal('editar', "Editar parâmetros do pedido", data, '');        
            }
        })
        
    }

    table_filters = $('#table-filters-param').DataTable({
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
                "targets": 'number_format',
                "className": 'number_format',
            }

        ],

        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]

    });

    function createBtEdit($id){
        return "<td><a href=\"#\" data-estabelecimento=\""+$id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Editar\"></a></td>";
    }

    function lerInformacoesTabela(){

        table_filters.clear().draw();

        $.ajax({
            url: "{{ route('parametros_pedido.filter') }}",
            dataType: 'json',
            data: {
                _token: '{{csrf_token()}}'
            },
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabelecimento,
                            data[field].valor_minimo_porcentagem,
                            data[field].valor_maximo_porcentagem,
                            data[field].dias_integracao,
                            createBtEdit(data[field].id),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw().nodes();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        showModalEdit($(this).data('estabelecimento'));
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
                    console.log(data);
                    $($modal).modal('hide');
                    message("Atenção", "Dados salvos com sucesso!");
                    lerInformacoesTabela();
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

@endsection
</script>