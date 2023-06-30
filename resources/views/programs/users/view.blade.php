@extends('layouts.page-dialog')

@section('content')
<form action="#" id="frm_cad" name="frm_cad" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('name', 'Nome Completo') }}
            {{ Form::text('name', $dados->name, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>

        <div class="form-group col-lg-4">
            {{ Form::label('username', 'Usuário') }}
            {{ Form::text('username', $dados->username, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>

        <div class="form-group col-lg-4">
            {{ Form::label('email', 'Email') }}
            {{ Form::email('email',  $dados->email, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('celular', 'Telefone Celular') }}
            {{ Form::text('celular', $dados->celular, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('tipo_usuario_id', 'Tipo de usuário') }}
            {{ Form::select('tipo_usuario_id', $tipos, $dados->tipo_usuario_id, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('setor', 'Setor') }}
            {{ Form::text('setor', $dados->setor, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('regiao_atuacao', 'Região de atuação') }}
            {{ Form::text('regiao_atuacao', $dados->regiao_atuacao, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>
        <div class="form-group col-lg-3">
            {{ Form::label('empresa_padrao_id', 'Empresa Padrão') }}
            {!! Form::select("empresa_padrao_id", returnEmpresasNasajonView(), $dados->empresa_padrao_id, ["class"=>"form-control", 'readonly' => 'readonly', 'disabled' => 'disabled']) !!}
        </div>

        <div class="form-group col-lg-3">
            {{ Form::label('cliente_padrao_id', 'Cliente Padrão') }}
            {{ Form::text('cliente_padrao_id', $dados->cliente_padrao_id, array('class' => 'form-control', 'readonly' => 'readonly', 'disabled' => 'disabled')) }}
        </div>
    </div>
</form>
@endsection