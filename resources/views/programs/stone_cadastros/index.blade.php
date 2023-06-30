@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">   
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form form-control', 'placeholder' => 'Estabelecimento']) !!}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <input type="button" id="btn-novo" class="btn btn-success float-right" value="Nova Maquininha" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-notas">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Razão Social</th>
                <th>ID Vínculo Fechado</th>
                <th class="tb_number">Stone Code</th>
                <th class="tb_number">Partner Stone</th>
                <th>Descrição</th>
                <th>Vínculo</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready(function(){
        buscarMaquininhas();
        $(document).find('#btn-filterform').on('click', function(){
            buscarMaquininhas();
        });

        $(document).find("#btn-novo").on("click", function(){
            novoCadastro();
        });

        $(document).find("#btn-clearform").on("click", function(){
            table_filters_maquinihas.clear().draw();
        });

    });

    function showModalClienteBusca(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: "POST",
            data: {
                _token: "{{csrf_token()}}"
            },
            success: function(body){
                $(document).find("#cliente_searsh_show").remove();
                createModal("cliente_searsh_show", title, body, "modal-lg");
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on("draw", function () {
                        modal.find("tbody").find("tr").off("click");
                        modal.find("tbody").find("tr").on("click", function(event){
                            returnDadosClienteBusca($(this), event);
                        });
                    });
                });
            }
        });
    }

    table_filters_maquinihas = $("#table-filters-notas").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
        "autoWidth": true,
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
                "targets": "tb_number"
            },
            { "targets": [-1, -2], "orderable": false},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ]]
        }
    );

    function buscarMaquininhas(){
        
        table_filters_maquinihas.clear().draw();

        $.ajax({
            url: '{{ route("stone_cadastro.filtro") }}',
            dataType: 'json',
            method: 'POST',
            data: $(document).find('#form_filter').serialize(),
            success: function(data){

                var response = data.response.retorno;
                var linhas = [];

                for(var field in response){
                    
                    titulo_linha = [
                        response[field].estabelecimento,
                        response[field].razao_social,
                        response[field].post_id,
                        response[field].stone_code,
                        response[field].partner_stone_id,
                        response[field].descricao,
                        response[field].vinculo,
                        createBtnConfig(response[field].id, response[field].stone_code),
                        createBtnDelete(response[field].id, response[field].stone_code)
                    ]

                    linhas.push(titulo_linha);

                }

                table_filters_maquinihas.rows.add(linhas).nodes().draw();

            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        });
    }

    function novoCadastro(){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            url: '{{ route('stone_cadastro.modal.cadastrar') }}',
            success: function(data){
                createModal('cadastro-maquininha', 'Novo Cadastro Maquininha Stone', data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', 'Ocorreu um erro interno, por favor contate a equipe responsável.');
            }
        });
    }

    function createBtnConfig($id, $stone){
        var $html = "<a href=\"#\" class=\"fa fa-cog\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"modalConfigurar('"+$id+"','"+$stone+"');\"></a>";
        return $html;
    }
    
    function modalConfigurar($id,$stone){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('stone_cadastro.modal.configurar') }}',
            success: function(data){
                createModal('configurar-maquininha', 'Configurar Máquininha Stone '+$stone, data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createBtnDelete($id, $stone_code){
        var $html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"excluir_maquininha('"+$id+"','"+$stone_code+"');\"></a>";
        return $html;
    }

    function excluir_maquininha($id,$stone_code){
        var $class = "dialog_option_deletar";
        var $name_option_sim = "excluir_maquininha_sim";
        var $option_sim = "";
        var $name_option_nao = "excluir_maquininha_nao";
        var $option_nao = "";
        var $name_option_cancelar = "excluir_maquininha_cancelar";
        var $option_cancelar = "";
        var sobrescrever = false;
        message_option_sim_nao("Atenção!", "Tem certeza que deseja excluir a máquininha "+$stone_code+"?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao, $name_option_cancelar, $option_cancelar);

        $(document).off("excluir_maquininha_sim");
        $(document).on("excluir_maquininha_sim", function(){
            $.ajax({
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: $id
                },
                url: '{{ route('stone_cadastro.excluir') }}',
                success: function(data){
                    buscarMaquininhas();
                    message('Atenção!', data.responseJSON.message);
                },
                error: function callback(data){
                    message('Atenção!', data.responseJSON.message);
                }
            });
        });
        
    }
@endsection