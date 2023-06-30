@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog-despesas" style="width:100%">
        <thead>
            <tr>
                <th></th> 
                <th class="tb_number">Jan</th>
                <th class="tb_number">Fev</th>
                <th class="tb_number">Mar</th>
                <th class="tb_number">Abr</th>
                <th class="tb_number">Mai</th>
                <th class="tb_number">Jun</th>
                <th class="tb_number">Jul</th>
                <th class="tb_number">Ago</th>
                <th class="tb_number">Set</th>
                <th class="tb_number">Out</th>
                <th class="tb_number">Nov</th>
                <th class="tb_number">Dez</th>
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados['bancos_realizados'] as $banco_realizado)
                <tr class="table-primary">
                    <td class="details-control desativo {{$banco_realizado["fornecedor_codigo"]}}" data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' ><a href='#' class='bt-plus-azul d-lg-inline {{$banco_realizado["fornecedor_codigo"]}}'></a>{{$banco_realizado['fornecedor']}}</a></td> 
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="01"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 01/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_1']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="02"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 02/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_2']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="03"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 03/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_3']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="04"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 04/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_4']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="05"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 05/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_5']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="06"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 06/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_6']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="07"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 07/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_7']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="08"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 08/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_8']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="09"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 09/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_9']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="10"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 10/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_10']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="11"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 11/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_11']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="12"  data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - 12/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_12']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="" data-status='' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto/Quitado: {{$banco_realizado['fornecedor']}} - {{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['inteiro_total']}}</a></td>
                </tr>
                <tr class="{{$banco_realizado["fornecedor_codigo"]}}">
                    <td>{{$banco_realizado['abertos']['descricao']}}</td> 
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="01"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 01/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_1']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="02"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 02/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_2']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="03"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 03/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_3']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="04"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 04/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_4']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="05"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 05/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_5']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="06"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 06/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_6']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="07"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 07/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_7']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="08"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 08/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_8']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="09"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 09/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_9']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="10"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 10/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_10']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="11"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 11/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_11']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="12"  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - 12/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_12']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes=""  data-status='Aberto' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Aberto: {{$banco_realizado['fornecedor']}} - {{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['abertos']['inteiro_total']}}</a></td>
                </tr>
                <tr class="{{$banco_realizado["fornecedor_codigo"]}}">
                    <td>{{$banco_realizado['quitados']['descricao']}}</td> 
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="01"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 01/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_1']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="02"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 02/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_2']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="03"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 03/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_3']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="04"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 04/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_4']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="05"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 05/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_5']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="06"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 06/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_6']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="07"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 07/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_7']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="08"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 08/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_8']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="09"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 09/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_9']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="10"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 10/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_10']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="11"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 11/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_11']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="12"  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - 12/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_12']}}</a></td>
                    <td><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes=""  data-status='Quitado' title='Visualizar' data-fornecedor_codigo='{{$banco_realizado["fornecedor_codigo"]}}' data-title="Banco Realizada Títulos Quitado: {{$banco_realizado['fornecedor']}} - {{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$banco_realizado['quitados']['inteiro_total']}}</a></td>
                </tr>
                <tr class="{{$banco_realizado["fornecedor_codigo"]}}">
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                    <td class="td-separador"></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total :</td> 
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="01"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 01/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_1']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="02"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 02/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_2']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="03"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 03/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_3']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="04"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 04/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_4']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="05"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 05/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_5']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="06"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 06/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_6']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="07"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 07/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_7']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="08"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 08/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_8']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="09"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 09/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_9']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="10"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 10/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_10']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="11"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 11/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_11']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes="12"  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: 12/{{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_12']}}</a></td>
                <td class="tb_number"><a href="#"  data-toggle='tooltip' data-html='true' data-ano="{{$dados['ano']}}" data-mes=""  title='Visualizar' data-fornecedor_codigo='' data-status='' data-title="Banco Realizada Títulos Aberto/Quitado: {{$dados['ano']}}" onclick="abriModalBancoTitulo($(this))">{{$dados['total']['inteiro_total']}}</a></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        var table_dialog = $('#table-dialog-despesas').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "ordering": false,
            "scrollCollapse": true,
            "scrollY": "70vh",
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
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
        }).on('click', 'td.details-control', function () {
            var fornecedor_codigo = $(this).closest('td.details-control').data("fornecedor_codigo");
            var td = $(this);
            if (td.hasClass("ativo")) {
                $(document).find('tr.'+fornecedor_codigo).hide();
                $(document).find(".bt-minus-azul."+fornecedor_codigo).addClass('bt-plus-azul');
                $(document).find(".bt-minus-azul."+fornecedor_codigo).removeClass('bt-minus-azul');
                td.addClass('desativo');
                td.removeClass('ativo');
            }else{
                $(document).find('tr.'+fornecedor_codigo).show();
                $(document).find(".bt-plus-azul."+fornecedor_codigo).addClass('bt-minus-azul');
                $(document).find(".bt-plus-azul."+fornecedor_codigo).removeClass('bt-plus-azul');
                td.addClass('ativo');
                td.removeClass('desativo'); 
            }
                        
        } );

        @foreach($dados['bancos_realizados'] as $banco_realizado)
            $(document).find('tr.{{$banco_realizado["fornecedor_codigo"]}}').hide();
        @endforeach

        setTimeout(function(){
            table_dialog.draw(false);
        }, 180);
    });

    function abriModalBancoTitulo($this){
        var ano = $($this).data("ano");
        var mes = $($this).data("mes");
        var fornecedor_codigo = $($this).data("fornecedor_codigo");
        var status = $($this).data("status");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.banco_titulo_detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                mes: mes,
                fornecedor_codigo: fornecedor_codigo,
                status: status,
            },
            success: function(body){
                createModal('modal_detalhes_banco_titulo', title, body, "modal-lg");
                var modal = $("#modal_detalhes_banco_titulo");
            }
        });
    }
</script>
@endsection