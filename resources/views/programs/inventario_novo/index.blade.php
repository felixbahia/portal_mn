@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimentos']) !!}
        </div>
        <div class="col-lg-1">
            <div class="form-check">
                {!! Form::checkbox('ativo', 'true', true, ['id' => 'ativo', 'class' => 'form-check-input']) !!}
                {!! Form::label('ativo', 'Ativo', ['class' => 'form-check-label', 'check' => 'true']) !!}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Iniciar</button>
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-inventario">
        <thead>
            <tr>
                <th rowspan="2">Código</th>
                <th rowspan="2">Estabelecimento</th>
                <th rowspan="2" class="tb_date">Data Inicial</th>
                <th rowspan="2" class="tb_date">Data Final</th>
                <th colspan="2">Estoque</th>
                <th colspan="2">1ª Contagem</th>
                <th colspan="2">2ª Contagem</th>
                <th colspan="2">3ª Contagem</th>
                <th rowspan="2">Log</th>
                <th rowspan="2" class="tb_number">P. Ñ. Enc.</th>
                <th rowspan="2">Finalizar</th>
            </tr>
            <tr>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Volume</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Volume</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Volume</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Volume</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {

        $("#btn-create").on("click", function(){
            showModalCreate();
        });

        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });
        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });

        table_filters = $('#table-filters-inventario').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "orderMulti": false,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" }
            ],
            "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
        });
        $("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter").serialize());
        });
        table_filters.on('draw', function () {
            $(document).find(".btn-pedido").off("click");
            $(document).find(".btn-pedido").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
        buscaDados($("#form_filter").serialize());
    });

    function showModalCreate(){
        $.ajax({
            url: '{{ route('inventario_novo.modal.iniciar') }}',
            method: 'GET',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body){
                var title = 'Iniciar Inventário';
                createModal('modal_iniciar', title, body, '');
            }
        });
    }

    function buscaDados(data_form){
        table_filters.clear().draw();
        $.ajax({
            url: '{{ route('inventario_novo.filtro') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form,
            success: function(callback){
                if(callback.status === 'success'){
                    var dados = callback.response;
                    table_filters.clear().draw();
                    var lines = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].codigo,
                            dados[field].estabelecimento,
                            dados[field].data_inicial,
                            dados[field].data_final,
                            viewDetalhesProduto(dados[field], dados[field].estoque) + "&nbsp;&nbsp;&nbsp;" + viewExportarProdutoExcel(dados[field], dados[field].estoque),
                            viewDetalhesPeca(dados[field], dados[field].volume) + "&nbsp;&nbsp;&nbsp;" +viewExportarPecaoExcel(dados[field], dados[field].estoque),
                            viewDetalhesProduto(dados[field], dados[field].contagem_1_estoque) + "&nbsp;&nbsp;&nbsp;" + viewExportarProdutoExcel(dados[field], dados[field].estoque),
                            viewDetalhesPeca(dados[field], dados[field].contagem_1_volume) + "&nbsp;&nbsp;&nbsp;" +viewExportarPecaoExcel(dados[field], dados[field].estoque),
                            viewDetalhesProduto(dados[field], dados[field].contagem_2_estoque) + "&nbsp;&nbsp;&nbsp;" + viewExportarProdutoExcel(dados[field], dados[field].estoque),
                            viewDetalhesPeca(dados[field], dados[field].contagem_2_volume) + "&nbsp;&nbsp;&nbsp;" +viewExportarPecaoExcel(dados[field], dados[field].estoque),
                            viewDetalhesProduto(dados[field], dados[field].contagem_3_estoque) + "&nbsp;&nbsp;&nbsp;" + viewExportarProdutoExcel(dados[field], dados[field].estoque),
                            viewDetalhesPeca(dados[field], dados[field].contagem_3_volume) + "&nbsp;&nbsp;&nbsp;" +viewExportarPecaoExcel(dados[field], dados[field].estoque),
                            viewExportarLogExcel(dados[field], dados[field].estoque),
                            viewExportarPecaNaoEncontradaExcel(dados[field], dados[field].peca_nao_encontrada),
                            createBtnFinalizar("{{ route('inventario_novo.modal.finalizar') }}", dados[field])
                        ];
                        lines.push(temp_field);
                    }
                    table_filters.rows.add(lines).draw().nodes();
                }
            }
        }).always(function() {
            hide_loader();
        });
    }

    function viewDetalhesProduto($this, $valor){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Produto\" onclick=\"showModalDetalhesProduto("+$this.id+", '"+$this.nome+"')\">"+$valor+"</a>";
        return html;
    }

    function showModalDetalhesProduto(id, nome){
        $.ajax({
            url: "{{ route('inventario_novo.modal.detalhes_produtos') }}",
            data: {_token: '{{ csrf_token() }}', id:id },
            method: 'POST',
            success: function(data){

                var $id = "editar";
                var $title = "Detalhes Produto";
                var $body = data;

                createModal($id, $title, $body, 'modal-lg')

                $(document).find('.ui-widget-content').css('z-index', "2000 !important");

            }
        });
    }

    function viewDetalhesPeca($this, $valor){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Peça\" onclick=\"showModalDetalhesPeca("+$this.id+", '"+$this.nome+"')\">"+$valor+"</a>";
        return html;
    }

    function showModalDetalhesPeca(id, nome){
        $.ajax({
            url: "{{ route('inventario_novo.modal.detalhes_pecas') }}",
            data: {_token: '{{ csrf_token() }}', id:id },
            method: 'POST',
            success: function(data){

                var $id = "editar";
                var $title = "Detalhes Peça";
                var $body = data;

                createModal($id, $title, $body, 'modal-lg')

                $(document).find('.ui-widget-content').css('z-index', "2000 !important");

            }
        });
    }

    function viewExportarPecaoExcel($this, $valor){
        var html = "<i class=\"btn-excel float-right\" data-id=\""+$this.id+"\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Peça\" onclick=\"showExportarPecaExcel("+$this.id+", '"+$this.nome+"')\"></i>";
        return html;
    }

    function showExportarPecaExcel(id, nome){
        $.ajax({
            url: "{{ route('inventario_novo.exportar_excel') }}",
            type: 'POST',
            dataType: 'json',
            data: {_token: '{{ csrf_token() }}', id:id },
            success: function(callback){
                var dados = callback.response;
                for(var field in dados.caminhos){
                    $('<form action="{{ route('inventario_novo.download') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="caminho" value="'+dados.caminhos[field]+'">\
                    </form>').appendTo('body').submit().remove();
                }
            }
        });
    }

    function viewExportarProdutoExcel($this, $valor){
        var html = "<i class=\"btn-excel float-right\" data-id=\""+$this.id+"\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Produto\" onclick=\"showExportarProdutoExcel("+$this.id+", '"+$this.nome+"')\"></i>";
        return html;
    }

    function showExportarProdutoExcel(id, nome){
        $.ajax({
            url: "{{ route('inventario_novo.exportar_produto_excel') }}",
            type: 'POST',
            dataType: 'json',
            data: {_token: '{{ csrf_token() }}', id:id },
            success: function(callback){
                var dados = callback.response;
                for(var field in dados.caminhos){
                    $('<form action="{{ route('inventario_novo.download') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="caminho" value="'+dados.caminhos[field]+'">\
                    </form>').appendTo('body').submit().remove();
                }
            }
        });
    }

    function viewExportarLogExcel($this, $valor){
        var html = "<i class=\"btn-excel float-right\" data-id=\""+$this.id+"\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Log\" onclick=\"showExportarLogExcel("+$this.id+", '"+$this.nome+"')\"></i>";
        return html;
    }

    function showExportarLogExcel(id, nome){
        $.ajax({
            url: "{{ route('inventario_novo.exportar_log_excel') }}",
            type: 'POST',
            dataType: 'json',
            data: {_token: '{{ csrf_token() }}', id:id },
            success: function(callback){
                var dados = callback.response;
                for(var field in dados.caminhos){
                    $('<form action="{{ route('inventario_novo.download') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="caminho" value="'+dados.caminhos[field]+'">\
                    </form>').appendTo('body').submit().remove();
                }
            }
        });
    }

    function viewExportarPecaNaoEncontradaExcel($this, $valor){
        var html = $valor+"&nbsp;&nbsp;&nbsp;<i class=\"btn-excel float-right\" data-id=\""+$this.id+"\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Peça Não Encontrada\" onclick=\"showExportarPecaNaoEncontradaExcel("+$this.id+")\"></i>";
        return html;
    }

    function showExportarPecaNaoEncontradaExcel(id){
        $.ajax({
            url: "{{ route('inventario_novo.exportar_peca_nao_encontrada_excel') }}",
            type: 'POST',
            dataType: 'json',
            data: {_token: '{{ csrf_token() }}', id:id },
            success: function(callback){
                var dados = callback.response;
                for(var field in dados.caminhos){
                    $('<form action="{{ route('inventario_novo.download') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="caminho" value="'+dados.caminhos[field]+'">\
                    </form>').appendTo('body').submit().remove();
                }
            }
        });
    }


    function createBtnFinalizar($url, $dados){
        $html = '';
        if($dados.data_final == '' ){
            var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Finalizar {{ CustomView::programaName() }}\" class=\"btn-pedido\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Finalizar\"></a>";
        }
        
        return $html;
    }

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: '{{ route('inventario_novo.modal.finalizar') }}',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_linha_edit_delete', title, body, modal_class);
                var modal = $("#modal_linha_edit_delete");
            }
        });
    }
    
    function createBtShowContagem($codigo_estabelecimento, $contagem, $quantidade, $criterios){
        var $html = "";
        $html = "<a href=\"#\" onclick=\"openContagem('"+$codigo_estabelecimento+"', "+$contagem+", '"+$criterios+"')\">"+$quantidade+"</a>";
        return $html;
    }
    
    function openContagem($estabelecimento, $contagem, $criterios){
		$.ajax({
			url: '{{ route('inventario.modal.contagem') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
                criterios: $criterios,
                contagem: $contagem
			},
			success: function(body){
				createModal('modal_contagem', "Contagem "+$contagem+" do estabelecimento "+$estabelecimento, body, 'modal-lg');
			}
		});
    }
    
    function openProduto($criterios, $produto){
        $.ajax({
            url: '{{ route('inventario.modal.produto') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                criterios: $criterios
            },
            success: function(body){
                createModal('modal_produto', "Abertura do produto "+$produto, body, 'modal-lg');
            }
        });
    }

    function openDiferenca($criterios, $produto){
        $.ajax({
            url: '{{ route('inventario.modal.diferenca') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                criterios: $criterios
            },
            success: function(body){
                createModal('modal_produto', "Abertura de difereça do produto "+$produto, body, 'modal-lg');
            }
        });
    }

    function aplicarEstoque(){
        $criterios = [];
        $(document).find("[name^='aplicar_estoque']:checked").each(function(){
            $criterios.push($(this).val());
        });
        $.ajax({
            url: '{{ route('inventario.aplicar_estoque') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                criterios: $criterios
            },
            success: function(callback){
                if(callback.status == 'success'){
                    $(document).find("[name^='aplicar_estoque']:checked").each(function(){
                        var $this = this;
                        $($this).parents('tr').remove();
                    });
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', data.message);
                }else{
                    message('Atenção', 'Ocorreu um erro inesperado.');
                }
            }
        });
    }

    function modalExcluirProdutosInventario($id, $row){
        $("[data-toggle='tooltip']").tooltip('hide');
        $.ajax({
            url: '{{ route('inventario.excluirproduto') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status == 'success'){
                    $($row).remove();
                }else{
                    message('Atenção', callback.message);
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', data.message);
                }else{
                    message('Atenção', 'Ocorreu um erro inesperado.');
                }
            }
        });
    }

    function createBtNaoInventariados(estabelecimento, estabelecimento_codigo){
        var $html = '';
        $html = '<a href="#"class="bt-view" onclick="openNaoInventario(\''+estabelecimento_codigo+'\')" data-toggle="tooltip" data-trigger="hover" title="produtos não inventariados"></a>';
        return $html;
    }

    function openNaoInventario($estabelecimento){
        $.ajax({
            url: '{{ route('inventario.modal.nao_inventariado') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                estabelecimento: $estabelecimento
            },
            success: function(body){
                createModal('modal_produto', "Itens não inventariados", body, 'modal-lg');
            }
        });
    }
@endsection
