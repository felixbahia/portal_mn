@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-2">
            <select name="estabelecimento" id="estabelecimento">
                <option value=''>Todos os estabelecimentos</option>
                @foreach(returnEmpresasNasajonView() as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-2">
            <input type="text" class="data" name="data_busca" id="data_busca" placeholder="Data DD/MM/AAAA" value="{{ date('d/m/Y') }}" maxlength="20">
        </div>
        <div class="col-2">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="prepago" id="prepago" value="true" checked />
                <label class="form-check-label" for="prepago"> Pre-pago</label>
            </div>
        </div>
        <div class="col-2">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="devolucao" id="devolucao" value="true" checked />
                <label class="form-check-label" for="devolucao"> Devolução</label>
            </div>
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
    <table class="table table-striped table-not-edit table-not-view table-faturamento" id="table-faturamento">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Dia</th>
                <th class="tb_number">Mes</th>
                <th class="tb_number">Ano</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td class="tb_number" id='total_dia'></td>
                <td class="tb_number" id='total_mes'></td>
                <td class="tb_number" id='total_ano'></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });
        $height = $("#app").height() - 300;
        table_filters = $('#table-faturamento').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollY": $height,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });

        $("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter"));
        });
    });
    function buscaDados($form){
        table_filters.clear().draw();
        $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_dia').html('');
        $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_mes').html('');
        $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_ano').html('');

        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('label.error-message').remove();
        $.ajax({
            url: '{{ route('faturamento.filtro') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                if(callback.status === 'success'){
                    var dados = callback.response.dados;
                    var total = callback.response.total;
                    var lines = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].estabelecimento,
                            createBtDia(dados[field], $form, false)+" "+createBtDiaFechamentoCaixa(dados[field], $form, false),
                            createBtMes(dados[field], $form, false)+" "+createBtMesFechamentoCaixa(dados[field], $form, false),
                            createBtAno(dados[field], $form)+" "+createBtAnoFechamentoCaixa(dados[field], $form, false),
                        ];
                        lines.push(temp_field);
                    }
                    var rows = table_filters.rows.add(lines).order([[ 0, 'asc' ]]).draw().nodes();
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_dia').html(createBtDia(total, $form, true)+" "+createBtDiaFechamentoCaixa(total, $form, true));
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_mes').html(createBtMes(total, $form, true)+" "+createBtMesFechamentoCaixa(total, $form, true));
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_ano').html(createBtAnoTotal(total.ano, $form)+" "+createBtAnoFechamentoCaixa(total, $form, true));
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $form.find('input[name="'+index+'"],select[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('input[name="'+index+'"],select[name="'+index+'"]').eq(0).addClass('error');
                    });
                    $form.find('input.error').eq(0).focus();
                }
            }
        }).always(function() {
            hide_loader();
        });
    }
    function createBtDia($text, $form, $todos){
        var html = '';
        if($todos == false){
            if($text.dia != ''){
                html = '<a href="#" onClick="openDialog(this, event, \'Detalhes do faturamento do estabelecimento '+$text.estabelecimento+'\')" data-route="{{ route('faturamento.dialog.dia') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="'+$text.estabelecimento_not_parse+'" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'">'+$text.dia+'</a>';
            }
        }else{
            if($text.dia != ''){
                html = '<a href="#" onClick="openDialog(this, event, \'Detalhes do faturamento todos estabelecimentos\')" data-route="{{ route('faturamento.dialog.dia') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'">'+$text.dia+'</a>';
            }
        }
        
        return html;
    }
    function createBtDiaFechamentoCaixa($text, $form, $todos){
        var html = '';
        if($todos == false){
            if($text.dia != ''){
                html = '<a href="#" class="bt-edit-money"  data-toggle="tooltip" data-placement="top" data-original-title="Fechamento Caixa" onClick="openDialog(this, event, \'Detalhes do Fechamento Caixa do estabelecimento '+$text.estabelecimento+' do dia '+$form.find('#data_busca').val()+'\')" data-route="{{ route('faturamento.dialog.dia_fechamento_caixa') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="'+$text.estabelecimento_not_parse+'" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'"></a>';
            }
        }else{
            if($text.dia != ''){
                html = '<a href="#" class="bt-edit-money"  data-toggle="tooltip" data-placement="top" data-original-title="Fechamento Caixa" onClick="openDialog(this, event, \'Detalhes do Fechamento Caixa todos estabelecimentos do dia '+$form.find('#data_busca').val()+'\')" data-route="{{ route('faturamento.dialog.dia_fechamento_caixa') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'"></a>';
            }
        }
        
        return html;
    }
    function createBtMes($text, $form, $todos){
        var html = '';
        if($todos == false){
            if($text.mes != ''){
                html = '<a href="#" onClick="openDialog(this, event, \'Detalhes do faturamento do estabelecimento '+$text.estabelecimento+'\')" data-route="{{ route('faturamento.dialog.mes') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="'+$text.estabelecimento_not_parse+'" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'">'+$text.mes+'</a>';
            }
        }else{
            if($text.mes != ''){
                html = '<a href="#" onClick="openDialog(this, event, \'Detalhes do faturamento todos estabelecimentos\')" data-route="{{ route('faturamento.dialog.mes') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'">'+$text.mes+'</a>';
            }
        }
        return html;
    }
    function createBtMesFechamentoCaixa($text, $form, $todos){
        var html = '';
        if($todos == false){
            if($text.mes != ''){
                html = '<a href="#" class="bt-edit-money" data-toggle="tooltip" data-placement="top" data-original-title="Fechamento Caixa" onClick="openDialog(this, event, \'Detalhes do Fechamento Caixa do estabelecimento '+$text.estabelecimento+'\')" data-route="{{ route('faturamento.dialog.mes_fechamento_caixa') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="'+$text.estabelecimento_not_parse+'" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'"></a>';
            }
        }else{
            if($text.mes != ''){
                html = '<a href="#" class="bt-edit-money" data-toggle="tooltip" data-placement="top" data-original-title="Fechamento Caixa" onClick="openDialog(this, event, \'Detalhes do Fechamento Caixa todos estabelecimentos\')" data-route="{{ route('faturamento.dialog.mes_fechamento_caixa') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'"></a>';
            }
        }
        return html;
    }
    function createBtAno($text, $form){
        var html = '';
        if($text.ano != ''){
            html = '<a href="#" onClick="openDialog(this, event, \'Detalhes do faturamento do estabelecimento '+$text.estabelecimento+'\')" data-route="{{ route('faturamento.dialog.ano_analise') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="'+$text.estabelecimento_not_parse+'" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'">'+$text.ano+'</a>';
        }
        return html;
    }
    function createBtAnoFechamentoCaixa($text, $form, $todos){
        var html = '';
        if($todos == false){
            if($text.mes != ''){
                html = '<a href="#" class="bt-edit-money"  data-toggle="tooltip" data-placement="top" data-original-title="Fechamento Caixa" onClick="openDialog(this, event, \'Detalhes do Fechamento Caixa do estabelecimento '+$text.estabelecimento+'\')" data-route="{{ route('faturamento.dialog.ano_fechamento_caixa') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="'+$text.estabelecimento_not_parse+'" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'"></a>';
            }
        }else{
            if($text.mes != ''){
                html = '<a href="#" class="bt-edit-money"  data-toggle="tooltip" data-placement="top" data-original-title="Fechamento Caixa" onClick="openDialog(this, event, \'Detalhes do Fechamento Caixa todos estabelecimentos\')" data-route="{{ route('faturamento.dialog.ano_fechamento_caixa') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'"></a>';
            }
        }
        return html;
    }
    function createBtAnoTotal($text, $form){
        var html = '';
        if($text.ano != ''){
            html = '<a href="#" onClick="openDialog(this, event, \'Detalhes do faturamento geral\')" data-route="{{ route('faturamento.dialog.ano_analise') }}" data-data_busca="'+$form.find('#data_busca').val()+'" data-estabelecimento="" data-prepago="'+$(document).find('#prepago').prop('checked')+'" data-devolucao="'+$(document).find('#devolucao').prop('checked')+'">'+$text+'</a>';
        }
        return html;
    }

    function openDialog($this, event, $title){
        event.stopPropagation();
        $.ajax({
            url: $($this).data('route'),
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: $($this).data('estabelecimento'),
                data_busca: $($this).data('data_busca'),
                prepago: $($this).data('prepago'),
                devolucao: $($this).data('devolucao')
            },
            success: function(callback){
                createModal("faturamento_detalhe", $title, callback, 'modal-lg');
            }
        })
        .always(function() {
            hide_loader();
        });
        

    }
    function openDialog2($this, event, $title){
        event.stopPropagation();
        $.ajax({
            url: $($this).data('route'),
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: $($this).data('estabelecimento'),
                data_busca: $($this).data('data_busca'),
                prepago: $($this).data('prepago'),
                devolucao: $($this).data('devolucao')
            },
            success: function(callback){
                createModal("faturamento_detalhe_detalhe", $title, callback, 'modal-lg');
            }
        })
        .always(function() {
            hide_loader();
        });
        

    }
@endsection
