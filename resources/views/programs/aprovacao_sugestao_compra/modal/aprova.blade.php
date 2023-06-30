@extends('layouts.page-dialog')

@section('content')

<div class="container">
 <form action="" name="form_sugestao_compra_edit" id="form_sugestao_compra_edit" onsubmit="return false;">
        @csrf
     <div class="form-row">
             <div class="form-group col-sm-12"> 
                     {{ Form::label('motivo', 'Observação', []) }}
                    <div class="input-group">
                        {{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
                       
                       {{ Form::text('motivo', $dados['motivo'], ['id' => 'motivo_edit', 'class' => 'form-control input-label', 'placeholder' => 'Observação', 'maxlength' => '255']) }}
                                              
                    </div>
             <div class="input-group">
                    <div class="form-group col-sm-12"> 
                            {{ Form::label('produto', 'Código', []) }}
                                <div class="input-group">
                                    {!! Form::text('produto_codigo', $dados['produto_codigo'], ['id' => 'produto_codigo', 'placeholder' => 'Código', 'class' => 'form-control input-label']) !!}
                                    
                                </div>

                                {{ Form::label('produto', 'Descrição Produto', []) }}
                                <div class="input-group">
                                    
                                    {!! Form::text('produto_descricao', $dados['produto_descricao'], ['id' => 'produto_descricao', 'placeholder' => 'Descrição', 'class' => 'form-control input-label']) !!}
                                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                                    
                                </div>
                    </div>
            </div>

   
    <div class="col-sm-12 mt-5">
        {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
</div> 
<script>
    $(document).ready( function (event) {

        $(document).find("#produto_descricao").autocomplete(optionsAutoCompleteProduto('produto_descricao'));
        $(document).find("#produto_codigo").autocomplete(optionsAutoCompleteProduto('produto_codigo'));
        form_modal_edit = $(document).find('#form_sugestao_compra_edit');
        $(document).find('#btn-salvar').on('click', function(event){
            event.stopPropagation();
    
            atualizaDados(form_modal_edit.serialize());
        });
        form_modal_edit.find("#bt-search-produto").off('click');
        form_modal_edit.find("#bt-search-produto").on('click', function(){
       
            showModalProduto(form_modal_edit,"Lista Produtos");
        });
    });

    function atualizaDados(data_form_modal_edit){
        form_modal_edit = $(document).find('#form_sugestao_compra_edit');
        var formData = new FormData($(document).find('#form_sugestao_compra_edit')[0]);
        $.ajax({
            url: "{{ route('aprovacao_sugestao_compra.aprovar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
       
                    $(form_modal_edit).parents('.modal').modal('hide');
                    filtro();
                
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    limparMesagemErroEdit();
                    showErrorsInputsEdit('#form_sugestao_compra_edit', field, errors[field]);
                }
            }
        });
    }

    function limparMesagemErroEdit(){    
      
        var form_modal_edit = $("#form_sugestao_compra_edit");
        form_modal_edit.find('.error-message').remove();
        form_modal_edit.find('input, select, span').removeClass('error-input');
    }
    function showErrorsInputsEdit(form, input, message){
        
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

    function optionsAutoCompleteProduto($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }
    
    function showModalProduto(form_modal){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
    
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProduto($(this), form_modal_edit);
                    });
    
                });
            }
        });
    }
    
    function returnDadosProduto($dados, form){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
      
        form.find('#produto_codigo').val($dados.find("td").eq(1).text());
   
        form.find('#produto_descricao').val($dados.find("td").eq(2).text());
        if(form.find('#produto_codigo').val()){
            form.find('#produto_codigo').prop('readonly', true);
            form.find('#produto_descricao').prop('readonly', true);
        }
    }

  
    function optionsAutoCompleteProduto($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.condicao = 'todos';
                $.post("{{ route('produto.autocompletelimitacao') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                  
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#produto_descricao").val(ui.item.label);
                $(document).find("#produto_codigo").val(ui.item.value);
                return false;
            }
        };
    }
</script>
@endsection