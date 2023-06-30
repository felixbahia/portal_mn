@extends('layouts.page-dialog')

@section('content')
<form name="form_delete_segmento" id="form_delete_segmento">
    <div class="row">
        <div class="col-sm">
            <h5>Deseja realmente apagar este segmento?</h5>
        </div>

    </div>
    <div class="row">
        <div class="col-sm">
            {{ Form::hidden('id', $segmento['id'], ['id' => 'id']) }}
            {{ $segmento['descricao'] }}
        </div>
    </div>
    <div class="content-buttons float-right mt-3">
        {{ Form::button('Cancelar', ['id' => 'form_delete_cancelar_btn', 'class' => 'btn btn-primary text-right']) }}
        {{ Form::button('Excluir', ['id' => 'form_delete_segmento_btn', 'class' => 'btn btn-danger']) }}
    </div>
</form>

<script>
    $(document).ready( function(){

        $(document).find('#form_delete_segmento_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('segmentos.excluir') }}",
                dataType: 'json',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: $(document).find("#id").val()
                },
                success: function(){
                    $(document).find('#modal-deletar-segmento').modal('hide');
                    buscarSegmentos();
                },
                error: function(callback){
                    errors = callback.responseJSON.message;

                    for(var field in errors){
                        showErrorsInputs('#form_delete_segmento', field, errors[field]);
                    }
                }
            })
        });
    });
    
    $(document).find("#form_delete_cancelar_btn").off("click");
	$(document).find("#form_delete_cancelar_btn").on("click", function(event){
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
