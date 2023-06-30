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
    <table class="table table-striped" id="table_consulta_peca">
        <thead>          
        	<tr>
                <th class="empresa">Empresa</th>
                <th class='number_format custo' colspan="5">Estoque</th>
                <th class='number_format custo' colspan="5">Fracionadas</th>

            </tr>

            <tr>
                <th></th>
                <th class='number_format'>%</th>
                <th class='number_format'>Peças</th>
                <th class="number_format kg">Kg</th>
                <th class='number_format metros'>Metros </th>
                <th class='number_format metros'>Outras Unidades </th>
                <th class='number_format'>%</th>
                <th class='number_format'>Peças</th>
                <th class="number_format kg">Kg</th>
                <th class='number_format metros'>Metros </th>
                <th class='number_format metros'>Outras Unidades </th>

            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td><strong><h5>Total:</h5></strong></td>
                <td id="porcentagem_total" class="number_format"></td>
                <td id="rolos_total" class="number_format"></td>
                <td id="peso_total" class="number_format"></td>
                <td id="metros_total" class="number_format"></td>
                <td id="outrasunidades_total" class="number_format"></td>
                <td id="porcentagem_frac_total" class="number_format"></td>
                <td id="qtde_pecas_frac_total" class="number_format"></td>
                <td id="peso_frac_total" class="number_format"></td>               
                <td id="metros_frac_total" class="number_format"></td>
                <td id="outrasunidades_frac_total" class="number_format"></td>
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



table_pecas_fracionadas = $('#table_consulta_peca').DataTable({
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
        }
    ]
});

function filterAjax(){
    estabelecimentos = $("#estabelecimento_filtro").val();
    grupos = $("#grupo_filtro").val();
    $(document).find("#porcentagem_total").html('');
    $(document).find("#rolos_total").html('');
	$(document).find("#peso_total").html('');
	$(document).find("#metros_total").html('');
	$(document).find("#outrasunidades_total").html(''); 
    $(document).find("#porcentagem_frac_total").html('');
    $(document).find("#qtde_pecas_frac_total").html(''); 
    $(document).find("#peso_frac_total").html('');  
    $(document).find("#metros_frac_total").html('');  
    $(document).find("#outrasunidades_frac_total").html(''); 
    filterClear();    
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: "{{ route('pecas_fracionadas.consulta')}}",
        data: {_token:'{{ csrf_token() }}',estabelecimento: estabelecimentos, grupo: $('#grupo_filter').val(), subgrupo: $('#subgrupo_filter').val(), linha: $('#linha_filter').val(), marca: $('#marca_filter').val()},
        success: function(callback){
			filterClear();
            var fields_consulta = [];
            for(var field in callback.response.dados){
                var temp_field = [
                    
                    callback.response.dados[field].empresa,
                    callback.response.dados[field].porcentagem,
                    createPecaEstoque(callback.response.dados[field].rolos, callback.response.dados[field].codigo_estabel), 
                    callback.response.dados[field].peso,
                    callback.response.dados[field].metros,
                    callback.response.dados[field].outras_unidades,
                    callback.response.dados[field].porcentagem_frac,
                    createPecaFrac(callback.response.dados[field].qtde_pecas_frac, callback.response.dados[field].codigo_estabel),
                    callback.response.dados[field].peso_frac,    
                    callback.response.dados[field].metros_frac,
                    callback.response.dados[field].outras_unidades_frac,                
                ];
                fields_consulta.push(temp_field);
            }

            $(document).find("#porcentagem_total").html(callback.response.porcentagem_total);
            $(document).find("#rolos_total").html(callback.response.rolos_total);
            $(document).find("#peso_total").html(callback.response.peso_total);
            $(document).find("#metros_total").html(callback.response.metros_total);
            $(document).find("#outrasunidades_total").html(callback.response.outrasunidades_total);
            $(document).find("#porcentagem_frac_total").html(callback.response.porcentagem_frac_total);
            $(document).find("#qtde_pecas_frac_total").html(callback.response.qtde_pecas_frac_total);
            $(document).find("#peso_frac_total").html(callback.response.peso_frac_total);
            $(document).find("#metros_frac_total").html(callback.response.metros_frac_total);
            $(document).find("#outrasunidades_frac_total").html(callback.response.outrasunidades_frac_total);
            table_pecas_fracionadas.rows.add(fields_consulta).draw().nodes();
        },
        error: function(error){
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
        }
    });
}

function filterClear(){
    table_pecas_fracionadas.clear().draw();
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

function createPecaEstoque($rolos, $empresa){
    grupo    = $('#grupo_filter').val();
    subgrupo = $('#subgrupo_filter').val();
    linha    = $('#linha_filter').val();
    marca    = $('#marca_filter').val();
    
    var html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes Geral Peças\" onclick=\"showModalPecaEst('"+$empresa+"','"+grupo+"','"+subgrupo+"','"+linha+"','"+marca+"');\">"+$rolos+"</a>";

    return html;
}

function showModalPecaEst($empresa, $grupo, $subgrupo, $linha, $marca){
    var url = '{{ route('pecas_fracionadas.exibir.geral') }}';
    var modal_class = 'modal-lg';
    var title = 'Detalhes Geral Peças';
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", estabelecimento: $empresa, grupo: $grupo, subgrupo: $subgrupo, linha: $linha, marca: $marca },
        success: function(body){
            createModal('modal_message_edit', title, body, modal_class);
        }
    });
}

function createPecaFrac($rolos, $empresa){
    grupo    = $('#grupo_filter').val();
    subgrupo = $('#subgrupo_filter').val();
    linha    = $('#linha_filter').val();
    marca    = $('#marca_filter').val();
    
    var html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes Peças Fracionadas\" onclick=\"showModalPecaFrac('"+$empresa+"','"+grupo+"','"+subgrupo+"','"+linha+"','"+marca+"');\">"+$rolos+"</a>";

    return html;
}

function showModalPecaFrac($empresa, $grupo, $subgrupo, $linha, $marca){
    var url = '{{ route('pecas_fracionadas.exibir.fracionadas') }}';
    var modal_class = 'modal-lg';
    var title = 'Detalhes Peças Fracionadas';
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", estabelecimento: $empresa, grupo: $grupo, subgrupo: $subgrupo, linha: $linha, marca: $marca },
        success: function(body){
            createModal('modal_message_edit', title, body, modal_class);
        }
    });
}

@endsection
