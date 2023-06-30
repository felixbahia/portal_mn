@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Analise</h3>
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
                {{ Form::text('qtd_meses', '', ['id' => 'qtd_meses', 'class' => 'form-control number', 'placeholder' => 'Quantidade de Meses para Média']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::select('inativos', [false => 'Inativos', true => 'Ativos'], '', ['id' => 'inativos', 'class' => 'form-control', 'placeholder' => 'Ativos e inativos']) }}
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
    <table class="table table-striped table-not-edit table-not-view" id="table_filter_index">
        <thead>
            <tr>
                <th rowspan="2">Marca</th>
                <th rowspan="2">LInha</th>
                <th rowspan="2">Produto</th>
                <th rowspan="2">Grupo</th>
                <th rowspan="2" class="tb_number">Saldo Anterior</th>
                <th colspan="7"><center>Ultimos <span id="meses"></span> meses</center></th>
                <th rowspan="2" class="tb_number">Estoque</th>
                <th colspan="3"><center>A receber</center></th>
            </tr>
            <tr>
                <th class="tb_number">Comprado</th>
                <th class="tb_date">última Compra</th>
                <th class="tb_number">Remessas</th>
                <th class="tb_number">Média de Remessas</th>
                <th class="tb_number">Vendido</th>
                <th class="tb_number">Média de venda</th>
                <th></th>
                <th class="tb_number">{{ $meses["atual"] }}</th>
                <th class="tb_number">{{ $meses["mes_1"] }}</th>
                <th class="tb_number">{{ $meses["mes_2"] }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $('.number').mask('0000');
        $("#nome").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#linha").autocomplete(optionsAutoComplete("linha"));
        $("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        table_filters_index = $('#table_filter_index')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "ordering": false,
            "language": {
                "decimal":        ".",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ",",
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
                {
                    "class": "tb_date", 
                    "targets": "tb_date"
                },
            ]
        });
        table_filters_index.on('draw', function () {
            $(document).find(".bt-produto").off("click");
            $(document).find(".bt-produto").on("click", function(event){
                event.stopPropagation();
                showModalArtigosProduto($(this));
            });
            $(document).find(".bt-comprado").off("click");
            $(document).find(".bt-comprado").on("click", function(event){
                event.stopPropagation();
                showModalArtigosComprado($(this));
            });

            $(document).find(".bt-vendido").off("click");
            $(document).find(".bt-vendido").on("click", function(event){
                event.stopPropagation();
                showModalArtigosVendido($(this));
            });

            $(document).find(".bt-mes-atual").off("click");
            $(document).find(".bt-mes-atual").on("click", function(event){
                event.stopPropagation();
                showModalArtigosMesAtual($(this));
            });

            $(document).find(".bt-mes-um").off("click");
            $(document).find(".bt-mes-um").on("click", function(event){
                event.stopPropagation();
                showModalArtigosMesUm($(this));
            });

            $(document).find(".bt-mes-dois").off("click");
            $(document).find(".bt-mes-dois").on("click", function(event){
                event.stopPropagation();
                showModalArtigosMesDois($(this));
            });

            $(document).find(".bt-abertura-Meses-Anteriores").off("click");
            $(document).find(".bt-abertura-Meses-Anteriores").on("click", function(event){
                event.stopPropagation();
                showModalArtigosMesesAnteriores($(this));
            });

            $(document).find(".bt-remessas").off("click");
            $(document).find(".bt-remessas").on("click", function(event){
            event.stopPropagation();
            showModalRemessas($(this));
        });
        });
    });
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
                    table_filters_index.draw();
                }, 100);
            }
        };
    }
    
    function filterAjax(data_form){
        var $return;
        $form = $("#form_filter");
        $('label.error-message').remove();
        $.ajax({
            url: "{{ route('compras_analise.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response.out;
                table_filters_index.clear().draw();
                if(data != null){
                    if(data.length > 0){
                        var fields_filter = [];
                        $('#meses').html("");
                        for(var field in data){
                            var temp_field = [
                                "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[field].marca + "''>" + data[field].marca + "</div></div>",
                                "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[field].linha + "''>" + data[field].linha + "</div></div>",
                                "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[field].nome + "''>" + data[field].nome + "</div></div>",
                                "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[field].grupo + "''>" + data[field].grupo + "</div></div>",
                                data[field].saldo_anterior,
                                createBtViewComprados(data[field]),
                                data[field].data_compra,
                                createBtViewRemessas(data[field]),
                                data[field].media_remessas,
                                createBtViewVendidos(data[field]),
                                data[field].media_venda,
                                createBtViewReceberMesAMes(data[field]),
                                data[field].estoque,
                                createBtViewReceberMesAtual(data[field]),
                                createBtViewReceberMesUm(data[field]),
                                createBtViewReceberMesDois(data[field]),
                                createBtViewArtigos(data[field]),
                            ];
                            fields_filter.push(temp_field);
                        }
                        $('#meses').append(callback.response.meses);
                        table_filters_index.rows.add(fields_filter).order([ 0, 'asc' ] ).draw().nodes();
                    }
                }
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
        });
    }

    function createBtViewArtigos($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.artigo') }}\" data-filter=\""+$this.filter+"\" data-title='Análise de produtos ' class='bt-view bt-produto'></a>"
        return html;
    }

    function createBtViewComprados($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.comprado') }}\" data-filter=\""+$this.filter+"\" data-title='Análise de Notas de Compras ' class='bt-comprado'>"+$this.comprado+"</a>"
        return html;
    }

    function createBtViewRemessas($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.analise.remessas') }}\" data-filter=\""+$this.filter+"\" data-title='Análise de Remessas Mensalmente ' class='bt-remessas'>"+$this.remessas+"</a>"
        return html;
    }

    function createBtViewVendidos($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.vendido') }}\" data-filter=\""+$this.filter+"\" data-title='Análise de Vendas Mensalmente ' class='bt-vendido'>"+$this.vendido+"</a>"
        return html;
    }

    function createBtViewReceberMesAtual($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.receber') }}\" data-mes='atual' data-filter=\""+$this.filter+"\" data-title='Análise a Receber Mês {{ parserNameMonthFull(date('m'))}} ' class='bt-mes-atual'>"+$this.a_receber_atual+"</a>"
        return html;
    }

    function createBtViewReceberMesUm($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.receber') }}\" data-mes='um' data-filter=\""+$this.filter+"\" data-title='Análise a Receber Mês {{ parserNameMonthFull(date('m', strtotime('+1 months')))}} ' class='bt-mes-um'>"+$this.a_receber_mes_1+"</a>"
        return html;
    }

    function createBtViewReceberMesDois($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.receber') }}\" data-mes='dois' data-filter=\""+$this.filter+"\" data-title='Análise a Receber Mês {{ parserNameMonthFull(date('m', strtotime('+2 months')))}} ' class='bt-mes-dois'>"+$this.a_receber_mes_2+"</a>"
        return html;
    }

    function createBtViewReceberMesAMes($this){
        var html = "<a href=\"#\" data-route=\"{{ route('compras_analise.modal.analise.mes') }}\" data-mes='dois' data-filter=\""+$this.filter+"\" data-title='Análise de Vendas/Compras últimos "+$this.qtd_meses+" Meses' class='bt-view bt-abertura-Meses-Anteriores'></a>"
        return html;
    }

    function showModalArtigosComprado($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_comprado", title, body, 'modal-lg');
            }
        });
    }

    function showModalArtigosVendido($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_vendido", title, body, 'modal-lg');
            }
        });
    }

    function showModalRemessas($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
                method: 'POST',
                success: function(body){
                createModal("model_analitico_view_remessas", title, body, 'modal-lg');
            }
        });
    }

    function showModalArtigosMesAtual($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_mes_atual", title, body, 'modal-lg');
            }
        });
    }

    function showModalArtigosMesUm($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_mes_um", title, body, 'modal-lg');
            }
        });
    }

    function showModalArtigosMesDois($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_mes_dois", title, body, 'modal-lg');
            }
        });
    }

    function showModalArtigosMesesAnteriores($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_meses_anteriores", title, body, 'modal-lg');
            }
        });
    }

    function showModalArtigosProduto($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var mes = $($this).data('mes');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, mes : mes},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_artigos_produto", title, body, 'modal-lg');
            }
        });
    }
@endsection
