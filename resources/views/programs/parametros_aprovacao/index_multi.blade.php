@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
	        <select name="empresa" id="empresa">
	            <option disabled>Estabelecimento</option>
	            @foreach(returnEmpresasPrologusView() as $key => $value)
	            <option value="{{ str_pad($key, 2, "0", STR_PAD_LEFT) }}" {{ Auth::user()->empresa_padrao_id == $key ? "selected='selected'": ''}}>{{ $value }}</option>
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
    <table class="table table-striped" id="table-filters-precos">
        <thead>
            <tr>
                <th rowspan="2" class="border-right align-middle">Estabelecimento</th>
                <th colspan="{{ $count_tipos }}" class="border-right text-center">Percentual limite de desconto</th>
                <th colspan="{{ $count_tipos }}" class="border-right text-center">Prazo adicional limite</th>
            </tr>
            <tr>
                @foreach ($tipo_usuarios as $key => $value)
                <th>{{$value->nome}}</th>
                @endforeach  
                @foreach ($tipo_usuarios as $key => $value)
                <th>{{$value->nome}}</th>
                @endforeach              
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-modal')

// Modal de Adição

// Modal de Edição

// Modal de Exclusão
@endsection

@section('script-footer')

    $(document).ready( function () {


        $("#coluna").on("change",function(){
            table_filters.clear().draw();
        });

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });

	});

    table_filters = $('#table-filters-precos').DataTable({
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
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });

    function showErrorsInputs(form, input, message){
        
        if (input == 'empresa' || input == 'estado'){
            var $input = $(form).find("select[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
        else{
            var $input = $(form).find("input[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
    }

    function filterAjax(data_form){
        var $return;	    
        table_filters.clear().draw();
	    var form = $("#form_filter");

        form.find('.error-message').remove();

        $.ajax({
            url: "{{ route('parametros_aprovacao.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                    
                var linhas = data;

                if(linhas.length > 0){

                    var fields_filter = [];

                    for(var field in data){

                        var temp_field = [
                            linhas[field].GRUPO,
                            linhas[field].CODPRD,
                            linhas[field].DESCR,
                            linhas[field].MARCA,
                            linhas[field].LINHA,
                            linhas[field].coluna_a,
                            linhas[field].coluna_b,
                            linhas[field].coluna_c,
                            linhas[field].prazo_vista,
                            linhas[field].prazo_15,
                            linhas[field].prazo_30,
                            linhas[field].prazo_45,
                            linhas[field].prazo_60,
                            linhas[field].aliquota_real +"%"
                        ];

                        fields_filter.push(temp_field);

                    }


                    table_filters.rows.add(fields_filter).draw().nodes();

                    var coluna = new Array("coluna_a", "coluna_b", "coluna_c");
                    var prazo = new Array("prazo_vista", "prazo_15", "prazo_30", "prazo_45", "prazo_60");

                    if(coluna.indexOf($("#coluna").val()) != -1){

                        table_filters.columns([5,6,7]).visible(false);
                        table_filters.columns([8,9,10,11,12,13]).visible(true);

                    }
                    else if (prazo.indexOf($("#coluna").val()) != -1){

                        table_filters.columns([8,9,10,11,12]).visible(false);
                        table_filters.columns([5,6,7,13]).visible(true);

                    }
                }

            },
            error: function(data){
                var errors = data.responseJSON.errors;
                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
            	}
            }
        });
    }
@endsection