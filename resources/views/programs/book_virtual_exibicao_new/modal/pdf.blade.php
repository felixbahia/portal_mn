@extends('layouts.page-dialog')
@section('content')
<form name="form_enviar_pdf" id="form_enviar_pdf">
    <div class="row">
        <div class="form-group col-sm-12">
            @csrf
            {{ Form::hidden('segmentos_id', $segmentos_id, ['id' => 'segmentos_id']) }}
            {{ Form::hidden('produto_grupos_id', $produto_grupos_id, ['id' => 'produto_grupos_id']) }}
            {{ Form::hidden('desenho', $desenho, ['id' => 'desenho']) }}
            {{ Form::hidden('quantidade_arquivos', $quantidade_arquivos, ['id' => 'quantidade_arquivos']) }}
        </div> 
        <div class="form-group col-md-6">
            Tamanho do arquivo <br/>{{ $tamanho_arquivo }}
        </div>
        @if(!empty($quantidade_arquivos))
            <div class="form-group col-md-6">
                 Quantidade de PDF's<br/>{{ $quantidade_arquivos }} Arquivos<br/>
            </div>
        @endif
    </div>
    <div class="form-group row">
        <div class="col-md-6">
            <a href="{{ $route }}"><i class="bt-download"></i></a> Download
        </div>
        <div class="col-md-6">
            <a href="#" id="btn_email"><i class="bt-email"></i></a> Email
        </div>
    </div>
</form>
<div class="form-group row">
    <div class="col-md-12">
        <sapn style="color:red; font: size 5px;">
            <b>Função LINK disponível:</b> agora você pode copiar a URL do documento sem precisar abrir uma nova
            aba. Basta clicar na opção LINK abaixo e colar no campo desajado para compartilhar o PDF do book virtual.
        </sapn>
    </div>
</div>
<div class="form-group row">
    <div class="form-group col-md-10">        
        {{ Form::text('link', $link_pdf, ['id' => 'link', 'class' => 'form-control', 'readonly']) }}
    </div>
    <div class="col-md-2">
        <button type="button" id="bt_copiar"  class="btn btn-secondary float-right">Copiar</button>
    </div>
</div>
<script>
    $(document).ready( function(){
        form = $(document).find("#form_enviar_pdf");

        $(document).find('#btn_email').off('click');
        $(document).find('#btn_email').on('click', function(event){
            event.stopPropagation();
            modalEnviarPDF(form);
        })
    });

    function modalEnviarPDF($this){
        xhr = $.ajax({
            url: '{{ route("book_virtual_exibicao_new.modal.enviar_pdf") }}',
            data: form.serialize(),
            method: 'POST',
            success: function(body){
                createModal("modal_enviar_pdf", "{{ $descricao }}", body, '');
            },
            error: function(body){
                message("Atenção", body.responseJSON.message);
            }
        });
    }

    jQuery(document).ready(function(){

        var btn = jQuery('#bt_copiar').attr('type', 'button');
    
        var conteudo = '#link';
    
        /*FUNÇÃO QUE COPIA*/
        jQuery(btn).click(function(){
            jQuery(conteudo).select();
            document.execCommand('copy');
            jQuery(this).text('Copiado!');
            jQuery(this).css('background-color','black');
            jQuery(this).css('color','white');
            setTimeout(function(){
                jQuery(btn).text('Copiar');
                jQuery(btn).css('background-color','#5A6268');
                jQuery(btn).css('color','white');
            },3000);
        });
    });

</script>
@endsection
