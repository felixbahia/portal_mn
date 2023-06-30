@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <select name="estabelecimento" id="estabelecimento">
                <option value="">Estábelecimento</option>
                @foreach($estabelecimentos as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <select name="usuario" id="usuario">
                <option value="">Coletores</option>
                @foreach($coletores as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
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
                <th class="tb_number">1ª contagem</th>
                <th class="tb_number">2ª contagem</th>
                <th>Itens não inventariados</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
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
        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
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
            ],
            "order": [[ 0, 'asc' ]]
        });
        $("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter").serialize());
        });
        buscaDados($("#form_filter").serialize());
    });
    function buscaDados(data_form){
        table_filters.clear().draw();
        $.ajax({
            url: '{{ route('inventario.filtro') }}',
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
                            dados[field].estabelecimento,
                            createBtShowContagem(dados[field].estabelecimento, "1", dados[field].contagem1, dados[field].criterios),
                            createBtShowContagem(dados[field].estabelecimento, "2", dados[field].contagem2, dados[field].criterios),
                            createBtNaoInventariados(dados[field].estabelecimento, dados[field].estabelecimento_codigo)
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
