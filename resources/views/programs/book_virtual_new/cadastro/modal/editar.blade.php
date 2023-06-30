@extends('layouts.page-dialog')

@section('content') 
<form action="" name="form_filter_itens_edt" class="cadPedido" id="form_filter_itens_edt" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('padrao_codigo', 'Padrão do Código', []) }}
            {{ Form::hidden('id', $dados['id']) }}
            {{ Form::select('padrao_codigo', $padroes_codigo, $dados['padrao_codigo'], ['id' => 'padrao_codigo', 'class' => 'form-control cad-grupo-form', 'placeholder' => 'Padrão do Código', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        Mostrar no book?
    </div>
    <div class="form-group col-sm-6">
        <div class="form-group  form-check form-check-inline">
            {{ Form::label('mostrar', 'Sim', ['class'=>'form-check-label']) }}
            {{ Form::radio('mostrar', true, ($dados['mostrar']  === true), ['id' => 'mostrar', 'class' => 'form-check-input']) }}
        </div>
        <div class="form-group  form-check form-check-inline">
            {!! Form::label('mostrar', 'Não', ['class'=>'form-check-label']) !!}
            {!! Form::radio('mostrar', false, ($dados['mostrar'] === false || $dados['mostrar'] === null), ['id' => 'mostrar', 'class' => 'form-check-input']) !!}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            {!! Form::label('imagem_grupo', 'Imagem do grupo') !!}
        </div>
        <div class="form-group col-sm-6">
            {!! Form::label('imagem_zoom', 'Imagem do zoom') !!}
        </div>
    </div>
    <div class="form-row">
        @if(!empty($dados['imagem']))
            <div class="form-group col-sm-3">
                <div data-toggle='tooltip' data-html='true' data-placement='right' title="Clique para expandir" class='img-container-thumbnail-300'>
                    <a href="{{ asset($dados['imagem']) }}" class="foto-thumb"> <img class="etiqueta-foto mt-2" src="{{ asset($dados['imagem']) }}" /></a>
                </div>
            </div>
        @endif
        <div class="form-group col-sm-3">
            {{ Form::file('imagem_grupo', ['id'=>'imagem_grupo', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) }}
        </div>
        @if(!empty($dados['imagem_zoom']))
            <div class="form-group col-sm-3">
                <div data-toggle='tooltip' data-html='true' data-placement='right' title="Clique para expandir" class='img-container-thumbnail-300'>
                    <a href="{{ asset($dados['imagem_zoom']) }}" class="foto-thumb"> <img class="etiqueta-foto mt-2" src="{{ asset($dados['imagem_zoom']) }}" /></a>
                </div>
            </div>
        @endif
        <div class="form-group col-sm-3">
            {{ Form::file('imagem_grupo_zoom', ['id'=>'imagem_grupo_zoom', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) }}
        </div>
	</div>
    <div class="col-sm-12 mb-3 d-none" id='div-desenhos'>
        <div class="content-dialog-table">
            Desenhos
            <div class="content-table">
                <table class="table table-striped" id="table-filters-desenhos">
                    <thead>
                        <tr>
                            <th>Código do desenho</th>
                            <th>Desenho zoom</th>
                            <th>Enviar desenho</th>
                            <th>Enviar desenho zoom</th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($desenhos as $desenho)
                            <tr>
                                <td>
                                    @if(isset($desenho['imagem']))
                                        <a href={!! $desenho['imagem'] !!} target='_blank' class='link-imagem'>{!! $desenho['codigo_desenho'] !!}</a>
                                    @else
                                        <span class="sem-link">{!! $desenho['codigo_desenho'] !!}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($desenho['imagem_zoom']))
                                        <a href={!! $desenho['imagem_zoom'] !!} target='_blank' class='link-imagem_zoom'>{!! $desenho['codigo_desenho'] !!}</a>
                                    @else
                                        <span class="sem-link">{!! $desenho['codigo_desenho'] !!}</span>
                                    @endif
                                </td>
                                <td>
                                    {!! Form::hidden('codigo_desenho_'.$desenho['codigo_desenho'], $desenho['codigo_desenho'], ['id' => 'codigo_desenho_'.$desenho['codigo_desenho']]) !!}
                                    {!! Form::file('imagem_'.$desenho['codigo_desenho'], ['id' => 'imagem_'.$desenho['codigo_desenho']]) !!}
                                    @if(isset($desenho['imagem']))
                                        {!! Form::button('Editar desenho', ['class' => 'btn btn-sm btn-success btn-editar-desenho', 'data-codigo' => $desenho['codigo_desenho'], "onclick" => "editarDesenho(this, " . $desenho['id'] . ")"]) !!}
                                    @else
                                        {!! Form::button('Salvar desenho', ['class' => 'btn btn-sm btn-success btn-salvar-desenho', 'data-codigo' => $desenho['codigo_desenho'], "onclick" => "salvarDesenho(this)"]) !!}
                                    @endif
                                </td>
                                <td>
                                    {!! Form::file('imagem_zoom_'.$desenho['codigo_desenho'], ['id' => 'imagem_zoom_'.$desenho['codigo_desenho']]) !!}
                                    @if(!empty($desenho['imagem']))
                                        {!! Form::button('Editar zoom', ['class' => 'btn btn-sm btn-success btn-editar-desenho', 'data-codigo' => $desenho['codigo_desenho'],  "onclick" => "editarDesenho(this, " . $desenho['id'] . ")"]) !!}
                                    @endif
                                </td>
                                @if(isset($desenho['imagem']))
                                    <td class="bt-delete" data-toggle="tooltip" data-html="true" title="Deletar Desenho" data-id="{{ $desenho["id"] }}" onclick="deletarDesenho($(this))"></td>
                                @else
                                    <td></td>
                                @endif
                                </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="content-dialog-table">
        <div class="content-table">
            <br>
            <table class="table table-striped table-filter-pedido-itens" id="table-filters-pedidos-itens">
                <thead>
                    <tr>
                        <th class="td_codigo_produto">Código</th>
                        <th>Descrição</th>
                        <th>SubGrupo</th>
                        <th>Marca</th>
                        <th>Linha</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($produtos))
                        @foreach ($produtos as $item)
                        <tr>
                            <td>{{ $item['codigo'] }}</td>
                            <td><div><div data-toggle="tooltip" data-html="true"  data-original-title="{{ $item['descricao'] }}">{{ $item['descricao'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true"  data-original-title="{{ $item['subgrupo'] }}">{{ $item['subgrupo'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true"  data-original-title="{{ $item['marca'] }}">{{ $item['marca'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true"  data-original-title="{{ $item['linha'] }}">{{ $item['linha'] }}</div></div></td>
                        </tr>
                        @endforeach
                        @endif
                </tbody>
            </table>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12"> 
                <button type="button" id="btn-salvar" class="btn btn-primary float-right">Salvar Todos</button>
            </div>
        </div>
    </div>
</form>
<script>
    table_produtos = '';
    itens = '';
    init();

    $(document).ready(function(){
        $(document).find(".foto-thumb").fancybox(
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

        form_modal_add = $(document).find('#form_filter_itens_edt');
        form_modal_add.find("#btn-salvar").on('click', function(){
            editarDados(form_modal_add.serialize());
        });

        esconderDivDesenhos();

        $(document).find('#padrao_codigo').on('change', function(){
            esconderDivDesenhos();
            gerarTabelaDesenhos();
        });

        

    });

    function init(){
        table_filters_produtos_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "35vh",
            "autoWidth": false,
            "drawCallback": function(settings) {
            },
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum produto inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum  produto inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
        };
        table_produtos = '';
        table_produtos = $(document).find('#table-filters-pedidos-itens').DataTable(table_filters_produtos_options);
        table_produtos.draw();
        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        table_filters_desenhos_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "35vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum desenho inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum desenho inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                {'targets': 'td_acao', 'class': 'td_acao', "orderable": false}
            ]
        };
        table_desenhos = $(document).find('#table-filters-desenhos').DataTable(table_filters_desenhos_options);
        table_desenhos.draw();
        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });
        
        $(document).find('#padrao_codigo').on('focus', function(){
            $(document).find('#padrao_codigo').data('old', $(document).find('#padrao_codigo').val());
        });
    }

    function editarDados(data_form_modalEdt){
        form_modal_edt = $(document).find('#form_filter_itens_edt');
        var formData = new FormData($(document).find('#form_filter_itens_edt')[0]); 
        var retorno = false;
        $.ajax({
            url: "{{ route('book_virtual_new.cadastro.editar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                retorno = true;
                $(form_modal_edt).parents('.modal').modal('hide');
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
            }
        });
        return retorno;
    }

    function limparMesagemErroEdt(){      
        var form_modal_add = $("#form_filter_itens_edt");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroEdt(json_error){
        var form_modal_add = $("#form_filter_itens_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdt(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsEdt(form_modal, input, message){
      
        if(input.localeCompare('id') == 0){
            var $input = $(form_modal).find("#form_filter_itens_edt");
            $(form_modal).find("input[name='id']").addClass('error-input');
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
        
    }

    function esconderDivDesenhos(){
        if($(document).find('#padrao_codigo').val()){
            $(document).find('#div-desenhos').removeClass('d-none');
        }
        else{
            $(document).find('#div-desenhos').addClass('d-none');
        }
    }
    

    function gerarTabelaDesenhos(){

        var codigos_desenhos = [];

        $(document).find('.link-imagem').each(function() {
            codigos_desenhos[$(this).html()] = $(this).html();
        });

        table_desenhos.rows($(document).find('.sem-link').parents('tr')).remove().draw();

        var desenhos = [];

        
        $(document).find('#table-filters-pedidos-itens').find('tbody').find('tr').has('td:not(.dataTables_empty)').each(function(){

            if(
                $(document).find('#padrao_codigo').val() == '6_10'
            ){

                if(
                    Object.values(codigos_desenhos).indexOf($(this).find('td:eq(0)').html().substring(6, 11)) < 0
                ){
                    if($(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(7, 12)+')').length > 0){
                        codigos_desenhos = codigos_desenhos.map(function(codigo) {
                            $this = $(this).find('td:eq(0)').html();
                            if($this !== undefined){
                                return codigo != $(this).find('td:eq(0)').html().substring(7, 12);
                            }
                        });

                        $(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(7, 12)+')').html($(this).find('td:eq(0)').html().substring(6, 11));

                        codigos_desenhos[$(this).find('td:eq(0)').html().substring(6, 11)] = $(this).find('td:eq(0)').html().substring(6, 11);
                    }
                    else if($(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(4, 10)+')').length > 0){

                        codigos_desenhos = codigos_desenhos.map(function(codigo) {
                            $this = $(this).find('td:eq(0)').html();
                            if($this !== undefined){
                                return codigo != $(this).find('td:eq(0)').html().substring(4, 10);
                            }
                        });

                        $(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(4, 10)+')').html($(this).find('td:eq(0)').html().substring(6, 11));

                        codigos_desenhos[$(this).find('td:eq(0)').html().substring(6, 11)] = $(this).find('td:eq(0)').html().substring(6, 11);
                    }
                    else if( Object.values(desenhos).indexOf($(this).find('td:eq(0)').html().substring(6, 11)) < 0 ){
                        desenhos[$(this).find('td:eq(0)').html().substring(6, 11)] = $(this).find('td:eq(0)').html().substring(6, 11);
                    }
                }
            }
            else if(
                $(document).find('#padrao_codigo').val() == '7_11'
            ){
                if(
                    Object.values(codigos_desenhos).indexOf($(this).find('td:eq(0)').html().substring(7, 12)) < 0
                ){
                    if($(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(6, 11)+')').length > 0){

                        codigos_desenhos = codigos_desenhos.map(function(codigo) {
                            $this = $(this).find('td:eq(0)').html();
                            if($this !== undefined){
                                return codigo != $(this).find('td:eq(0)').html().substring(6, 11);
                            }
                        });

                        $(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(6, 11)+')').html($(this).find('td:eq(0)').html().substring(7, 12));

                        codigos_desenhos[$(this).find('td:eq(0)').html().substring(7, 12)] = $(this).find('td:eq(0)').html().substring(7, 12);
                    }
                    else if($(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(4, 10)+')').length > 0){

                        codigos_desenhos = codigos_desenhos.map(function(codigo) {
                            $this = $(this).find('td:eq(0)').html();
                            if($this !== undefined){
                                return codigo != $(this).find('td:eq(0)').html().substring(4, 10);
                            }
                        });

                        $(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(4, 10)+')').html($(this).find('td:eq(0)').html().substring(7, 12));

                        codigos_desenhos[$(this).find('td:eq(0)').html().substring(7, 12)] = $(this).find('td:eq(0)').html().substring(7, 12);
                    }
                    else if( Object.values(desenhos).indexOf($(this).find('td:eq(0)').html().substring(7, 12)) < 0 ){
                        desenhos[$(this).find('td:eq(0)').html().substring(7, 12)] = $(this).find('td:eq(0)').html().substring(7, 12);
                    }
                }
            }
            else if(
                $(document).find('#padrao_codigo').val() == '4_9'
            ){
                if(
                    Object.values(codigos_desenhos).indexOf($(this).find('td:eq(0)').html().substring(4, 10)) < 0
                ){
                    if($(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(6, 11)+')').length > 0){

                        codigos_desenhos = codigos_desenhos.map(function(codigo) {
                            $this = $(this).find('td:eq(0)').html();
                            if($this !== undefined){
                                return codigo != $(this).find('td:eq(0)').html().substring(6, 11);
                            }

                        });

                        $(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(6, 11)+')').html($(this).find('td:eq(0)').html().substring(4, 10));

                        codigos_desenhos[$(this).find('td:eq(0)').html().substring(4, 10)] = $(this).find('td:eq(0)').html().substring(4, 10);
                    }
                    else if($(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(7, 12)+')').length > 0){
                        codigos_desenhos = codigos_desenhos.map(function(codigo) {
                            $this = $(this).find('td:eq(0)').html();
                            if($this !== undefined){
                                return codigo != $(this).find('td:eq(0)').html().substring(7, 12);
                            }

                        });

                        $(document).find('.link-imagem:contains('+$(this).find('td:eq(0)').html().substring(7, 12)+')').html($(this).find('td:eq(0)').html().substring(4, 10));

                        codigos_desenhos[$(this).find('td:eq(0)').html().substring(4, 10)] = $(this).find('td:eq(0)').html().substring(4, 10);
                    }
                    else if( Object.values(desenhos).indexOf($(this).find('td:eq(0)').html().substring(7, 12)) < 0 ){
                        desenhos[$(this).find('td:eq(0)').html().substring(4, 10)] = $(this).find('td:eq(0)').html().substring(4, 10);
                    }
                }
            }
        });

        x = codigos_desenhos.length;

        Object.values(desenhos).forEach(function(item){
            table_desenhos.row.add([
                "<span class=\"sem-link\">"+ item +"</span>",
                "<span class=\"sem-link\">"+ item +"</span>",
                createFormNovaImagem("{!! $id !!}", item),
                enviarDesenhoZoomAdd(),
                semIconeDeletar(),
            ]);
        });

        table_desenhos.draw();
    }

    function salvarDesenho(elemento){

        var form = $(elemento).parents('form');
        var row = $(elemento).parents('tr');
        var data = new FormData(form[0]);
        var codigo = $(elemento).data('codigo');

        data.append('id', '{!! $id !!}');
        data.append('codigo', codigo);
        data.append('_token', '{!! csrf_token() !!}');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: "{{ route('book_virtual_new.desenho.salvar') }}", 
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                table_desenhos.row(row).remove();
                table_desenhos.row.add([
                        createLinkImagem(callback.response.codigo_desenho,callback.response.imagem),
                        createLinkSemImagem(callback.response.codigo_desenho),
                        createFormEditarImagem(callback.response.id, callback.response.codigo_desenho),
                        enviarDesenhoZoomEdit(callback.response.id, callback.response.codigo_desenho),
                        iconeDeletar(callback.response.id)
                    ]).draw();
                form.find('.link-imagem').val('');
                form.find('#imagem').val('');
                    
            },
            error: function(callback){
                var dados = callback.responseJSON.error;
                for(var field in dados){
                    showErrorsInputsEdt(form, field, dados[field])
                }
            }
        });
    }

    function createLinkImagem($codigo, $url){
        var html = "<a href='"+$url+"' target='_blank' class='link-imagem'>"+$codigo+"</a>";
        return html
    }

    function createLinkImagemZoom($codigo, $url){
        var html = '';

        if($url.length > 0){
            html = "<a href='"+$url+"' target='_blank' class='link-imagem'>"+$codigo+"</a>";
        }else{
            html = "<span class=\"sem-link\">"+$codigo+"</span>";
        }
        return html
    }

    function createLinkSemImagem($codigo){
        var html = "<span class=\"sem-link\">"+$codigo+"</span>";
        return html
    }

    function createFormNovaImagem($id, $codigo_desenho){
        var html = "<input name=\"codigo_desenho_"+$codigo_desenho+"\" id=\"codigo_desenho_"+$codigo_desenho+"\" type=\"hidden\" value=\""+$codigo_desenho+"\" autocomplete=\"off\"><input class=\"imagem\" name=\"imagem__"+$codigo_desenho+"\" id=\"imagem__"+$codigo_desenho+"\" type=\"file\" autocomplete=\"off\"><button class=\"btn btn-sm btn-success btn-salvar-desenho\" type=\"button\" data-codigo=\""+$codigo_desenho+"\" onclick=\"salvarDesenho(this)\">Salvar desenho</button></form>";

        return html;
    }

    function createFormEditarImagem($id, $codigo_desenho){
        var html = "<input name=\"codigo_desenho_"+$codigo_desenho+"\" id=\"codigo_desenho_"+$codigo_desenho+"\" type=\"hidden\" value=\""+$codigo_desenho+"\" autocomplete=\"off\"><input class=\"imagem\" name=\"imagem__"+$codigo_desenho+"\" id=\"imagem__"+$codigo_desenho+"\" type=\"file\" autocomplete=\"off\"><button class=\"btn btn-sm btn-success btn-editar-desenho\" type=\"button\" data-codigo=\""+$codigo_desenho+"\" onclick=\"editarDesenho(this, "+$id+")\">Editar desenho</button></form>";

        return html;
    }

    function iconeDeletar($id){
        var html = "<div class=\"bt-delete\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Deletar Desenho\" data-id=\""+$id+"\" onclick=\"deletarDesenho($(this))\">";
        return html;
    }
    
    function semIconeDeletar(){
        var html = '';
        return html;
    }

    function enviarDesenhoZoomEdit($id, $codigo_desenho){
        var html = "<input name=\"codigo_desenho_"+$codigo_desenho+"\" id=\"codigo_desenho_"+$codigo_desenho+"\" type=\"hidden\" value=\""+$codigo_desenho+"\" autocomplete=\"off\"><input class=\"imagem_zoom\" name=\"imagem_zoom_"+$codigo_desenho+"\" id=\"imagem_zoom_"+$codigo_desenho+"\" type=\"file\" autocomplete=\"off\"><button class=\"btn btn-sm btn-success btn-editar-desenho\" type=\"button\" data-codigo=\""+$codigo_desenho+"\" onclick=\"editarDesenho(this, "+$id+")\">Editar zoom</button></form>";
        return html;
    }

    function enviarDesenhoZoomAdd(){
        var html = "";
        return html;
    }

    function editarDesenho(elemento, $id){

        var form = $(elemento).parents('form');
        var row = $(elemento).parents('tr');
        var codigo = $(elemento).data('codigo');

        var data = new FormData(form[0]);

        data.append('id', $id);
        data.append('codigo', codigo);
        data.append('_token', '{!! csrf_token() !!}');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: "{{ route('book_virtual_new.desenho.editar') }}", 
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                table_desenhos.row(row).remove();
                table_desenhos.row.add([
                        createLinkImagem(callback.response.codigo_desenho,callback.response.imagem),
                        createLinkImagemZoom(callback.response.codigo_desenho,callback.response.imagem_zoom),
                        createFormEditarImagem(callback.response.id, callback.response.codigo_desenho),
                        enviarDesenhoZoomEdit(callback.response.id, callback.response.codigo_desenho),
                        iconeDeletar(callback.response.id)
                    ]).draw();
                
                form.find('.link-imagem').val('');
                form.find('#imagem_'+codigo).val('');
                form.find('#imagem_zoom_'+codigo).val('');
                    
            },
            error: function(callback){
                var dados = callback.responseJSON.error;
                for(var field in dados){
                    showErrorsInputsEdt(form, field, dados[field])
                }
            }
        });
    }

    function deletarDesenho(elemento){

        var form = $(elemento).parents('form');
        var row = $(elemento).parents('tr');
        
        $.ajax({
            url: "{{ route('book_virtual_new.desenho.deletar') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: $(elemento).data('id')
            },
            success: function(callback){
                table_desenhos.row(row).remove();
                table_desenhos.row.add([
                        createLinkSemImagem(callback.response.codigo_desenho),
                        createLinkSemImagem(callback.response.codigo_desenho),
                        createFormNovaImagem(callback.response.id, callback.response.codigo_desenho),
                        enviarDesenhoZoomAdd(callback.response.id, callback.response.codigo_desenho),
                        semIconeDeletar()
                    ]).draw();
                form.find('.link-imagem').val('');
                form.find('#imagem').val('');
            },
			error: function(callback){
				var dados = callback.responseJSON.error;
                for(var field in dados){
                    showErrorsInputsEdt(form, field, dados[field])
                }
			}
        });
    }
    function isNumber(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

</script>
@endsection
