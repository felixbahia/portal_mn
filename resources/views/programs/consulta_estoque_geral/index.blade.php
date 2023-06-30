@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento_filtro', 'class' => 'form-control', 'placeholder' => 'Estabelecimento'])}}
        </div>
        <div class="col-lg-2">
            <div class="input-group">
                {{ Form::text('grupo_filter', '', ['id' => 'grupo_filter', 'class' => 'form-control', 'placeholder' => 'Grupos']) }}
            </div>
        </div>
        <div class="col-lg-2">
            <div class="input-group">
                {{ Form::text('subgrupo_filter', '', ['id' => 'subgrupo_filter', 'class' => 'form-control', 'placeholder' => 'Subgrupos']) }}
            </div>
        </div>
        <div class="col-lg-2">
            <div class="input-group">
                {{ Form::text('linha_filter', '', ['id' => 'linha_filter', 'class' => 'form-control', 'placeholder' => 'Linhas']) }}
            </div>
        </div>
        <div class="col-lg-2">
            <div class="input-group">
                {{ Form::text('marca_filter', '', ['id' => 'marca_filter', 'class' => 'form-control', 'placeholder' => 'Marcas']) }}
            </div>
        </div>
    </div>
    <div class="content-buttons row">
        <div class="col-xs-12 col-sm-6">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table_consulta_estoque">
        <thead>

          
        	<tr>
                <th rowspan="2" class="empresa" rowspan="2">Empresa</th>
                <th class="number_format kg" rowspan="2">Kg</th>
                <th class='number_format metros'rowspan="2">Metros </th>
                <th class='number_format metros' rowspan="2">Outras Unidades </th>
                <th class='number_format' rowspan="2">Peças </th>
                <th class='number_format custo' colspan="6">Custo</th>

            </tr>
            <tr>

                <th class='number_format custo'>Contábil (Nasajon)</th>
                <th class='number_format custo'>Médio Contábil (Portal)</th>
                <th class='number_format custo'>Médio Gerencial (Portal)</th>
                <th class='number_format custo'>Gerencial</th>
                <th class='number_format custo'>Médio Armazém</th>
                <th class='number_format custo'>Gerencial Armazém</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td><strong><h5>Total:</h5></strong></td>
                <td id="peso_total" class="number_format"></td>
                <td id="metros_total" class="number_format"></td>
                <td id="outrasunidades_total" class="number_format"></td>
                <td id="rolos_total" class="number_format"></td>
                <td id="custo_contabil_total" class="number_format"></td>
                <td id="custo_contabil_portal_total" class="number_format"></td>
                <td id="custo_gerencial_portal_total" class="number_format"></td>
                <td id="custo_gerencial_total" class="number_format"></td>
                <td id="custo_armazem_total" class="number_format"></td>
                <td id="custo_armazem_total_ultimo" class="number_format"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready(function(){
    form = $(document).find('#form_filter');
    form.find("#btn-filterform").on("click", function(){
        filterAjax();
    });
    $(document).find("#grupo_filter").autocomplete(optionsAutoComplete("grupo"));
    $(document).find("#subgrupo_filter").autocomplete(optionsAutoComplete("subgrupo"));
    $(document).find("#linha_filter").autocomplete(optionsAutoComplete("linha"));
    $(document).find("#marca_filter").autocomplete(optionsAutoComplete("marca"));
});

