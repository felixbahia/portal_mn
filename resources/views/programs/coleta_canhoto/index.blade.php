@extends('layouts.app')

@section('content')
<div class="conteudo_centralizado" style="scroll-behavior: inherit;height: 1000px">
    <div class="content-filter-dialog">	
        <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
            @csrf
            <h3>{{ CustomView::programaName() }}</h3>
            {{ Form::hidden('uuid_cupom', (isset($dados['uuid']) && !empty($dados['uuid'])) ? $dados['uuid'] : '', ['id' => 'uuid_cupom', 'class' => 'form-control']) }}
            <div class="content-fields">
                {{ Form::label('label_nota', 'Pesquisar Por:') }}<br>
                {{ Form::radio('forma_busca', 'chave', true, ['id' => 'radio_chave']) }}
                {{ Form::label('label_nota', 'Chave da Nota', ['class'=>'input-label']) }}&nbsp;
                {{ Form::radio('forma_busca', 'numero', false, ['id' => 'radio_numero']) }}
                {{ Form::label('label_nota', 'Número da Nota', ['class'=>'input-label']) }}&nbsp;
                {{ Form::radio('forma_busca', 'radio_cupom', false, ['id' => 'radio_cupom']) }}
                {{ Form::label('label_nota', 'Cupom Fiscal', ['class'=>'input-label']) }}
                <div class="form-group col-sm-12" id="chave_nota"> 
                    {{ Form::label('nota', 'Chave da Nota') }}
                    {{ Form::text('nota', ($codigo == 'erro') ? '' : $codigo, ['id' => 'nota', 'class' => 'form-control', 'placeholder' => 'Digite ou scaneie a chave da nota', 'maxlength' => '250']) }}
                    <div class="col-sm-2"> 
                        <a href="http://zxing.appspot.com/scan?ret={{ route('coleta_canhoto.index') }}?codigo={CODE}" >Celular</a> 
                    </div>
                </div>
                <div class="form-group col-sm-12 numero" id="div_numero_nota">
                    {{ Form::label('numero_nota', 'Número da Nota') }}
                    {{ Form::text('numero_nota', '', ['id' => 'numero_nota','class' => 'form-control','placeholder' => 'Digite o número nota']) }}
                </div>
                <div class="form-group col-sm-12" id="div_estabelecimentos_nota">
                    {{ Form::select('estabelecimentos', $estabelecimentos, '', ['id' => 'estabelecimentos','class' => 'form-control']) }}
                </div>
                <div class="form-group col-sm-12 numero" id="div_numero_cupom">
                    {{ Form::label('numero_cupom', 'Número do Cupom') }}
                    {{ Form::text('numero_cupom', '', ['id' => 'numero_cupom','class' => 'form-control','placeholder' => 'Digite o número do Cupom']) }}
                </div>
                <div class="form-group col-sm-12" id="div_emissao_cupom">
                    {{ Form::label('data_emissao_cupom', 'Data Emissão') }}
                    {{ Form::text('data_emissao_cupom',  '', ['id' => 'data_emissao_cupom', 'class' => 'form-control data', 'placeholder' => 'Data  Emissão', 'maxlength' => '20']) }}
               </div>
               <div class="form-group col-sm-12" id="div_serie_cupom">
                   {{ Form::label('serie_cupom', 'Série Cupom') }}
                   {{ Form::text('serie_cupom', '', ['id' => 'serie_cupom', 'class' => 'form-control    ', 'placeholder' => 'Série', 'maxlength' => '20']) }}
                </div>
                <div class="form-group col-sm-12" id="div_estabelecimentos_cupom">
                    {{ Form::select('estabelecimentos_cupom', $estabelecimentos, '', ['id' => 'estabelecimentos_cupom','class' => 'form-control']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('data_saida', 'Data Saída') }}
                    {{ Form::text('data_saida', (isset($dados['datasaida'])) ? $dados['datasaida'] : '', ['id' => 'data_saida', 'class' => 'form-control data', 'placeholder' => 'Data Saída', 'maxlength' => '20']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('label_foto', 'Documento de Entrega') }}
                    {{ Form::file('foto', ['id'=>'foto', 'class' => 'btn btn-sm btn-light']) }}
                    <div id="div_foto">
                        @if(!empty($dados['canhoto']))
                            <a href='{{$dados['canhoto']}}'  class="btn-foto-estoque thumb ml-2 mt-1" data-toggle="popover" data-trigger="hover" title="Foto Canhoto" data-content="<img src='{{$dados['canhoto']}}' width='250' class='rounded mx-auto d-block' alt='Canhoto'>"></a>
                        @endif
                    </div>
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('peso', 'Peso Total das Mercadorias') }}
                    {{ Form::text('peso', (isset($dados['pesoliquido'])) ? $dados['pesoliquido'] : '', ['id' => 'peso', 'class' => 'form-control text-right', 'placeholder' => 'Peso Total das Mercadorias', 'maxlength' => '10']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('estabelecimento', 'Estabelecimento', []) }}
                    {{ Form::hidden('estabelecimento', (isset($dados['estabelecimento_codigo'])) ? $dados['estabelecimento_codigo'] : '', ['id' => 'estabelecimento', 'class' => 'form-control', 'maxlength' => '10']) }}
                    {{ Form::text('estabelecimento_descricao', (isset($dados['estabelecimento_codigo'])) ? $dados['estabelecimento_codigo'].' - '.$dados['estabelecimento_descricao']  : '', ['id' => 'estabelecimento_descricao', 'class' => 'form-control', 'placeholder' => 'Estabelecimento', 'maxlength' => '10', 'readonly' => 'true']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('numero', 'Número da Nota') }}
                    {{ Form::text('numero', (isset($dados['numero'])) ? $dados['numero'] : '', ['id' => 'numero', 'class' => 'form-control', 'placeholder' => 'Número', 'maxlength' => '20', 'readonly' => 'true']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('cliente', 'Cliente') }}
                    {{ Form::text('cliente', (isset($dados['cliente_nome'])) ? $dados['cliente_nome'] : '', ['id'=>'cliente', 'class' => 'form-control text', 'readonly' => 'true', 'placeholder' => 'Cliente']) }}
                    {{ Form::hidden('documento_cliente', (isset($dados['cliente_documento'])) ? $dados['cliente_documento'] : '', ['id'=>'documento_cliente', 'class' => 'form-control text', 'readonly' => 'true', 'placeholder' => 'Documento Cliente']) }}
                </div>

                <div class="content-buttons">
                    {{ Form::label('emissao', 'Emissão') }}
                    {{ Form::text('emissao', (isset($dados['emissao'])) ? $dados['emissao'] : '', ['id' => 'emissao', 'class' => 'form-control data', 'placeholder' => 'Data de Emissão', 'maxlength' => '10','readonly' => 'true']) }}
                </div>
            </div>
            <div class="content-buttons">
                {{ Form::button('Adicionar', array('class' => 'btn-create', 'id' => 'btn-filterform')) }}
        
            </div>
        </form>
    </div>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });
    
    $(document).find("#peso").maskMoney({thousands:'', decimal:',', precision: 3});
    $(document).find('#btn-filterform').on('click', function(){
        filterAjax();
    });
    popover();

    $("#div_numero_nota").hide();
    $("#div_estabelecimentos_nota").hide();
    $("#div_numero_cupom").hide();
    $("#div_estabelecimentos_cupom").hide();
    $("#div_emissao_cupom").hide();
    $("#div_serie_cupom").hide();

    $(document).find("#radio_chave").prop("checked", true);
    $(document).find("input[name='forma_busca']").off("change");

    $(document).find("input[name='forma_busca']").on("change", function(){
        if($(this).val() === "chave"){
            $("#chave_nota").show();
            $("#div_numero_nota").hide();
            $("#div_estabelecimentos_nota").hide();
            $("#numero_nota").val('');
            $("#numero_cupom").val('');
            $("select[name=estabelecimentos_cupom]").val($("select[name=estabelecimentos_cupom] option:first-child").val());
            $("select[name=estabelecimentos]").val($("select[name=estabelecimentos] option:first-child").val());
            $("#div_emissao_cupom").hide();
            $("#div_serie_cupom").hide();
            $("#cliente").val('');
            $("#emissao").val('');
            $("#numero").val('');
            $("#estabelecimento_descricao").val('');
            $("#data_emissao_cupom").val('');
            $("#serie_cupom").val('');

        }else if($(this).val() === "numero"){
            $("#chave_nota").hide();
            $("#div_numero_nota").show();
            $("#div_estabelecimentos_nota").show();
            $("#nota").val('');
            $("#div_estabelecimentos_cupom").hide();
            $("#div_numero_cupom").hide();
            $("#div_emissao_cupom").hide();
            $("#numero_cupom").val('');
            $("#div_serie_cupom").hide();
            $("#div_serie_cupom").val('');
            $("#cliente").val('');
            $("#emissao").val('');
            $("#numero").val('');
            $("#estabelecimento_descricao").val('');
            $("#data_emissao_cupom").val('');
            $("#serie_cupom").val('');
            $("select[name=estabelecimentos_cupom]").val($("select[name=estabelecimentos_cupom] option:first-child").val());
            $("select[name=estabelecimentos]").val($("select[name=estabelecimentos] option:first-child").val());

        }else if($(this).val() === "radio_cupom"){
            $("#chave_nota").hide();
            $("#div_numero_nota").hide();
            $("#div_estabelecimentos_nota").hide();
            $("#numero_nota").val('');
            $("select[name=estabelecimentos_cupom]").val($("select[name=estabelecimentos_cupom] option:first-child").val());
            $("select[name=estabelecimentos]").val($("select[name=estabelecimentos] option:first-child").val());
            $("#chave_nota").hide();
            $("#div_numero_nota").hide();
            $("#div_estabelecimentos_nota").hide();
            $("#nota").val('');
            $("#div_serie_cupom").show();
            $("#div_serie_cupom").val('');
            $("#div_estabelecimentos_cupom").show();
            $("#div_numero_cupom").show();
            $("#div_emissao_cupom").show();
            $("#numero_cupom").val('');
            $("#cliente").val('');
            $("#emissao").val('');
            $("#numero").val('');
            $("#estabelecimento_descricao").val('');
            $("#data_emissao_cupom").val('');
            $("#serie_cupom").val('');

        }
        
    });

    @if($codigo == 'erro')
        message('Atenção', 'Nota não encontrada','error');
    @endif

    $(document).find("input").on("keydown", function(event){
        var $this = $(this);
        if(event.which == 17){
            $(this).val('');
        }else if(event.which == 9){
            return false;
        }
    });
    $(document).find("input").on("keyup", function(event){
        var $this = $(this);
        if(event.which == 13 || event.which == 17){
            var campos = $(document).find("input:visible");
            var indice = campos.index(event.target) + 1;
            if($this.val() === ''){
                event.stopPropagation();
                return false;
            }
            if($this.attr('id') === 'nota' || 
                $this.attr('id') === 'numero_nota' || 
                $this.attr('id') === 'numero_cupom' ||
                $this.attr('id') === 'data_emissao_cupom' ||
                $this.attr('id') === 'serie_cupom'){
                buscaNota();
            }
            var seletor = $(campos[indice]).focus();
            if (seletor.length == 0) {
                event.target.focus();
            }
        }
    });
    $(document).find("#estabelecimentos").on("change", function(event){
        buscaNota();
    });
    $(document).find("#estabelecimentos_cupom").on("change", function(event){
        buscaNota();
    });
});

