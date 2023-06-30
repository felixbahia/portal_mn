@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_gerar_romaneio" id="form_gerar_romaneio" onsubmit="return false;">
        @csrf
        <h3>{{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2">
                {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento']) !!}
            </div>
            <div class="col-lg-2">
                <input type="text" name="numero_nota" id="numero_nota" value="" placeholder="Número da Nota" maxlength="6" require/>
            </div>
            <div class="col-lg-2">
                <input type="text" class="data" name="emissao" id="emissao" placeholder="Emissão MM/AAAA" value="{{ date('m/Y') }}" date-max-date="today" maxlength="20" require>
            </div>
        </div>
        <div class="content-buttons">
            <button type="button" name="btn_gerar_csv" id="btn_gerar_csv" class="btn btn-success">Gerar Romaneio CSV</button>
            <button type="button" name="btn_gerar_etq" id="btn_gerar_etq" class="btn btn-success">Gerar Romaneio ETQ</button>
        </div>
    </form>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $('.data').mask('00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'mm/yyyy',
            zIndex: 100,
            autoHide: true,
            endDate : new Date()
        });
        $("#form_gerar_romaneio").find("#btn_gerar_csv").on("click", function(){
            var data_form = $("#form_gerar_romaneio").serialize();
            limparMesagemErro();
            if(validarDados(data_form)){
                gerarExportacao(data_form);
                inicio();
            }
        });
        $("#form_gerar_romaneio").find("#btn_gerar_etq").on("click", function(){
            var data_form = $("#form_gerar_romaneio").serialize();
            limparMesagemErro();
            if(validarDados(data_form)){
                gerarExportacaoEtq(data_form);
                inicio();
            }
        });
    });

    function inicio(){
        $("#estabelecimento").val('');
        $("#numero_nota").val('');
        $("#emissao").val("{{ date('m/Y') }}");
    }

    function mensagemErro(json_error){
        var form = $("#form_gerar_romaneio");

        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form, field, json_error.error[field]);
            }
        }
    }

    function validarDados(data_form){
        var retorno = false;
        $.ajax({
            url: "{{ route('exportacao_romaneio.validar') }}", 
            dataType: 'json',
            data: data_form,
            method: 'POST',
            async: false,
            success: function(callback){
                retorno = true;
            },
            error: function(callback){
                var dados = callback.responseJSON;
                mensagemErro(dados);
            }
        });
        return retorno;
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparMesagemErro(){
        var form = $("#form_gerar_romaneio");

        form.find('.error-message').remove();
        form.find('input, select').removeClass('error-input');
    }

    function gerarExportacao(data_form){
        $('<form action="{{ route('exportacao_romaneio.exportar') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="estabelecimento" value="'+$("#estabelecimento").val()+'"/>\
                <input type="hidden" name="numero_nota" value="'+$("#numero_nota").val()+'"/>\
                <input type="hidden" name="emissao" value="'+$("#emissao").val()+'"/>\
                </form>').appendTo('body').submit().remove();
        
    }

    function gerarExportacaoEtq(data_form){
        $('<form action="{{ route('exportacao_romaneio.exportar_etq') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="estabelecimento" value="'+$("#estabelecimento").val()+'"/>\
                <input type="hidden" name="numero_nota" value="'+$("#numero_nota").val()+'"/>\
                <input type="hidden" name="emissao" value="'+$("#emissao").val()+'"/>\
                </form>').appendTo('body').submit().remove();
        
    }
@endsection