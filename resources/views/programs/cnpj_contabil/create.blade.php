@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('fornecedor_contabil.store') }}" id="frm_cad" name="frm_cad" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('estabel', 'Estábelecimento') }}
        {!! Form::select("estabel", returnEmpresasPrologusView(), "", ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('codcad', 'Código') }}
        {{ Form::text('codcad', '', array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('codcad_nome', 'Nome') }}
        {{ Form::text('codcad_nome', '', array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
    </div>
    <div class="form-group">
        {{ Form::label('conta_contabil', 'Conta Contábil') }}
        {{ Form::text('conta_contabil', '', array('class' => 'form-control')) }}
    </div>

    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}

</form>
@endsection