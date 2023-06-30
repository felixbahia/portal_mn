@extends('layouts.page-dialog')

@section('content')
<form action="{{ route('usuario.update', ['id'=> $dados->id]) }}" id="frm_cad" name="frm_cad" onsubmit="return false;">
    @csrf
    {{ Form::hidden('id', $dados->id)}}

    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('name', 'Nome Completo') }}
            {{ Form::text('name', $dados->name, array('class' => 'form-control')) }}
        </div>

        <div class="form-group col-lg-4">
            {{ Form::label('username', 'Usuário') }}
            {{ Form::text('username', $dados->username, array('class' => 'form-control')) }}
        </div>

        <div class="form-group col-lg-4">
            {{ Form::label('email', 'Email') }}
            {{ Form::email('email',  $dados->email, array('class' => 'form-control')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('celular', 'Telefone Celular') }}
            {{ Form::text('celular', $dados->celular, array('class' => 'form-control')) }}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('tipo_usuario_id', 'Tipo de usuário') }}
            {{ Form::select('tipo_usuario_id', $tipos, $dados->tipo_usuario_id, array('class' => 'form-control')) }}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('setor', 'Setor') }}
            {{ Form::text('setor', $dados->setor, array('class' => 'form-control')) }}
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('regiao_atuacao', 'Região de atuação') }}
            {{ Form::text('regiao_atuacao', $dados->regiao_atuacao, array('class' => 'form-control')) }}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('empresa_padrao_id', 'Empresa Padrão') }}
            {!! Form::select("empresa_padrao_id", returnEmpresasNasajonView(), $dados->empresa_padrao_id, ["class"=>"form-control"]) !!}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('cliente_padrao_id', 'Cliente Padrão') }}
            {{ Form::text('cliente_padrao_id', $dados->cliente_padrao_id, array('class' => 'form-control')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('role', 'Perfil de acesso') }}
            {!! Form::select("role", $roles, $dados->role_id, ["class"=>"form-control"]) !!}
        </div>
        <div class="form-group col-lg-4 {{ (count($responsaveis) === 1 ? "display_none" : "") }}">
            {{ Form::label('responsavel', 'Responsável') }}
            {!! Form::select("responsavel", $responsaveis, $dados->responsavel, ["class"=>"form-control"]) !!}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('codigo_representante', 'Código representante') }}
            {!! Form::select("codigo_representante", $representantes, $dados->codigo_representante, ["class"=>"form-control"]) !!}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('comissao_a', 'Comissão A') }}
            {{ Form::text('comissao_a', $dados->comissao_a, array('class' => 'form-control')) }}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('comissao_b', 'Comissão B') }}
            {{ Form::text('comissao_b', $dados->comissao_b, array('class' => 'form-control')) }}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('comissao_c', 'Comissão Equipe') }}
            {{ Form::text('comissao_c', $dados->comissao_c, array('class' => 'form-control')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-4">
            {{ Form::label('codigo_nasajon', 'Usuário Nasajon') }}
            {!! Form::select("codigo_nasajon", $usuarios_nasajon, $dados->codigo_nasajon, ["class"=>"form-control"]) !!}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('folha_matricula', 'Matrícula da Folha') }}
            {!! Form::text("folha_matricula", $dados->folha_matricula, ["class"=>"form-control"]) !!}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('folha_lotacao', 'Instituição') }}
            {!! Form::select("folha_lotacao", $instituicoes, $dados->folha_lotacao, ["class"=>"form-control", 'placeholder' => 'Selecione a Instituição']) !!}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('acrescimo_objetivo', 'Acréscimo por atingimento objetivo %') }}
            {!! Form::text("acrescimo_objetivo", parserQtd($dados->acrescimo_objetivo), ['class' => 'acrescimo_objetivo form-control']) !!}
        </div>
        <div class="form-group col-lg-4">
            {{ Form::label('supervisores', 'Supervisor') }}
            {!! Form::select("supervisores", $supervisores, $supervisor_atual, ["class"=>"form-control", 'placeholder' => 'Selecione o Supervisor']) !!}
        </div>
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
<script>
    $(document).ready(function(){
        $(document).find(".acrescimo_objetivo").maskMoney({thousands:'', decimal:','});
    });
</script>
@endsection