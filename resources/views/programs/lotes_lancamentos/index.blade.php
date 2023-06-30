@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="date_ini" id="date_ini" value="{{ date('01/m/Y') }}" placeholder="Data inícial" maxlength="20" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="date_end" id="date_end" value="{{ date('d/m/Y') }}" placeholder="Data final" maxlength="20" />
        </div>
        <div class="col-lg-2">
            <select name="empresa" id="empresa">
                <option value="">Estábelecimento</option>
                @foreach(returnEmpresasPrologusView() as $key => $value)
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
    <table class="table table-striped exportFilter" id="table-filters">
        <thead>
            <tr>
                <th>Estábelecimento</th>
                <th>Arquivo</th>
                <th>Status</th>
                <th>Lançamentos gerados</th>
                <th>Lançamentos gerados com erro</th>
                <th>Gerar Novamente</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-modal')
<div class="modal fade" id="model_lotes_lancamentos" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg-tabela" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Lançamentos gerados <span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
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
                showModal($(this).data('route'), $(this).data('status'));
            });
            $(document).find(".bt-gerar").off("click");
            $(document).find(".bt-gerar").on("click", function(event){
                event.stopPropagation();
                gerarLote($(this).data('route'), $(this));
            });
        });
    });
    function showModal(url, $status){
        if(parseInt($status) == 1){
            $("#model_lotes_lancamentos").find(".modal-title").find("span").html("com erro");
        }
        $("#model_lotes_lancamentos").modal("toggle");
        $("#model_lotes_lancamentos").off('shown.bs.modal');
        $("#model_lotes_lancamentos").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: url,
                success: function(data){
                    modal.find('.modal-body').html(data);
                }
            });
        });
        $("#model_lotes_lancamentos").off('hidden.bs.modal');
        $("#model_lotes_lancamentos").on('hidden.bs.modal', function (e) {
            $("#model_lotes_lancamentos").find('.modal-body').html('');
            $("#model_lotes_lancamentos").find(".modal-title").find("span").html('');
        });
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('lotes_lancamentos.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].empresa,
                            data[field].nome,
                            data[field].status,
                            data[field].qtd_gerados,
                            data[field].qtd_erros,
                            data[field].btn_gerar,
                        ];
                        fields_filter.push(temp_field);
                    }
                    var rows = table_filters.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
                    $(rows).each(function(){
                        var tds = $(this).find("td");
                        tds.eq(2).addClass("tb_number");
                        tds.eq(3).addClass("tb_number");
                    });
                    $(document).find(".bt-view").off("click");
                    $(document).find(".bt-view").on("click", function(event){
                        event.stopPropagation();
                        showModal($(this).data('route'), $(this).data('status'));
                    });
                    $(document).find(".bt-gerar").off("click");
                    $(document).find(".bt-gerar").on("click", function(event){
                        event.stopPropagation();
                        gerarLote($(this).data('route'), $(this));
                    });
                }
            }
        });
    }
    function gerarLote(url, $this){
        $.ajax({
            url: url,
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}"},
            method: 'POST',
            success: function(data){
                filterAjax($("#form_filter").serialize());
            }
        });
    }
@endsection
