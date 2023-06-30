@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog" id="form_filter_dialog" onsubmit="return false;">
        @csrf
        <input type="hidden" name="estabelecimento" value="{{ $estabelecimento }}">
        <div class="content-fields">
            <div class="col-lg-3">
                <input type="text" name="nome" id="nome" value="" placeholder="Nome" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="codtran" id="codtran" value="" placeholder="Código da transportadora" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <select name="viatran" id="viatran">
                    <option value="" selected>Via de transporte</option>
                    <option value="0">NOSSO CARRO</option>
                    <option value="1">RODOVIÁRIO</option>
                    <option value="2">FERROVIÁRIO</option>
                    <option value="3">AÉREO</option>
                    <option value="4">FLUVIAL</option>
                    <option value="5">MARÍTIMO</option>
                    <option value="6">RETIRADA</option>
                </select>
            </div>
            <div class="col-lg-2">
                <input type="text" name="cidade" id="cidade" value="" placeholder="Cidade" maxlength="250" />
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-transportador">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>CNPJ</th>
                <th>Via de transporte</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th>visualizar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script>
    table_dialog = [];
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
        $('[data-toggle="tooltip"]').tooltip();
        table_dialog = $("#table-filters-dialog-transportador").DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 10,
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
                    "targets": ($('#able-filters-dialog-transportador thead th').length - 1),
                    "orderable": false
                },
            ]
        });
        table_dialog.on('draw', function () {
            $(document).find(".bt-view-transportador").off("click");
            $(document).find(".bt-view-transportador").on("click", function(event){
                event.stopPropagation();
                showModalViewTransportador($(this));
            });
        });
    });
    function changeTextOverflowTrs($dados){
        $.each($dados, function(k, line){
            $.each(line, function(k1, dado){
                $dados[k][k1] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+dado+"\">"+dado+"</div></div>";
            });
        });
        return $dados;
    }
    function filterAjaxDialog(data_form){
        var $return;
        table_dialog.clear().draw();
        $.ajax({
            url: "{{ route('transportador.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].codtran,
                            data[field].nome,
                            data[field].cgc,
                            data[field].viatran,
                            data[field].cidade,
                            data[field].estado,
                            createBtnViewTransportador("{{ route('transportador.view') }}", data[field].codtran)
                        ];
                        fields_filter.push(temp_field);
                    }
                    // fields_filter = changeTextOverflowTrs(fields_filter);
                    table_dialog.rows.add(fields_filter).order([ 2, 'asc' ] ).draw().nodes();
                    $(document).find(".bt-view-transportador").off("click");
                    $(document).find(".bt-view-transportador").on("click", function(event){
                        event.stopPropagation();
                        showModalViewTransportador($(this));
                    });
                }
            }
        });
    }
    function createBtnViewTransportador($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" class=\"bt-view bt-view-transportador\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\"></a>";
        return $html;
    }

    function showModalViewTransportador($this){
        var url = $($this).data("route");
        var id = $($this).data("id");
        $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", codigo: id},
            method: 'POST',
            success: function($body){
                createModal('modal_transportador_view', 'Dados de transportador', $body, '')
            }
        });
    }
</script>
@endsection