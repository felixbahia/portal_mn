@extends('layouts.app-deslogado')

@section('content')
@if (!empty($dados['motivo']))
<div class="alert alert-danger" role="alert">
	{{$dados['motivo']}}</p> 
</div>
@endif
<div class="col-lg-12">			
    <div class='pedido_detalhes_content'>
        <div class="row">
            <div class="col-sm-12">
                <b>Cliente:</b><br>
                {{ $dados['cliente'] }}
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <b>Data:</b><br>
                {{ $dados['data'] }}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <b>Valor Total:</b><br>
                {{ $dados['valor_total'] }}
            </div>
            <div class="col-sm-4">
                <b>Juros por Mês:</b><br>
                {{ $dados['juros_mes'] }}
            </div>
            <div class="col-sm-4">
                <b>Valor da Renegociação:</b><br>
                {{ $dados['valor_total_juros'] }}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <b>Quantidade de Parcelas:</b><br>
                {{ $dados['quantidade_parcela'] }}
            </div>
            <div class="col-sm-4">
                <b>Período:</b><br>
                {{ $dados['periodo'] }}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <b>Titulos Renegociado:</b><br>
            </div>
        </div>
        <div class="row">
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                        <thead>
                            <tr>
                                <th>Estabelecimento</th>
                                <th>Título</th>
                                <th>Parcela</th>
                                <th>Data Emissão</th>
                                <th>Data Vencimento</th>
                                <th>Valor Original</th>
                                <th>Val. Reneg.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dados['titulos'] as $titulo)
                                <tr>
                                    <td>{{ $titulo['estabelecimento'] }}</td>
                                    <td>{{ $titulo['titulo'] }}</td>
                                    <td class="tb_number">{{ $titulo['parcela'] }}</td>
                                    <td class="tb_date">{{ $titulo['data_emissao'] }}</td>
                                    <td class="tb_date">{{ $titulo['data_vencimento'] }}</td>
                                    <td class="tb_number">{{ $titulo['valor_original'] }}</td>
                                    <td class="tb_number">{{ $titulo['total_valor_atualizado'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <b>Titulos Novos:</b><br>
                <b>Encargos:</b>{{ $dados['encargos'] }}<br>
            </div>
        </div>
        <div class="row">
            @if(count($parcelas) < 1) 
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                            <thead>
                                <tr>
                                    <th>Parcela</th>
                                    <th>Vencimento</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dados['datas'] as $parcela)
                                    <tr>
                                        <td class="tb_number">{{ $parcela['numero'] }}</td>
                                        <td class="tb_date">{{ $parcela['data'] }}</td>
                           
                                        <td class="tb_number">{{ $parcela['valor'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                        <thead>
                            <tr>
                                <th>Estabelecimento</th>
                                <th>Parcela</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($parcelas as $parcela)
                                <tr>
                                    <td>{{ $parcela['estabelecimento_codigo'] }}</td>
                                    <td class="tb_number">{{ $parcela['numero'] }}</td>
                                    <td class="tb_date">{{ $parcela['data_parcela'] }}</td>
                                    <td class="tb_number">{{ $parcela['valor'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<hr>

@if (empty($dados['motivo']))
<form action="" name="form_aprovacao_cliente" id="form_aprovacao_cliente"  onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $id, ['id' => 'id']) !!}
    {!! Form::hidden('tipo', 'previa', ['id' => 'tipo']) !!}
    <div class="center_content mt-1" style="position: relative;text-align: center;" id="button-center">
        {{ Form::submit('Aprovar', array('class' => 'btn btn-primary', 'id' => 'btn-aceito')) }}
        {{ Form::button('Recusar', array('class' => 'btn btn-danger', 'id' => 'btn-recuso')) }}
    </div> 

</form>
@endif
@endsection
@section('script-footer')
@if(empty($dados['motivo']))
$(document).ready( function () {
    form_modal = $(document).find("#form_aprovacao_cliente");

    form_modal.find("#btn-aceito").on('click', function(){
        aceitoContrato(form_modal);
    });

    form_modal.find("#btn-recuso").on('click', function(){
        modalRecusarRenegociacao(form_modal);
    });
});

function aceitoContrato(form_modal){
    data_form_modal = form_modal.serialize();
    $.ajax({
        url: "{{ route('aprovacao_renegociacao_titulo.aprovacao_cliente') }}", 
        dataType: 'json',
        data: data_form_modal,
        method: 'POST',
        async: false,
        success: function(callback){
            message("Atenção", "Prévia aprovada com sucesso!");
            window.location.reload(true);
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
            var dados = callback.responseJSON;
        }
    });
}

function modalRecusarRenegociacao(form_modal){
    data_form_modal = form_modal.serialize();
    $.ajax({
        url: '{{ route('aprovacao_renegociacao_titulo.modal.recusa_cliente') }}',
        type: 'POST',
        data: data_form_modal,
        success: function (body){
            createModal('recusa_cliente',  '', body, '');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}
@endif
@endsection