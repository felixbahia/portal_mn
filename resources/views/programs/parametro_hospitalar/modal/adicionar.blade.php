@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_parametro_hospitalar" id="form_parametro_hospitalar" onsubmit="return false;">
        @csrf
        
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
                        <td>{{ Form::text('mark_up_grande_sp', '', ['id' => 'mark_up_grande_sp', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                        <td>{{ Form::text('frete_grande_sp', '', ['id' => 'frete_grande_sp', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>GRANDE RJ</td>
                        <td>{{ Form::text('mark_up_grande_rj', '', ['id' => 'mark_up_grande_rj', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                        <td>{{ Form::text('frete_grande_rj', '', ['id' => 'frete_grande_rj', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>SUDESTE</td>
                        <td>{{ Form::text('mark_up_sudeste', '', ['id' => 'mark_up_sudeste', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                        <td>{{ Form::text('frete_sudeste', '', ['id' => 'frete_sudeste', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>SUL</td>
                        <td>{{ Form::text('mark_up_sul', '', ['id' => 'mark_up_sul', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                        <td>{{ Form::text('frete_sul', '', ['id' => 'frete_sul', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>CENTRO OESTE</td>
                        <td>{{ Form::text('mark_up_centro_oeste', '', ['id' => 'mark_up_centro_oeste', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                        <td>{{ Form::text('frete_centro_oeste', '', ['id' => 'frete_centro_oeste', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>NORDESTE</td>
                        <td>{{ Form::text('mark_up_nordeste', '', ['id' => 'mark_up_nordeste', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                        <td>{{ Form::text('frete_nordeste', '', ['id' => 'frete_nordeste', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>NORTE</td>
                        <td>{{ Form::text('mark_up_norte', '', ['id' => 'mark_up_norte', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                        <td>{{ Form::text('frete_norte', '', ['id' => 'frete_norte', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
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
                        <td>{{ Form::text('condicao_0', '', ['id' => 'condicao_0', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>30 Dias</td>
                        <td>{{ Form::text('condicao_30', '', ['id' => 'condicao_30', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>60 Dias</td>
                        <td>{{ Form::text('condicao_60', '', ['id' => 'condicao_60', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                    <tr>
                        <td>Superior a 60 dias</td>
                        <td>{{ Form::text('condicao_61', '', ['id' => 'condicao_61', 'class' => 'form-control text-right', 'maxlength' => '8']) }}</td>
                    </tr>
                </table>
            </div>
            <div class="tab-pane" id="parametro_hospitalar_insc_estadual" role="tabpanel" aria-labelledby="dados-tab">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('desconto_inscricao_estadual', 'Desconto Inscrição Estadual', []) }}
                    {{ Form::text('desconto_inscricao_estadual', '', ['id' => 'desconto_inscricao_estadual', 'class' => 'form-control text-right', 'placeholder' => 'Desconto Inscrição Estadual', 'maxlength' => '40']) }}
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
        form_modal.find("#frete_grande_sp").maskMoney({thousands:'', decimal:','});
        form_modal.find("#mark_up_grande_rj").maskMoney({thousands:'', decimal:','});
        form_modal.find("#frete_grande_rj").maskMoney({thousands:'', decimal:','});
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
            inserirDados(form_modal.serialize());
        });
    });
    function inserirDados(data_form_modal){
        $.ajax({
            url: "{{ route('parametro_hospitalar.adicionar') }}", 
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