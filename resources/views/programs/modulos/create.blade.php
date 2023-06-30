@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('modulos.store') }}" id="frm_cad" name="frm_cad" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('nome', 'Nome') }}
        {{ Form::text('nome', '', array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('url', 'URL') }}
        {{ Form::text('url', '', array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('nome', 'Ícone') }}
        {!! Form::file('icon', ['id'=>'icon', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) !!}
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}

</form>
@endsection