function buscaNota(){
    var chave = $("#nota").val();
    var numero_nota = $("#numero_nota").val();
    var numero_cupom = $("#numero_cupom").val();
    var data_emissao_cupom = $("#data_emissao_cupom").val();
    var serie_cupom = $("#serie_cupom").val();
    var estabelecimentos = $("#estabelecimentos option:selected").val();
    var estabelecimentos_cupom = $("#estabelecimentos_cupom option:selected").val();
    var forma_busca = $("input[name='forma_busca']:checked").val();
    $(".error-message").remove();
    
    $.ajax({
        url: '{{ route('coleta_canhoto.busca_nota') }}',
        data: {
            _token : "{{ csrf_token() }}",
            chave: chave,
            numero_nota : numero_nota,
            estabelecimentos : estabelecimentos,
            forma_busca : forma_busca,
            estabelecimentos_cupom : estabelecimentos_cupom,
            numero_cupom : numero_cupom,
            data_emissao_cupom : data_emissao_cupom,
            serie_cupom : serie_cupom
        },
        method: 'POST',
        success: function(data){     
            if(data.status == 'success'){
                var estabelecimento = '';
                if(data.response.estabelecimento_codigo){
                    estabelecimento = data.response.estabelecimento_codigo+' - '+data.response.estabelecimento_descricao;
                }
                if(data.response.canhoto){
                    $(document).find("#div_foto").empty();
                    $(document).find("#div_foto").append("<a href=\""+data.response.canhoto+"\"  class='btn-foto-estoque thumb ml-2 mt-1' data-toggle='popover' data-trigger='hover' title='Foto Canhoto' data-content=\""+createBodyPopOver(data.response.canhoto)+"\"></a>");
                    popover();
                }else{
                    $(document).find("#div_foto").empty();
                }
                $(document).find("#cliente").val(data.response.cliente_nome);
                $(document).find("#estabelecimento").val(data.response.estabelecimento_codigo);
                $(document).find("#data_saida").val(data.response.datasaida);
                $(document).find("#emissao").val(data.response.emissao);
                $(document).find("#estabelecimento_descricao").val(estabelecimento);
                $(document).find("#numero").val(data.response.numero);
                $(document).find("#peso").val(data.response.pesoliquido);
                $(document).find("#documento_cliente").val(data.response.cliente_documento);
                $(document).find("#uuid_cupom").val(data.response.uuid);
            }else{
                $(document).find("#div_foto").empty();
                $(document).find("#cliente").val('');
                $(document).find("#estabelecimento").val('');
                $(document).find("#data_saida").val('');
                $(document).find("#emissao").val('');
                $(document).find("#estabelecimento_descricao").val('');
                $(document).find("#numero").val('');
                $(document).find("#peso").val('');
                $(document).find("#documento_cliente").val('');
                $(document).find("#uuid_cupom").val('');
                message('Atenção',data.message,'error');
            }
        },
        error: function(data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(field, errors[field]);
            }
        }
    });
}

