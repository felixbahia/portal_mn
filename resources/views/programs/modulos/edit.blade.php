@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('modulos.update', ['id' => $dados["id"]]) }}" id="frm_edit" name="frm_edit" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('nome', 'Nome') }}
        {{ Form::text('nome', $dados["nome"], array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('url', 'URL') }}
        {{ Form::text('url', $dados["url"], array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('nome', 'Ícone') }}
        <div class="img-edit-model">
            <img src="{{ asset($Imagem) }}" border="0" alt="" />
        </div>
        {{ Form::hidden('icon_temp', $dados["icon"]) }}
        {{ Form::file('icon', ['id'=>'icon', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) }}
    </div>

    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
@endsection