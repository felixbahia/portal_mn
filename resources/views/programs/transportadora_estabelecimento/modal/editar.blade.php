@extends('layouts.page-dialog')

@section('content')
<form id="form_edit_transportadora_estabelecimento">
    @csrf
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('transportadora_codigo', 'Transportador', []) }}
                        <div class="input-group">
                            {{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
                            {{ Form::hidden('transportadora_codigo', $dados['transportadora_codigo'], ['id' => 'transportadora_codigo']) }}

                            {{ Form::text('transportadora_nome',  $dados['transportadora_nome'], ['id' => 'transportador', 'class' => 'form-control input-label', 'placeholder' => 'Transportador']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-transportadora-busca" data-route="{{ route("transportador.index.dialog") }}"><i class="bt-view m-2"></i></span>
                        </div>
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('label_estabelecimento', 'Estabelecimento', []) }}
                    {{ Form::select("estabelecimento",$estabelecimentos, $dados['estabelecimento'], ["id" => "estabelecimento", "class"=>"form-control"]) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('label_frete', 'Frete', []) }}
                    {{ Form::select("tipo_frete", $tipo_fretes, $dados['tipo_frete'], ["id" => "tipo_frete", "class"=>"form-control"]) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('label_origem', 'Origem', []) }}
                    {{ Form::select("uf_origem",  $uf_origens, $dados['uf_origem'], ["id" => "uf_origem", "class"=>"form-control"]) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('label_destino', 'Destino', []) }}
                    {{ Form::select("uf_destino", $uf_destinos, $dados['uf_destino'], ["id" => "uf_destino", "class"=>"form-control"]) }}
                </div>


            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Salvar', ['id' => 'form_edit_transportadora_estabelecimento_btn', 'class' => 'btn btn-primary']) }}
    </div>
</form>

<script>
    $(document).ready( function(){

        $(document).find("#form_edit_transportadora_estabelecimento").find("#transportador").autocomplete(optionsAutoCompleteTransportadora("transportador"));
        form_modal_edit = $(document).find('#form_edit_transportadora_estabelecimento');
        $(document).find('#form_edit_transportadora_estabelecimento_btn').on('click', function(event){
            event.stopPropagation();
    
            atualizaDados(form_modal_edit.serialize());
        });


        form_modal_edit.find("#bt-search-transportadora-busca").off('click');
        form_modal_edit.find("#bt-search-transportadora-busca").on('click', function(){
          
            modalTransportador();
        });

    });

    function atualizaDados(data_form_modal_edit){
        form_modal_edit = $(document).find('#form_edit_transportadora_estabelecimento');
        var formData = new FormData($(document).find('#form_edit_transportadora_estabelecimento')[0]);
        $.ajax({
            url: "{{ route('transportadora_estabelecimento.editar') }}", 
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
                    showErrorsInputsEdit('#form_edit_transportadora_estabelecimento', field, errors[field]);
                }
            }
        });
    }

    function limparMesagemErroEdit(){      
        var form_modal_edit = $("#form_edit_transportadora_estabelecimento");
        form_modal_edit.find('.error-message').remove();
        form_modal_edit.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsEdit(form, input, message){
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
							returnDadosTransportador($(this), modal);
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
