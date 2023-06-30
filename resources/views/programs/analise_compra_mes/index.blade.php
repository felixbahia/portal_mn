@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem para {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="form-group col-lg-3 col-xl-3">
                {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('codigo', '', ['id' => 'codigo', 'class' => 'form-control', 'placeholder' => 'Código do Produto']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('nome', '', ['id' => 'nome', 'class' => 'form-control', 'placeholder' => 'Nome do Produto']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca']) }}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimentos'])}}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('qtd_mes_estoque', '', ['id' => 'qtd_mes_estoque', 'class' => 'form-control number', 'placeholder' => 'Quantidade de Mêses em Estoque para Compra']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('qtd_mes_media', '', ['id' => 'qtd_mes_media', 'class' => 'form-control number', 'placeholder' => 'Quantidade de Mêses para Média de Vendas']) }}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-2 col-xl-3">
                <div class="col-md-6 ml-4 mt-7">
                    {{ Form::checkbox('compra', '1', '',  ['id' => 'compra', 'class' => 'form-check-input']) }}
                    {{ Form::label('compra', 'Apenas com necessidade', ['class' => 'form-check-label','for' => 'compra']) }}
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Marca</th>
                <th>Linha</th>
                <th>Grupo</th>
                <th class="tb_number">Estoque</th>
                <th class="tb_number">Compras</th>
                <th class="tb_number" id="vendas">Vendas</th>
                <th class="tb_number" id="remessas">Remessas</th>
                <th class="tb_number" id="mediavendas">Média</th>
                <th class="tb_number" id="necessidade">Necessidade</th>
                <th>Grupo</th>
                <th>Produto</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tfoot>
    </table>
</div>

@endsection

