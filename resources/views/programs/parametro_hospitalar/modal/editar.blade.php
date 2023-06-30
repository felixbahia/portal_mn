@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_parametro_hospitalar" id="form_parametro_hospitalar" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id_grande_sp', $dados['GRANDE SP']['id'], ['id' => 'id_grande_sp']) !!}
    @if(empty($dados ['GRANDE RJ']))
    {!! Form::hidden('id_grande_rj', '', ['id' => 'id_grande_rj']) !!}
    @else
    {!! Form::hidden('id_grande_rj', $dados['GRANDE RJ']['id'], ['id' => 'id_grande_rj']) !!}
    @endif
    {!! Form::hidden('id_sudeste', $dados['SUDESTE']['id'], ['id' => 'id_sudeste']) !!}
    {!! Form::hidden('id_sul', $dados['SUL']['id'], ['id' => 'id_sul']) !!}
    {!! Form::hidden('id_centro_oeste', $dados['CENTRO OESTE']['id'], ['id' => 'id_centro_oeste']) !!}
    {!! Form::hidden('id_nordeste', $dados['NORDESTE']['id'], ['id' => 'id_nordeste']) !!}
    {!! Form::hidden('id_norte', $dados['NORTE']['id'], ['id' => 'id_norte']) !!}
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id='parametro-hospitalar-header-tab' data-toggle="tab" href="#parametro_hospitalar_header" role="tab" aria-controls="parametro_hospitalar_header" aria-selected="true">Região</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="parametro-hospitalar-pagamento-tab" data-toggle="tab" href="#parametro_hospitalar_pagamento" role="tab" aria-controls="parametro_hospitalar_pagamento" aria-selected="false">Pagamento</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="parametro-hospitalar-insc_estadual-tab" data-toggle="tab" href="#parametro_hospitalar_insc_estadual" role="tab" aria-controls="parametro_hospitalar_insc_estadual" aria-selected="false">Inscrição Estadual</a>
        </li>
    </ul>
    <div class="tab-content pt-3" id="ParametroHospitalarHeaderContainer">
        <div class="tab-pane show active" id="parametro_hospitalar_header" role="tabpanel" aria-labelledby="dados-tab">
            <table class='table table-striped'>
                <tr>
                    <th>Margem</th>
                    <th>Mark UP</th>
                    <th>Frete</th>
                </tr>
                <tr>
                    <td>GRANDE SP</td>
                    <td>{{ Form::text('mark_up_grande_sp', $dados ['GRANDE SP']['mark_up'], ['id' => 'mark_up_grande_sp', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_grande_sp', $dados ['GRANDE SP']['frete'], ['id' => 'frete_grande_sp', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                </tr>
                <tr>
                    <td>GRANDE RJ</td>
                    @if(empty($dados ['GRANDE RJ']))
                    <td>{{ Form::text('mark_up_grande_rj', '', ['id' => 'mark_up_grande_rj', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_grande_rj', '', ['id' => 'frete_grande_rj', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    @else
                    <td>{{ Form::text('mark_up_grande_rj', $dados ['GRANDE RJ']['mark_up'], ['id' => 'mark_up_grande_rj', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_grande_rj', $dados ['GRANDE RJ']['frete'], ['id' => 'frete_grande_rj', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    @endif
                </tr>
                <tr>
                    <td>SUDESTE</td>
                    <td>{{ Form::text('mark_up_sudeste', $dados ['SUDESTE']['frete'], ['id' => 'mark_up_sudeste', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_sudeste', $dados ['SUDESTE']['mark_up'], ['id' => 'frete_sudeste', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                </tr>
                <tr>
                    <td>SUL</td>
                    <td>{{ Form::text('mark_up_sul', $dados ['SUL']['mark_up'], ['id' => 'mark_up_sul', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_sul', $dados ['SUL']['frete'], ['id' => 'frete_sul', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                </tr>
                <tr>
                    <td>CENTRO OESTE</td>
                    <td>{{ Form::text('mark_up_centro_oeste', $dados ['CENTRO OESTE']['mark_up'], ['id' => 'mark_up_centro_oeste', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_centro_oeste', $dados ['CENTRO OESTE']['frete'], ['id' => 'frete_centro_oeste', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                </tr>
                <tr>
                    <td>NORDESTE</td>
                    <td>{{ Form::text('mark_up_nordeste', $dados ['NORDESTE']['mark_up'], ['id' => 'mark_up_nordeste', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_nordeste', $dados ['NORDESTE']['frete'], ['id' => 'frete_nordeste', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                </tr>
                <tr>
                    <td>NORTE</td>
                    <td>{{ Form::text('mark_up_norte', $dados ['NORTE']['mark_up'], ['id' => 'mark_up_norte', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                    <td>{{ Form::text('frete_norte', $dados ['NORTE']['frete'], ['id' => 'frete_norte', 'class' => 'form-control text-right', 'maxlength' => '5']) }}</td>
                </tr>
            </table>
        </div>
        <div class="tab-pane" id="parametro_hospitalar_pagamento" role="tabpanel" aria-labelledby="dados-tab">
            <table class="table  table-striped">
                <tr>
                    <th>Condição</th>
                    <th>Desconto</th>
                </tr>
                <tr>
                    <td>Imediato</td>
                    <td>{{ Form::text('condicao_0', $dados['desconto_pagamento_antecipado_0'], ['id' => 'condicao_0', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                </tr>
                <tr>
                    <td>30 Dias</td>
                    <td>{{ Form::text('condicao_30', $dados['desconto_pagamento_antecipado_30'], ['id' => 'condicao_30', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                </tr>
                <tr>
                    <td>60 Dias</td>
                    <td>{{ Form::text('condicao_60', $dados['desconto_pagamento_antecipado_60'], ['id' => 'condicao_60', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                </tr>
                <tr>
                    <td>Superior a 60 dias</td>
                    <td>{{ Form::text('condicao_61', $dados['desconto_pagamento_antecipado_61'], ['id' => 'condicao_61', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                </tr>
            </table>
        </div>
        <div class="tab-pane" id="parametro_hospitalar_insc_estadual" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-group col-sm-12"> 
                {{ Form::label('desconto_inscricao_estadual', 'Desconto Inscrição Estadual', []) }}
                {{ Form::text('desconto_inscricao_estadual', $dados['desconto_inscricao_estadual'], ['id' => 'desconto_inscricao_estadual', 'class' => 'form-control text-right', 'placeholder' => 'Desconto Inscrição Estadual', 'maxlength' => '40']) }}
            </div>
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal = $(document).find("#form_parametro_hospitalar");
        form_modal.find("#desconto_inscricao_estadual").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_grande_sp").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_grande_rj").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_grande_rj").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_grande_sp").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_sudeste").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_sudeste").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_sul").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_sul").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_centro_oeste").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_centro_oeste").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_nordeste").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_nordeste").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_norte").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_norte").maskMoney({thousands:'', decimal:','});
        form_modal.find("#condicao_0").maskMoney({thousands:'', decimal:','});
        form_modal.find("#condicao_30").maskMoney({thousands:'', decimal:','});
        form_modal.find("#condicao_60").maskMoney({thousands:'', decimal:','});
        form_modal.find("#condicao_61").maskMoney({thousands:'', decimal:','});

        form_modal.find("#btn-salvar").on('click', function(){
            editarDados(form_modal.serialize());
        });
    });
    function editarDados(data_form_modal){
        $.ajax({
            url: "{{ route('parametro_hospitalar.editar') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                //filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErro();
                mensagemErro(dados);
            }
        });
    }

    function limparMesagemErro(){      
        var form_modal = $("#form_parametro_hospitalar");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }

    function mensagemErro(json_error){
        var form_modal = $("#form_parametro_hospitalar");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_modal, input, message){
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
        
</script>
@endsection