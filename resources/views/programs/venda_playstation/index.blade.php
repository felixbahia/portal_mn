@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2"> 
                {{ Form::text('mes_ano', '', ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
            </div>
            <div class="col-lg-2"> 
                {{ Form::text('dolar', '', ['id' => 'dolar', 'class' => 'form-control text-right decimal_quatro_casas', 'placeholder' => 'Dolar Referência', 'maxlength' => '7']) }}
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
        <div>Vendas</div>
        <table class="table table-striped" id="table-filters">
   
            <thead>
   
                <tr>
                    <th>Personagem<br></th>
                    <th>Produto</th>
                    <th>Codigo</th>
                    <th>Plataforma</th>
                    <th>Território</th>
                    <th  class="tb_number">Quantidade</th>
                    <th class="tb_number">Preço R$</th>
                    <th  class="tb_number">Total R$</th>
                    <th  class="tb_number">Dedução R$</th>
                    <th  class="tb_number">Receita Liquida R$</th>
                    <th  class="tb_number">Royalts %</th>
                    <th  class="tb_number">Royalts_Ganhos R$</th>
                    <th class="tb_number">Ganhos US$</th>

                </tr>
            </thead>
            <tbody>
                <tfoot>
                    <tr>
 
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                        <td class="tb_number">Total :</div></td>
                        <td  class="tb_number" id='total_quantidade'></td>
                        <td class="td_acao"></td>
                        <td  class="tb_number" id='total_valor'></td>
                        <td  class="tb_number" id='total_deducao'></td>
                        <td  class="tb_number" id='total_liquido'></td>
                    
                        <td class="td_acao"></td>
                        <td  class="tb_number" id='total_royalt'></td>
                          
               
                        <td  class="tb_number" id='total_usd'></td>
                   
                   </tr>
                </tfoot>
            </tbody>
            
        </table>
    </div>
        <div class="content-table">
            <div>Devolução</div>
        <table class="table table-striped" id="table_filters_dev">
            <thead>
                <tr>

                    <th>Personagem<br></th>
                    <th>Produto</th>
                    <th>Codigo</th>
                    <th>Plataforma</th>
                    <th>Território</th>
                    <th  class="tb_number">Quantidade</th>
                    <th class="tb_number">Preço R$</th>
                    <th  class="tb_number">Total R$</th>
                    <th  class="tb_number">Dedução R$</th>
                    <th  class="tb_number">Receita Liquida R$</th>
                    <th  class="tb_number">Royalts %</th>
                    <th  class="tb_number">Royalts_Ganhos R$</th>
                    <th class="tb_number">Ganhos US$</th>

                </tr>
            </thead>
            <tbody>
                <tfoot>
                    <tr>
 
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                        <td class="tb_number">Total :</div></td>
                        <td  class="tb_number" id='total_quantidade_dev'></td>
                        <td class="td_acao"></td>
                        <td  class="tb_number" id='total_valor_dev'></td>
                        <td  class="tb_number" id='total_deducao_dev'></td>
                        <td  class="tb_number" id='total_liquido_dev'></td>
                        
                    
                        <td class="td_acao"></td>
                        <td  class="tb_number" id='total_royalt_dev'></td>
                          
               
                        <td  class="tb_number" id='total_usd_dev'></td>
                   
       

                    </tr>
                </tfoot>
            </tbody>
            
        </table>

          </div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        form_filter = $(document).find("#form_filter");

        form_filter.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_filter.find('.data').mask('00/0000');

        form_filter.find("#btn-create").off("click");
        form_filter.find("#btn-create").on("click",function(){
            showModalCreate();
        });

        form_filter.find("#btn-filterform").off("click");
        form_filter.find("#btn-filterform").on("click", function(){
            filterClear();
            filterAjax();
        });
        form_filter.find(".decimal_quatro_casas").maskMoney({thousands:'.', decimal:',', precision: 4});
       table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
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
                                if(column == 10){
                                    if(data != ''){
                                        
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','').replace('%','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                    
                               
                                }

                                if(column >= 5 && column != 10 ){
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
                            footer: function(data, column) {

                             
                                if(column == 5 || column == 7 || column == 8 || column == 9 || column ==11 || column == 12){

                                    if(data != ''){

                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                              }
                              data = $('<p>' + data + '</p>').text();
                              return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
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
        table_filters_dev = $('#table_filters_dev').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
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
                                if(column == 10){
                                    if(data != ''){
                                        
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','').replace('%','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                    
                               
                                }

                                if(column >= 5 && column != 10 ){
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
                            footer: function(data, column) {

                             
                                if(column == 5 || column == 7 || column == 8 || column == 9 || column ==11 || column == 12){

                                    if(data != ''){

                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                              }
                              data = $('<p>' + data + '</p>').text();
                              return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
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



    function filterAjax(){
        form_filter = $(document).find("#form_filter");
        data_form_filter = form_filter.serialize();
        limparMesagemErro(form_filter);
        filterClear();
        $.ajax({
            url: '{{ route('venda_playstation.filtro')}}',
            data: data_form_filter,
            method: 'POST',
            success: function(data){
                console.log(data);
                linhas = [];
                linhas_dev = [];
                
                for (var fields in data.response.venda_playstations){
                    temp_array = [
                        data.response.venda_playstations[fields].personagem,
                        ajusteTamanhoTable(data.response.venda_playstations[fields].produto),
                        data.response.venda_playstations[fields].codigo,
                        data.response.venda_playstations[fields].plataforma,
                        data.response.venda_playstations[fields].territorio,
                        data.response.venda_playstations[fields].retorno_unitario,
                        data.response.venda_playstations[fields].preco,
                        data.response.venda_playstations[fields].retorno_subtotal,
                        data.response.venda_playstations[fields].deducao,
                        data.response.venda_playstations[fields].liquida_receita,
                        data.response.venda_playstations[fields].royalts,
                        data.response.venda_playstations[fields].royalts_ganhos,
                        data.response.venda_playstations[fields].ganhos_usd,
              
                    ];
                    linhas.push(temp_array)
                }
                table_filters.rows.add(linhas).draw();
       
                $(document).find('#total_quantidade').html(data.response.total_quantidade);
                $(document).find('#total_valor').html(data.response.total_valor);
                $(document).find('#total_royalt').html(data.response.total_royalt);
                $(document).find('#total_deducao').html(data.response.total_deducao);
                $(document).find('#total_liquido').html(data.response.total_liquido);
                $(document).find('#total_usd').html(data.response.total_usd);

                for (var fields in data.response.devolucao_playstations){
                    temp_array_dev = [
                        data.response.devolucao_playstations[fields].personagem,
                        ajusteTamanhoTable(data.response.devolucao_playstations[fields].produto),
                        data.response.devolucao_playstations[fields].codigo,
                        data.response.devolucao_playstations[fields].plataforma,
                        data.response.devolucao_playstations[fields].territorio,
                        data.response.devolucao_playstations[fields].retorno_unitario,
                        data.response.devolucao_playstations[fields].preco,
                        data.response.devolucao_playstations[fields].retorno_subtotal,
                        data.response.devolucao_playstations[fields].deducao,
                        data.response.devolucao_playstations[fields].liquida_receita,
                        data.response.devolucao_playstations[fields].royalts,
                        data.response.devolucao_playstations[fields].royalts_ganhos,
                        data.response.devolucao_playstations[fields].ganhos_usd,
              
                    ];
                    linhas_dev.push(temp_array_dev)
                }
                table_filters_dev.rows.add(linhas_dev).draw();
       
                $(document).find('#total_quantidade_dev').html(data.response.total_quantidade_dev);
                $(document).find('#total_valor_dev').html(data.response.total_valor_dev);
                $(document).find('#total_royalt_dev').html(data.response.total_royalt_dev);
                $(document).find('#total_deducao_dev').html(data.response.total_deducao_dev);
                $(document).find('#total_liquido_dev').html(data.response.total_liquido_dev);
                $(document).find('#total_usd_dev').html(data.response.total_usd_dev);
            },
            error: function(data){
                var errors = data.responseJSON.error;
                for(var field in errors){
                    showErrorsInputs(form_filter, field, errors[field])
                }
            }
        });
    }

    function filterClear(){
        table_filters.clear().draw();
        table_filters_dev.clear().draw();
    }

    
 function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function showErrorsInputs(form_filter, input, message){
    var $input = $(form_filter).find("input[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}
 
function limparMesagemErro(form_filter){   
    form_filter.find('.error-message').remove();
    form_filter.find('input, select, span').removeClass('error-input');
}
 
@endsection