@section('script-footer')
    $(document).ready( function () {
        $('.number').mask('0000');
        $('#btn-filterform').on('click', function(){
			filtro();
		});
        table_filters.destroy();
        table_filters = $('#table-filters')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": true,
            "orderMulti": false,
            "pageLength": 15,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: 'Análise de Produto',
                    footer: true,
                    customize: function( xlsx ) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        $('row c[r^="C"]', sheet).attr( 's', '2' );
                    },
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 6 || column === 7 || column === 8 || column === 9 || column === 10){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            },
                            footer: function(data) {
                                data = $('<p>' + data + '</p>').text();
                                return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                            }
                        }
                    },
                },
            ],
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
                    "class": "tb_number", 
                    "targets": "tb_number"
                },
            ]
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-view-estoque").off("click");
            $(document).find(".bt-view-estoque").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-view-compras").off("click");
            $(document).find(".bt-view-compras").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-view-vendas").off("click");
            $(document).find(".bt-view-vendas").on("click", function(event){
                event.stopPropagation();
                showModalVendas($(this));
            });
            $(document).find(".bt-open-view-analise-produto-abertura").off("click");
            $(document).find(".bt-open-view-analise-produto-abertura").on("click", function(event){
                event.stopPropagation();
                showModalProduto($(this));
            });
            $(document).find(".bt-view-remessas").off("click");
            $(document).find(".bt-view-remessas").on("click", function(event){
                event.stopPropagation();
                showModalRemessas($(this));
            });
        });
        $("#nome").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#linha").autocomplete(optionsAutoComplete("linha"));
        $("#grupo").autocomplete(optionsAutoComplete("grupo"));

    });

    function filtro(){
        table_filters.clear().draw();
        $form = $("#form_filter");
        $data = $form.serialize();
        $('label.error-message').remove();
		$.ajax({
			url: '{{ route('analise_compras_mes.filter')}}',
			type: 'POST',
			data: $data,
			success: function(data){
                var out = [];
                $('#vendas').html("");
                $('#mediavendas').html("");
                $('#necessidade').html("");
                $('#remessas').html("");

                var estoque = 0;
                var compras = 0;
                var vendas = 0;
                var media = 0;
                var necessidade = 0;
				for (var fields in data.response.saida){
                    out.push([
                        data.response.saida[fields].estabelecimento,
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].marca + "''>" + data.response.saida[fields].marca + "</div></div>",
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].linha + "''>" + data.response.saida[fields].linha + "</div></div>",
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].grupo + "''>" + data.response.saida[fields].grupo + "</div></div>",
                        createBtViewEstoque(data.response.saida[fields]),
                        createBtViewCompras(data.response.saida[fields]),
                        createBtViewVendas(data.response.saida[fields]),
                        createBtViewRemessas(data.response.saida[fields]),
                        data.response.saida[fields].media,
                        data.response.saida[fields].necessidade,
                        createBtView(data.response.saida[fields]),
                        createBtViewProduto(data.response.saida[fields]),
                    ]);
                }
                $('#vendas').append('Vendas ('+$('#qtd_mes_media').val()+' Meses)');
                $('#remessas').append('Remessas ('+$('#qtd_mes_media').val()+' Meses)');
                $('#mediavendas').append('Média ('+$('#qtd_mes_media').val()+' Meses)');
                $('#necessidade').append('Necesidade ('+$('#qtd_mes_estoque').val()+' meses)');
                table_filters.rows.add(out).draw();
                $(table_filters.column(3).footer()).html('total');
                $(table_filters.column(4).footer()).html(data.response.total.estoque);
                $(table_filters.column(5).footer()).html(data.response.total.compras);
                $(table_filters.column(6).footer()).html(data.response.total.vendas);
                $(table_filters.column(7).footer()).html(data.response.total.remessas);
                $(table_filters.column(8).footer()).html(data.response.total.media);
                $(table_filters.column(9).footer()).html(data.response.total.necessidade);
                
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

    function showModal($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var codigo = $($this).data('codigo');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters: filter},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view", title, body, 'modal-lg');
            }
        });
    }

    function showModalProduto($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters: filter},
            method: 'POST',
            success: function(body){
                createModal("model_analise_produto", title, body, 'modal-lg');
            }
        });
    }

    function showModalRemessas($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_vendas", title, body, 'modal-lg');
            }
        });
    }

    function showModalVendas($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var codigo = $($this).data('codigo');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_vendas", title, body, 'modal-lg');
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
            minLength: 3
        };
    }

    function createBtView($this){
        var html = "<a href=\"#\" data-route=\"{{ route('analise_compras_mes.modal.abertura') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO POR GRUPO' class='bt-view'></a>"
        return html;
    }

    function createBtViewProduto($this){
        var html = "<a href=\"#\" class=\"bt-view bt-open-view-analise-produto-abertura\" data-title='ANALITÍCO POR PRODUTO' data-route=\"{{ route('analise_compras_mes.modal.index') }}\" data-filter=\""+$this.filter+"\" ></a>"
        return html;
    }

    function createBtViewEstoque($this){
        var html = "<a href=\"#\" data-route=\"{{ route('analise_compras_mes.modal.estoque') }}\" data-filter=\""+$this.filter+"\" data-title='ANALITÍCO DE ESTOQUE' class='bt-view-estoque'>"+$this.estoque+"</a>";
        return html;
    }

    function createBtViewCompras($this){
        var html = "<a href=\"#\" data-route=\"{{ route('analise_compras_mes.modal.compras') }}\" data-filter=\""+$this.filter+"\" data-title='ANALITÍCO DE COMPRAS' class='bt-view-compras'>"+$this.compras+"</a>";
        return html;
    }

    function createBtViewVendas($this){
        var html = "<a href=\"#\" data-route=\"{{ route('analise_compras_mes.modal.vendas') }}\" data-filter=\""+$this.filter+"\" data-title='ANALITÍCO DE PRODUTOS' class='bt-view-vendas'>"+$this.vendas+"</a>";
        return html;
    }

    function createBtViewRemessas($this){
        var html = "<a href=\"#\" data-route=\"{{ route('analise_compras_mes.modal.remessas') }}\" data-filter=\""+$this.filter+"\" data-title='ANALITÍCO DE REMESSAS' class='bt-view-remessas'>"+$this.remessas+"</a>";
        return html;
    }

@endsection