@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
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
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-filter-clientes" id="table-filters">
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
@endsection

@section('script-footer')
    $(document).ready( function () {
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

    function showModal($this){
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
    function createBtnView($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\"></a>";
        return $html;
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
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
                            createBtnView("{{ route('transportador.view') }}", data[field].codtran)
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 2, 'asc'] ).draw().nodes();
                    $(document).find(".bt-view").off("click");
                    $(document).find(".bt-view").on("click", function(event){
                        event.stopPropagation();
                        showModal($(this));
                    });
                }
            }
        });
    }
@endsection
