@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-4"> 
                {{ Form::text('mes_ano', $data, ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
            </div>
            <div class="col-lg-4"> 
                {{ Form::select('unidade_negocio', $unidades_negocios, '', ['id' => 'unidade_negocio', 'class' => 'form-control input-label', 'placeholder' => 'Selecione Unidade Negócio', 'maxlength' => '250']) }}
            </div>
            <div class="col-lg-4"> 
                {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        @if(Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('RH'))
        <button name="btn-gerar_folha" id="btn-gerar_folha" class="btn-create">Gerar Folha</button>
        @endif
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-produto-analise" id="table-filters">
        <thead>
            <tr>
                <th>Equipe</th>
                <th class="tb_number">Meta</th>
                <th class="tb_number">Faturado por Nota</th>
                <th class="tb_number">% Atingimento</th>
                <th class="tb_number">% Comissão Por Equipe</th>
                <th class="tb_number">Valor Comissão Por Equipe</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total:</td>
                <td class="tb_number" id="total_meta"></td>
                <td class="tb_number" id="total_faturamento"></td>
                <td class="tb_number" id="total_atingimento"></td>
                <td class="tb_number"></td>
                <td class="tb_number" id="total_comissao"></td>
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
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form.find('.data').mask('00/0000');

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

        $("#btn-gerar_folha").off("click");
        $("#btn-gerar_folha").on("click", function(){
            showGerarFolha();
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
            url: '{{ route('comissao_venda_interna.filtro')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var unidade in data.response.unidades){
                    temp_array = [
                        createBtViewMembro(data.response.unidades[unidade], data.response.mes_ano),
                        data.response.unidades[unidade].meta,
                        createBtViewEquipeIndividual(data.response.unidades[unidade], data.response.filtro, "Equipe: "+data.response.unidades[unidade].unidade+" - "+data.response.data_escolhida),
                        data.response.unidades[unidade].atingimento_porcetagem,
                        data.response.unidades[unidade].comissao_porcetagem,
                        data.response.unidades[unidade].valor,
                    ];
                    linhas.push(temp_array);
                }
                table_filters.rows.add(linhas).draw();

                $(document).find('#total_meta').html(data.response.total_meta);
                $(document).find('#total_faturamento').html(data.response.total_faturamento);
                $(document).find('#total_atingimento').html(data.response.total_atingimento);
                $(document).find('#total_comissao').html(data.response.total_comissao);
            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function createBtViewEquipeIndividual($value, $filtro, $title){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-filtro=\""+$filtro+"\" data-id_unidade_negocio=\""+$value.id+"\" title='Visualizar do Gráfico' data-title=\""+$title+"\" onclick=\"abrirModalEquipeIndividual($(this))\">"+$value.faturamento+"</a>";
    
        return html;
    }

    function createBtViewMembro($value, $mes_ano){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-mes_ano=\""+$mes_ano+"\" data-id=\""+$value.id+"\" data-title=\"Equipe: " + $value.unidade + "- Meta: " + $value.meta + " - Faturamento: " + $value.faturamento + " - Atigimento: " + $value.atingimento_porcetagem + " - Comissao: " + $value.comissao_porcetagem + " - Valor: " + $value.valor +"\" onclick=\"abrirModalMembros($(this))\" title='Por Vendedores'>"+$value.unidade+"</a>";
    
        return html;
    }

    function abrirModalEquipeIndividual($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('mapa_venda.modal.equipe_individual') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                title: title,
            },
            success: function(body){
                createModal('modal_equipe', title, body, "modal-lg");
                var modal = $("#modal_equipe");
            }
        });
    }

    function abrirModalMembros($this){
        var title = $($this).data("title");
        var mes_ano = $($this).data("mes_ano");
        var id = $($this).data("id");
        $.ajax({
            url: '{{ route('comissao_venda_interna.modal.membro') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                mes_ano: mes_ano,
            },
            success: function(body){
                createModal('modal_membro', title, body, "modal-lg");
                var modal = $("#modal_membro");
            }
        });
    }

    function createBtViewGrupoEquipe($value, $filtro, $title){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-codigo_vendedor=\""+$value.vendedor_codigo+"\" data-filtro=\""+$filtro+"\" data-id_unidade_negocio=\""+$value.id+"\" title='Visualizar do Gráfico' data-title=\""+$title+"\" onclick=\"abrirModalGrupoEquipe($(this))\">"+$value.valor+"</a>";
    
        return html;
    }

    function abrirModalGrupoEquipe($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var codigo_cliente = "";
        var title = $($this).data("title");
        var codigo_vendedor = $($this).data("codigo_vendedor");
        $.ajax({
            url: '{{ route('mapa_venda.modal.grupo') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                codigo_cliente: codigo_cliente,
                title: title,
                codigo_vendedor: codigo_vendedor
            },
            success: function(body){
                createModal('modal_grupo', title, body, "modal-lg");
                var modal = $("#modal_grupo");
            }
        });
    }

    function showGerarFolha(){
        form = $(document).find("#form_filter");
        data_form = form.serialize();
        
        mes_ano = form.find("#mes_ano").val();

        $.ajax({
            url: '{{ route('acompanhamento_comissao.verificacao_gerar_integracao_com_folha')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                gerarExportacao();
            },
            error: function(data){
                if(data.responseJSON.message === "Não há vendedor interno."){
                    message("Atenção", "Não há vendedor interno para Unidade Negócio.");
                }else{
                    var $class = "dialog_option_deletar";
                    var $name_option_sim = "gerar_integracao_sim";
                    var $option_sim = "Sim";
                    var $name_option_nao = "gerar_integracao_nao";
                    var $option_nao = "Não";
                    var $name_option_cadastro = "gerar_integracao_cadastro";
                    var $option_cadastro = "Cadastro";
    
                    message_option_sim_nao_cadastro("Atenção!", "Há Vendedor(es) Interno(s) com cadastro incompleto, deseja gerar assim mesmo?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao, $name_option_cadastro, $option_cadastro);
    
                    $(document).off("gerar_integracao_sim");
                    $(document).on("gerar_integracao_sim", function(){
                        gerarExportacao();
                    });
    
                    $(document).off("gerar_integracao_cadastro");
                    $(document).on("gerar_integracao_cadastro", function(){
                        abrirModalCadastroFolha();
                    });
                }
            }
        });
    }

    function gerarExportacao(){
        $('<form action="{{ route('acompanhamento_comissao.gerar_integracao_com_folha') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="mes_ano" value="'+$("#mes_ano").val()+'"/>\
                <input type="hidden" name="unidade_negocio" value="'+$("#unidade_negocio").val()+'"/>\
                <input type="hidden" name="vendedor" value="'+$("#vendedor").val()+'"/>\
                </form>').appendTo('body').submit().remove();
        
    }

    function abrirModalCadastroFolha(){
        var title = "Cadastro Usuário Folha";
        $.ajax({
            url: '{{ route('acompanhamento_comissao.modal.cadastro_folha') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
            },
            success: function(body){
                createModal('modal_cadastro_folha', title, body, "modal-lg");
                var modal = $("#modal_cadastro_folha");
            }
        });
    }

    
@endsection