@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
	<form action="#" name="form_filter_produtos" id="form_filter_produtos" onsubmit="return false;">
	    @csrf
        {!! Form::hidden('campanha_id', (isset($id_campanha)) ? $id_campanha : '', ["id" => 'campanha_id']) !!}
	    <div class="content-fields">
	        <div class="col-lg-2">
	            <input type="text" name="grupo" id="grupo_modal_busca" value="" placeholder="Grupo" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="subgrupo" id="subgrupo_modal_busca" value="" placeholder="Subgrupo" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="codigo" id="codigo_modal_busca" value="" placeholder="Código Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="nome" id="nome_modal_busca" value="" placeholder="Nome Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="marca" id="marca_modal_busca" value="" placeholder="Marca" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="linha" id="linha_modal_busca" value="" placeholder="Linha" maxlength="250" />
	        </div>
	    </div>
        <div class="form-row mb-3">
            <div class="col-lg-2 ml-4">

                {{ Form::checkbox('somente_diponivel', '1', true, ['id' => 'somente_diponivel','class' => 'form-check-input']) }}
                {{ Form::label('somente_diponivel', 'DISPONÍVEL PARA CAMPANHA ', ['class' => 'form-check-label']) }}

            </div>
            <div class="col-lg-2">

                {{ Form::checkbox('sem_estoque', '1', false, ['id' => 'sem_estoque','class' => 'form-check-input']) }}
                {{ Form::label('sem_estoque', 'SEM ESTOQUE', ['class' => 'form-check-label']) }}

            </div>
        </div>
	    <div class="content-buttons">
	        <button name="btn-filterform" id="btn-filterform-modal" class="btn-filter">Buscar</button>
	        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
			<div class="col-sm" id='enviar-div'>
				{!! Form::button('Enviar Todos os Produtos da Busca', ['id' => 'enviar_todos_itens_busca', 'class' => 'btn btn-success float-right']) !!}
			</div>
	    </div>
	</form>
</div>
<div class="content-dialog-table">
	<div class="content-table">
	    <table class="table table-striped" id="table-filters-produtos">
	        <thead>
	            <tr>
                    <th>Selecionar</th>
	                <th>Grupo</th>
	                <th>SubGrupo</th>
	                <th>Código</th>
	                <th>Descrição</th>
	                <th>Marca</th>
	                <th>Linha</th>
	                <th>Segmento</th>
	                <th>ATIVO EM OUTRA CAMPANHA</th>
	                <th>Estoque</th>
	            </tr>
	        </thead>
	        <tbody>
	        </tbody>
	    </table>
	</div>
    <div class="col-sm" id='enviar-div'>
        {!! Form::button('Enviar Produtos Selecionados', ['id' => 'enviar_itens_selecionados', 'class' => 'btn btn-success float-right mt-3']) !!}
    </div>
