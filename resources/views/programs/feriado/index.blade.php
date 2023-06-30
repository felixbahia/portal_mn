@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-6"> 
                {{ Form::text('mes_ano', '', ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
            <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
        </div>
    </form>
@endsection
@section('content')
    <div class="content-table">
        <table class="table table-striped" id="table-filters">
            <thead>
                <tr>
                    <th class="tb_date">Feriado</th>
                    <th class="td_acao">Editar</th>
                    <th class="td_acao">Excluir</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
            </tfoot>
        </table>
    </div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        form = $(document).find("#form_filter");

        form.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form.find('.data').mask('00/0000');

        form.find("#btn-create").off("click");
        form.find("#btn-create").on("click",function(){
            showModalCreate();
        });

        form.find("#btn-filterform").off("click");
        form.find("#btn-filterform").on("click", function(){
            filterClear();
            filterAjax();
        });

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
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                },
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },
                {
                    'targets': 'tb_date',
                    'class': 'tb_date',
                }
            ],
        });
    });

    function showModalCreate(){
        $.ajax({
            url: '{{ route('feriado.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
                var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal('modal_feriado_adicionar', title, body, '');
            }
        });
    }

    function filterAjax(){
        filterClear();
        form = $(document).find("#form_filter");
        $(document).find('#total_basecomissao').html('');
        data_form = form.serialize();
        filterClear();
        $.ajax({
            url: '{{ route('feriado.filtro')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var fields in data.response){
                    temp_array = [
                        data.response[fields].feriado,
                        createBtnEdit("{{ route('feriado.modal.editar') }}", data.response[fields]),
                        createBtnDelete("{{ route('feriado.modal.deletar') }}", data.response[fields]),
                    ];
                    linhas.push(temp_array)
                }
                table_filters.rows.add(linhas).draw();

                $(document).find('#total_meta_principal').html(data.response.total_meta);
            }
        });
    }

    function filterClear(){
        table_filters.clear().draw();
    }
    
    function createBtnEdit($url, $value){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$value.id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"showModal($(this))\"></a>";

        return $html;
    }
    function createBtnDelete($url, $value){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$value.id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\" onclick=\"showModal($(this))\"></a>";

        return $html;
    }

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_feriado_edit_delete', title, body, modal_class);
                var modal = $("#modal_feriado_edit_delete");
            }
        });
    }

@endsection