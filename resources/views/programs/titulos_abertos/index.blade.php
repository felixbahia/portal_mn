@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            <div class="input-group">
                <input type="text" class="form-control input-label" name="cliente" id="cliente" value="{{CustomView::retornaClientePadraoNome()}}" placeholder="Cliente - CPF/CNPJ" maxlength="250" />
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="row">
                <div class="col">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="emissao_vencimento" id="emissao" value="emissao" />
                        <label class="form-check-label" for="emissao">Emissão</label>
                    </div>
                </div>
                <div class="col">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="emissao_vencimento" id="vencimento" value="vencimento" />
                        <label class="form-check-label" for="vencimento">Vencimento</label>
                    </div>
                </div>
            </div>
        </div>
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
<div class="total_vencidos_avencer row text-right">
    <div class="total_vencidos offset-2 col-lg-5">
        <div class="row">
            <div class="title col-lg-12">Total vencidos: <span class="vencidos_count"></span> títulos - Valor: R$ <span class="vencidos_valor"></span>
            </div>
        </div>
    </div>
    <div class="total_avencer col-lg-5">
        <div class="row">
            <div class="title col-lg-12">Total a vencer: <span class="a_vencer_count"></span> títulos - Valor: R$ <span class="a_vencer_valor"></span>
            </div>
        </div>
    </div>
</div>
<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view table-titulos_abertos" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Tipo</th>
                <th class="tb_number">Titulo</th>
                <th class="tb_number">Parcela</th>
                <th class="tb_number">Nota</th>
                <th class="sort-date">Data Emissão</th>
                <th class="sort-date">Data Vencimento</th>
                <th class="tb_number">Valor</th>
                <th class="tb_number">Dias Em atraso</th>
                <th>Banco</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
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
        $('.total_vencidos_avencer').hide();
        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });
        $(document).find("#cliente").autocomplete(optionsAutoCompleteCliente());
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
        });

        $("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter"));
        });
        $("#form_filter").find("#bt-search-cliente-busca").on("click", function(){
            showModalCliente($(this).data("route"), "Lista de Clientes");
        });
        table_filters.on('draw', function () {
            $('[data-toggle="popover"]').popover({
                container: 'body',
                html: true,
                show: true,
                template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
            });
        });


        $(".btn-clear").on("click", function(){

            $('[data-toggle="popover"]').popover('hide');

            $form = $(this).parents('form');

            $.ajax({
                url: '{{ route('cliente.apagaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(){
                    $form.find('input, select').not('[class^=btn-]').not('[name=_token]').not('[type=radio]').val('');
                    $form.find('[type=radio]').prop('checked', false);
                    table_filters.clear().draw();
                }
            });
            
        });
    });
    function showModalCliente(url, title){
        $('.total_vencidos_avencer').hide();
        table_filters.clear().draw();
        xhr = $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#cliente_searsh_show").find("tbody").find("tr").off("click");
                        $(document).find("#cliente_searsh_show").find("tbody").find("tr").on("click", function(){
                            returnDados($(this));
                        });
                    });
                });
            }
        });
    }
    function returnDados($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_searsh_show").modal("hide");
        $("#form_filter").find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
    }
    function buscaDados($form){
        $('.total_vencidos_avencer').hide();
        $form.find('.error-message').remove();
        if(($form.find("#data_fim").val()).trim().replace('_','') == ''){
            $form.find("#data_fim").val('');
            $form.find("#data_fim").after('<label class="error-message" for="data_fim">Informe uma data</label>');
        }
        if(($form.find("#data_inicio").val()).trim().replace('_','') == ''){
            $form.find("#data_inicio").val('');
            $form.find("#data_inicio").after('<label class="error-message" for="data_inicio">Informe uma data</label>');
        }
        if(($form.find("#cliente").val()).trim().replace('_','') == ''){
            $form.find("#cliente").val('');
            $form.find("#cliente").focus();
            $form.find("#bt-search").after('<label class="error-message" for="data_fim">Informe um cliente</label>');
        }
        if($form.find('.error-message').length){
            return false;
        }
        $('[data-toggle="popover"]').popover('hide');
        table_filters.clear().draw();
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        $.ajax({
            url: '{{ route('titulos.filtro') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){

                if(callback.status === 'success'){
                    $form.find("#nome").val(callback.cliente.nome);
                    var dados = callback.data;
                    var lines = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].estabelecimento,
                            dados[field].tipo,
                            dados[field].numero_titulo,
                            dados[field].parcela,
                            createLinkNf(dados[field]),
                            dados[field].data_emissao,
                            dados[field].data_vencimento,
                            dados[field].valor,
                            dados[field].dias_atraso,
                            createBtBanco(dados[field]),
                        ];
                        lines.push(temp_field);
                    }
                    $('.total_vencidos_avencer').find('.vencidos_count').html(callback.total.vencidos.quantidade);
                    $('.total_vencidos_avencer').find('.vencidos_valor').html(callback.total.vencidos.valor);
                    $('.total_vencidos_avencer').find('.a_vencer_count').html(callback.total.avencer.quantidade);
                    $('.total_vencidos_avencer').find('.a_vencer_valor').html(callback.total.avencer.valor);
                    $('.total_vencidos_avencer').show();

                    if($(document).find('#vencimento').prop('checked')){
                        var rows = table_filters.rows.add(lines).order([ 6, 'desc' ]).draw();
                    }
                    else if($(document).find('#emissao').prop('checked')){
                        var rows = table_filters.rows.add(lines).order([ 5, 'desc' ]).draw();
                    }
                    else{
                        var rows = table_filters.rows.add(lines).draw();
                    }

                    $('[data-toggle="popover"]').popover({
                        container: 'body',
                        html: true,
                        show: true,
                        template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                    });
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.errors;
                    $.each(data, function(index, el) {
                        console.log(index, el[0]);
                        $form.find('input[name="'+index+'"]').eq(0).focus();
                        $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el[0]+'</label>'); 
                    });
                }
            }
        }).always(function() {
            hide_loader();
        });
        
    }

    function createLinkNf($this){
        var html = "";
        if($this.numero_nota !== ""){

            if($this.origem == 'prologos'){
                html = "<a href='#' onclick=\"showNotasDetalhes('" + $this.estabelecimento_not_parse + "', '" + $this.numero_documento + "', '" + $this.data_emissao + "')\">" + $this.numero_nota + "</a>";
            }

            else if($this.origem == 'nasajon'){
                html = "<a href='#' onclick=\"showNotasDetalhesNasajon('" + $this.id_nota + "')\">" + $this.numero_nota + "</a>";
            }

            else{
                html = $this.numero_nota;
            }
        }

        return html;
    }

    function createBtBanco($this){
        var html = "";
        if($this.banco !== "" && $this.banco_popover !== ""){
            html = "<div><div data-toggle=\"popover\" data-trigger='hover' data-content=\""+createBodyPopOver($this.banco_popover)+"\">"+$this.banco+"</div></div>";
        }else if($this.banco !== ""){
            html = "<div><div>"+$this.banco+"</div></div>";
        }
        return html;
    }
    function createBodyPopOver($this){
        var $return = "";
        $return += "<p>"+$this+"</p>";
        return $return;
    }
    function showNotasDetalhes(estabelecimento, nota_fiscal, data){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                documento: nota_fiscal,
                data: data
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }

        });
    }

    function showNotasDetalhesNasajon($id_nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }

        });

    }

    function optionsAutoCompleteCliente(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum cliente encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#cliente").val(ui.item.label);
                return false;
            }
        };
    }
@endsection