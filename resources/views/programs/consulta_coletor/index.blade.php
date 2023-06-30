 @extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
            <div class="col-lg-2">
                <select name="estabelecimento" id="estabelecimento">
                    <option value="">Estábelecimento</option>
                    @foreach($estabelecimentos as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
            </div>
            <div class="col-lg-3">
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Conferência</th>
                <th class="tb_number">Guarda</th>
                <th class="tb_number">Separação</th>
                <th class="tb_number">Total</th>
                <th>Operador</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td class="tb_number" id='total_conferencia'></td>
                <td class="tb_number" id='total_guarda'></td>
                <td class="tb_number" id='total_separacao'></td>
                <td class="tb_number" id='total_geral'></td>
                <td  id='lupa'></td>
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
        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });
        carregarData();
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
            ],
            "order": [[ 0, 'asc' ]]
        });
        $("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter").serialize());
        });
        buscaDados($("#form_filter").serialize());
    });
    function buscaDados(data_form){
        table_filters.clear().draw();
        $(document).find('#table-filters').find('#total_conferencia').html('');
        $(document).find('#table-filters').find('#total_guarda').html('');
        $(document).find('#table-filters').find('#total_separacao').html('');
        $.ajax({
            url: "{{ route('consulta_coletor.filtro') }}",
            type: 'POST',
            dataType: 'json',
            data: data_form,
            success: function(callback){
                if(callback.status === 'success'){
        
                    var dados = callback.response.dados;
                  
                    table_filters.clear().draw();
                    var lines = [];
                    var total_confere = callback.response.total_confere;
                    var total_guarda = callback.response.total_guarda;
                    var total_separa= callback.response.total_separa;
                    var total_geral= callback.response.total_geral;
                 
              
                    for(var field in dados){
                        var temp_field = [
                            dados[field].estabelecimento_descricao,
                
                            linkDialogColetorConfere(dados[field].estabelecimento_codigo,dados[field].Conferencia),
                           
                            linkDialogColetorGuarda( dados[field].estabelecimento_codigo,dados[field].Guarda),
                            linkDialogColetorSepara(dados[field].estabelecimento_codigo,dados[field].Separação),
                            linkDialogColetorTotal(dados[field].estabelecimento_codigo, dados[field].total),
                            criarBtnOperador(dados[field].estabelecimento_codigo)

                        ];
                        lines.push(temp_field);
                    }
                    table_filters.rows.add(lines).draw().nodes();
                    $(document).find('#table-filters').find('#total_conferencia').html(total_confere);
                    $(document).find('#table-filters').find('#total_guarda').html(total_guarda);
                    $(document).find('#table-filters').find('#total_separacao').html(total_separa);
                    $(document).find('#table-filters').find('#total_geral').html(total_geral);
                    $(document).find('#table-filters').find('#lupa').html(criarBtnOperadorTotal());
         
                }
            }
        }).always(function() {
            hide_loader();
        });
    }
    
    function linkDialogColetorConfere($estabe,$operacao){
 
        if($operacao > 0){
            html = "<a href=\"#\" data-url=\"\" data-title=\""+$estabe+"\" data-filtro=\""+'Conferencia'+"\" data-codigo_estabelecimento=\""+$estabe+"\" onclick=\"modalOperacao($(this)) ;\">"+$operacao+"</a>"
        }else{
            html ='';
        }
        return html;
    }
    function linkDialogColetorGuarda($estabe,$operacao){
    if($operacao > 0){
       html = "<a href=\"#\" data-url=\"\" data-title=\""+$estabe+"\" data-filtro=\""+'Guarda'+"\" data-codigo_estabelecimento=\""+$estabe+"\" onclick=\"modalOperacao($(this));\">"+$operacao+"</a>"
    }else{
        html ='';
    }
    return html;

    }
    function linkDialogColetorSepara($estabe,$operacao){
     if($operacao > 0){
           html = "<a href=\"#\" data-url=\"\" data-title=\""+$estabe+"\" data-filtro=\""+'Separação'+"\" data-codigo_estabelecimento=\""+$estabe+"\" onclick=\"modalOperacao($(this));\">"+$operacao+"</a>"
    }else{
        html ='';
    }
    return html;

    }
    function linkDialogColetorTotal($estabe,$operacao){
        if($operacao > 0){
             html = "<a href=\"#\" data-url=\"\" data-title=\""+$estabe+"\"  data-codigo_estabelecimento=\""+$estabe+"\" onclick=\"modalOperacao($(this));\">"+$operacao+"</a>"
       
        }else{
            html ='';
        }
    return html;
     }
     function criarBtnOperador($estabe){
        var $html = "<a href='#' data-title=\""+$estabe+"\"  data-codigo_estabelecimento=\""+$estabe+"\" class=\"bt-view\" id=bt-operador data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"dialogOperador($(this));\"></a>";
 
        return $html;
    }

    function criarBtnOperadorTotal(){
        var $html = "<a href='#'  data-title=\""+'TODOS'+"\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"dialogOperador($(this));\"></a>";
 
        return $html;
    }

    
    function dialogColetor($this){
        
        var title = "Detalhes do Coletor " + $this.data("title");
        var codigo_estabelecimento = $this.data("codigo_estabelecimento");
    
        var filtro = $this.data("filtro");
  
        $.ajax({
            url: "{{ route('consulta_coletor.dialog')}}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                codigo_estabelecimento : codigo_estabelecimento,
                filtro : filtro,
                data_inicio: $('#data_inicio').val(),
                data_fim: $('#data_fim').val()
            },
            success: function(body){
                createModal('modal_dialog', title, body, "modal-lg");
                var modal = $("#modal_dialog");
            }
        });
    }

    function dialogOperador($this){
        
        var title = "Detalhes do Coletor " + $this.data("title");
        var codigo_estabelecimento = $this.data("codigo_estabelecimento");
        var filtro = $this.data("filtro");
  
        $.ajax({
            url: "{{ route('consulta_coletor.dialog')}}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                 filtro : filtro,
                 codigo_estabelecimento : codigo_estabelecimento,
                data_inicio: $('#data_inicio').val(),
                data_fim: $('#data_fim').val()
            },
            success: function(body){
                createModal('modal_dialog', title, body, "modal-lg");
                var modal = $("#modal_dialog");
            }
        });
    }

    function modalOperacao($this){
        
        var title = "Detalhes do Coletor " + $this.data("title");
        var codigo_estabelecimento = $this.data("codigo_estabelecimento");
        var filtro = $this.data("filtro");
     
        $.ajax({
            url: "{{ route('consulta_coletor.operacao')}}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                codigo_estabelecimento : codigo_estabelecimento,
                 filtro : filtro,
                data_inicio: $('#data_inicio').val(),
                data_fim: $('#data_fim').val()
            },
            success: function(body){
                createModal('modal_dialog', title, body, "modal-lg");
                var modal = $("#modal_dialog");
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