</div>
<script type='text/javascript'>
    table_campanha_busca_produtos = [];
	$(document).ready(function($) {
	    table_campanha_busca_produtos = $(document).find("#table-filters-produtos").DataTable({
	        "searching": false,
	        "lengthChange": false,
	        "info": false,
	        "pageLength": 13,
	        "orderMulti": false,
	        "language": {
	            "decimal":        ",",
	            "emptyTable":     "Nenhum registro encontrado",
	            "infoPostFix":    "",
	            "thousands":      ".",
	            "loadingRecords": "Carregando...",
	            "processing":     "Processando...",
	            "zeroRecords":    "Nenhum registro encontrado",
                "search": "Busca",
	            "paginate": {
	                "first":      "<<",
	                "last":       ">>",
	                "next":       ">",
	                "previous":   "<"
	            }
	        },
            "drawCallback": function(settings) {
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

            },
	        "order": [ [6, "desc"]]
	    });

        
        table_campanha_busca_produtos.on('draw', function () {
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModalProdutoDetalhes($(this));
            });
        });

		$(document).find("#btn-filterform-modal").off("click");
		$(document).find("#btn-filterform-modal").on("click", function(){
            if(table_produtos_hidden.data().count() > 0){
                var $class = "dialog_option_deletar";
                var $name_option_sim = "aprovar_nova_busca_sim";
                var $option_sim = "Sim";
                var $name_option_nao = "aprovar_nova_busca_nao";
                var $option_nao = "Não";
          
                message_sim_nao("Atenção", "Existem Produtos selecionados que não foram adicionados na campanha, tem certeza que deseja realizar uma nova pesquisa?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);
                
                $(document).off("aprovar_nova_busca_nao");
                $(document).on("aprovar_nova_busca_nao", function(){
                    return false;
                });

                $(document).off("aprovar_nova_busca_sim");
                $(document).on("aprovar_nova_busca_sim", function(){
                    buscaProdutos();
                });
            }else{
                buscaProdutos();
            }

		});

		$(document).find('#form_filter_produtos').find("#nome_modal_busca").autocomplete(optionsAutoCompleteProduto("nome"));
		$(document).find('#form_filter_produtos').find("#marca_modal_busca").autocomplete(optionsAutoCompleteProduto("marca"));
		$(document).find('#form_filter_produtos').find("#linha_modal_busca").autocomplete(optionsAutoCompleteProduto("linha"));
		$(document).find('#form_filter_produtos').find("#grupo_modal_busca").autocomplete(optionsAutoCompleteProduto("grupo"));
		$(document).find('#form_filter_produtos').find("#subgrupo_modal_busca").autocomplete(optionsAutoCompleteProduto("subgrupo"));
        
	});

    function buscaProdutos(){
        form = $(document).find('#form_filter_produtos');
        table_campanha_busca_produtos.clear().draw();
        form.find('.error-message').remove();
        form.find(".error-input").removeClass('error-input');
        table_produtos_hidden.clear().draw();

        $.ajax({
            url: '{{route('campanha.buscar_produtos')}}',
            type: 'POST',
            data: form.serialize(),
            success: function(data){
                var temp_line;
                var lines = [];

                for (var field in data.response.retorno){
                    
                    temp_line = [
                        createBtCheckBoxProduto((data.response.retorno[field])),
                        "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='"+data.response.retorno[field].grupo+"'>"+data.response.retorno[field].grupo+"</div></div>",
                        "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='"+data.response.retorno[field].subgrupo+"'>"+data.response.retorno[field].subgrupo+"</div></div>",
                        "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='"+data.response.retorno[field].codigo+"'>"+data.response.retorno[field].codigo+"</div></div>",
                        "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='"+data.response.retorno[field].descricao+"'>"+data.response.retorno[field].descricao+"</div></div>",
                        "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='"+data.response.retorno[field].marca+"'>"+data.response.retorno[field].marca+"</div></div>",
                        "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='"+data.response.retorno[field].linha+"'>"+data.response.retorno[field].linha+"</div></div>",
                        "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].segmento + "'>" + data.response.retorno[field].segmento + "</div></div>",
                        createBtViewAtivo(data.response.retorno[field],data.response.retorno[field].estabelecimento),
                        createBtView(data.response.retorno[field])
                    ];
                    lines.push(temp_line);
                }
                table_campanha_busca_produtos.rows.add(lines).draw();
            },
            error: function(data){
                var errors = data.responseJSON.errors;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }


    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.clear().draw();
                    filtro();
                }, 100);
            }
        };
    }

    function optionsAutoCompleteProduto($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#table-modal-produtos-busca-campanha').css('z-index')) + 1));
            },
        };
    }
    
    function createBodyPopOver($this){
        var $return = "";
        $.each($this,function(index, el) {
            $return += "<p>"+this+"</p>";
        });
        return $return;
    } 
    
    function createBtViewAtivo($this,estabelecimento_var){
        var html = "";
        var remover_espacos_estabelecimento = $this.estabelecimento.replaceAll(' ','');
        var produto_codigo = $this.codigo.replaceAll('/','');
        
        if($this.ativo != "" && $this.mesma_campanha === false){
            html = "<center><a href='#' class='text-danger mensagem_ativo"+$this.codigo+remover_espacos_estabelecimento+"' data-toggle='tooltip' data-placement='left' data-html='true' title='' onclick=\"mensagemRemoverProdutoCampanhaAtivo('"+produto_codigo+remover_espacos_estabelecimento+"','"+$this.codigo+"','"+$this.campanha_nome+"','"+estabelecimento_var+"','"+$this.estabelecimento_codigo+"','"+$this.campanha_id+"')\" data-original-title='"+$this.ativo+"'><strong>ATIVO</strong></a></center>";
        }else if($this.ativo != "" && $this.mesma_campanha === true){
            html = "<center><a href='#' class='text-info mensagem_ativo"+$this.codigo+remover_espacos_estabelecimento+"' data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='"+$this.ativo+"'><strong>ATIVO</strong></a></center>";
        }

        return html;
    }

    function createBtCheckBoxProduto($this){
        var html = "";
        var remover_espacos_estabelecimento = $this.estabelecimento.replaceAll(' ','');
        var produto_codigo = $this.codigo.replaceAll('/','');


        if($this.ativo == ""){
            html = "<center><input type='checkbox' id='"+produto_codigo+"_check' name='horns' data-subgrupo='"+$this.subgrupo+"' data-marca='"+$this.marca+"' data-linha='"+$this.linha+"' data-grupo='"+$this.grupo+"' data-codigo='"+$this.codigo+"' data-segmento='"+$this.segmento+"' data-estabelecimento='"+remover_espacos_estabelecimento+"' data-duplicidade_array='"+produto_codigo+"' data-duplicidade='"+produto_codigo+"' data-descricao='"+$this.descricao+"' data-campanha_nome='"+$this.campanha_nome+"' data-campanha_id='"+$this.campanha_id+"' class='verifica_produto_selecionado'></center>";
        }else{
            html = "<center><input type='checkbox' id='"+produto_codigo+"_check' name='horns' data-subgrupo='"+$this.subgrupo+"' data-marca='"+$this.marca+"' data-linha='"+$this.linha+"' data-grupo='"+$this.grupo+"' data-codigo='"+$this.codigo+"'data-segmento='"+$this.segmento+"' data-estabelecimento='"+remover_espacos_estabelecimento+"' data-duplicidade='"+produto_codigo+"' data-duplicidade_array='"+produto_codigo+"' data-descricao='"+$this.descricao+"' class='verifica_produto_selecionado ativo"+produto_codigo+" d-none'></center>";
        }


        return html;
    }

    function createBtView($this){
        var html = "";

        if($this.estoque == "0,00"){
            html = "<a href=\"#\" data-route=\"{{ route('produto.view') }}\" data-id=\""+$this.codigo+"\" class=\"bt-view\" data-title='"+$this.title_modal+"'>"+$this.estoque+"</a>";
        }else{
            html = "<a href=\"#\" data-route=\"{{ route('produto.view') }}\" data-id=\""+$this.codigo+"\" class=\"bt-view\" data-toggle=\"popover\" data-trigger='hover' title=\"Estoque por empresa\" data-title='"+$this.title_modal+"' data-content=\""+createBodyPopOver($this.total_popover)+"\">"+$this.estoque+"</a>";
        }

        return html;
    }

    function showModalProdutoDetalhes($this){
        var url = $($this).data("route");
        var id = $($this).data("id");
        var title = "Visualizar Produto - " + $($this).data('title');

        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", codprd: id},
            method: 'POST',
            success: function(body){
                createModal("model_produto_view", title, body, 'modal-lg');
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function showModalPecaPeca($this){
        var url = $($this).data("url");
        var codigo = $($this).data("codigo");
        var estabel = $($this).data("estabel");
        var title = $($this).data('title');

        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel},
            method: 'POST',
            success: function(body){
                createModal("model_pecapeca_view", title, body, 'modal-lg');
                var modal = $(document).find("#model_pecapeca_view");
                modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').off("click");
                modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').on('click', function(){
                    showModalPecaPecaPedido($(this));
                });
                $("#model_pecapeca_view").off('hidden.bs.modal');
                $("#model_pecapeca_view").on('hidden.bs.modal', function (e) {
                    $(".modal-backdrop").css("z-index", 9);
                    $("#model_pecapeca_view").remove();
                });
            }
        });

    }

    function showModalPecaPecaPedido($this){
        var url = $($this).data("url");
        var codigo = $($this).data("codigo");
        var estabel = $($this).data("estabel");
        var codigo_item = $($this).data("codigo_item");

        $("#model_pecapeca_pedido_view").modal("toggle");
        $("#model_pecapeca_pedido_view").find('.modal-title').html($($this).data('title'));
        $("#model_pecapeca_pedido_view").off('shown.bs.modal');
        $("#model_pecapeca_pedido_view").on('shown.bs.modal', function (event) {
            var modal = $(this);
            xhr = $.ajax({
                url: url,
                data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel, codigo_item: codigo_item},
                method: 'POST',
                success: function(data){
                    modal.find('.modal-body').html(data);
                }
            });
        });
        $("#model_pecapeca_pedido_view").off('hidden.bs.modal');
        $("#model_pecapeca_pedido_view").on('hidden.bs.modal', function (e) {
            $(".modal-backdrop").css("z-index", 13);
            $("#model_pecapeca_pedido_view").find('.modal-body').html('');
            $("#model_pecapeca_pedido_view").find('.modal-title').html('');
        });
    }

    function mensagemRemoverProdutoCampanhaAtivo(classRemover,codigo,campanha,estabelecimento,codigo_estabelecimento,campanha_id){
        var $class = "dialog_option_deletar";
		var $name_option_sim = "remover_produto_campanha_ativo_sim";
		var $option_sim = "Sim";
		var $name_option_nao = "aprovar_nova_busca_nao";
		var $option_nao = "Não";
		message_sim_nao("Atenção", "Desativar o produto "+codigo+" da campanha "+campanha +"?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);
		
        $(document).off("aprovar_nova_busca_nao");
		$(document).on("aprovar_nova_busca_nao", function(){
			return false;
		});

		$(document).off("remover_produto_campanha_ativo_sim");
		$(document).on("remover_produto_campanha_ativo_sim", function(){
            removerProdutoAtivoCampanha(classRemover,codigo,codigo_estabelecimento,campanha,campanha_id);
		});
    }

    function removerProdutoAtivoCampanha(classRemover,codigo,codigo_estabelecimento,campanha,campanha_id){
        xhr = $.ajax({
            url: '{{ Route('campanha.remover_produto') }}',
            data: {_token: "{{ csrf_token() }}", codigo: codigo, estabelecimento: codigo_estabelecimento, campanha: campanha, campanha_id: campanha_id},
            method: 'POST',
            success: function(body){
                if(body.status == 'success'){
                    $(document).find('.ativo'+classRemover).removeClass('d-none');
                    $(document).find('.mensagem_ativo'+classRemover).addClass('d-none');
                }else{
                    message('Algo ocorreu de errado na exclusão, por favor contate o setor responsável.');
                }
            },
            error: function(data){
                console.log(data);
                message('Algo ocorreu de errado na exclusão, por favor contate o setor responsável.');
            }
        });
    }

</script>
@endsection

