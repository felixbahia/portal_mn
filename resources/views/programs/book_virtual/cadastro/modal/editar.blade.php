@extends('layouts.page-dialog')

@section('content') 
<form action="" name="form_filter_itens_edt" class="cadPedido" id="form_filter_itens_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('itens', $itens,['id' => 'itens']) !!}
    {{ Form::hidden('id', $id) }}
    <div class="form-row">
        <div class="form-group col-sm-4"> 
            {{ Form::label('book_virtual', 'Book Virtual', []) }}
            {{ Form::text('book_virtual', $book['num_book'], ['id' => 'book_virtual', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Book Virtual', 'maxlength' => '250']) }}
        </div>
        <div class="form-group col-sm-4">
            {{ Form::label('padrao_codigo', 'Padrão do código') }}
            {{ Form::select('padrao_codigo', $padroes_codigo, $book['padrao_codigo'], ['data-old' => $book['padrao_codigo'], 'id' => 'padrao_codigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => '']) }}
        </div>
        <div class="form-group col-sm-4"> 
            {{ Form::label('artigo', 'Artigo', []) }}
            {{ Form::text('artigo', $book['artigo'], ['id' => 'artigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Artigo', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('nome_artigo', 'Nome do Artigo', []) }}
            {{ Form::text('nome_artigo', $book['nome'], ['id' => 'nome_artigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Nome do Artigo', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('pecas', 'Peças de', []) }}
            {{ Form::text('pecas', $book['pecas'], ['id' => 'pecas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Peças de', 'maxlength' => '250']) }}
        </div>
    </div>
</form>

<div class="row">
    <div class="col-sm-6">
        <div class="row">
            <div class="form-group col-sm-12 ml-3"> 
                <form action="" name="form-instrucao-lavagem" id="form-instrucao-lavagem">
                    <div class="row">
                        @csrf
                        {{ Form::hidden('id', $id) }}

                        {{ Form::label('nome', 'Instruções de Lavagem') }}
                        <div id="div-instrucoes-lavagem" class="img-edit-model @if (empty($book["thumb_instrucoes_lavagem"])) d-none @endif">
                            <a id='link-instrucoes-lavagem' href="{{ asset($book["img_instrucoes_lavagem"]) }}" class='thumb-instrucao'> <img id="img-instrucoes-lavagem" src="{{ asset($book["thumb_instrucoes_lavagem"]) }}" border="0" alt="" /></a>
                        </div>
                        {{ Form::hidden('icon_temp', $book["img_instrucoes_lavagem"]) }}
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            {{ Form::file('instrucoes_lavagem', ['id'=>'instrucoes_lavagem', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) }}
                        </div>
                        <div class="col-sm-6">
                            {!! Form::button('Salvar instruções de lavagem', ['id' => 'btn-salvar-instrucoes-lavagem', 'class' => 'btn btn-sm btn-success']) !!}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-6 mb-3 d-none" id='div-desenhos'>
        <div class="content-dialog-table">
            Desenhos
            <div class="content-table">
                <table class="table table-striped" id="table-filters-desenhos">
                    <thead>
                        <tr>
                            <th>Código do desenho</th>
                            <th>Enviar desenho</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($desenhos as $key => $desenho)
                        <tr>
                            <td>
                                @if(isset($desenho['imagem']))
                                <a href={!! $desenho['imagem'] !!} target='_blank' class='link-imagem'>{!! $desenho['codigo_desenho'] !!}</a>
                                @else
                                <span class="sem-link">{!! $desenho['codigo_desenho'] !!}</span>
                                @endif
                            </td>
                            <td>
                                <form action="" name="form-desenho-{{ $key }}" onsubmit="return false;">
                                    {!! Form::hidden('codigo_desenho', $desenho['codigo_desenho']) !!}
                                    {!! Form::file('imagem', ['class' => 'imagem']) !!}
                                    @if(isset($desenho['imagem']))
                                    {!! Form::button('Editar desenho', ['class' => 'btn btn-sm btn-success btn-editar-desenho', "onclick" => "editarDesenho(this, " . $desenho['id'] . ")"]) !!}
                                    @else
                                    {!! Form::button('Salvar desenho', ['class' => 'btn btn-sm btn-success btn-salvar-desenho', "onclick" => "salvarDesenho(this)"]) !!}
                                    @endif
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="div-desenho-tamanho-real" class="d-none">
        <div class="title">Imagem em tamanho real</div>
        <div id="desenho-tamanho-real-link" class='float-left mt-1 mr-3'>
            {!! $book['imagem_tamanho_real'] !!}
        </div>
        <div class='float-left'>
            <form action="" name="form-desenho-tamanho-real" onsubmit="return false;">
                @csrf
                {{ Form::hidden('id', $id) }}
                {!! Form::file('imagem_tamanho_real', ['class' => 'imagem_tamanho_real', 'id' => 'imagem_tamanho_real']) !!}
                {!! Form::button('Salvar desenho', ['class' => 'btn btn-sm btn-success btn-salvar-desenho', "onclick" => "salvarDesenhoTamanhoReal(this)"]) !!}
            </form>
        </div>
    </div>
</div>
<form action="" id="form_produtos_edt" onsubmit='return false'>
    @csrf
    <div class="content-filter-dialog">	
        <div class="form-row">
            <div class="form-group col-sm-2">
                {{ Form::label('codigo_produto', 'Código do produto', []) }}
                <div class="input-group" id="cod_produto_group">
                    {{ Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="form-group col-sm-3">
                {{ Form::label('descricao', 'Descrição', []) }}
                <div class="input-group" id="cod_produto_group">
                    {{ Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Nome do Produto', "maxlength" => "250"]) }}
                </div>
            </div>
            <div class="form-group col-sm-3">
                {{ Form::label('grupo', 'Grupo', []) }}
                {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control cad-book_virtual-form','placeholder' => 'Grupo', 'maxlength' => '250', 'disabled']) }}
            </div>
            <div class="form-group col-sm-2">
                {{ Form::label('marca', 'Marca', []) }}
                {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Marca', 'maxlength' => '250', 'disabled']) }}
            </div>
            <div class="form-group col-sm-2">
                {{ Form::label('linha', 'Linha', []) }}
                {{ Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Linha', 'maxlength' => '250', 'disabled']) }}
            </div>
        </div>
        <div class="content-buttons">
            {{ Form::button('Inserir Produto', array('class' => 'btn btn-success float-right', 'id' => 'btn-create-itens-pedido')) }}
        </div>
    </div>
</form>

    <div class="content-dialog-table">
        <div class="content-table" id="table-filter-pedido">
            <br>
            <table class="table table-striped table-filter-pedido-itens" id="table-filters-pedidos-itens-edt">
                <thead>
                    <tr>
                        <th class="td_codigo_produto">Código</th>
                        <th>Descrição</th>
                        <th>Grupo</th>
                        <th>Marca</th>
                        <th>Linha</th>
                        <th class="td_acao"></th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($dados))
                        @foreach ($dados as $item)
                        <tr>
                            <td>{{ $item['codigo'] }} {!! $item['foto'] !!}</td>
                            <td>{!! $item['descricao'] !!}</td>
                            <td>{!! $item['grupo'] !!}</td>
                            <td>{!! $item['marca'] !!}</td>
                            <td>{{ $item['linha'] }}</td>
                            <td><a href="#" class="bt-delete" title='Excluir' onclick="produtoExcluir($(this).parents('tr'), '{{ trim($item['codigo']) }}')"></a></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="col-sm-12 mt-5" id="button-bottom">
            <button type="button" id="btn-salvar-edt" class="btn btn-primary float-right">Salvar</button>
        </div>
    </div>
</div>
<script>
    table_produtos = '';
    itens = '';
    initEdt();

    function initEdt(){
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
                $(document).find('.estoque, .preco, #total_pedido').popover({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'manual'
                });
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
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                {
                    'targets': 'td_foto',
                    'width': '150px',
                    'height': '150px'
                },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                },
                {
                    'targets': ['td_quantidade', 'td_preco', 'td_total'],
                    'width': '120px'
                },
                {
                    'targets': ['td_comissao', 'td_coluna'],
                    'width': '50px'
                },
                {
                    'targets': 'td_codigo_produto',
                    'width': '100px'
                },
                
            ],
            "order": [[ 1, 'asc' ]]
        };
        table_filters_desenhos_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "10vh",
            "autoWidth": false,
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
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" }
            ]
        };
        table_produtos = '';
        table_produtos = $(document).find('#table-filters-pedidos-itens-edt').DataTable(table_filters_produtos_options);
        table_desenhos = $(document).find('#table-filters-desenhos').DataTable(table_filters_desenhos_options);
        table_produtos.draw();
        table_desenhos.draw();
        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        $(document).find('#tipo_material').on('change', function(){
            if($(this).val() == 'estampados'){
                $(document).find('#padrao_codigo').parent().removeClass('d-none');
                $(document).find('#padrao_codigo').val('7_11');
            }
            else if($(this).val() == 'fio_tinto'){
                $(document).find('#padrao_codigo').parent().removeClass('d-none');
                $(document).find('#padrao_codigo').val('6_10');
            }
            else if($('#segmento option:selected').text() == 'CONFECCIONADOS'){
                $(document).find('#padrao_codigo').parent().removeClass('d-none');
                $(document).find('#padrao_codigo').val('4_9');
            }
            else{
                $(document).find('#padrao_codigo').val('');
                $(document).find('#padrao_codigo').parent().addClass('d-none');
            }

        });

        $(document).find('#segmento').on('change', function(){
            if($(this).find('option:selected').text() == 'CONFECCIONADOS'){
                $(document).find('#padrao_codigo').parent().removeClass('d-none');
                $(document).find('#padrao_codigo').val('4_9');
            }
        });

        $(document).find('#padrao_codigo').on('focus', function(){
            $(document).find('#padrao_codigo').data('old', $(document).find('#padrao_codigo').val());
        });

    }


    $(document).ready( function () {

        $(document).find('a.thumb-instrucao').fancybox();
        $(document).find('a.thumb').fancybox(
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

        $(document).find("#btn-salvar-edt").off('click');
        $(document).find("#btn-salvar-edt").on('click', function(){
            editarDados();
        });

        form_produtos_edt = $(document).find('#form_produtos_edt');

        form_produtos_edt.find("#bt-search-produto").on('click', function(){
            var dados_error = form_produtos_edt.find('.error-input');
            if(dados_error.length > 0){
                form_produtos_edt.find('#codigo_produto').val('');
                form_produtos_edt.find('#descricao').val('');
                form_produtos_edt.find('#grupo').val('');
            }
            showModalProdutoEdt(form_produtos_edt);
        })

        $(document).find("#btn-create-itens-pedido").off("click");
        $(document).find("#btn-create-itens-pedido").on("click", function () {
            if (form_produtos_edt.find("#codigo_produto").val() != ''){
                salvarProdutoNoPedidoEdt();
                limparCamposEdt();
            }
        });

        form_produtos_edt.find("#descricao").autocomplete(optionsAutoCompleteEdt("nome"));

        form_produtos_edt.find('#codigo_produto').blur(function(){
            pesquisaProdutoCodigoEdt(form_produtos_edt);
        });

        form_produtos_edt.find('#descricao').change(function() {
            limparMesagemErroEdt();
            pesquisaProdutoDescricaoEdt(form_produtos_edt);
        });

        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        $(document).find('#btn-salvar-instrucoes-lavagem').on('click', function(){
            salvarInstrucaoLavagem();
        });

        esconderDivDesenhos();

        $(document).find('#tipo_material, #padrao_codigo, #segmento').on('change', function(){
            esconderDivDesenhos();
            gerarTabelaDesenhos();
        });
    });

    function editarDados(){

        limparMesagemErroEdt();

        var form_modal_edt = $(document).find('#form_filter_itens_edt');
        var data_form_modal_edt = form_modal_edt.serialize()

        var erro_imagem = 0;
        var erro = 0;
        
        $(document).find("#table-filters-pedidos-itens-edt").find('tbody').find('tr').each(function(i){

            if($(document).find('#padrao_codigo').val() != ''){
                if( 
                    $(document).find('#padrao_codigo').val() == '7_11' ||
                    ($(document).find('#padrao_codigo').val() == '' && $(document).find('#tipo_material').val() == 'estampados')
                ){
                    var artigo = $(this).find('td').eq(0).html().substring(0, 7).toString();
                    var desenho = $(this).find('td').eq(0).html().substring(7, 12).toString();
                }
                else if(
                    $(document).find('#padrao_codigo').val() == '6_10' ||
                    ($(document).find('#padrao_codigo').val() == '' && $(document).find('#tipo_material').val() == 'fio_tinto')
                ){
                    var artigo = $(this).find('td').eq(0).html().substring(0, 6).toString();
                    var desenho = $(this).find('td').eq(0).html().substring(6, 11).toString();
                }
                else if(
                    $(document).find('#padrao_codigo').val() == '4_9' ||
                    ($(document).find('#padrao_codigo').val() == '' && $(document).find('#segmento label:selected').text() == 'CONFECCIONADOS')
                ){
                    var artigo = $(this).find('td').eq(0).html().substring(0, 4).toString();
                    var desenho = $(this).find('td').eq(0).html().substring(4, 9).toString();
                }

                if(artigo != $(document).find('#artigo').val()){
                    showErrorsInputsEdt(form_modal_edt, 'itens.'+i, '');
                    erro++;
                }
            }
        });

        if(erro > 0){
            showErrorsInputsEdt(form_modal_edt, 'artigo', 'Itens inválidos, não pertencem a este artigo');
            $(document).find("#table-filters-pedidos-itens-edt").after("<label class='error-message'>Itens inválidos, não pertencem a este artigo</label>");
        }

        if(erro > 0 || erro_imagem > 0){
            return false;
        }

        data_form_modal_edt = form_modal_edt.serialize();
        var formData = new FormData($(document).find('#form_filter_itens_edt')[0]);
        var retorno = false;
        $.ajax({
            url: "{{ route('book_virtual.cadastro.editar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                retorno = true;
                $(form_modal_edt).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                mensagemErroEdt(dados);
            }
        });
        return retorno;
    }

    function showModalProdutoEdt(form_modal_edt){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {

                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProdutoEdt($(this), form_modal_edt);
                    });

                });
            }
        });
    }

    function returnDadosProdutoEdt($dados, form_modal_edt){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){  
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_edt.find('#codigo_produto').val($dados.find("td").eq(1).text());
        pesquisaProdutoCodigoEdt(form_modal_edt);
    };

    function pesquisaProdutoCodigoEdt(form_modal_edt){
        limparMesagemErroEdt(false);
        data_form_modal_edt = form_modal_edt.serialize();
        $.ajax({
            url: '{{ route('produto.pesquisaprodutocodigoespecificacao')}}',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoEdt(produto, form_modal_edt);
            },
            error: function(callback){
                if(form_modal_edt.find('#codigo_produto').val() == ''){
                    var dados = callback.responseJSON;
                    form_modal_edt.find('#codigo_produto').focus();
                    mensagemErroEdt(dados);
                }
            }
        });
    }

    function dadosRetornoEdt(produto, form_modal_edt){
        form_modal_edt.find('#codigo_produto').val(produto['codigo_produto']);
        form_modal_edt.find('#descricao').val(produto['descricao']);
        form_modal_edt.find('#grupo').val(produto['grupo']);
        form_modal_edt.find('#marca').val(produto['marca']);
        form_modal_edt.find('#linha').val(produto['linha']);
    }

    function limparMesagemErroEdt(){      
        var form_modal_edt = $(document);
        form_modal_edt.find('.error-message').remove();
        form_modal_edt.find('input, select, span, td, table').removeClass('error-input');
    }

    function mensagemErroEdt(json_error){
        var form_modal_edt = $("#form_filter_itens_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdt(form_modal_edt, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsEdt(form_modal_edt, input, message){
        if(input.localeCompare('itens') == 0){
            var $input = $(form_modal_edt).find("input[name='descricao'], select[name='descricao']");
            message = 'Não há itens adicionado na tabela'
        }
        else if(input.startsWith('itens')){
            var explode = input.split('.');
            $(document).find("#table-filters-pedidos-itens-edt").find('tbody').find("tr").eq(explode[1]).find('td').addClass('error-input');
            return;
        }
        else if(input == 'imagem_tamanho_real'){
            var $input = $(document).find('#imagem_tamanho_real')
        }
        else if(input == 'desenhos'){
            for(var item in message){
                $(document).find("input[value='"+message[item]+"']").parents('tr').find('td').addClass('error-input');
            }
            var $input = $(document).find('#div-desenhos').find('table');
            var message = 'Há desenhos que não foram cadastrados'
        }
        else{
            var $input = $(form_modal_edt).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function salvarProdutoNoPedidoEdt(){
        form = $(document).find("#form_produtos_edt");
    
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
    
        $(document).find('.pedido-item-form').blur();
        var codigo = form.find("#codigo_produto").val();
        var itens = $(document).find("#form_filter_itens_edt").find("#itens").val();
        $.ajax({
            url: '{{ Route("book_virtual.item.adicionar") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item: itens,
                cod_produto: codigo
                
            },
            success: function(data) {
    
                var field = [
                    data.response.codigo + ' ' + data.response.foto,
                    data.response.descricao,
                    data.response.grupo,
                    data.response.marca,
                    data.response.linha,
                    createBtExcluirProduto(data)
                ];
                
                table_produtos.row.add(field).draw();

                dados_produto = field;
    
                limparCamposEdt();
                $(document).find("#form_filter_itens_edt").find("#itens").val(data.response.itens);
                $(document).find('a.thumb').fancybox(
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


                $('[data-toggle="popover"]').off('show.bs.popover');
                $('[data-toggle="popover"]').popover('hide');

                $('[data-toggle="popover"]').popover({
                    container: 'body',
                    html: true,
                    show: true,
                    template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                });

                var codigos_desenhos = $(document).find('.link-imagem').map(function() {
                    return $(this).html();
                }).get();

                codigos_desenhos = codigos_desenhos.concat($(document).find('.sem-link').map(function() {
                    return $(this).html();
                }).get());

                if(!codigos_desenhos.includes(data.response.codigo.substring(7, 12)) && ($(document).find('#padrao_codigo').val() == '7_11' || ($(document).find('#padrao_codigo').val() == '' && $(document).find('#tipo_material').val() == 'estampados'))){
                    table_desenhos.row.add([
                        "<span class=\"sem-link\">"+data.response.codigo.substring(7, 12)+"</span>",
                        createFormNovaImagem("{!! $id !!}", codigos_desenhos.length, data.response.codigo.substring(7, 12))
                    ]).draw();
                }
                else if(!codigos_desenhos.includes(data.response.codigo.substring(6, 11)) && ($(document).find('#padrao_codigo').val() == '6_10' || ($(document).find('#padrao_codigo').val() == '' && $(document).find('#tipo_material').val() == 'fio_tinto'))){
                    table_desenhos.row.add([
                        "<span class=\"sem-link\">"+data.response.codigo.substring(6, 11)+"</span>",
                        createFormNovaImagem("{!! $id !!}", codigos_desenhos.length, data.response.codigo.substring(6, 11))
                    ]).draw();
                }
                else if(!codigos_desenhos.includes(data.response.codigo.substring(4, 10)) && ($(document).find('#padrao_codigo').val() == '4_9' || ($(document).find('#padrao_codigo').val() == '' && $(document).find('#segmento').find('option:selected').text() == 'CONFECCIONADOS'))){
                    table_desenhos.row.add([
                        "<span class=\"sem-link\">"+data.response.codigo.substring(4, 10)+"</span>",
                        createFormNovaImagem("{!! $id !!}", codigos_desenhos.length, data.response.codigo.substring(4, 10))
                    ]).draw();
                }
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
            }
        }).always( function(){
                limparCamposEdt();
                $(document).find('#descricao').focus();
            }
        );
    }

    function createBtExcluirProduto($this){
        var html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' onclick=\"produtoExcluir($(this).parents('tr'), '"+$this.response.codigo+"')\"></a>";

        return html;
    }
    
    function produtoExcluir(obj, $codigo){
        form = $(document).find("#form_filter_itens_edt");
    
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
    
        $(document).find('.pedido-item-form').blur();
        var itens = form.find("#itens").val();
        $.ajax({
            url: '{{ Route("book_virtual.item.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                codigo: $codigo,
                item: itens
            },
            success: function(data){
                table_produtos.row(obj).remove().draw();
                $(document).find("#form_filter_itens_edt").find("#itens").val(data.response);
            }
        });
    }

    function optionsAutoCompleteEdt($name){
        
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_book_virtual_cadastro_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {  
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum produto encontrado');
                    event.stopPropagation();
                    event.target.focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    event.stopPropagation();
                    form_modal_edt = $(document).find('#form_produtos_edt');
                    pesquisaProdutoDescricaoEdt(form_modal_edt);
                    switch($name){
                        case 'grupo':
                            form_modal_edt.find('#codigo_produto').focus();
                            break;
                        default:
                            form_modal_edt.find('#cliente').focus();
                            break;
                    }
                    limparMesagemErroEdt(false);
                }, 100);
            }
        };
    }

    function pesquisaProdutoCodigoEdt(form_modal_edt){
        limparMesagemErroEdt(false);
        data_form_modal_edt = form_modal_edt.serialize();

        $.ajax({
            url: '{{ route('produto.pesquisaprodutocodigoespecificacao')}}',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoEdt(produto, form_modal_edt);
            },
            error: function(callback){
                if(form_modal_edt.find('#codigo_produto').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_edt.find('#codigo_produto').focus();
                    mensagemErroEdt(dados);
                }
                limparCamposEdt();
            }
        });
    }

    function pesquisaProdutoDescricaoEdt(){
        form_modal_edt = $(document).find('#form_produtos_edt');
        data_form_modal_edt = form_modal_edt.serialize();
        $.ajax({
            url: '{{ route('produto.pesquisaprodutodescricaoespecificacao')}}',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoEdt(produto, form_modal_edt);
            },
            error: function(callback){
                if(form_modal_edt.find('#descricao').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_edt.find('#descricao').focus();
                    mensagemErroEdt(dados);
                }
                limparCamposEdt();
            }
        });
    }

    function limparCamposEdt(){
        form_modal_edt = $(document).find('#form_produtos_edt');

        form_modal_edt.find('#codigo_produto').val('');
        form_modal_edt.find('#descricao').val('');
        form_modal_edt.find('#grupo').val('');
        form_modal_edt.find('#marca').val('');
        form_modal_edt.find('#linha').val('');
    }

    function salvarDesenho(elemento){

        var form = $(elemento).parents('form');
        var row = $(elemento).parents('tr');
        var key = form.prop('name').split('-')[2];
        
        var data = new FormData(form[0]);

        data.append('id', '{!! $id !!}');
        data.append('_token', '{!! csrf_token() !!}');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        if(form.find('.imagem')[0].files[0] != undefined && form.find('.imagem')[0].files[0].size > 2097152){
            showErrorsInputsEdt(form, 'imagem', 'A imagem deve ser menor que 2MB');
            return;
        }

        $.ajax({
            url: "{{ route('book_virtual.desenho.salvar') }}", 
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
                        createFormEditarImagem(callback.response.id, key, callback.response.codigo_desenho)
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


    function editarDesenho(elemento, $id){

        var form = $(elemento).parents('form');
        var row = $(elemento).parents('tr');
        var key = form.prop('name').split('-')[2];
        
        var data = new FormData(form[0]);

        data.append('id', $id);
        data.append('_token', '{!! csrf_token() !!}');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        if(form.find('.imagem')[0].files[0] != undefined && form.find('.imagem')[0].files[0].size > 2097152){
            showErrorsInputsEdt(form, 'imagem', 'A imagem deve ser menor que 2MB');
            return;
        }

        $.ajax({
            url: "{{ route('book_virtual.desenho.editar') }}", 
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
                        createFormEditarImagem(callback.response.id, key, callback.response.codigo_desenho)
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

    function salvarDesenhoTamanhoReal(obj){

        form = $(obj).parents('form');

        var data = new FormData(form[0]);

        if(form.find('.imagem_tamanho_real')[0].files[0] != undefined && form.find('.imagem_tamanho_real')[0].files[0].size > 2097152){
            showErrorsInputsEdt(form, 'imagem_tamanho_real', 'A imagem deve ser menor que 2MB');
            return;
        }

        $.ajax({
            url: "{{ route('book_virtual.desenho.lisos') }}", 
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function (callback){
                $(document).find('#desenho-tamanho-real-link').html(callback.response.link);
                form.find('.imagem_tamanho_real').val('');
            },
            error: function(callback){
                var dados = callback.responseJSON.error;

                for(var field in dados){
                    showErrorsInputsEdt(form, field, dados[field])
                }
            }
        });
    }

    function salvarInstrucaoLavagem(){

        var form = $(document).find('#form-instrucao-lavagem');
        var data = new FormData(form[0]);

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        if($(document).find('#instrucoes_lavagem')[0].files[0] != undefined && $(document).find('#instrucoes_lavagem')[0].files[0].size > 2097152){
            showErrorsInputsEdt(form, 'instrucoes_lavagem', 'A imagem deve ser menor que 2MB');
            return;
        }

        $.ajax({
            url: "{{ route('book_virtual.instrucoes_lavagem.salvar') }}", 
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                
                form.find('#instrucoes_lavagem').val('');
                $(document).find("#div-instrucoes-lavagem").removeClass("d-none");
                $(document).find("#link-instrucoes-lavagem").prop('href', callback.response.img_instrucoes_lavagem)
                $(document).find("#img-instrucoes-lavagem").prop('src', callback.response.thumb_instrucoes_lavagem)
                    
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

    function createFormNovaImagem($id, $key, $codigo_desenho){
        var html = "<form action=\"\" name=\"form-desenho-"+$key+"\" onsubmit=\"return false;\"><input name=\"codigo_desenho\" type=\"hidden\" value=\""+$codigo_desenho+"\" autocomplete=\"off\"><input class=\"imagem\" name=\"imagem\" type=\"file\" autocomplete=\"off\"><button class=\"btn btn-sm btn-success btn-salvar-desenho\" type=\"button\" onclick=\"salvarDesenho(this)\">Salvar desenho</button></form>";

        return html;
    }

    function createFormEditarImagem($id, $key, $codigo_desenho){
        var html = "<form action=\"\" name=\"form-desenho-"+$key+"\" onsubmit=\"return false;\"><input name=\"codigo_desenho\" type=\"hidden\" value=\""+$codigo_desenho+"\" autocomplete=\"off\"><input class=\"imagem\" name=\"imagem\" type=\"file\" autocomplete=\"off\"><button class=\"btn btn-sm btn-success btn-editar-desenho\" type=\"button\" onclick=\"editarDesenho(this, "+$id+")\">Editar desenho</button></form>";

        return html;
    }

    function esconderDivDesenhos(){
        if($(document).find('#padrao_codigo').val() != '' || $(document).find('#tipo_material').val() == 'estampados' || $(document).find('#tipo_material').val() == 'fio_tinto'){
            $(document).find('#div-desenhos').removeClass('d-none');
            $(document).find('#div-desenho-tamanho-real').addClass('d-none');
        }
        else{
            $(document).find('#div-desenhos').addClass('d-none');
            $(document).find('#div-desenho-tamanho-real').removeClass('d-none');
        }
    }

    function gerarTabelaDesenhos(){

        var codigos_desenhos = [];

        $(document).find('.link-imagem').each(function() {
            codigos_desenhos[$(this).html()] = $(this).html();
        });

        table_desenhos.rows($(document).find('.sem-link').parents('tr')).remove().draw();

        var desenhos = [];

            
        $(document).find('#table-filters-pedidos-itens-edt').find('tbody').find('tr').has('td:not(.dataTables_empty)').each(function(){

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
                createFormNovaImagem("{!! $id !!}", x++, item)
            ]);
        });

        table_desenhos.draw();

    }
</script>
@endsection