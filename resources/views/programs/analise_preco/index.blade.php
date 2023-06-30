@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="row">
                <div class="form-group col-lg-3 col-xl-2">
                    {{ Form::text('grupo', '', ['id' => 'grupo', 'placeholder' => 'Grupo', 'maxlength' => '250']) }}
                </div>
                <div class="form-group col-lg-3 col-xl-2">
                    {{ Form::text('nome', '', ['id' => 'descricao', 'placeholder' => 'Descrição', 'maxlength' => '250']) }}
                </div>
                <div class="form-group col-lg-3 col-xl-2">
                    {{ Form::text('codigo', '', ['id' => 'codigo_produto', 'placeholder' => 'Código de produto', 'maxlength' => '250']) }}
                </div>
                <div class="col-sm-2">
                    {{ Form::text('marca', '', ['id' => 'marca', 'placeholder' => 'Marca', 'maxlength' => '250']) }}
                </div>
                <div class="form-group col-lg-3 col-xl-2">
                    {{ Form::text('linha', '', ['id' => 'linha', 'placeholder' => 'Linha', 'maxlength' => '250']) }}
                </div>
                <div class="row">
                    <div class="form-group col-lg-2 col-xl-6">
                        {{ Form::text('markup_abaixo', '', ['id' => 'markup_abaixo', 'placeholder' => 'Markup lista abaixo de', 'maxlength' => '250']) }}
                    </div>
                    <div class="form-group col-lg-2 col-xl-6">
                        {{ Form::text('markup_acima', '', ['id' => 'markup_acima', 'placeholder' => 'Markup lista acima de', 'maxlength' => '250']) }}
                    </div>
                </div>
            </div>
        </div>

    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>

</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-precos">
        <thead>
        <tr>
            <th class='grupo'>Grupo</th>
            <th class='subgrupo'>Subgrupo</th>
            <th class='marca'>Marca</th>
            <th class='linha'>Linha</th>
            <th class='itens'>Itens</th>
            <th class="tb_number">Custo<br/>Medio</th>
            <th class="tb_number">Valor<br/>Ult. Compra</th>
            <th class="tb_number">Preço<br/>Venda</th>
            <th class="tb_number">Markup<br/>Lista</th>
            <th class="tb_number">Preço<br/>Med VD</th>
            <th class="tb_number">Markup<br/>Venda</th>
            <th class="tb_date">Data<br/>Ult. Compra</th>
            <th class="tb_number">Dias<br/>S/venda</th>
            <th class='medida'>UN</th>
            <th class="tb_number">Med VD<br/>6 Meses</th>
            <th class="tb_number">Meses<br/>Estoque</th>
            <th class="tb_number">Estoque</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')

    $(document).ready( function () {


        $('#btn-filterform').on('click', function(){
            filtro($('#form_filter').serialize(), false);
        });

        $('#markup_abaixo').maskMoney({thousands:'.', decimal:','});
        $('#markup_acima').maskMoney({thousands:'.', decimal:','});

        $("#marca").autocomplete(optionsAutoCompleteMarca());
        $("#grupo").autocomplete(optionsAutoCompleteGrupo());
        $("#linha").autocomplete(optionsAutoCompleteLinha());
        $("#descricao").autocomplete(optionsAutoComplete("nome"));

        $(document).find(".bt-view-estoque").off("click");
            $(document).find(".bt-view-estoque").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
	});

    function optionsAutoComplete($name, element = null){

        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                request.term = request.term.toLowerCase(); 
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($('.modal').css('z-index')) + 1));
        	},
            select: function( event, ui ) {
                setTimeout(function(){
                    filtro($('#form_filter').serialize(), false);
                }, 100);
            }
        };
    }

    function optionsAutoCompleteGrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#form_filter').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteLinha(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.linha.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#form_filter').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteMarca(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.marca.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#form_filter').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

    table_filters = $('#table-filters-precos').DataTable({
        "searching": false,
        "lengthChange": false,
        "autoWidth": false,
        "info": false,
        "pageLength": 15,
        "language": {
            "decimal":        ",",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum registro encontrado",
            "paginate": {
                "first":      "<<",
                "last":       ">>",
                "next":       ">",
                "previous":   "<"
            }
        },
        "columnDefs": [
            {
                'targets': 'tb_number',
                "width": '70px',
                "class": 'tb_number'
            },
            { 
                "targets": 'tb_date',
                "class": 'tb_date',
                "width": '5%' 
            },
            {
                'targets': 'nome',
                'width': '20%'
            },
            {
                'targets': 'grupo',
                'width': '150px',
                'class': 'grupo'
            },
            {
                'targets': 'subgrupo',
                'width': '30px',
                'class': 'subgrupo'
            },
            {
                'targets': 'itens',
                'width': '5px', 
                'class': 'tb_number'
            },
            {
                'targets': 'medida',
                'className': 'text-left medida'
            },
            
        ],
        "order": [[ 1, 'asc' ], [2, 'asc']]
    });

    function filtro(){
        table_filters.clear().draw();
        $form = $("#form_filter");
        $data = $form.serialize();
        $('label.error-message').remove();
		$.ajax({
			url: '{{ route('analise_preco.filter')}}',
			type: 'POST',
			data: $data,
			success: function(data){
                var out = [];
                var linhas = data.response.saida;
				for (var fields in linhas){
                    out.push([
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[fields].grupo+"\">"+linhas[fields].grupo+"</div></div>",
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[fields].subgrupo+"\">"+linhas[fields].subgrupo+"</div></div>",
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[fields].marca+"\">"+linhas[fields].marca+"</div></div>",
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[fields].linha+"\">"+linhas[fields].linha+"</div></div>",
                        criarLinkProdutos(linhas[fields].total_itens, linhas[fields].hash),
                        linhas[fields].custo_gerencial_medio,
                        linhas[fields].ultima_compra,
                        linhas[fields].preco_venda,
                        linhas[fields].markup_lista,
                        linhas[fields].preco_medio,
                        linhas[fields].markup_venda,
                        linhas[fields].data_ultima_compra,
                        linhas[fields].dias_parado,
                        linhas[fields].unidade,
                        linhas[fields].media,
                        linhas[fields].meses_estoque,
                        criarLinkEstoque(linhas[fields].estoque, linhas[fields].filters), 
                    ]);
                }
                table_filters.rows.add(out).draw();   
			},
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                    });
                    $form.find('input.error').eq(0).focus();
                }
            }
		}).always(function() {
            hide_loader();
        });
    }

    function criarLinkProdutos($total_itens, $hash){
        var html = "<a href=\"#\" title='Produtos contidos' data-hash='"+$hash+"' onclick=\"modalProdutos($(this).data('hash'))\">" + $total_itens + "</a>"
        
        return html;
    }

    function criarLinkEstoque($estoque, $filters){
        var html = "<a href=\"#\" title='Estoque' data-filters='"+$filters+"' onclick=\"modalEstoque($(this).data('filters'))\">" + $estoque + "</a>"
        
        return html;
    }

    function modalProdutos($hash){
        $.ajax({
            url: '{{ Route("analise_preco.modal.produtos") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            success: function(data){
                createModal('modal_produtos', 'Produtos contidos neste agrupamento', data, 'modal-lg');
            }
        });
    }

    function modalEstoque($filters){
        $.ajax({
            url: '{{ Route("analise_preco.modal.estoque") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                filters: $filters
            },
            success: function(data){
                createModal('modal_produtos', 'ANALITÍCO DE ESTOQUE', data, 'modal-lg');
            }
        });
    }
@endsection
