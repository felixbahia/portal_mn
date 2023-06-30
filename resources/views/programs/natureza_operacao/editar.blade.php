@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('natureza_operacao.edit') }}" id="frm_cad" name="frm_cad" onsubmit="return false;">
    @csrf
    {{ Form::hidden('id', $natureza_operacao->id) }}
    <div class="form-group">
        {{ Form::label('estabelecimento', 'Estabelecimento') }}
        {!! Form::select("estabelecimento", returnEmpresasNasajonView(), $natureza_operacao->estabelecimento, ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('estado_destino', 'Estado de destino') }}
        {{ Form::select('estado_destino', $estados, $natureza_operacao->estado_destino, array('class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('nat_op_pj', 'Natureza de Operação - Pessoa Jurídica') }}
        {{ Form::text('nat_op_pj', $natureza_operacao->nat_op_pj, array('id' => 'nat_op_pf', 'class' => 'form-control')) }}
    </div>
    <div class="form-group">
        {{ Form::label('nat_op_pf', 'Natureza de Operação - Pessoa Física') }}
        {{ Form::text('nat_op_pf', $natureza_operacao->nat_op_pf, array('id' => 'nat_op_pf', 'class' => 'form-control')) }}
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
@endsection


