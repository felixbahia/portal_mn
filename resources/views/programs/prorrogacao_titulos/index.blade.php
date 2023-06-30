@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2 col-xl-2">
                {{ Form::select("estabelecimento", $estabelecimentos, "", ["id" => "estabelecimento", "class" => "form-control", "placeholder" => "Estabelecimento"]) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select("banco", $bancos, '', ["id" => "banco", "class"=>"form-control", "placeholder" => "Bancos"]) }}
            </div>
            <div class="col-lg-2">
                {!! Form::text('data_de', '', ['id' => 'data_de', 'class' => 'data', 'placeholder' => 'Data de vencimento de DD/MM/AAAA', 'maxlength' => '20']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('data_ate', '', ['id' => 'data_ate', 'class' => 'data', 'placeholder' => 'Data de vencimento até DD/MM/AAAA', 'maxlength' => '20']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('data_nova', '', ['id' => 'data_nova', 'class' => 'data_nova', 'placeholder' => 'Data de vencimento nova DD/MM/AAAA', 'maxlength' => '20']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                {!! Form::text('taxa_juros', '', ['id' => 'taxa_juros', 'class' => 'taxa_juros text-right', 'placeholder' => 'Taxa de juros diarias', 'maxlength' => '9']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('despesas_adicionais', '', ['id' => 'despesas_adicionais', 'class' => 'despesas_adicionais text-right', 'placeholder' => 'Despesas adicionais bancarias', 'maxlength' => '9']) !!}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar campos" />
        <button name="btn-create" id="btn-create" class="btn-create">Renegociar titulos</button>
    </div>
</form>
@endsection
@section('content')
<div class="row content_protesto">
    <div class="col-6 titulo_atualizados">
        <h3>Titulos que serão atualizados</h3>
        <div>
            <h3>Valor Total:</h3>
            <div class="valor"></div>
        </div>
        <div>
            <h3>Quantidade:</h3>
            <div class="quantidade"></div>
        </div>
    </div>
    <div class="col-6 titulo_nao_atualizados">
        <h3>Titulos que não serão atualizados</h3>
        <div>
            <h3>Valor Total:</h3>
            <div class="valor"></div>
        </div>
        <div>
            <h3>Quantidade:</h3>
            <div class="quantidade"></div>
        </div>
    </div>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    form = $(document).find("#form_filter");

    form.find('.data').mask('00/00/0000');
    form.find('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 100,
        autoHide: true,
        endDate: new Date(),
    });
    form.find('.data_nova').mask('00/00/0000');
    form.find('.data_nova').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 100,
        autoHide: true,
        startDate: new Date(),
    });
    form.find('#data_de').on('pick.datepicker', function (e) {
        if(form.find('#data_ate').datepicker('getDate') < e.date){
            form.find('#data_ate').val('');
        }
        form.find('#data_ate').datepicker('setStartDate', e.date);
        form.find('#data_ate').datepicker('update');
    });

    form.find('.taxa_juros').maskMoney({thousands:'', decimal:',', precision: 3});
    form.find('.despesas_adicionais').maskMoney({thousands:'', decimal:',', precision: 2});

    form.find("#estabelecimento").off("change");
    form.find("#estabelecimento").on("change", function(event){
        event.stopPropagation();
        buscaTitulos();
    });

    form.find("#banco").off("change");
    form.find("#banco").on("change", function(event){
        event.stopPropagation();
        buscaTitulos();
    });

    form.find("#data_de").off("change");
    form.find("#data_de").on("change", function(event){
        event.stopPropagation();
        buscaTitulos();
    });

    form.find("#data_ate").off("change");
    form.find("#data_ate").on("change", function(event){
        event.stopPropagation();
        buscaTitulos();
    });

    form.find("#data_nova").off("change");
    form.find("#data_nova").on("change", function(event){
        event.stopPropagation();
        buscaTitulos();
    });

    form.find("#btn-create").off("click");
    form.find("#btn-create").on("click", function(event){
        event.stopPropagation();
        processarTitulos();
    });

    form.find(".btn-clear").on("click", function(){
        $form = $(this).parents('form');
        $form.find('input, select').not('[class^=btn-]').not('[name=_token]').val('');
    });
});
function buscaTitulos(){
    $form = $(document).find("#form_filter");
    $data_ate = $form.find("#data_ate").val();
    $data_de = $form.find("#data_de").val();
    $data_nova = $form.find("#data_nova").val();
    if($data_ate != '' && $data_de != '' && $data_nova != ''){
        data_form = $form.serialize();
        $.ajax({
            url: '{{ route('prorrogacao_titulos.busca_valores')}}',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var response = callback.response;
                    $(document).find('.titulo_atualizados').find('.valor').html(response.valor_seram_prorrogados);
                    $(document).find('.titulo_nao_atualizados').find('.valor').html(response.valor_nao_seram_prorrogados);

                    $(document).find('.titulo_atualizados').find('.quantidade').html(response.titulos_seram_prorrogados);
                    $(document).find('.titulo_nao_atualizados').find('.quantidade').html(response.titulos_nao_seram_prorrogados);
                }
            },
            error: function(callback){
                var errors = callback.responseJSON.error;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }
}
function showErrorsInputs(form, input, message){
    var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}
function processarTitulos(){
    $form = $(document).find("#form_filter");
    $data_ate = $form.find("#data_ate").val();
    $data_de = $form.find("#data_de").val();
    $data_nova = $form.find("#data_nova").val();
    if($data_ate != '' && $data_de != '' && $data_nova != ''){
        data_form = $form.serialize();
        $.ajax({
            url: '{{ route('prorrogacao_titulos.processar_titulos')}}',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var response = callback.response;
                    $(document).find('.titulo_atualizados').find('.valor').html(response.valor_seram_prorrogados);
                    $(document).find('.titulo_nao_atualizados').find('.valor').html(response.valor_nao_seram_prorrogados);

                    $(document).find('.titulo_atualizados').find('.quantidade').html(response.titulos_seram_prorrogados);
                    $(document).find('.titulo_nao_atualizados').find('.quantidade').html(response.titulos_nao_seram_prorrogados);

                    $form.trigger("reset");
                }
            },
            error: function(callback){
                if(callback.responseJSON.error){
                    var errors = callback.responseJSON.error;
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }else{
                    message('Atenção', callback.responseJSON.message);
                }
            }
        });
    }
}
@endsection