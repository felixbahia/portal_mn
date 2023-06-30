@extends('layouts.app')

@section('content')

       <table>
          <tbody>
               <tr>
                  <td width="33%" valign="top">
                    {{-- Uma Coluna  --}}
                    @foreach($response as $coluna)
                    @if($coluna['coluna']=='Primeira') 
                        <div class="lista-ramais">        
                            @if($coluna['departamento']=='idem')    
                            <div>{{  $coluna['nome'] }} </div><div align='right'> {{  $coluna['ramal'] }} </div>                                    
                            @else
                            <div  class="title-ramais"> {{ $coluna['departamento'] }} </div> 
                            <div>{{  $coluna['nome'] }} </div><div align='right'> {{  $coluna['ramal'] }} </div>  
                            @endif
                        </div>
                    @endif
                @endforeach</td>  
                  
                 
                <td width="33%" valign="top">
                    {{-- Uma Coluna  --}}
                    @foreach($response as $coluna)
                    @if($coluna['coluna']=='Segunda') 
                        <div class="lista-ramais">        
                            @if($coluna['departamento']=='idem')                                    
                            <div>{{  $coluna['nome'] }} </div><div align='right'> {{  $coluna['ramal'] }} </div>                                   
                            @else
                            <div  class="title-ramais"> {{ $coluna['departamento'] }} </div> 
                            <div>{{  $coluna['nome'] }} </div><div align='right'> {{  $coluna['ramal'] }} </div>  
                            @endif
                        </div>
                    @endif
                @endforeach</td>  
                <td width="33%" valign="top">
                    {{-- Uma Coluna  --}}
                    @foreach($response as $coluna)
                    @if($coluna['coluna']=='Terceira') 
                        <div class="lista-ramais">        
                            @if($coluna['departamento']=='idem')                                    
                            <div>{{  $coluna['nome'] }} </div><div align='right'> {{  $coluna['ramal'] }} </div>                                     
                            @else
                            <div  class="title-ramais"> {{ $coluna['departamento'] }} </div> 
                            <div>{{  $coluna['nome'] }} </div><div align='right'> {{  $coluna['ramal'] }} </div>  
                            @endif
                        </div>
                    @endif
                @endforeach</td>  
               </tr>
                  
           </tbody>
          
       </table>
  


@endsection