@extends('layouts.page-dialog')

@section('content')

<form action="#" onsubmit="return false" id='adiciona_item'>
    <div class='ml-3'>
        @csrf            
        <b>Estabelecimento:</b> {{ $estabelecimento }}<br>
        <b>Número do Pedido:</b> {{ $numero_pedido }}<br>
        <b>Fornecedor:</b> {{ $fornecedor }}<br>
    </div>
    
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped" id="table-filters-modal">
                <thead>
                    <tr>
                        <th class='tb_number'>Código</th>
                        <th>Grupo</th>
                        <th>Descrição</th>
                        <th>Custo unitário Gerencial</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itens as $item)
                    <tr>
                        <td>{{ $item['codigo_produto'] }}</td>
                        <td>{{ $item['grupo'] }}</td>
                        <td>{{ $item['descricao'] }}</td>
                        <td><input type="text" class="form-control form-control-sm custo text-right" value='{{ $item['custo'] }}' maxlength='9'></td>
                        <td><a href="#" class="bt-copy" data-toggle="tooltip" data-placement="top" title="Replicar este preço para todos os outros produtos" onclick="replicarPrecos(event);"></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="content-buttons">
        <button name="salvar" id="btn-salvar" class="btn btn-success float-right mt-2 mr-2">Gravar dados</button>
    </div>

</form>

<script>

    $(document).ready(function(){
        $(document).find('#valor').maskMoney({thousands:'', decimal:','});

        $(window).keydown(function(event){
            if(event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
        
        $(document).find('#btn-salvar').on('click', function(){
            salvar();
        });

        $(document).find('.custo').maskMoney({thousands:'.', decimal:','});

        setTimeout( function(){
            table_filters_custos.columns.adjust().draw();
            }, 500
        );
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
                'targets': "tb_number",
                'width': "10vw"
            },
            {
                'targets': 1,
                'width': "10vw"
            },
            {
                'targets': -2,
                'class': 'campo_digitavel',
                'width': '1px !important',
                "orderable": false,
                "width": "20vw"
            },
            {
                "targets": -1,
                "orderable": false,
                "width": "1vw"

            }
        ]
    };

    table_filters_custos = $('#table-filters-modal').DataTable(table_filters_custos_options);

    function showErrorsInputs(form, input, message){

        if(input.substring(0,6) == 'custos'){

            var campo = Number(input.split('.')[1]);
            var $input = $(document).find('#table-filters-modal').find('tr:eq('+campo+')').find('input');
        }
        else{
            var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function salvar(){

        var custos = [];

        form = $(document).find("#adiciona_item");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
        
        $(document).find('#table-filters-modal').find('tr').each( function(){

            if(! $(this).find('td:eq(0)').hasClass('dataTable_empty')){
                var custo = {
                    'codigo_produto':  $(this).find('td:eq(0)').html(),
                    'custo':  $(this).find('input').val()
                };

                custos.push(custo);
            }

        });

        if(custos.length > 0){
            $.ajax({
                url: '{{ route('valor_custo_nota_produto.editar_custo')}}',
                dataType: 'json',
                data: {
                    '_token': '{{ csrf_token() }}',
                    id:'{{ $id }}',
                    'estabelecimento': '{{ $estabelecimento_codigo }}',
                    'numero_pedido': '{{ $numero_pedido }}',
                    'custos': custos
                },
                method: 'POST',
                success: function(data){
                    $(document).find('#modal_novo').modal('hide');
                    filterAjax($("#form_filter").serialize());
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
        };
    }

    function replicarPrecos(event){

        var elemento = $(event.target);
        var valor = $(event.target).parent().parent().find('input').val();
        $('#table-filters-modal').find('input').val(valor);

    }

</script>
@endsection