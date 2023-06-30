@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_proposta_link" id="form_proposta_link" onsubmit="return false;">
    <div class="col-sm-12">
        Proposta gerada, segue o link abaixo.
        <div class="input-group">
            <input type="text" id="link_proposta" name="link_proposta" value="{{ $link_proposta }}" class="form-control">
            <div class="input-group-append">
                <span class="input-group-text" id="basic-addon2"><a href="#" onclick="copiarlinkproposta()" class="bt-duplicar" data-toggle="tooltip" data-html="true" title="Copiar Link "></a></span>
            </div>
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::submit('OK', array('class' => 'btn btn-primary float-right', 'id' => 'btn-aceito')) }}
    </div> 
</form>
<script>
    $(document).ready(function(){
        $(document).find("#btn-aceito").off('click');
        $(document).find("#btn-aceito").on('click', function(){
            aceitar();
        });
    });

    function aceitar(){
        copiarlinkproposta();
        $(document).find('#modal_proposta_link').modal('hide');
    }
    function copiarlinkproposta() { 
		var textoCopiado = document.getElementById("link_proposta");
		textoCopiado.select();
		document.execCommand("Copy");
	}
</script>
@endsection