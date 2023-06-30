@extends('layouts.app')
@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2">
                <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="{{ date('d/m/Y') }}" maxlength="20">
            </div>
            <div class="col-lg-2">
                <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="{{ date('d/m/Y') }}" maxlength="20">
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <button type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear">Limpar busca</button>
        </div>
    </form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view " id="table-filters-monitoracao-separacao">
        <thead>
            <tr>
                <th class="th_estabelecimento" rowspan="2">Estabelecimento</th>
                <th rowspan="2">Pedidos Emitidos</th>
                <th rowspan="2">Pedidos em Aprovação</th>
                <th colspan="2">Pedidos em aberto</th>
                <th colspan="2">Pedidos Faturados</th>
            </tr>
            <tr>
                <th>Dentro do prazo</th>
                <th>Fora do prazo</th>
                <th>Dentro do prazo</th>
                <th>Fora do prazo</th>
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
        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });
        $('#data_fim').datepicker('setStartDate', new Date());
        $('#data_fim').datepicker('update');
        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
            $('#data_fim').datepicker('update');
        });
    });

    table_filters = $('#table-filters-monitoracao-separacao').DataTable({
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
            }        },
        "columnDefs": [
            {
                "targets": 'th_estabelecimento',
                "width": '10%'
            }
        ],
        "order": [[ 0, 'asc' ]]
    });
    function filterAjax(data_form){
        var $return;
        var form = $("#form_filter");
        table_filters.clear().draw();

        form.find('.error-message').remove();
        
        $.ajax({
            url: "{{ route('monitoracao.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status === 'success'){
                    table_filters.clear().draw();
                    var data = callback.response;
                    if(Object.keys(data).length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].estabelecimento_nome,
                                creatBtPedidoEmitido(data[field].pedidos_emitidos, data[field].estabelecimento),
                                creatBtPedidoEmAprovacao(data[field].pedidos_em_aprovacao, data[field].estabelecimento),

                                creatBtSeparacaoNoPrazo(data[field].pedidos_em_aberto_no_prazo, data[field].estabelecimento),
                                creatBtSeparacaoForaDoPrazo(data[field].pedidos_em_aberto_fora_do_prazo, data[field].estabelecimento),

                                creatBtFaturamentoNoPrazo(data[field].pedidos_faturados_no_prazo, data[field].estabelecimento),
                                creatBtFaturamentoForaDoPrazo(data[field].pedidos_faturados_fora_do_prazo, data[field].estabelecimento)
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_filters.rows.add(fields_filter).draw().nodes();
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
    function creatBtPedidoEmitido($texto, $estabelecimento){
        var $html = '';
        if($texto !== ''){
            $html = '<a href="#" onclick="openDialogPedido(\''+$estabelecimento+'\', \'emissao\')">'+$texto+'</a>';
            $html = $texto;
        }
        return $html;
    }
    function creatBtPedidoEmAprovacao($texto, $estabelecimento){
        var $html = '';
        if($texto !== ''){
            $html = '<a href="#" onclick="openDialogPedido(\''+$estabelecimento+'\', \'aprovado\')">'+$texto+'</a>';
            $html = $texto;
        }
        return $html;
    }
    function creatBtFaturamentoNoPrazo($texto, $estabelecimento){
        var $html = '';
        if($texto !== ''){
            $html = '<a href="#" onclick="openDialogFaturamento(\''+$estabelecimento+'\', \'no_prazo\')">'+$texto+'</a>';
        }
        return $html;
    }

    function creatBtFaturamentoForaDoPrazo($texto, $estabelecimento){
        var $html = '';
        if($texto !== ''){
            $html = '<a href="#" onclick="openDialogFaturamento(\''+$estabelecimento+'\', \'fora_do_prazo\')">'+$texto+'</a>';
        }
        return $html;
    }
    function creatBtSeparacaoNoPrazo($texto, $estabelecimento){
        var $html = '';
        if($texto !== ''){
            $html = '<a href="#" onclick="openDialogSeparacao(\''+$estabelecimento+'\', \'no_prazo\')">'+$texto+'</a>';
        }
        return $html;
    }
    function creatBtSeparacaoForaDoPrazo($texto, $estabelecimento){
        var $html = '';
        if($texto !== ''){
            $html = '<a href="#" onclick="openDialogSeparacao(\''+$estabelecimento+'\', \'fora_do_prazo\')">'+$texto+'</a>';
        }
        return $html;
    }

    function openDialogFaturamento($estabelecimento, $coluna){
        $.ajax({
            url: '{{ route('monitoracao.modal.abertura') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                estabelecimento: $estabelecimento,
                coluna: $coluna,
                data_inicio: $('#data_inicio').val(),
                data_fim: $('#data_fim').val()
            },
            success: function(body){
                title = '';
                createModal("pedidos_monitoracao_separacao_dialog", title, body, 'modal-lg');
                var modal = $(document).find("#pedidos_monitoracao_separacao_dialog");
            }
        });
    }
    function openDialogSeparacao($estabelecimento, $coluna){
        $.ajax({
            url: '{{ route('monitoracao.modal.abertura_separacao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                estabelecimento: $estabelecimento,
                coluna: $coluna,
                data_inicio: $('#data_inicio').val(),
                data_fim: $('#data_fim').val()
            },
            success: function(body){
                title = '';
                createModal("pedidos_monitoracao_separacao_dialog", title, body, 'modal-lg');
                var modal = $(document).find("#pedidos_monitoracao_separacao_dialog");
            }
        });
    }
    
    function showItens($pedido, $origem, $estabelecimento){
        var title = "Dados do pedido";
        
        if($origem != 'nasajon'){
            title += ": "+$pedido;
        }

        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {
                _token: "{{ csrf_token() }}",
                origem: $origem,
                pedido: $pedido, 
                estabelecimento: $estabelecimento
            },
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
                var modal = $(document).find("#itens_pedido");
            }
        });
    }
@endsection