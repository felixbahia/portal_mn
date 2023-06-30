@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('banco_contabil.store') }}" id="frm_cad" name="frm_cad" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('estabel', 'Estábelecimento') }}
        {!! Form::select("estabel", returnEmpresasPrologusView(), "", ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('codbco', 'Código banco') }}
        {{ Form::text('codbco', '', array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('contactb', 'Conta Contábil') }}
        {{ Form::text('contactb', '', array('class' => 'form-control')) }}
    </div>

    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}

</form>
@endsection