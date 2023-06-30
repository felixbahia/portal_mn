@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome / Razão Social" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="nome_guerra" id="nome_guerra" value="" placeholder="Nome Fantasia / Apelido" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="cnpj_cpf" id="cnpj_cpf" value="" placeholder="CNPJ / CPF" maxlength="250" />
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
    <table class="table table-striped produto-analise" id="table-filters-aprove">
        <thead>
            <tr>
                <th class="th-reprove">Reprovar</th>
                <th>CNPJ / CPF</th>
                <th>Nome / Razão Social</th>
                <th>Vendedor</th>
                <th>Pedidos</th>
                <th>Valor Total</th>
                <th class="th-view">Serasa</th>
                <th class="th-view">Visualizar</th>
                <th class="th-aprove">Aprovar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        table_filters = $('#table-filters-aprove').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "autoWidth": false,
            "pageLength": 15,
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
            "order": [[ 1, "desc" ]],
            "columnDefs": [
                {
                    "targets": ['th-aprove','th-reprove','th-view'],
                    "orderable": false,
                    'width': '80px'
                }
            ]
        });
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModalView($(this));
            });
            $(document).find(".bt-aprove").off("click");
            $(document).find(".bt-aprove").on("click", function(event){
                event.stopPropagation();
                aprovarCadastro($(this));
            });
            $(document).find(".bt-reprove").off("click");
            $(document).find(".bt-reprove").on("click", function(event){
                event.stopPropagation();
                reprovarCadastro($(this));
            });
        });
    });

    function showModalView($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var $nome = $($this).data("nome");
        var title = "Visualizar {{ CustomView::programaName() }} - "+$nome+"";
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_cliente_novo_view', title, body, 'modal-lg');
                var modal = $("#modal_cliente_novo_view");
            }
        });
    }

    function reprovarCadastro($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var $nome = $($this).data("nome");
        var title = "Visualizar {{ CustomView::programaName() }} - "+$nome+"";
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_cliente_novo_reprovar', title, body, '');
                var modal = $("#modal_cliente_novo_reprovar");
            }
        });
    }

    function aprovarCadastro($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var $nome = $($this).data("nome");
        var title = "Visualizar {{ CustomView::programaName() }} - "+$nome+"";
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_cliente_novo_aprovar', title, body, '');
                var modal = $("#modal_cliente_novo_aprovar");
            }
        });
    }

    function createBtnSerasa($url, $id, $nome){
        var $html = "<a href=\"#\" class=\"bt-view\" data-id=\""+$id+"\" data-nome=\""+$nome+"\" data-route=\""+$url+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Serasa\"></a>";
        return $html;
    }
    function createBtnView($url, $id, $nome){
        var $html = "<a href=\"#\" class=\"bt-view\" data-id=\""+$id+"\" data-nome=\""+$nome+"\" data-route=\""+$url+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\"></a>";
        return $html;
    }
    function createBtnAprovar($url, $id, $nome){
        var $html = "<div class=\"bt-aprove\" data-id=\""+$id+"\" data-nome=\""+$nome+"\" data-route=\""+$url+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Aprovar\"></div>";
        return $html;
    }
    function createBtnReprovar($url, $id, $nome){
        var $html = "<div class=\"bt-reprove\" data-id=\""+$id+"\" data-nome=\""+$nome+"\" data-route=\""+$url+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Reprovar\"></div>";
        return $html;
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('cliente_novo.aprovacao.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            createBtnReprovar("{{ route('cliente_novo.aprovacao.confirmacao.reprovado') }}", data[field].id, data[field].nome_razao),
                            data[field].cpf_cnpj,
                            data[field].nome_razao,
                            data[field].vendedor,
                            data[field].pedidos_count,
                            data[field].pedidos_total,
                            createBtnSerasa("{{ route('cliente_novo.aprovacao.serasa') }}", data[field].id, data[field].nome_razao),
                            createBtnView("{{ route('cliente_novo.view') }}", data[field].id, data[field].nome_razao),
                            createBtnAprovar("{{ route('cliente_novo.aprovacao.confirmacao.aprovado') }}", data[field].id, data[field].nome_razao)
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
                }
            }
        });
    }
@endsection