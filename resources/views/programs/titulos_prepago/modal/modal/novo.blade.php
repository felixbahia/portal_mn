@extends('layouts.page-dialog')

@section('content')

<form action="#" onsubmit="return false" id='adiciona_item'>
    <div class="content-filter-dialog">	
        
            @csrf
            {{ Form::hidden('id', $id, ['id' => 'id'])}}
            
            <div class="content-fields">
                <div class="col-sm-2">
                    {{ Form::label('estabelecimento_modal', 'Estabelecimento', []) }}
                    {{ Form::select('estabelecimento', returnEmpresasNasajonView(), '', ['id' => 'estabelecimento_modal', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}       
                </div>
                <div class="col-sm-2">
                    {{ Form::label('nota_numero_modal', 'Número da Nota', []) }}
                    {{ Form::text('nota_numero', '', ['id' => 'nota_numero_modal', 'class' => 'form-control', 'placeholder' => 'Número da Nota']) }}       
                </div>
            </div>
        </form>
    </div>
    
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped" id="table-filters-modal">
                <thead>
                    <tr>
                        <th class='tb_number'>Código</th>
                        <th>Grupo</th>
                        <th>Descrição</th>
                        <th class='tb_number'>Custo unitário</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</form>
<script>

    $(document).ready(function(){

        $(document).find('#valor').maskMoney({thousands:'', decimal:','});
    });

    table_filters_custos_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
        'columnDefs': [
            {
                "class": "tb_number",
                'type': 'num-fmt',
                'targets': "tb_number",
                'width': '70px'
            },
        ]
    };

    table_filters_custos = $('#table-filters-modal').DataTable(table_filters_pedidos_options);

    function recuperarNota(){
        $.ajax({
            url: '{{ route('valor_custo_nota_produto.modal.recupera_nota')}}',
            dataType: 'json',
            data: [
                '_token': '{{ csrf_token() }}',
                'estabelecimento': $(document).find('#estabelecimento_modal').val(),
                'pedido_numero': $(document).find('#pedido_numero_modal').val()
            ],
            method: 'POST',
            success: function(data){
                valores = [];
                for (var fields in data.response){
                    temp_array = [
                        data[fields].codigo,
                        data[fields].grupo,
                        data[fields].descricao,
                        "<input type='text' class='custo text-right'>"
                    ];
                    valores.push(temp_array)
                }

                table_filters_custos.rows.add(valores).draw();
            },
            error: function(data){
                hide_loader();
                if((data.responseJSON.errors)){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }else{
                    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
                }
            }
        });
    }
    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

</script>
@endsection