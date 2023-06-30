@extends('layouts.page-dialog')

@section('content')

<div class="container">
 <form action="" name="form_sugestao_compra_edit" id="form_sugestao_compra_edit" onsubmit="return false;">
        @csrf
     <div class="form-row">
             <div class="form-group col-sm-12"> 
                     {{ Form::label('cliente', 'Cliente', []) }}
                    <div class="input-group">
                        {{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
                        {{ Form::hidden('cliente_codigo', $dados['cliente_codigo'], ['id' => 'cliente_codigo']) }}
                        {{ Form::hidden('produto_codigo', $dados['produto_codigo'], ['id' => 'produto_codigo']) }}
    
                        {{ Form::text('cliente_nome',  $dados['cliente_nome'], ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Cliente', 'maxlength' => '255']) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                        
                    </div>

             <div class="input-group">
                    <div class="form-group col-sm-12"> 
                            {{ Form::label('produto', 'Produto', []) }}
                                <div class="input-group">
                                    {!! Form::text('produto_descricao', $dados['produto_descricao'], ['id' => 'produto_descricao', 'placeholder' => 'Produto', 'class' => 'form-control input-label']) !!}
                                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                                    
                                </div>
                    </div>
                    <div class="form-group col-sm-12"> 
                        {{ Form::label('composicao', 'Composição', []) }}
                       <div class="input-group">
                           {!! Form::text('composicao',  $dados['composicao'], ['id' => 'composicao', 'placeholder' => 'Composição do Produto', 'class' => 'form-control input-label']) !!}
                          
                       </div>
    
                   </div>
            </div>

            <div class="form-group col-sm-12"> 
                        {{ Form::label('volume_produto_lbl', 'Volume', []) }}
                        <div class="input-group">
                            {{ Form::text('volume_produto', $dados['volume_produto'], ['id' => 'volume_produto', 'class' => 'form-control text-right', 'placeholder' => 'Volume dos Produtos', 'maxlength' => '10']) }}
                        </div>
                    </div>
                <div class="form-group col-sm-12"> 
                    {{ Form::label('valor_estimado_venda_lbl', 'Preço', []) }}
                    <div class="input-group">
                        {{ Form::text('valor_estimado_venda', $dados['valor_estimado_venda'], ['id' => 'valor_estimado_venda', 'class' => 'form-control text-right', 'placeholder' => 'Valor Unitário Estimado de Venda', 'maxlength' => '10']) }}
                    </div>
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('arquivo_foto_sugestao', 'Foto (Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo_foto_sugestao[]', ['id'=>'arquivo_foto_sugestao', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;", 'multiple'], null) !!}
                   
               </div>
   
    
    <div class="col-sm-12 mt-5">
        {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
</div> 
<script>
    $(document).ready( function (event) {
        $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente('cliente_nome'));
        $(document).find("#produto_descricao").autocomplete(optionsAutoCompleteProduto('produto_descricao'));
   
        form_modal_edit = $(document).find('#form_sugestao_compra_edit');
        $(document).find('#btn-salvar').on('click', function(event){
            event.stopPropagation();
    
            atualizaDados(form_modal_edit.serialize());
        });
        $('#volume_produto').maskMoney({thousands:'', decimal:',', precision:3});
        $('#valor_estimado_venda').maskMoney({thousands:'.', decimal:','});
        form_modal_edit.find("#bt-search-produto").off('click');
        form_modal_edit.find("#bt-search-produto").on('click', function(){
       
            showModalProduto(form_modal_edit,"Lista Produtos");
        });
        form_modal_edit.find("#bt-bt-search-cliente-busca").off('click');
        form_modal_edit.find("#bt-search-cliente-busca").on('click', function(){
            showModalCliente(form_modal_edit,"Lista Clientes");
        });
    });

    function atualizaDados(data_form_modal_edit){
        form_modal_edit = $(document).find('#form_sugestao_compra_edit');
        var formData = new FormData($(document).find('#form_sugestao_compra_edit')[0]);
        $.ajax({
            url: "{{ route('sugestao_compra.editar') }}", 
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
        
        if(input == 'arquivo_foto_sugestao'){
            var $input = $(form).find("#"+input);
            $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.parent().children().addClass('error-input');

        }else{
            var $input = $(form).find("input[name='"+input+"']");
            $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.parent().children().addClass('error-input');
        }
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

  
    function optionsAutoCompleteCliente($name){
     
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('clientes.autocomplete') }}", request, response);
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
    
    function showModalCliente(form_modal){
        $.ajax({
            url: '{{ route('cliente.index.dialogCadastro') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("cliente_searsh_show", "Buscar cliente", data, 'modal-lg');
                table_dialog.on('draw', function () {

                    $(document).find("#cliente_searsh_show").find('tbody').find("tr").off("click");
                    $(document).find("#cliente_searsh_show").find('tbody').find("tr").on("click", function(){
         
                        returnDadosCliente($(this), form_modal_edit);
                    });
    
                });
            }
        });
    }
    
    function returnDadosCliente($dados, form){
     
        if($dados.find("td").eq(0).hasClass("dataTables_empty")){
            return false;
        }
        $(document).find("#cliente_nome").val($dados.find("td").eq(1).text());
        $(document).find("#cliente_codigo").val($dados.find("td").eq(3).text());
        if($(document).find('#cliente_codigo').val()){
            $(document).find('#cliente_codigo').prop('readonly', true);
            $(document).find('#cliente_nome').prop('readonly', true);
        }
        $(document).find("#cliente_searsh_show").modal("hide");
    }


    function optionsAutoCompleteCliente($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
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
                $(document).find("#" + $elemento).val(ui.item.label);
                $(document).find("#cliente_codigo").val(ui.item.value);
                return false;
            }
        };
    }

    function optionsAutoCompleteProduto($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('produtos_nasajon.autocomplete') }}", request, response);
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
                $(document).find("#" + $elemento).val(ui.item.label);
                $(document).find("#produto_codigo").val(ui.item.value);
                return false;
            }
        };
    }
</script>
@endsection