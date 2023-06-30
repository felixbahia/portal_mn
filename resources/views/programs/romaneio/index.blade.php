@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
            <div class="col-lg-2">
                <select name="estabelecimento" id="estabelecimento">
                    <option value="">Estabelecimento</option>
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
            <div class="col-lg-2">
                {{ Form::text('numero_nota', '', ['id' => 'numero_nota', 'class' => 'form-control input-label', 'placeholder' => 'Nota Fiscal', 'maxlength' => '10']) }}
 
            </div>
            <div class="col-lg-2">
                {{ Form::select("situacao", $situacoes, 'Todos', ["id" => "situacao", "class"=>"form-control"]) }}
            </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        
    </div>
</form>
@endsection
@section('content')
<div class="content-table notas-importadas">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters_romaneio">
        <thead>
            <tr>
                <th rowspan="2">Estab.</th>
                <th colspan="2">Nota Fiscal</th>
       
                <th colspan="3">Data</th>
                <th rowspan="2" class="tb_dados">Fornecedor</th>
                <th colspan="2">Romaneio</th>
           
                <th colspan="2">Diferença</th>
                <th colspan="2">Endereçada Romaneio</th>
                <th colspan="2">Endereçada Nota</th>
                <th colspan="2">Finaliza</th>
                <th rowspan="2">Defeito</th>
            </tr>

             <tr>
                <th   class="tb_number">Remessa</th>
                <th class="tb_number">Entrada</th>
                <th class="tb_date">Ra</th>
                <th class="tb_date">Emissão</th>
                <th class="tb_date">Entrada</th>
                <th   class="tb_number">Peças</th>
                <th class="tb_number">Qtde</th>
         
                <th class="tb_number">Peças</th>
                <th class="tb_number">Qtde</th>
                <th class="tb_number">Peças</th>
                <th class="tb_number">Qtde</th>
                <th class="tb_number">Peças</th>
                <th class="tb_number">Qtde</th>
                <th >Guarda</th>
                <th >Processo</th>
            
           </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number" id='total_romaneio_pecas'></td>
                <td class="tb_number" id='total_romaneio_qtde'></td>
                 <td class="tb_number" id='total_diferenca_pecas'></td>
                <td class="tb_number" id='total_diferenca_qtde'></td>
                <td class="tb_number" id='total_enderecada_romaneio_pecas'></td>
                <td class="tb_number" id='total_enderecada_romaneio_qtde'></td>
                <td class="tb_number" id='total_enderecada_nota_pecas'></td>
                <td class="tb_number" id='total_enderecada_nota_qtde'></td>
                <td></td>
                <td></td>
                <td></td>
  
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
  
        table_filters = $('#table-filters_romaneio').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,

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
                
                  
                    { "class": "tb_date", targets: "tb_date" },
                    {
                        className: "notas-col tb_number",
                        "targets": [7,8]
                        },
                        {
                        "class": "pedido-col tb_number",
                        "targets": [9,10]
                        },
                        {
                        "class": "compras-col tb_number",
                        "targets": [11,12]
                        },
                        {
                        "class": "lancadas-col tb_number",
                        "targets": [13,14]
                        },
                
            ],

           
        });
        $("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter").serialize());
        });

    });
    function buscaDados(data_form){
        filterClear();
        $(document).find('#table-filters_romaneio').find('#total_romaneio_pecas').html('');
        $(document).find('#table-filters_romaneio').find('#total_enderecada_romaneio_pecas').html('');
        $(document).find('#table-filters_romaneio').find('#total_enderecada_nota_pecas').html('');
        $(document).find('#table-filters_romaneio').find('#total_romaneio_qtde').html('');
        $(document).find('#table-filters_romaneio').find('#total_diferenca_qtde').html('');
        $(document).find('#table-filters_romaneio').find('#total_enderecada_romaneio_qtde').html('');
        $(document).find('#table-filters_romaneio').find('#total_enderecada_nota_qtde').html('');

        $.ajax({
            url: "{{ route('romaneio.filtro') }}",
            type: 'POST',
            dataType: 'json',
            data: data_form,
            success: function(callback){

                if(callback.status === 'success'){
        
                    var dados = callback.response.dados;
                  
          
                     romaneios = [];
                    var total_romaneio_qtde = callback.response.total_romaneio_qtde;
                                 var total_diferenca_qtde = callback.response.total_diferenca_qtde;
                    var total_enderecada_romaneio_qtde= callback.response.total_enderecada_romaneio_qtde;
                    var total_enderecada_nota_qtde= callback.response.total_enderecada_nota_qtde;
                    var total_geral_qtde= callback.response.total_geral_qtde;

                    var total_romaneio_pecas = callback.response.total_romaneio_pecas;
       
                    var total_diferenca_pecas = callback.response.total_diferenca_pecas;
                    var total_enderecada_romaneio_pecas= callback.response.total_enderecada_romaneio_pecas;
                    var total_enderecada_nota_pecas= callback.response.total_enderecada_nota_pecas;
                    var total_geral_pecas= callback.response.total_geral_pecas;
                    var pode_finalizar = callback.response.pode_finalizar;
                   
              
                    for(var field in dados){
         
                        var temp_field = [

                   
                            quebraTexto(dados[field].estabelecimento_descricao),
                            linkNota(dados[field].chave_nota,dados[field].numero_remessa,dados[field].tem_nota_remessa),
                            linkNotaCompra(dados[field].id_nota,dados[field].chave_nota,dados[field].numero_nota,dados[field].tem_nota_compra),
                            dados[field].ra_data,
                            dados[field].emissao,
                            dados[field].entrada,
                            quebraTexto(dados[field].fornecedor),
                            dados[field].qtde_romaneio_pecas,
                            linkRomaneio(dados[field].chave_nota,dados[field].nota_coletor, dados[field].qtde_romaneio_qtde,dados[field].estabelecimento_codigo, dados[field].fornecedor),
                            dados[field].qtde_diferenca_pecas,
                            linkRomaneioDefeito(dados[field].chave_nota,dados[field].nota_coletor, dados[field].qtde_diferenca_qtde,dados[field].estabelecimento_codigo, dados[field].fornecedor),
                            dados[field].qtde_enderecada_romaneio_pecas,
                           dados[field].qtde_enderecada_romaneio_qtde,
                            dados[field].qtde_enderecada_nota_pecas,
                            dados[field].qtde_enderecada_nota_qtde,
                            criarBtnGuarda(dados[field].chave_nota,dados[field].estabelecimento_codigo,dados[field].nota_coletor,dados[field].confirmado,dados[field]),
                            criarBtnFinaliza(dados[field].estabelecimento_codigo,dados[field].nota_coletor,dados[field].confirmado,pode_finalizar,dados[field].situacao, dados[field].ra_data),
                            criarBtnDefeito(dados[field].estabelecimento_codigo,dados[field].nota_coletor,dados[field].situacao),
  
                        ];
           
                        romaneios.push(temp_field);
                    }
                 
                    table_filters.rows.add(romaneios).draw().nodes();
                 
      
                    $(document).find('#table-filters_romaneio').find('#total_romaneio_qtde').html(total_romaneio_qtde);
                                  $(document).find('#table-filters_romaneio').find('#total_diferenca_qtde').html(total_diferenca_qtde);
                    $(document).find('#table-filters_romaneio').find('#total_enderecada_romaneio_qtde').html(total_enderecada_romaneio_qtde);
                    $(document).find('#table-filters_romaneio').find('#total_enderecada_nota_qtde').html(total_enderecada_nota_qtde);
                    $(document).find('#table-filters_romaneio').find('#total_romaneio_pecas').html(total_romaneio_pecas);
                             $(document).find('#table-filters_romaneio').find('#total_diferenca_pecas').html(total_diferenca_pecas);
                    $(document).find('#table-filters_romaneio').find('#total_enderecada_romaneio_pecas').html(total_enderecada_romaneio_pecas);
                    $(document).find('#table-filters_romaneio').find('#total_enderecada_nota_pecas').html(total_enderecada_nota_pecas);
                 


         
                }
            }
        }).always(function() {
            hide_loader();
        });
    }

    function filterClear(){
        table_filters.clear().draw();
    }


    function criarBtnGuarda($chave,$estabe,$nota,$confirmado,$dados){
        var $html="<center>OK</center>";
   
      if($confirmado == false){
             $html = "<div <a href='#' data-chave_nota=\""+$chave+"\" data-nota_coletor=\""+$nota+"\"  data-codigo_estabelecimento=\""+$estabe+"\" class=\"bt-aprove\" id=\""+$chave+"\" data-toggle=\"tooltip\" data-trigger=\"hover\" data-placement=\"center\" onclick=\"guardaMercadoria($(this));\"></a></div>";
       }else if( $dados.qtde_romaneio_pecas > ($dados.total_guarda)) {
        $html = "<center><div <a href='#'  class=\"fa fa-times error-icon\"></a></div></center>";

       }
   
        return $html;
    }
    function criarBtnDefeito($estabe,$nota,$finalizado){
        var $html="<center>OK</center>";
        if($finalizado !='finalizado'){
       
            $html = "<center><div <a href='#'  data-nota_coletor=\""+$nota+"\"  data-codigo_estabelecimento=\""+$estabe+"\"  class=\"bt-list\" id=bt-defeito data-toggle=\"tooltip\"  data-trigger=\"hover\" data-placement=\"center\" onclick=\"apontaDefeito($(this));\"></a></div></center>";
        }
        return $html;
    }

    function criarBtnFinaliza($estabe,$nota,$confirmado, $pode_finalizar,$finalizado,$ra_data){
        var $html ='';
      if($confirmado == true && $pode_finalizar ==true  && $finalizado !='finalizado'){
             $html = "<div <a href='#' data-nota_coletor=\""+$nota+"\"  data-codigo_estabelecimento=\""+$estabe+"\"  data-ra_data=\""+ $ra_data+"\" class=\"bt-aprove\" id=\""+$nota+"\" data-toggle=\"tooltip\" data-trigger=\"hover\" data-placement=\"center\" onclick=\"modalFinaliza($(this));\"></a></div>";
      }else if($finalizado =='finalizado'){
        $html="<center>OK</center>";
      }
        return $html;
    }
    
    function guardaMercadoria($this){

      var nota_coletor = $this.data("nota_coletor");
      var estabelecimento = $this.data("codigo_estabelecimento"); 
      var chave_nota = $this.data("chave_nota");
      
        $.ajax({
            url: '{{ route('romaneio.guarda_mercadoria') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                nota_coletor: nota_coletor,
                estabelecimento: estabelecimento,
                chave_nota :chave_nota
            },
            success: function(data){
        
                $("#"+chave_nota).removeClass( "bt-aprove" ).html("<center>OK</center>");
                  message('Sucesso','Guarda Confimada'  +'.<br />');
                
            },
            error: function (callback){
                if((callback.responseJSON)){
                    var dados = callback.responseJSON;
                    var mensagem;
                    
                    for(var field in dados){
                         if(dados[field].length >3){
                            mensagem =dados[field];
                        }
                    }
                    message('Erro ao Confirmar Guarda',mensagem+'.<br />');
                
                }
            }
        });
    }
 

    function linkNota($chave,$nota,$tem_nota){
        var html ='';
        if($nota != ''){
                if($tem_nota == true){
                        html = " <a  href=\"#\" data-url=\"\" data-chave_nota=\""+$chave+"\"  data-documento=\""+$nota+"\"  onclick=\"showNotasEntradaDetalhesNasajon($(this))\">"+$nota+" <i class='fa fa-check check-icon'></i> </a>";
                }else{
                   html = "<a  href=\"#\" data-url=\"\" data-chave_nota=\""+$chave+"\"  data-documento=\""+$nota+"\"  onclick=\"showNotasEntradaDetalhesNasajon($(this))\">"+$nota+" <i class='fa fa-times error-icon'></i> </a>";
                }
          }
             
       
           return html;
     }
     function linkNotaCompra($id_nota,$chave,$nota,$tem_nota){
        var html ='';
       if($nota != ''){
                if($tem_nota == true){
                    html = " <a href=\"#\" data-url=\"\" data-id_nota=\""+$id_nota+"\" data-chave_nota=\""+$chave+"\"  data-documento=\""+$nota+"\"  onclick=\"showNotasEntradaDetalhesNasajon($(this));\">"+$nota+" <i class='fa fa-check check-icon'></i> </a>";
                }else{
                   html = " <a href=\"#\" data-url=\"\" data-id_nota=\""+$id_nota+"\" data-chave_nota=\""+$chave+"\"  data-documento=\""+$nota+"\"  onclick=\"showNotasEntradaDetalhesNasajon($(this));\">"+$nota+" <i class='fa fa-times error-icon'></i> </a>";
               }
          }
             
      return html;
    }
     function linkRomaneio($chave,$nota,$qtde,$estabelecimento,$fornecedor){
   
        var html = "<a href=\"#\" data-url=\"\" data-chave_nota=\""+$chave+"\"  data-documento=\""+$nota+"\" data-estabelecimento=\""+$estabelecimento+"\" data-fornecedor=\""+$fornecedor+"\" onclick=\"showRomaneio($(this));\">"+$qtde+"</a>"
  

        return html;
    }
    function linkRomaneioDefeito($chave,$nota,$qtde,$estabelecimento,$fornecedor){
   

        var html = "<a href=\"#\" data-url=\"\" data-chave_nota=\""+$chave+"\"  data-documento=\""+$nota+"\" data-estabelecimento=\""+$estabelecimento+"\" data-fornecedor=\""+$fornecedor+"\" onclick=\"showRomaneioDefeito($(this));\">"+$qtde+"</a>"
  

        return html;
    }


     function showNotasEntradaDetalhesNasajon($this){

        var chave_nota = $this.data("chave_nota");
        var documento = $this.data("documento");
        var id_nota = $this.data("id_nota");
    
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.modal.exibir_busca')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                chave_nota: chave_nota,
                id_nota: id_nota
                   },
            success: function(body){
                createModal("nota_detalhes","Detalhes da nota: " + documento, body, 'modal-lg');
                $(document).find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            },
            error: function(callback){
                message("Atenção","Nota ainda não Confirmada");
            }
        });
    }

    
    function showRomaneio($this){
        

        var chave_nota = $this.data("chave_nota");
        var documento = $this.data("documento");
        var estabelecimento = $this.data("estabelecimento");
        var fornecedor = $this.data("fornecedor");
        var title ="Estabelecimento " + estabelecimento + " Nota Fiscal " + documento +" Fornecedor " + fornecedor;
  
        $.ajax({
            url: "{{ route('romaneio.dialog')}}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                chave_nota : chave_nota
 
            },
            success: function(body){
                createModal('modal_dialog', title, body, "modal-lg");
                var modal = $("#modal_dialog");
            }
        });
    }


    function showRomaneioDefeito($this){
        

        var chave_nota = $this.data("chave_nota");
        var documento = $this.data("documento");
        var title =" Nota Fiscal " + documento;
  
        $.ajax({
            url: "{{ route('romaneio.dialog_defeito')}}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                chave_nota : chave_nota
 
            },
            success: function(body){
                createModal('modal_dialog', title, body, "modal-lg");
                var modal = $("#modal_dialog");
            }
        });
    }


    function apontaDefeito($this){
    
        var nota_coletor = $this.data("nota_coletor");
        var codigo_estabelecimento = $this.data("codigo_estabelecimento");  
        var title ="Estabelecimento "+  codigo_estabelecimento +  " Nota Fiscal " + nota_coletor;
     
        $.ajax({
            url: "{{ route('consulta_coletor.defeito')}}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                codigo_estabelecimento : codigo_estabelecimento,
                 numero_nota : nota_coletor,
            },
            success: function(body){
                createModal('modal_dialog', title, body, "modal-lg");
                var modal = $("#modal_dialog");
            }
        });
    }

        

    function carregarData(){
        var d = new Date();
 
        $('#data_inicio').val(dataAtualFormatada(d));
        $('#data_fim').val(dataAtualFormatada(d));
    }
    function dataAtualFormatada(data){
            dia  = data.getDate().toString().padStart(2, '0'),
            mes  = (data.getMonth()+1).toString().padStart(2, '0'), //+1 pois no getMonth Janeiro começa com zero.
            ano  = data.getFullYear();
        return dia+"/"+mes+"/"+ano;
    }

    function quebraTexto($texto){
        if($texto == null){
             $html='';
         }else{
             
             var $html =   "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\"\" data-placement=\"left\" data-original-title=\""+ $texto +"\">" + $texto + "</div></div>";
         }
        
         return $html;
 
     }

     function modalFinaliza($this){

        var nota_coletor = $this.data("nota_coletor");
        var estabelecimento = $this.data("codigo_estabelecimento"); 
        var ra_data = $this.data("ra_data"); 
   
        $.ajax({
            url: '{{ route("romaneio.modal.finaliza") }}',
            data: {_token: '{{ csrf_token() }}', numero_nota:nota_coletor,estabelecimento:estabelecimento, ra_data : ra_data, },
            method: 'POST',
            success: function(data){
                var title = 'Finalizando processo';
      
       
                createModal("modal_finaliza_processo", title, data, '');
              
                    $("#"+nota_coletor).removeClass("bt-aprove").html("<center>OK</center>");
               
                 
            
               
            
                
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
     
            }
        });
    }

@endsection
