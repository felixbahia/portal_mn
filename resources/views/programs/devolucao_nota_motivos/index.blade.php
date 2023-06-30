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
        @if(in_array(Auth::user()->id, [46, 105]) || Auth::user()->hasRole('Administradores'))
            <input type="button" id="btn-novo" class="btn btn-success float-right" value="Novo motivo" />
        @endif
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-motivos">
        <thead>
            <tr>
                <th>Descrição</th>
                @if(in_array(Auth::user()->id, [46, 105]) || Auth::user()->hasRole('Administradores'))
                    <th class="tb_premiacao">Afeta <br/>Premiação?</th>
                    <th></th>
                    <th></th>
                    <th></th>
                @endif
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
            buscarMotivos();
        });

        @if(in_array(Auth::user()->id, [46, 105]) || Auth::user()->hasRole('Administradores'))
            $(document).find("#btn-novo").on("click", function(){
                modalNovoMotivo();
            });
        @endif
    });

    table_filters_motivos = $("#table-filters-motivos").DataTable({
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
                "targets": [-1, -2, -3],
                "orderable": false,
                "width": '1vw'
            },
            {
                "width": "5px", 
                "targets": "tb_premiacao"
            }
        ]
    });

    function buscarMotivos(){

        table_filters_motivos.clear().draw();
        
        $.ajax({
            url: '{{ route("devolucao_nota_motivo.filter") }}',
            dataType: 'json',
            method: 'POST',
            data: $(document).find('#form_filter').serialize(),
            success: function(data){

                var response = data.response.dados;
                var linhas = [];

                for(var field in response){
                    motivo_linha = [
                        response[field].descricao,
                        @if(in_array(Auth::user()->id, [46, 105]) || Auth::user()->hasRole('Administradores'))
                            response[field].afeta_premiacao,
                            createBtnStatus(response[field].id),
                            createBtnEdit(response[field].id),
                            createBtnDelete(response[field].id)
                        @endif
                    ]
                    linhas.push(motivo_linha);
                }
                table_filters_motivos.rows.add(linhas).nodes().draw();
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        });
    }
    @if(in_array(Auth::user()->id, [46, 105]) || Auth::user()->hasRole('Administradores'))
        function modalNovoMotivo(){
            $.ajax({
                url: '{{ route("devolucao_nota_motivo.modal.novo") }}',
                method: 'POST',
                data: {
                    '_token': '{{ csrf_token() }}'
                },
                success: function(data){

                    var id = 'modal-novo-motivo';
                    var title = 'Novo Motivo de Devolução'

                    createModal(id, title, data, '');
                },
                error: function callback(data){
                    message('Atenção!', data.responseJSON.message)  
                }
            });
        }

        function createBtnEdit($id){

            var $html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title='Editar' onclick=\"editarMotivo('"+$id+"');\"></a>";

            return $html;
        }

        function createBtnDelete($id){

            var $html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title='Excluir' onclick=\"excluirMotivo('"+$id+"');\"></a>";

            return $html;
        }

        function createBtnStatus($id){

            var $html = "<a href=\"#\" class=\"btn-lista\" data-toggle=\"tooltip\" data-placement=\"top\" title='Escolher Status/Etapas' onclick=\"editarStatus('"+$id+"');\"></a>";

            return $html;
        }

        function editarMotivo($id){
            $.ajax({
                url: '{{ route("devolucao_nota_motivo.modal.editar") }}',
                method: 'POST',
                data: {
                    'id': $id,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(data){

                    var id = 'modal-editar-motivo';
                    var title = 'Editar Motivo de Devolução'

                    createModal(id, title, data, '');
                },
                error: function callback(data){
                    message('Atenção!', data.responseJSON.message)  
                }
            })
        }

        function editarStatus($id){
            $.ajax({
                url: '{{ route("devolucao_nota_motivo.modal.status") }}',
                method: 'POST',
                data: {
                    'id': $id,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(data){

                    var id = 'modal-motivo-status';
                    var title = 'Status do motivo'

                    createModal(id, title, data, '');
                },
                error: function callback(data){
                    message('Atenção!', data.responseJSON.message)  
                }
            })
        }

        function excluirMotivo($id){
            $.ajax({
                url: '{{ route("devolucao_nota_motivo.salvar.excluir") }}',
                method: 'POST',
                dataType: 'json',
                data: {
                    'id': $id,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(data){
                    message('Atenção!', data.message);
                    buscarMotivos();
                },
                error: function callback(data){
                    message('Atenção!', data.responseJSON.message)  
                }
            })
        }
    @endif
@endsection
</script>
