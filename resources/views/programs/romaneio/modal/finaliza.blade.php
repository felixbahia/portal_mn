@extends('layouts.page-dialog')

@section('content')
<form name="form_finaliza_processo" id="form_finaliza_processo">
    @csrf
    <div class="row">
        <div class="col-sm">
            <h5>Finalizando processo o sistema fara os ajuste de estoque apontados na diferença e enviara para ação em compras confirma?</h5>
        </div>

    </div>
    <div class="row">
        <div class="col-sm">
            {{ Form::hidden('numero_nota', $dados['numero_nota'], ['numero_nota' => 'numero_nota']) }}
            {{ Form::hidden('ra_data', $dados['ra_data'], ['ra_data' => 'ra_data']) }}
            {{ Form::hidden('estabelecimento', $dados['estabelecimento'], ['estabelecimento' => 'estabelecimento']) }}
            <h4>Nota Fiscal {{  $dados['numero_nota'] }}  </h4>
        </div>
    </div>
    <div class="content-buttons float-right mt-3">
        {{ Form::button('Cancelar', ['id' => 'form_finaliza_cancelar_btn', 'class' => 'btn btn-primary text-right']) }}
        {{ Form::button('Confirma', ['id' => 'form_finaliza_processo_btn', 'class' => 'btn btn-danger']) }}
    </div>
</form>

<script>
    $(document).ready( function(){

        $(document).find('#form_finaliza_processo_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('romaneio.finaliza_processo') }}",
                dataType: 'json',
                method: 'POST',
                data: $(document).find('#form_finaliza_processo').serialize(),
                success: function(callback){
                    if(callback.status === 'success'){
                        $(document).find('#modal_finaliza_processo').modal('hide');
                       
                        
                     }
                },
                error: function(callback){
                    errors = callback.responseJSON.message;
                    for(var field in errors){
                        showErrorsInputs('#form_finaliza_processo', field, errors[field]);
                    }
                }
            })
        });
    });
    
    $(document).find("#form_finaliza_cancelar_btn").off("click");
	$(document).find("#form_finaliza_cancelar_btn").on("click", function(event){
        var $this = $(this);
        $($this).parents(".modal").modal("hide");
       
	});

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

</script>
@endsection
