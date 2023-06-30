@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('fornecedor_contabil.update', ['codcad' => $dados["codcad"], 'estabel'=>$dados["estabel"]]) }}" id="frm_edit" name="frm_edit" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('estabel', 'Estábelecimento') }}
        {!! Form::select("estabel", returnEmpresasPrologusView(), $dados["estabel"], ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('codcad', 'Código') }}
        {{ Form::text('codcad', $dados["codcad"], array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('codcad_nome', 'Nome') }}
        {{ Form::text('codcad_nome', $dados["nome"], array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
    </div>
    <div class="form-group">
        {{ Form::label('contactb', 'Conta Contábil') }}
        {{ Form::text('contactb', $dados["contactb"], array('class' => 'form-control')) }}
    </div>

    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
@endsection