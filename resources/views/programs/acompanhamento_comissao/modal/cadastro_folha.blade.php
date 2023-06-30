@extends('layouts.page-dialog')

@section('content')
<form action="#" name="form_cadatro_folha_em_massa" id="form_cadatro_folha_em_massa" onsubmit="return false;">
    @csrf
    <table class="table table-striped">
        <thead>
            <th>Vendedor</th>
            <th>Matrícula da Folha</th>
            <th>Instituição</th>
        </thead>
        <tbody>
            @foreach($usuarios as $usuario)
            <tr>
                <td>{{ $usuario["nome"] }}</td>
                <td>
                    {!! Form::text("folha_matricula-".$usuario["id"], '', ["class"=>"form-control", 'id' => "folha_matricula-".$usuario["id"]]) !!}
                </td>
                <td>
                    {!! Form::select("folha_lotacao-".$usuario["id"], $instituicoes, '', ["class"=>"form-control", 'placeholder' => 'Selecione a Instituição', 'id' => "folha_lotacao-".$usuario["id"]]) !!}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
        </tfoot>
    </table>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Gerar Folha', array('class' => 'btn btn-success float-right', 'id' => 'btn-create-gerar_folha', 'name' => "btn-create")) }}
        {{ Form::button('Salvar Castrado', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar', 'style' => "margin-right: 5px;")) }}   
    </div> 
</form>
<script>
    array_usuarios = [];
    @foreach($usuarios as $usuario)
        array_usuarios.push("{{ $usuario['id'] }}");
    @endforeach

    $(document).ready( function () {
        form_modal = $(document).find('#form_cadatro_folha_em_massa');
        
        form_modal.find("#btn-salvar").off('click');
        form_modal.find("#btn-salvar").on('click', function(){
            salvarUsuarios(form_modal);
        });

        form_modal.find("#btn-create-gerar_folha").off("click");
        form_modal.find("#btn-create-gerar_folha").on("click", function(){
            gerarExportacao(mes_ano);
        });
    });

    function salvarUsuarios(form_modal){
        var_usuarios = [];

        array_usuarios.forEach(function imprimir(item){
            var dados = [];
            dados.push(item);
            dados.push(form_modal.find("#folha_matricula-"+item).val());
            dados.push(form_modal.find("#folha_lotacao-"+item).val());
            var_usuarios.push(dados);
        });

        $.ajax({
            url: "{{ route('acompanhamento_comissao.atualizar_cadastro_user_folha') }}", 
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                array_usuarios: var_usuarios,
            },
            method: 'POST',
            async: false,
            success: function(callback){
                message("Atenção", "Atualizado com sucesso!");
            },
            error: function(callback){

            }
        });
    }
</script>
@endsection