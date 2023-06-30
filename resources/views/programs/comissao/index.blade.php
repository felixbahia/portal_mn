@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Todos'])}}
        </div>
        @if (!in_array(Auth::user()->tipo_usuario_id, [12,16]))
        <div class="form-group col-lg-2">
            {{ Form::select('representantes', $representantes, '', ['class' => 'form-control', 'placeholder' => 'Todos'])}}
        </div>
        @endif
        @if (!in_array(Auth::user()->tipo_usuario_id, [12,16]))
        <div class="col-lg-2">
            <select name="tipo" id="tipo">
                @foreach($tipos as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-lg-2">
            <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
        </div>
        <div class="col-lg-2">
            <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="" maxlength="20">
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
    <table class="table table-striped table-not-edit table-not-view table-comissao_not_user" id="table-filters">
        <thead>
            <tr>
                <th>Representante</th>
                <th>Equipe</th>
                <th class="tb_number">Valor Vendido</th>
                <th class="tb_number">Devolução</th>
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>Total:</td>
                <td class="tb_number" id='total_basecomissao'></td>
                <td class="tb_number" id='total_devolucao'></td>
                <td class="tb_number" id='total_valor'></td>
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
            zIndex: 100,
            autoHide: true
        });
        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
            $('#data_fim').datepicker('update');
        });
        carregarData();
        table_filters.destroy();
        $height = $("#app").height() - 300;
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollY": $height,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column > 1){
                                    if(data != ''){
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            },
                            footer: function(data) {
                                data = $('<p>' + data + '</p>').text();
                                return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                            }
                        }
                    },
                },
            ],
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
                    "class": "tb_number", 
                    "type": 'num-fmt', 
                    "targets": "tb_number",
                    render: $.fn.dataTable.render.number( '.', ',', 2 )},
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
        $('.dataTables_scrollFootInner').find('#total_comissao').html('');
        $('.dataTables_scrollFootInner').find('#total_devolucao').html('');
        $('.dataTables_scrollFootInner').find('#total_valor').html('');
        $('.dataTables_scrollFootInner').find('#total_basecomissao').html('');
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('label.error-message').remove();
        $.ajax({
            url: '{{ route('comissao.filtro') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                if(callback.status === 'success'){
                    var dados = callback.response.titulos;
                    var total = callback.response.total;
                    var lines = [];
                    if(dados.length){
                        for(var field in dados){
                            var temp_field = [
                                createLinkRepresentante(dados[field], $form),
                                dados[field].equipe,
                                dados[field].base_comissao,
                                dados[field].valor_devolucao,
                                dados[field].valor_total
                            ];
                            lines.push(temp_field);
                        }
                        var rows = table_filters.rows.add(lines).order([[ 0, 'asc' ]]).draw().nodes();
                        $('.dataTables_scrollFootInner').find('#total_devolucao').html(total.devolucao);
                        $('.dataTables_scrollFootInner').find('#total_valor').html(total.total);
                        $('.dataTables_scrollFootInner').find('#total_basecomissao').html(total.base_comissao);
                    }
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                    });
                    $form.find('input.error').eq(0).focus();
                }
            }
        }).always(function() {
            hide_loader();
        });
        
    }
    function createLinkRepresentante($this, $form){
        var html = "";
        if($this.numero_nota !== ""){
            html = "<a href='#' onclick=\"showComissaoDetalhes('" 
            + $this.representante_not_parse + "', '" 
            + $form.find('#data_inicio').val() + "', '" 
            + $form.find('#data_fim').val() + "', '" 
            + $form.find('#estabelecimento').val() + "', '"
            + $this.cod_representante + "', '" 
            + $this.representante
            + "')\">" + $this.representante + "</a>";
        }
        return html;
    }
    function showComissaoDetalhes(representante, data_inicio, data_fim, estabelecimento, cod_representante, nome){
        $.ajax({
            url: '{{ route('comissao.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                representante: representante,
                data_inicio: data_inicio,
                data_fim: data_fim,
                estabelecimento: estabelecimento
            },
            success: function(body){
                createModal("comissao_representante", '{{ CustomView::programaName() }} Representante ' + nome + "(" + cod_representante + ") - Período(" + data_inicio + " até " + data_fim + ")", body, 'modal-lg');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }

        });
    }
    function carregarData(){
        var d = new Date();
        var anoC = d.getFullYear();
        var mesC = d.getMonth();

        var d1 = new Date (anoC, mesC, 1);
        var d2 = new Date (anoC, mesC+1, 0);
        $('#data_inicio').val(dataAtualFormatada(d1));
        $('#data_fim').val(dataAtualFormatada(d2));
    }
    function dataAtualFormatada(data){
            dia  = data.getDate().toString().padStart(2, '0'),
            mes  = (data.getMonth()+1).toString().padStart(2, '0'), //+1 pois no getMonth Janeiro começa com zero.
            ano  = data.getFullYear();
        return dia+"/"+mes+"/"+ano;
    }
@endsection
