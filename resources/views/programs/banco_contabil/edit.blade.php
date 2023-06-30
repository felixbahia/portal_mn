@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('banco_contabil.update', ['estabel' => $dados["estabel"], 'codbco' => $dados["codbco"], 'contactb' => $dados["contactb"]]) }}" id="frm_edit" name="frm_edit" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('estabel', 'Estábelecimento') }}
        {!! Form::select("estabel", returnEmpresasPrologusView(), $dados["estabel"], ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('codbco', 'Código banco') }}
        {{ Form::text('codbco', $dados["codbco"], array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('contactb', 'Conta Contábil') }}
        {{ Form::text('contactb', $dados["contactb"], array('class' => 'form-control')) }}
    </div>

    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
@endsection