function showErrorsInputs(input, message){
    var $input = $("input[name='"+input+"'],select[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('is-invalid');
}

function popover(){

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

    $(document).find("a.thumb").fancybox(
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

function filterAjax(){
    var form = $(document).find('#form_filter');
    var data = new FormData(form[0]);

    form.find('.error-message').remove();
    form.find('.error-input').removeClass('error-input');

    if($(document).find('#foto')[0].files[0] != undefined && $(document).find('#foto')[0].files[0].size > 3145728){
        message('Erro Upload', 'A imagem deve ser menor que 3MB','error');
        return;
    }

    $.ajax({
        url: '{{ route('coleta_canhoto.gravar') }}',
        dataType: 'json',
        data: data,
        processData: false,
        contentType: false,
        method: 'POST',
        success: function(data){     
            if(data.status === 'sucess'){
                $(document).find("#div_foto").empty();
                $('#form_filter')[0].reset();
                message('Atenção',data.message,'success');
            }else if(data.status === 'error'){
                message('Atenção',data.message,'error');
            }     
        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    form.find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                form.find('input.error').eq(0).focus();
            }
        }
    }).always(function() {
        hide_loader();
    });
}

function createBodyPopOver($this){
    var $return = "";
    $return = "<img src='"+$this+"' width='250' class='rounded mx-auto d-block' alt='Canhoto'>";
    return $return;
}
@endsection