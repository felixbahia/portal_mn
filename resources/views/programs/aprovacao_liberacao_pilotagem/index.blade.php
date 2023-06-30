@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_aprovacao_pilotagem_comissao" id="form_aprovacao_pilotagem_comissao" onsubmit="return false">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                {{ Form::select('vendedor_representante', $vendedor_representante, '', ["id" => 'vendedor_representante', 'class' => 'form-control', 'placeholder' => 'Representantes'])}}
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
    <table class="table table-striped" id="table-filters-aprovacao_pilotagem_comissao">
        <thead>
            <tr>
                <th class="th-reprove"></th>
                <th>Representante</th>
                <th>Nota</th>
                <th class="tb_number">Valor <br/>Desconto</th>
                <th class="tb_number">Valor <br/>Crédito</th>
                <th class="tb_number">% Comissão<br/>Atual</th>
                <th class="tb_number">% Comissão<br/>Alterada</th>
                <th></th>
                <th class="th-aprove"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready(function(){
        $(document).find('#btn-filterform').on('click', function(event){
            event.stopPropagation();
            filtro();
        });

        filtro();
        setInterval(function(){
            filtro();
        }, 180000);

        table_filters_aprovacao_pilotagem_comissao.on('draw', function () {
            $('[data-toggle="tooltip"]').tooltip();
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(){
                modalVisualizar($(this));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(){
                modalDeletar($(this));
            });
            $(document).find(".bt-aprove").off("click");
            $(document).find(".bt-aprove").on("click", function(){
                aprovarPilotagem($(this));
            });
            $(document).find(".bt-reprove").off("click");
            $(document).find(".bt-reprove").on("click", function(){
                modalReprovarPilotagem($(this));
            });
            $(document).find(".bt-modal-nota").off("click");
            $(document).find(".bt-modal-nota").on("click", function(){
                abrirModalNota($(this));
            });
        });
    });


    table_filters_aprovacao_pilotagem_comissao = $('#table-filters-aprovacao_pilotagem_comissao')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
    }).DataTable({
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
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
            {
                "targets": ['th-aprove', 'th-reprove'],
            },
        ]
    });

    function filtro(){
        table_filters_aprovacao_pilotagem_comissao.clear().draw();
        form = $(document).find("#form_aprovacao_pilotagem_comissao");
        data_form = form.serialize();
        $.ajax({
            url: "{{ route('aprovacao_liberacao_pilotagem.filtro') }}", 
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var dados = callback.response.saida;
                var fields_filter = [];
                for(var field in dados){
                    var temp_field = [
                        criarBtReprovar(dados[field]),
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + dados[field].representante + "''>" + dados[field].representante + "</div></div>",
                        criarLinkNota(dados[field]),
                        dados[field].valor_desconto,
                        dados[field].valor_credito,
                        dados[field].comissao_atual,
                        dados[field].comissao_alterada,
                        criarBtVisualizar(dados[field]),
                        criarBtAprovar(dados[field]),
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters_aprovacao_pilotagem_comissao.rows.add(fields_filter).draw().nodes();
            },
            error: function(callback){
                message('Atenção', 'Nenhuma Registro localizado');
            }
        });
    }

    function criarBtReprovar($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" title=\"Reprovar\"  class=\"bt-reprove\" data-route=\"{{ route('aprovacao_liberacao_pilotagem.modal.recusar') }}\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }

    function criarBtAprovar($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-aprove\" title=\"Aprovar\" data-route=\"{{ route('liberacao_pilotagem.aprovar') }}\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }

    function criarBtVisualizar($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" data-visualizar=\"true\" class=\"bt-view\" title=\"Visualizar\" data-route=\"{{ route('liberacao_pilotagem.modal.visualizar') }}\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }

    function criarLinkNota($dados){
        var html = "<a href=\"#\" data-route=\"{{ route('notas_nasajon.modal.exibir') }}\" data-nota_id=\""+$dados.nota_id+"\" data-title='DETALHES DA NOTA: "+$dados.nota+"' class='bt-modal-nota'>"+$dados.nota +"</a>"
        return html;
    }

    function modalVisualizar($this) {
        var url = $($this).data("route");
        var $id = $($this).data("id");
        $.ajax({
            url: url,
            data: {_token: '{{ csrf_token() }}', 
                id: $id
            },
            method: 'POST',
            success: function(body){
                var title = 'Visualização de {{ CustomView::programaName() }}';
                createModal("modal_visualizar_aprovacao_pilotagem", title, body, 'modal-lg');
            },
        });
    }

    function aprovarPilotagem($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        $.ajax({
            url: url,
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            method: 'POST',
            success: function(callback){
                if(callback.status === 'success'){
                    filtro();
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!');
                }
            }
        });
    }

    function modalReprovarPilotagem($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        $.ajax({
            url: url,
            data: {
                _token: '{{ csrf_token() }}',
                 id: $id
            },
            method: 'POST',
            success: function(body){
                var title = 'Recusa de {{ CustomView::programaName() }}';
                createModal("modal_recusa_aprovacao_pilotagem", title, body,'');
            },
        });
    }

    function abrirModalNota($this){
        var url = $($this).data("route");
        var nota_id = $($this).data("nota_id");
        var title = $($this).data('title');
        xhr = $.ajax({
            url: url,
            data: {
                _token: "{{ csrf_token() }}", id_nota: nota_id},
            method: 'POST',
            success: function(body){
                createModal("modal_nota_detalhes", title, body, 'modal-lg');
            }
        });
    }

    
@endsection