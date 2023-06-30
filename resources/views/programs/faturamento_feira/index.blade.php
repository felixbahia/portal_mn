@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-2">
            <input type="text" name="grupo_filtro" id="grupo_filtro"  placeholder="Grupo">
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
                <th class="tb_date_150">Data</th>
                <th class="tb_number">Dinheiro</th>
                <th class="tb_number">Cartão Débito</th>
                <th class="tb_number">Cartão Crédito</th>
                <th class="tb_number">Pix</th>
                <th class="tb_number">Total</th>
                <th>Produtos</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total:</td>
                <td class="tb_number" id='total_dinheiro'></td>
                <td class="tb_number" id='total_cartao_debito'></td>
                <td class="tb_number" id='total_cartao_credito'></td>
                <td class="tb_number" id='total_pix'></td>
                <td class="tb_number" id='total_total'></td>
                <td id='total_produtos'></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $("#grupo_filtro").autocomplete(optionsAutoCompleteGrupo("grupo"));
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
                                if(column >= 1){
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date_150", targets: "tb_date_150" }
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
            url: '{{ route('faturamento_feira.filtro') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                if(callback.status === 'success'){
                    var dados = callback.response.dados;
                    var total = callback.response.total;
                    var filtro_grupo = callback.response.filtro_grupo;
                    console.log(filtro_grupo);
                    var lines = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].data,
                            dados[field].dinheiro,
                            dados[field].cartao_debito,
                            dados[field].cartao_credito,
                            dados[field].pix,
                            dados[field].total,
                            createBtViewProduto(dados[field].data, filtro_grupo),
                        ];
                        lines.push(temp_field);
                    }
                    var rows = table_filters.rows.add(lines).order([[ 0, 'asc' ]]).draw().nodes();
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_dinheiro').html(total.dinheiro);
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_cartao_debito').html(total.cartao_debito);
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_cartao_credito').html(total.cartao_credito);
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_pix').html(total.pix);
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_total').html(total.total);
                    $('.dataTables_scrollFootInner').find('.table-faturamento').find('#total_produtos').html(createBtViewProduto("", filtro_grupo));
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
    
    function createBtViewProduto($data, $filtro_grupo){
        html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' data-data=\""+$data+"\" data-placement='left' data-filtro_grupo=\""+$filtro_grupo+"\" title='Produtos' data-title=\"Produtos\" onclick=\"abrirModalGrupo($(this))\"></a>";
    
        return html;
    }

    function abrirModalGrupo($this){
        var data = $($this).data("data");
        var filtro_grupo = $($this).data("filtro_grupo");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('faturamento_feira.modal.detalhes_produtos') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                data: data,
                filtro_grupo: filtro_grupo,
            },
            success: function(body){
                createModal('modal_grupo', title, body, "modal-lg");
                var modal = $("#modal_grupo");
            }
        });
    }

    function optionsAutoCompleteGrupo($name){
  
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.grupo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.clear().draw();
                    filtro();
                }, 100);
            }
        };
    }
@endsection
