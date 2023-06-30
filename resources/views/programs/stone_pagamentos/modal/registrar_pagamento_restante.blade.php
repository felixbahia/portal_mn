@extends('layouts.page-dialog')

@section('content')
<form action="#" name='form-stone_cadastro' id='form-stone_cadastro' onsubmit="return false;" enctype="multipart/form-data">
    @csrf
    <div class="border border-dark rounded p-1 mb-1">
        <div class="row">
            <div class="col-sm-4">
                {!! Form::label('forma_pagamento', 'Forma de Pagamento', ['class'=>'input-label']) !!}
                {!! Form::text('forma_pagamento_pago', $transacao_paga['forma_pagamento_pago'], ['id' => 'forma_pagamento_pago', 'class' => 'form-control', 'maxlength' => '150','readonly']) !!}
            </div>
            <div class="col-sm-4">
                {!! Form::label('bandeiras', 'Bandeira', ['class'=>'input-label']) !!}
                {!! Form::text('bandeiras_pago', $transacao_paga['bandeiras_pago'], ['id' => 'bandeiras_pago', 'class' => 'form-control', 'maxlength' => '150','readonly']) !!}
            </div>
            <div class="col-sm-4">
                {!! Form::label('codigo_autorizacao', 'Código Autorização Transação', ['class'=>'input-label']) !!}
                {!! Form::text('codigo_autorizacao_pago', $transacao_paga['codigo_autorizacao_pago'], ['id' => 'codigo_autorizacao_pago', 'class' => 'form-control', 'maxlength' => '150','readonly']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                {!! Form::label('parcelamento', 'Parcelamento', ['class'=>'input-label']) !!}
                {!! Form::text('parcelamento_pago', $transacao_paga['parcelamento_pago'], ['id' => 'parcelamento_pago', 'class' => 'form-control', 'maxlength' => '150','readonly']) !!}
            </div>
            <div class="col-sm-4">
                {!! Form::label('data_autorizacao', 'Data de Autorização', ['class'=>'input-label']) !!}
                {!! Form::text('data_autorizacao_pago', $transacao_paga['data_autorizacao_pago'], ['id' => 'data_autorizacao_pago', 'class' => 'form-control', 'maxlength' => '150','readonly']) !!}
            </div>
            <div class="col-sm-4">
                {!! Form::label('valor', 'Valor', ['class'=>'input-label']) !!}
                {!! Form::text('valor_pago', $transacao_paga['valor_pago'], ['id' => 'valor_pago', 'class' => 'form-control', 'maxlength' => '150','readonly']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                {!! Form::label('tid', 'Stone Id (TID)', ['class'=>'input-label']) !!}
                {!! Form::text('stine_id_pago', $transacao_paga['stine_id_pago'], ['id' => 'stine_id_pago', 'class' => 'form-control', 'maxlength' => '150','readonly']) !!}
            </div>
        </div>
    </div>

    <div class="border border-dark rounded p-1 mb-1">
        <div class="row">
            <div class="col-sm-4">
                <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('forma_pagamento', 'Forma de Pagamento', ['class'=>'input-label']) !!}
                {!! Form::select('forma_pagamento[1]', $forma_pagamento, '', ['id' => 'forma_pagamento[1]', 'class' => 'form-control', 'placeholder' => 'Selecione']) !!}
            </div>
            <div class="col-sm-4">
                <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('bandeiras', 'Bandeira', ['class'=>'input-label']) !!}
                {!! Form::select('bandeiras[1]', $bandeiras, '', ['id' => 'bandeiras[1]', 'class' => 'form-control', 'placeholder' => 'Selecione']) !!}
                {{ Form::hidden("pedido_id", $pedido_id, ["id" => "pedido_id"]) }}
            </div>
            <div class="col-sm-4">
                <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('codigo_autorizacao', 'Código Autorização Transação', ['class'=>'input-label']) !!}
                <i href="{{url('/images/help_payment/help_codigo_autorizacao.png')}}" class="btn-informacao float-none thumb" data-toggle="popover" data-placement="right" data-trigger="hover" title="Código a ser preenchido" data-content="<img src='{{url('/images/help_payment/help_codigo_autorizacao.png')}}' width='250' class='rounded mx-auto d-block' alt='Código'>"></i>
                {!! Form::text('codigo_autorizacao[1]', '', ['id' => 'codigo_autorizacao', 'class' => 'form-control', 'maxlength' => '150']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('parcelamento', 'Parcelamento', ['class'=>'input-label']) !!}
                {!! Form::select('parcelamento[1]', $parcelamentos, '', ['id' => 'parcelamento[1]', 'class' => 'form-control', 'placeholder' => 'Selecione']) !!}
            </div>
            <div class="col-sm-4">
                <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('data_autorizacao', 'Data de Autorização', ['class'=>'input-label']) !!}
                {!! Form::text('data_autorizacao[1]', '', ['id' => 'data_autorizacao[1]', 'class' => 'form-control data_autorizacao', 'maxlength' => '20','readonly']) !!}
            </div>
            <div class="col-sm-4">
                <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('valor', 'Valor', ['class'=>'input-label']) !!}
                {!! Form::text('valor[1]', '', ['id' => 'valor[1]', 'class' => 'form-control valor', 'maxlength' => '50']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('tid', 'Stone Id (TID)', ['class'=>'input-label']) !!}
                <i href="{{url('/images/help_payment/help_stone_id.png')}}" class="btn-informacao float-none thumb" data-toggle="popover" data-placement="right" data-trigger="hover" title="ID da Transação" data-content="<img src='{{url('/images/help_payment/help_stone_id.png')}}' width='250' class='rounded mx-auto d-block' alt='ID Transação'>"></i>
                {!! Form::text('tid[1]', '', ['id' => 'tid[1]', 'class' => 'form-control', 'maxlength' => '30']) !!}
            </div>
        </div>
    </div>
    <div class="adicionar-elemento">
    </div>
</form>

<div class="row mt-2">
    <div class="col-sm text-right" id='enviar-div'>
        {!! Form::button('Adicionar Pagamento', ['id' => 'adicionar_pagamento', 'class' => 'btn btn-success float-left', 'form' => 'form-stone_cadastro']) !!}
        {!! Form::button('Salvar', ['id' => 'btn_enviar', 'class' => 'btn btn-success', 'form' => 'form-stone_cadastro']) !!}
    </div>
</div>

<script>
    $(document).ready(function(){
        $(document).find('#btn_enviar').on('click', function(){
            registrarPagamentos();
        });

        dataMask();

        var x = 2;
		var max_fields = 7;
		
		$('#adicionar_pagamento').click (function(e){
			e.preventDefault(); 
			if (x < max_fields){
                var anterior = 0;
				var conteudo = 
                '<div class="border border-dark rounded p-1 mb-1 remove'+x+'" id="adicionar-documento-div-'+x+'">'+
                    '<div class="row">'+
                        '<div class="col-sm-4">'+
                            '<span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span> {!! Form::label("forma_pagamento", "Forma de Pagamento", ["class"=>"input-label"]) !!}'+
                            '<select id="forma_pagamento['+x+']" class="form-control forma_pagamento'+x+'" name="forma_pagamento['+x+']">'+
                                '<option selected="selected" value="">Selecione</option>'+
                                '@foreach ($forma_pagamento as $key_pagamento => $forma_pagamentos)'+
                                    '<option value="{{$key_pagamento}}">{{$forma_pagamentos}}</option>'+
                                '@endforeach'+
                            '</select>'+
                        '</div>'+
                        '<div class="col-sm-4">'+
                            '<span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span> {!! Form::label("bandeiras", "Bandeira", ["class"=>"input-label"]) !!}'+
                            '<select id="bandeiras['+x+']" class="form-control bandeiras'+x+'" name="bandeiras['+x+']">'+
                                '<option selected="selected" value="">Selecione</option>'+
                                '@foreach ($bandeiras as $key_bandeira => $bandeira)'+
                                    '<option value="{{$key_bandeira}}">{{$bandeira}}</option>'+
                                '@endforeach'+
                            '</select>'+
                        '</div>'+
                        '<div class="col-sm-4">'+
                            '<span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span> {!! Form::label("codigo_autorizacao", "Código Autorização Transação", ["class"=>"input-label"]) !!}'+
                            '<i href="{{url("/images/help_payment/help_codigo_autorizacao.png")}}" class="btn-informacao float-none thumb" data-toggle="popover" data-placement="right" data-trigger="hover" title="Código a ser preenchido" data-content=\'<img src="{{url("/images/help_payment/help_codigo_autorizacao.png")}}" width="250" class="rounded mx-auto d-block" alt="Código">\'></i>'+
                            '<input type="text" id="codigo_autorizacao['+x+']" name="codigo_autorizacao['+x+']" class="form-control codigo_autorizacao'+x+'"  length="50">'+
                        '</div>'+
                    '</div>'+
                    '<div class="row">'+
                        '<div class="col-sm-4">'+
                            '<span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span> {!! Form::label("parcelamento", "Parcelamento", ["class"=>"input-label"]) !!}'+
                            '<select id="parcelamento['+x+']" class="form-control parcelamento'+x+'" name="parcelamento['+x+']">'+
                                '<option selected="selected" value="">Selecione</option>'+
                                '@foreach ($parcelamentos as $key_parcelamento => $parcelamento)'+
                                    '<option value="{{$key_parcelamento}}">{{$parcelamento}}</option>'+
                                '@endforeach'+
                            '</select>'+
                        '</div>'+
                        '<div class="col-sm-4">'+
                            '<span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span> {!! Form::label("data_autorizacao", "Data de Autorização", ["class"=>"input-label"]) !!}'+
                            '<input type="text" id="data_autorizacao['+x+']" name="data_autorizacao['+x+']" readonly class="form-control data_autorizacao data_autorizacao'+x+'"  length="20">'+
                        '</div>'+
                        '<div class="col-sm-4">'+
                            '<span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span> {!! Form::label("valor", "Valor", ["class"=>"input-label"]) !!}'+
                            '<input type="text" id="valor['+x+']" name="valor['+x+']" class="form-control valor valor'+x+'"  length="50">'+
                        '</div>'+
                    '</div>'+
                    '<div class="row">'+
                        '<div class="col-sm-12">'+
                            '<span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span> {!! Form::label("tid", "STONE ID", ["class"=>"input-label"]) !!}'+
                            '<i href="{{url("/images/help_payment/help_stone_id.png")}}" class="btn-informacao float-none thumb" data-toggle="popover" data-placement="right" data-trigger="hover" title="ID da Transação" data-content=\'<img src="{{url("/images/help_payment/help_stone_id.png")}}" width="250" class="rounded mx-auto d-block" alt="ID Transação">\'></i>'+
                            '<input type="text" id="tid['+x+']" name="tid['+x+']" class="form-control tid'+x+'"  length="50">'+
                        '</div>'+
                        '<div class="col-sm-12 mt-3">'+
                            '<input type="button" id="remove'+x+'" class="btn btn-danger remove_documento" value="Remover">'+
                        '</div>'+    
                    '</div>'+
                '</div>';

				if(x > 2){
                    anterior = x - 2;
                    for(var i = 2; i < x; i++){
                        var bandeiras = $(".bandeiras"+i+" option:selected").text();
                        var codigo_autorizacao = $(".codigo_autorizacao"+i).val();
                        var data_autorizacao = $(".data_autorizacao"+i).val();
                        var forma_pagamento = $(".forma_pagamento"+i+" option:selected").text();
                        var valor = $(".valor"+i).val();
                        var tid = $(".tid"+i).val();

                        if( bandeiras == ''             || 
                            codigo_autorizacao == ''    ||
                            data_autorizacao == ''      ||
                            forma_pagamento == ''       ||
                            valor == ''                 ||
                            tid == ''                   
                        ){
                            message("Atenção", "Preencha os campos para adicionar mais pagamentos.");
                            return false;
                        }
                    }
                    $(document).find("#remove"+anterior).hide();
                }
				
				$(document).find('.adicionar-elemento').append(conteudo);
				x++;
			}else{
				message('Alerta','Limite Máximo de Pagamentos Atingido');
			}
		});

		$('.adicionar-elemento').on("click",".remove_documento",function(e) {
			e.preventDefault();
			var anterior = x - 2;
            $(document).find("#remove"+anterior).show();
            if(x > 1){
                x --; 
            }
			var id = $(this).attr('id');
			$(document).find('.'+ id).remove();
		});

        setInterval(function(){
            dataMask();
            valorMask();
            mostrarHelpPayment();
            }
        , 1000);
    });

    function dataMask(){
        $('.data_autorizacao').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            autoHide: true
        });
    }

    function valorMask(){
        $(document).find(".valor").maskMoney({thousands:'.', decimal:',', precision: 2});
    }

    function mostrarHelpPayment(){
        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        $('[data-toggle="popover"]').on('show.bs.popover', function () {
            var $this = $(this);
            $('.popover').not($this).each(function(){
                $("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
            });
            $("body").on("keyup", function(e){
                if(e.keyCode == 27){
                    $($this).popover('hide');
                }
            });
        });

        $(document).find("i.thumb").fancybox(
            {
                onComplete: function(){
                    $('#fancybox-content')
                        .on('mouseover', function(){
                            $(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
                        })
                        .on('mouseout', function(){
                            $(this).children('#fancybox-img').css({'transform': 'scale(1)'});
                        })
                        .on('mousemove', function(e){
                            $(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
                    });
                }
            }
        );
    }

    function registrarPagamentos(){

        var form = $(document).find('#form-stone_cadastro');
        var dados = form.serialize();
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("stone_pagamentos.registrar_pagamento_restante") }}',
            method: 'POST',
            data: dados,
            success: function(data){
                if(data.status == 'success'){
                    message('Atenção', data.message);
                    $(document).find('#registrar-pagamentos').modal('hide');
                    buscarPedidos();
                }else if(data.status == 'error'){
                    message('Atenção', data.message);
                }else{
                    message('Atenção', 'Erro ao registrar, consulte o setor responsável.');
                }
            },
            error: function(callback){
                if(callback.responseJSON.message != ''){
                    message('Atenção', callback.responseJSON.message);
                    return false;
                }
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputsModalNovo(form, field, errors[field]);
                }
            }
        })
    }

    function showErrorsInputsModalNovo(form, input, message){
        var inputexplode = input.split(".");
		if(inputexplode.length > 1){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
			message = message;
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
		}else if(input.match(/produtos/i) != null){
            $input = $(document).find(".valor_parcial:not([disabled])").eq(input.split('.')[1]);
        }
        else if(input == 'valor_parcial'){
            $input = form.find(".valor_parcial_radio").parent().parent();
        }else{
            var $input = form.find("input[name='"+input+"'], select[name='"+input+"'], textarea[name='"+input+"']");
        }
    }

</script>
@endsection