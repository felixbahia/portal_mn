
    </div>
    <div class="background_alterar_auth"></div>
    <!-- Scripts -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('includes/bootstrap/js/bootstrap.js') }}"></script>
    <script>
        $(document).ready( function () {
            $("body").on("keyup", function(e){
                if(e.keyCode == 27){
                    $(".empresa-auth-change").slideUp("fast");
                    $(".background_alterar_auth").hide();
                }
            });
            $("#bt_empresa_auth").on("click", function(){
                $(".empresa-auth-change").slideToggle("fast");
                $(".background_alterar_auth").toggle();
            });
            $(".background_alterar_auth").on("click", function(){
                $(".empresa-auth-change").slideToggle("fast");
                $(".background_alterar_auth").toggle();
            });
            $("#bt_change_empresa_auth").on("click", function(){
                var $empresa = $("#empresa-padrao-auth").val();
                $.ajax({
                    url: '{{ route("usuario.update-empresa") }}',
                    dataType: 'json',
                    method: 'POST',
                    data: {_token: "{{ csrf_token() }}", empresa: $empresa},
                    success: function(data){
                        if(data.status === "success"){
                            location.reload(); 
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
