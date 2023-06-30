@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_baixar_titulo" id="form_baixar_titulo" onsubmit="return false;">
    @csrf
    {!! Form::hidden('titulo_id', $titulo_id, ["id" => 'titulo_id']) !!}
    @if(!empty($pago_valor))
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('tipo_baixa', 'Valor: '.$titulo_valor.' - Juros: '.$juros_valor.' - Pago: '.$pago_valor.' - Saldo: '.$saldo_valor, []) }}
        </div>
    </div>
    @else
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('tipo_baixa', 'Valor: '.$titulo_valor.' - Juros: '.$juros_valor.' - Saldo: '.$saldo_valor, []) }}
        </div>
    </div>
    @endif
    @if(!empty(parserNumber($juros_valor)))
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('baixa_contabil', 'Valor para Baixa Contábil – Recebido: '.$saldo_valor.' - Juros: '.$juros_valor, ['id' => 'baixa_contabil']) }}
        </div>
    </div>
    @else
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('baixa_contabil', 'Valor para Baixa Contábil – Recebido : '.$saldo_valor, ['id' => 'baixa_contabil']) }}
        </div>
    </div>
    @endif
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('tipo_baixa', 'Tipo de Baixa', []) }}
            <div class="form-check">
                {{ Form::radio('tipo', 'total', '', ['class' => 'form-check-input', 'id'=>'tipo_total', 'checked']) }}
                {{ Form::label('tipo_total', 'Total', ['class'=>'form-check-label']) }}
            </div>
        </div>
        <div class="form-group col-sm-6"> 
            <br/>
            <div class="form-check">
                {{ Form::radio('tipo', 'parcial', '', ['class' => 'form-check-input', 'id'=>'tipo_parcial']) }}
                {{ Form::label('tipo_parcial', 'Parcial', ['class'=>'form-check-label']) }}
            </div>
        </div>
    </div>
    @if(Auth::user()->hasRole('Juridico'))
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('conta_bancaria', 'Conta Bancária', []) }}
            {{ Form::select('conta_bancaria', $contas_bancarias, $conta_bancaria_ragazzi, ['id' => 'conta_bancaria', 'class' => 'form-control', 'placeholder' => 'Escolhe', 'readonly']) }}
        </div>
    </div>
    @else
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('conta_bancaria', 'Conta Bancária', []) }}
            {{ Form::select('conta_bancaria', $contas_bancarias, '', ['id' => 'conta_bancaria', 'class' => 'form-control', 'placeholder' => 'Escolhe']) }}
        </div>
    </div>
    @endif
    <div class="hide-on-total">
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('valor_recebido_parcial', 'Valor Recebido', []) }}
                {{ Form::text('valor_recebido_parcial', '', ['id' => 'valor_recebido_parcial', 'class' => 'form-control valor text-right', 'placeholder' => 'Valor Recebido', 'maxlength' => '12']) }}
            </div>
        </div>
    </div>
    <div class="hide-on-parcial">
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('valor_recebido', 'Valor Recebido', []) }}
                {{ Form::text('valor_recebido', $saldo_valor, ['id' => 'valor_recebido', 'class' => 'form-control valor text-right', 'placeholder' => 'Valor Recebido', 'maxlength' => '12']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('juros', 'Juros', []) }}
                {{ Form::text('juros', $juros_valor, ['id' => 'juros', 'class' => 'form-control valor text-right hide-on-parcial', 'placeholder' => 'Juros', 'maxlength' => '12']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('desconto', 'Desconto', []) }}
                {{ Form::text('desconto', $desconto_valor, ['id' => 'desconto', 'class' => 'form-control valor text-right hide-on-parcial', 'placeholder' => 'Desconto', 'maxlength' => '12']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('multa', 'Multa', []) }}
                {{ Form::text('multa', '', ['id' => 'multa', 'class' => 'form-control valor text-right hide-on-parcial', 'placeholder' => 'Multa', 'maxlength' => '12']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('taxa_boleto_banco', 'Taxa Boleto/Banco', []) }}
                {{ Form::text('taxa_boleto_banco', '', ['id' => 'taxa_boleto_banco', 'class' => 'form-control valor text-right hide-on-parcial', 'placeholder' => 'Taxa Boleto/Banco', 'maxlength' => '12']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('honorarios_cliente', 'Honorários Cliente', []) }}
                {{ Form::text('honorarios_cliente', '', ['id' => 'honorarios_cliente', 'class' => 'form-control valor text-right hide-on-parcial', 'placeholder' => 'Honorários Cliente', 'maxlength' => '12']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('comissao_cobranca', 'Comissão Cobrança', []) }}
                {{ Form::text('comissao_cobranca', '', ['id' => 'comissao_cobranca', 'class' => 'form-control valor text-right hide-on-parcial', 'placeholder' => 'Comissão Cobrança', 'maxlength' => '12']) }}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                {{ Form::label('tarifa_bancaria_mn', 'Tarifa Bancária MN', []) }}
                {{ Form::text('tarifa_bancaria_mn', '', ['id' => 'tarifa_bancaria_mn', 'class' => 'form-control valor text-right hide-on-parcial', 'placeholder' => 'Tarifa Bancária MN', 'maxlength' => '12']) }}
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('data_baixa', 'Data da Baixa', []) }}
            {{ Form::text('data_baixa', date('d/m/Y'), ['id' => 'data_baixa', 'class' => 'form-control data', 'placeholder' => 'Data da Baixa', 'maxlength' => '20']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>

<script type="text/javascript">
    $(document).ready(function(){
        form_modal_baixar_titulo = $(document).find('#form_baixar_titulo');

        form_modal_baixar_titulo.find('.hide-on-total').hide();

        form_modal_baixar_titulo.find(".valor").maskMoney({thousands:'.', decimal:','});

        form_modal_baixar_titulo.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            startDate: "{{ $data_minima }}",
            endDate: new Date(),
        });
        form_modal_baixar_titulo.find('.data').mask('00/00/0000');

        form_modal_baixar_titulo.find("#btn-salvar").off('click');
        form_modal_baixar_titulo.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_baixar_titulo.serialize());
        });

        form_modal_baixar_titulo.find("#tipo_parcial").off('click');
        form_modal_baixar_titulo.find("#tipo_parcial").on('click', function(){
            form_modal_baixar_titulo.find('.hide-on-parcial').hide();
            form_modal_baixar_titulo.find('.hide-on-total').show();
        });

        form_modal_baixar_titulo.find("#tipo_total").off('click');
        form_modal_baixar_titulo.find("#tipo_total").on('click', function(){
            form_modal_baixar_titulo.find('.hide-on-total').hide();
            form_modal_baixar_titulo.find('.hide-on-parcial').show();
        });

        form_modal_baixar_titulo.find("#valor_recebido").off('keyup');
        form_modal_baixar_titulo.find("#valor_recebido").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "recebido");
        });
        form_modal_baixar_titulo.find("#juros").off('keyup');
        form_modal_baixar_titulo.find("#juros").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "juros");
        });
        form_modal_baixar_titulo.find("#desconto").off('keyup');
        form_modal_baixar_titulo.find("#desconto").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "desconto");
        });
        form_modal_baixar_titulo.find("#taxa_boleto_banco").off('keyup');
        form_modal_baixar_titulo.find("#taxa_boleto_banco").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "desconto_extra");
        });
        form_modal_baixar_titulo.find("#honorarios_cliente").off('keyup');
        form_modal_baixar_titulo.find("#honorarios_cliente").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "desconto_extra");
        });
        form_modal_baixar_titulo.find("#comissao_cobranca").off('keyup');
        form_modal_baixar_titulo.find("#comissao_cobranca").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "desconto_extra");
        });
        form_modal_baixar_titulo.find("#tarifa_bancaria_mn").off('keyup');
        form_modal_baixar_titulo.find("#tarifa_bancaria_mn").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "desconto_extra");
        });
        form_modal_baixar_titulo.find("#multa").off('keyup');
        form_modal_baixar_titulo.find("#multa").on('keyup', function(){
            calculoValores(form_modal_baixar_titulo, "multa");
        });
    });

    function inserirDados(data_form_modal_baixar_titulo){
        $.ajax({
            url: "{{ route('baixar_titulo.baixar_titulo_na_nasajon') }}", 
            dataType: 'json',
            data: data_form_modal_baixar_titulo,
            method: 'POST',
            success: function(callback){
                loader();
                $(form_modal_baixar_titulo).parents('.modal').modal('hide');
                filterAjaxModalTitulosBaixa($("#form_filter").serialize());
                message("Atenção", "baixa realizada com sucesso!");
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_baixar_titulo = $("#form_baixar_titulo");
        form_modal_baixar_titulo.find('.error-message').remove();
        form_modal_baixar_titulo.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_baixar_titulo = $("#form_baixar_titulo");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_baixar_titulo, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_baixar_titulo, input, message){
        var $input = $(form_modal_baixar_titulo).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function calculoValores(form_modal_baixar_titulo, tipo){
        saldo_valor = "{{ $saldo_valor }}";
        juros_valor = "{{ $juros_valor }}";
        titulo_valor = "{{ $titulo_valor }}";
        var valor_recebido = form_modal_baixar_titulo.find("#valor_recebido").val().replace(/\./g,"").replace(/\,/g, ".");
        var juros = form_modal_baixar_titulo.find("#juros").val().replace(/\./g,"").replace(/\,/g, ".");
        var desconto = form_modal_baixar_titulo.find("#desconto").val().replace(/\./g,"").replace(/\,/g, ".");
        var taxa_boleto_banco = form_modal_baixar_titulo.find("#taxa_boleto_banco").val().replace(/\./g,"").replace(/\,/g, ".");
        var honorarios_cliente = form_modal_baixar_titulo.find("#honorarios_cliente").val().replace(/\./g,"").replace(/\,/g, ".");
        var comissao_cobranca = form_modal_baixar_titulo.find("#comissao_cobranca").val().replace(/\./g,"").replace(/\,/g, ".");
        var tarifa_bancaria_mn = form_modal_baixar_titulo.find("#tarifa_bancaria_mn").val().replace(/\./g,"").replace(/\,/g, ".");
        var multa = form_modal_baixar_titulo.find("#multa").val().replace(/\./g,"").replace(/\,/g, ".");
        var desconto_extra = 0.0;
        var retorno = "";

        var conta_bancaria = form_modal_baixar_titulo.find("#conta_bancaria").val();

        saldo_valor = saldo_valor.replace(/\./g,"").replace(/\,/g, ".");
        titulo_valor = titulo_valor.replace(/\./g,"").replace(/\,/g, ".");
        juros_valor = juros_valor.replace(/\./g,"").replace(/\,/g, ".");

        saldo_valor = parseFloat(saldo_valor);
        titulo_valor = parseFloat(titulo_valor);
        juros_valor = parseFloat(juros_valor);

        if(valor_recebido != "" || !$.isEmptyObject(valor_recebido)){
            valor_recebido = parseFloat(valor_recebido);
        }else{
            valor_recebido = parseFloat("0.0");
        }
        
        if(juros != "" || !$.isEmptyObject(juros)){
            juros = parseFloat(juros);
        }else{
            juros = parseFloat("0.0");
        }

        if(desconto != "" || !$.isEmptyObject(desconto)){
            desconto = parseFloat(desconto);
        }else{
            desconto = parseFloat("0.0");
        }
        
        if(taxa_boleto_banco != "" || !$.isEmptyObject(taxa_boleto_banco)){
            taxa_boleto_banco = parseFloat(taxa_boleto_banco);
        }else{
            taxa_boleto_banco = parseFloat("0.0");
        }

        if(honorarios_cliente != "" || !$.isEmptyObject(honorarios_cliente)){
            honorarios_cliente = parseFloat(honorarios_cliente);
        }else{
            honorarios_cliente = parseFloat("0.0");
        }

        if(comissao_cobranca != "" || !$.isEmptyObject(comissao_cobranca)){
            comissao_cobranca = parseFloat(comissao_cobranca);
        }else{
            comissao_cobranca = parseFloat("0.0");
        }

        if(tarifa_bancaria_mn != "" || !$.isEmptyObject(tarifa_bancaria_mn)){
            tarifa_bancaria_mn = parseFloat(tarifa_bancaria_mn);
        }else{
            tarifa_bancaria_mn = parseFloat("0.0");
        }

        if(multa != "" || !$.isEmptyObject(multa)){
            multa = parseFloat(multa);
        }else{
            multa = parseFloat("0.0");
        }
        if('{{$data_emissao}}' == '2022-07-01'){
            if(tipo === "juros" || tipo === "desconto" || tipo === "multa"){
                valor_recebido = saldo_valor - juros_valor + multa + juros - desconto;
                form_modal_baixar_titulo.find("#valor_recebido").val(numberToReal(valor_recebido.toFixed(2)));
            }else if(tipo === "recebido"){
                if(((saldo_valor - juros_valor) - valor_recebido) < 0){
                    juros = valor_recebido - (saldo_valor - juros_valor) - multa;
                    desconto = 0;
                }else{
                    juros = 0;
                    desconto = (saldo_valor - juros_valor) - valor_recebido;
                }       
                
                form_modal_baixar_titulo.find("#desconto").val(numberToReal(desconto.toFixed(2)));
                form_modal_baixar_titulo.find("#juros").val(numberToReal(juros.toFixed(2)));
            }
        }

        if('{{$data_emissao}}' != '2022-07-01'){
            desconto_extra = comissao_cobranca;
            juros = juros + multa + taxa_boleto_banco + honorarios_cliente + tarifa_bancaria_mn;
            valor_recebido = valor_recebido ;
            console.log('{{$renegociado}}' == '1');
            console.log('{{$renegociado}}');
            if('{{$renegociado}}' == '1'){
                valor_recebido = valor_recebido + juros;
            }
        }else {
            desconto_extra = taxa_boleto_banco + honorarios_cliente + comissao_cobranca + tarifa_bancaria_mn; 
            juros = juros + multa;  
        }
        

        desconto = desconto + desconto_extra;


        retorno = "Valor para Baixa Contábil – Recebido: "+numberToReal((valor_recebido - desconto_extra).toFixed(2));

        if(juros > 0){
            retorno = retorno + " - Juros: "+numberToReal(juros.toFixed(2));
        }
        
        if(desconto > 0){
            retorno = retorno + " - Desconto: "+numberToReal((desconto).toFixed(2));
        }

        form_modal_baixar_titulo.find("#baixa_contabil").html(retorno);
    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }
</script>
@endsection