function filterAjax(){
	$(document).find("#custo_contabil_total").html('');
	$(document).find("#custo_contabil_portal_total").html('');
	$(document).find("#custo_gerencial_portal_total").html('');
	$(document).find("#custo_gerencial_total").html('');
    $(document).find("#custo_armazem_total").html('');
    $(document).find("#custo_armazem_total_ultimo").html('');
	$(document).find("#peso_total").html('');
	$(document).find("#metros_total").html('');
    $(document).find("#rolos_total").html('');
	$(document).find("#outrasunidades_total").html(''); 
    estabelecimentos = $("#estabelecimento_filtro").val();
    filterClear();
    $.ajax({
        method: 'POST',
        dataType: 'json',
        url: "{{ route('consulta_estoque_geral.consulta')}}",
        data: {_token:'{{ csrf_token() }}',estabelecimento: estabelecimentos, grupo: $('#grupo_filter').val(), subgrupo: $('#subgrupo_filter').val(), linha: $('#linha_filter').val(), marca: $('#marca_filter').val()},
        success: function(callback){
			filterClear();
            var fields_consulta = [];
            for(var field in callback.response.dados){
                var temp_field = [
                    callback.response.dados[field].empresa,
                    callback.response.dados[field].peso,
                    callback.response.dados[field].metros,
                    callback.response.dados[field].outras_unidades,
                    callback.response.dados[field].rolos,
                    callback.response.dados[field].valor_custo_contabil_nasajon,
                    callback.response.dados[field].valor_custo_contabil_portal,
                    callback.response.dados[field].valor_custo_gerencial_portal,
                    callback.response.dados[field].valor_custo_gerencial,
                    callback.response.dados[field].valor_custo_armazem,
                    callback.response.dados[field].valor_custo_armazem_ultimo,
                ];
                fields_consulta.push(temp_field);
            }
            $(document).find("#custo_contabil_total").html(callback.response.valor_custo_contabil_nasajon);
            $(document).find("#custo_contabil_portal_total").html(callback.response.custo_contabil_portal_total);
            $(document).find("#custo_gerencial_portal_total").html(callback.response.custo_gerencial_total_portal);
            $(document).find("#custo_gerencial_total").html(callback.response.custo_gerencial_total);
            $(document).find("#custo_armazem_total").html(callback.response.custo_armazem_total);
            $(document).find("#custo_armazem_total_ultimo").html(callback.response.custo_armazem_total_ultimo);
            $(document).find("#peso_total").html(callback.response.peso_total);
            $(document).find("#metros_total").html(callback.response.metros_total);
            $(document).find("#outrasunidades_total").html(callback.response.outras_unidades_total);
            $(document).find("#rolos_total").html(callback.response.rolos_total);
            table_estoque_geral.rows.add(fields_consulta).draw().nodes();
        },
        error: function(error){
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }
    });
}

function filterClear(){
    table_estoque_geral.clear().draw();
}

table_estoque_geral = $('#table_consulta_estoque').DataTable({
    "searching": false,
    "paging": false,
    "lengthChange": false,
    "info": false,
    "pageLength": 15,
    "orderMulti": false,
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
            "targets": 'number_format',
            className: 'number_format'
        },
        {
            "targets": 'empresa',
            'width': '10%'
        },
        {
            "targets": 'kg',
            'width': '10%'
        },
        {
            "targets": 'metros',
            'width': '10%'
        },
        {
            "targets": 'custo',
            'width': '10%'
        }
    ]
});

table_estoque_geral.on('draw', function () {
    $(document).find(".bt-view-produto").off("click");
    $(document).find(".bt-view-produto").on("click", function(event){
        event.stopPropagation();
        showModalProduto($(this));
    });
});


function detalhe(estabelecimento, grupo, subgrupo, linha, marca,estab){

    var estabelecimentos = estabelecimento;
    var grupos = grupo;
    var subgrupos = subgrupo;
    var linhas = linha;
    var marcas = marca;


    var title =linha+ "Consulta Estoque em Geral - " + estab ;
    $.ajax({
        url: "{{route('consulta_estoque_geral.detalhes')}}",
        data: {_token: '{{ csrf_token() }}', estabelecimentos: estabelecimentos, grupos: grupos, linhas: linhas, marcas: marcas},
        method: 'POST',
        success: function(body){
            createModal("show_detalhe", title, body, 'modal-lg');
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
                filterAjax($("#form_filter"));
            }, 100);
        }
    };
}

function showModalProduto($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filtro: filter},
        method: 'POST',
        success: function(body){
            createModal("model_analise_produto", title, body, 'modal-lg');
        }
    });
}
@endsection
