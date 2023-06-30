@extends('layouts.page-dialog')

@section('content')

<form action="#" onsubmit="return false" id='adiciona_item'>
    <div class="content-filter-dialog">	
        <div>
            @csrf            
            <div class="content-fields">
                <div class="col-sm-3">
                    {{ Form::label('estabelecimento_modal', 'Estabelecimento', []) }}
                    {{ Form::select('estabelecimento', returnEmpresasNasajonView(), '', ['id' => 'estabelecimento_modal', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}       
                    {{ Form::hidden('estabelecimento_validado', '', ['id' => 'estabelecimento_validado']) }}
                </div>
                <div class="col-sm-2">
                    {{ Form::label('numero_pedido_modal', 'Número do Pedido', []) }}
                    {{ Form::text('numero_pedido', '', ['id' => 'numero_pedido_modal', 'class' => 'form-control', 'placeholder' => 'Número do pedido']) }}       
                    {{ Form::hidden('numero_pedido_validado', '', ['id' => 'numero_pedido_validado']) }}

                </div>
                <div class="col-sm-2">
                    <br>
                    <button name="buscar" id="btn-buscar" class="btn btn-primary btn-sm mt-1">Buscar</button>
                </div>
                <div class="col-sm-12 mt-2 ml-1 d-none">
                    <b>Fornecedor:</b> <span id='fornecedor_modal'></span>
                </div>
            </div>
        </div>
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

        $(document).find('#btn-buscar').on('click', function(){
            table_filters_custos.clear().draw();

            $(document).find('#estabelecimento_validado').val('');
            $(document).find('#numero_pedido_validado').val('');


            if($(document).find('#estabelecimento_modal').val().length  > 0 && $(document).find('#numero_pedido_modal').val().length > 0){
                recuperarNota();
            }
            
        })

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

    function recuperarNota(){

        form = $(document).find("#adiciona_item");
        $(document).find('#fornecedor_modal').parent().addClass('d-none');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route('valor_custo_nota_produto.modal.recupera_nota')}}',
            dataType: 'json',
            data: {
                '_token': '{{ csrf_token() }}',
                'estabelecimento': $(document).find('#estabelecimento_modal').val(),
                'numero_pedido': $(document).find('#numero_pedido_modal').val()
            },
            method: 'POST',
            success: function(data){
                valores = [];
                itens = data.response.itens;
                for (var fields in itens){
                    temp_array = [
                        itens[fields].codigo,
                        itens[fields].grupo,
                        itens[fields].descricao,
                        "<input type='text' class='form-control form-control-sm custo text-right' maxlength='9'>",
                        createBtnReplicar(),
                    ];
                    valores.push(temp_array)
                }

                $(document).find('#estabelecimento_validado').val(data.response.estabelecimento);
                $(document).find('#numero_pedido_validado').val(data.response.numero_pedido);

                table_filters_custos.rows.add(valores).columns.adjust().draw();
                table_filters_custos.columns.adjust().draw();
                $(document).find('.custo').maskMoney({thousands:'.', decimal:','});
                $(document).find('#fornecedor_modal').html(data.response.fornecedor)
                $(document).find('#fornecedor_modal').parent().removeClass('d-none');
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

            if(! $(this).find('td:eq(0)').hasClass('dataTables_empty')){
                var custo = {
                    'cod_produto':  $(this).find('td:eq(0)').html(),
                    'custo':  $(this).find('input').val()
                };

                custos.push(custo);
            }

        });

        if(custos.length > 0){
            $.ajax({
                url: '{{ route('valor_custo_nota_produto.novo_custo')}}',
                dataType: 'json',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'estabelecimento': $(document).find('#estabelecimento_validado').val(),
                    'numero_pedido': $(document).find('#numero_pedido_validado').val(),
                    'custos': custos
                },
                method: 'POST',
                success: function(data){
                    filterAjax($("#form_filter").serialize());
                    $(document).find('#modal_novo').modal('hide');
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

    function createBtnReplicar(){
        var $html = "<a href=\"#\" class=\"bt-copy\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Replicar este preço para todos os outros produtos\" onclick=\"replicarPrecos(event);\"></a>";

        return $html;
    }

    function replicarPrecos(event){

        var elemento = $(event.target);
        var valor = $(event.target).parent().parent().find('input').val();
        $('#table-filters-modal').find('input').val(valor);

    }

</script>
@endsection