@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {!! Form::text('descricao', '', ['id' => 'descricao_filter', 'class' => 'form-control', 'placeholder' => 'Descrição']) !!}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <input type="button" id="btn-novo" class="btn btn-success float-right" value="Novo motivo" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-status">
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Selecionável</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')

    $(document).ready(function(){
        $(document).find("#btn-filterform").on('click', function(){
            buscarStatus();
        });

        $(document).find("#btn-novo").on("click", function(){
            modalNovoStatus();
        });
    });

    table_filters_status = $("#table-filters-status").DataTable({
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
                "targets": [-1, -2],
                "orderable": false,
                "width": '1vw'
            },
            {
                "targets": 1,
                "orderable": false,
                'class': 'text-center',
                "width": '1vw'
            }
        ]
    });

    function buscarStatus(){

        table_filters_status.clear().draw();
        
        $.ajax({
            url: '{{ route("devolucao_nota_status.filter") }}',
            dataType: 'json',
            method: 'POST',
            data: $(document).find('#form_filter').serialize(),
            success: function(data){

                var response = data.response.dados;
                var linhas = [];

                for(var field in response){
                    motivo_linha = [
                        response[field].status,
                        iconeSelecionavel(response[field].selecionavel),
                        createBtnEdit(response[field].id),
                        createBtnDelete(response[field].id)
                    ]
                    linhas.push(motivo_linha);
                }
                table_filters_status.rows.add(linhas).nodes().draw();
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        });
    }

    function modalNovoStatus(){
        $.ajax({
            url: '{{ route("devolucao_nota_status.modal.novo") }}',
            method: 'POST',
            data: {
                '_token': '{{ csrf_token() }}'
            },
            success: function(data){

                var id = 'modal-novo-status';
                var title = 'Novo Status'

                createModal(id, title, data, '');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        });
    }

    function createBtnEdit($id){

        var $html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" title=\"Editar status\" data-placement=\"top\" onclick=\"editarStatus('"+$id+"');\"></a>";

        return $html;
    }

    function createBtnDelete($id){

        var $html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" title=\"Excluir\" data-placement=\"top\" onclick=\"excluirStatus('"+$id+"');\"></a>";

        return $html;
    }

    function createBtnUsers($id){

        var $html = "<a href=\"#\" class=\"btn-users\" data-toggle=\"tooltip\" title=\"Editar usuários\" data-placement=\"top\" onclick=\"modal_usuarios('"+$id+"');\"></a>";

        return $html;
    }

    function editarStatus($id){
        $.ajax({
            url: '{{ route("devolucao_nota_status.modal.editar") }}',
            method: 'POST',
            data: {
                'id': $id,
                '_token': '{{ csrf_token() }}'
            },
            success: function(data){

                var id = 'modal-editar-status';
                var title = 'Editar Status de Devolução'

                createModal(id, title, data, '');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        })
    }

    function excluirStatus($id){
        $.ajax({
            url: '{{ route("devolucao_nota_status.salvar.excluir") }}',
            method: 'POST',
            dataType: 'json',
            data: {
                'id': $id,
                '_token': '{{ csrf_token() }}'
            },
            success: function(data){
                message('Atenção!', data.message);
                buscarStatus();
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        })
    }

    function iconeSelecionavel($selecionavel){
        if($selecionavel){
            return "<i class='fa fa-check check-icon' aria-hidden='true'></i>";
        }
        else{
            return "<i class='fa fa-times error-icon'  aria-hidden='true'></i>";
        }
    }

@endsection
</script>
