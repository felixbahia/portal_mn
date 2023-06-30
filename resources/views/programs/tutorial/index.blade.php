@extends('layouts.app-lista-preco')
@section('content')

<div class="content-book-virtual">
    @csrf
    <div class="title-book-virtual">Tutoriais</div>
    
        @foreach ($dados as $key => $video)
        <div class="row">
    
            <div class="col-lg-2">
                <div style="font-weight:900" class="col">  <a  href="#" >{{ $video['modulo'] }}</a> </div>
        </div>
        
          <div class="col-lg-10">
            @if(!empty($video['modulo']))
                    <div  class="col"> <hr><a  href="#"   data-chave={{$key}} data-id={{ $video['id'] }} onclick='modalVideoTutorial($(this))'> {{ $video['descricao'] }}</a> </hr></div>
            @else
                  <div  class="col"> <a  href="#"   data-chave={{$key}} data-id={{ $video['id'] }} onclick='modalVideoTutorial($(this))'> {{ $video['descricao'] }}</a> </div>
            @endif
        </div>
        </div>

  
      
      @endforeach
 
</div>


<script>

    function modalVideoTutorial($this) {
       
        var id = $this.data("id");
   
        var chave = $this.data("chave");
          $.ajax({
            url: '{{ route("tutorial.modal.video") }}',
            data: {_token: '{{ csrf_token() }}', id:id},
            method: 'POST',
            success: function(body){
    
                var title = 'Video Tutorial';
      
                createModal("form_video_tutorial_" +chave , title, body, '');
            }
        });
    }
</script>

@endsection
