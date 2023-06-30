@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-4"> 
                {{ Form::text('ano', $data, ['id' => 'ano', 'class' => 'form-control data', 'placeholder' => 'Ano', 'maxlength' => '20']) }}
            </div>
            @if (!in_array(Auth::user()->tipo_usuario_id, [12,16]))
            <div class="col-lg-8"> 
                {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
            </div>
            @endif
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
    <table class="table table-striped table-produto-analise" id="table-filters">
        <thead>
            <tr>
                <th>Representante</th>
                <th class="tb_number">Vendas Ano Anterior</th>
                <th class="tb_number">Valor Crédito</th>
                <th class="tb_number">Valor Devolução</th>
                <th class="tb_number">Valor Frete</th>
                <th class="tb_number">Saldo</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total:</td>
                <td class="tb_number" id="total_vendas_ano"></td>
                <td class="tb_number" id="total_valor_credito"></td>
                <td class="tb_number" id="total_valor_devolucao"></td>
                <td class="tb_number" id="total_valor_frete"></td>
                <td class="tb_number" id="total_saldo"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    form = $(document).find("#form_filter");

    form.find("#btn-filterform").off("click");
    form.find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    form.find('.data').datepicker({ 
        format: 'yyyy',
        zIndex: 2000,
        language: 'pt-BR',
        autoHide: true,
    });
    form.find('.data').mask('0000');

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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                "orderable": false
            }
        ],
    });
});

function filterClear(){
    table_filters.clear().draw();
}

function filterAjax(){
    filterClear();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('beneficio_representante.frete_devolucao.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            linhas = [];
            
            for (var index in data.response.movimentacoes){
                temp_array = [
                    data.response.movimentacoes[index].vendedor,
                    data.response.movimentacoes[index].vendas_ano,
                    data.response.movimentacoes[index].valor_credito,
                    createBtViewDevolução(data.response.movimentacoes[index], "Devoluções do Vendedor: "+data.response.movimentacoes[index].vendedor),
                    data.response.movimentacoes[index].valor_frete,
                    data.response.movimentacoes[index].saldo,
                ];
                linhas.push(temp_array);
            }
            table_filters.rows.add(linhas).draw();

            $(document).find('#total_vendas_ano').html(data.response.total_vendas_ano);
            $(document).find('#total_valor_credito').html(data.response.total_valor_credito);
            $(document).find('#total_valor_devolucao').html(data.response.total_valor_devolucao);
            $(document).find('#total_valor_frete').html(data.response.total_valor_frete);
            $(document).find('#total_saldo').html(data.response.total_saldo);
        }
    });
}

function createBtViewDevolução($value, $title){
    html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-ano_atual=\""+$value.ano_atual+"\" data-codigo_vendedor=\""+$value.codigo_vendedor+"\" title='Visualizar Devolução' data-title=\""+$title+"\" onclick=\"abrirModalDevolução($(this))\">"+$value.valor_devolucao+"</a>";

    return html;
}

function abrirModalDevolução($this){
    var ano_atual = $($this).data("ano_atual");
    var codigo_vendedor = $($this).data("codigo_vendedor");
    var title = $($this).data("title");
    $.ajax({
        url: '{{ route('beneficio_representante.frete_devolucao.modal.devolucao') }}',
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            ano_atual: ano_atual,
            codigo_vendedor: codigo_vendedor,
        },
        success: function(body){
            createModal('modal_devolucao', title, body, "modal-lg");
            var modal = $("#modal_devolucao");
        }
    });
}
@endsection