@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog" id="form_filter_dialog" onsubmit="return false;">
        @csrf
        <input type="hidden" name="conta_ordem" id="conta_ordem" value="{{ $conta_ordem }}" />
        <input type="hidden" name="estabelecimento" id="estabelecimento" value="{{ $estabelecimento }}" />
        <div class="content-fields">
            <div class="col-lg-3">
                <input type="text" name="nome_razao" id="nome_razao" value="" placeholder="Nome / Razão Social" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="codigo" id="codigo" value="" placeholder="Código de cadastro" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <input type="text" name="nome_guerra" id="nome_guerra" value="" placeholder="Nome Fantasia / Apelido" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="cpf_cnpj" id="cpf_cnpj" value="" placeholder="CNPJ / CPF" maxlength="250" />
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
            <button name="btn-create" id="btn-create" class="btn-create">Novo cadastro</button>
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog table-filter-clientes" id="table-filters-dialog-cliente">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome / Razão Social</th>
                <th>Nome Fantasia / Apelido</th>
                <th>CNPJ / CPF</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th class="th_view">Visualizar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    table_dialog = [];
    table_dialog = $("#table-filters-dialog-cliente").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "pageLength": 10,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
        'columnDefs': [
            {
                "targets": 'th_view',
                "width": '50px',
                "class": 'th_view',
                "orderable": false
            },
        ]
    });
    $(document).ready( function () {
        $("#form_filter_dialog").find("#btn-filterform").on("click", function(){
            filterAjaxDialog($("#form_filter_dialog").serialize());
        });
        $('#table-filters-dialog-cliente').find("td").off('mouseenter');
        $('#table-filters-dialog-cliente').find("td").on('mouseenter', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
                $this.attr('data-original-title', $this.text());
            }
        });
        $("#form_filter_dialog").find("#btn-create").on("click", function(){
            showModalCreate();
        });
        $('[data-toggle="tooltip"]').tooltip();
        table_dialog.on('draw', function () {
            $(document).find('#table-filters-dialog-cliente').find(".bt-view").off("click");
            $(document).find('#table-filters-dialog-cliente').find(".bt-view").on("click", function(event){
                showModalViewCliente($(this));
            });
        });
    });
    function showModalCreate(){
        $.ajax({
            url: '{{ route('cliente_novo.create') }}',
            method: 'GET',
            success: function(body){
                var title = 'Cadastro de Cliene Novo';
                createModal('modal_cliente_novo_adicionar', title, body, "modal-lg");
                var modal = $("#modal_cliente_novo_adicionar");
            }
        });
    }
    function showModalViewCliente($this){
        var id = $($this).data("id");
        $.ajax({
            url: "{{ route('cliente.view') }}",
            data: {_token: "{{ csrf_token() }}", codcad: id, estabelecimento: '{{ $estabelecimento }}'},
            method: 'POST',
            success: function(body){
                var title = 'Visualizar Cliente';
                createModal('modal_cliente_view', title, body, "modal-lg");
                var modal = $("#modal_cliente_view");
            }
        });
    }
    function changeTextOverflowTrs($dados){
        $.each($dados, function(k, line){
            $.each(line, function(k1, dado){
                $dados[k][k1] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+dado+"\">"+dado+"</div></div>";
            });
        });
        return $dados;
    }
    function createBtnView($id){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\"></a>";
        return $html;
    }
    function filterAjaxDialog(data_form){
        var $return;
        table_dialog.clear().draw();
        $.ajax({
            url: "{{ route('cliente.filterCadastro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                table_dialog.clear().draw();
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].codigo,
                            data[field].nome,
                            data[field].apelido_guerra,
                            data[field].cpf_cnpj,
                            data[field].cidade,
                            data[field].estado,
                            createBtnView(data[field].codigo)
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_dialog.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
                    $(document).find('#table-filters-dialog-cliente').find(".bt-view").off("click");
                    $(document).find('#table-filters-dialog-cliente').find(".bt-view").on("click", function(event){
                        showModalViewCliente($(this));
                    });
                }
            }
        });
    }
</script>
@endsection