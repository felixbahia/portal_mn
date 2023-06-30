@extends('layouts.page-dialog')

@section('content')
    <form action="" name="form_add_transportadora_estabelecimento" id="form_add_transportadora_estabelecimento" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('transportadoras', 'Transportador', []) }}
                        <div class="input-group">
                            {{ Form::hidden('transportadora_codigo','', ['id' => 'transportadora_codigo']) }}

                            {{ Form::text('transportadora_nome', '', ['id' => 'transportador', 'class' => 'form-control input-label', 'placeholder' => 'Transportador']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-transportadora-busca" data-route="{{ route("transportador.index.dialog") }}"><i class="bt-view m-2"></i></span>
                        </div>
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('Estabelecimento', 'Estabelecimento', []) }}
                    {{ Form::select("estabelecimento", $estabelecimentos,'Todos', ["id" => "estabelecimento", "class"=>"form-control"]) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('Frete', 'Frete', []) }}
                    {{ Form::select("tipo_frete", $tipo_fretes, 'Todos', ["id" => "tipo_frete", "class"=>"form-control"]) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('Origem', 'Origem', []) }}
                    {{ Form::select("uf_origem", $uf_origens, 'Todos', ["id" => "uf_origem", "class"=>"form-control"]) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('Destino', 'Destino', []) }}
                    {{ Form::select("uf_destino", $uf_destinos, 'Todos', ["id" => "uf_destino", "class"=>"form-control"]) }}
                </div>
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Cadastrar', ['id' => 'form_add_transportadora_estabelecimento_btn', 'class' => 'btn btn-success']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
  
        $(document).find("#form_add_transportadora_estabelecimento").find("#transportador").autocomplete(optionsAutoCompleteTransportadora("transportador"));
   
        form_modal_add = $(document).find('#form_add_transportadora_estabelecimento');
 
        form_modal_add.find("#bt-search-transportadora-busca").off('click');
        form_modal_add.find("#bt-search-transportadora-busca").on('click', function(){
          
            modalTransportador();
        });

        form_modal_add.find('#form_add_transportadora_estabelecimento_btn').on('click', function(event){
            event.stopPropagation();
            inserirDados(form_modal_add.serialize());
  
        });

    });

    function inserirDados(data_form_modal_add){
        form_modal_add = $(document).find('#form_add_transportadora_estabelecimento');
        var formData = new FormData($(document).find('#form_add_transportadora_estabelecimento')[0]);

        $.ajax({
            url: "{{ route('transportadora_estabelecimento.salvar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_add).parents('.modal').modal('hide');
                filtro();
            },
            error: function(callback){
                errors = callback.responseJSON.error;
                    for(var field in errors){

                     
                        limparMesagemErroAdd();
                  
                        showErrorsInputsAdd('#form_add_transportadora_estabelecimento', field, errors[field]);
                    }
            }
        });
    }
    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_add_transportadora_estabelecimento");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsAdd(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

  	
    function modalTransportador(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}'},
            success: function(data){
				$(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
							returnDadosTransportador($(this),modal);
                        });
                    });
                });
            }

        });
    }
       function returnDadosTransportador($dados, modal){
            if($dados.find("td").eq(0).hasClass('dataTables_empty')){
                return false;
            }
       
            $(document).find("#transportadora_codigo").val($dados.find("td:eq(0)").text());
            $(document).find("#transportador").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
            modal.modal('hide');
            
            
      }
    function optionsAutoCompleteTransportadora($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('transportador.autocomplete') }}", request, response);
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
                $(document).find("#transportadora_codigo").val(ui.item.value);
                return false;
            }
        };
    }

  

</script>
@endsection
