
    </div>
    @yield('content-modal')
    <div class="background_alterar_auth"></div>
    <!-- Scripts -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('includes/moment/moment.min.js') }}"></script>
    <script src="{{ asset('includes/moment/moment-with-locales.js') }}"></script>
    <script src="{{ asset('includes/moment/locale/pt-br.js') }}"></script>
    <script src="{{ asset('includes/jQueryMask/jquery.mask.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('includes/bootstrap/js/bootstrap.js') }}"></script>
    <script src="{{ asset('includes/jQueryUi/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('includes/LoadPage/loader.js') }}"></script>
    <script src="{{ asset('includes/datetimepicker/datepicker.min.js') }}"></script>
    <script src="{{ asset('includes/datetimepicker/datepicker.pt-BR.js') }}"></script>
    <script src="{{ asset('includes/datetimepicker/jquery.dateandtime.js') }}"></script>
    <script src="{{ asset('includes/DataTables/datatables.min.js') }}"></script>
    <script src="{{ asset('includes/DataTables/Buttons-1.5.2/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('includes/moment/datetime-moment.js') }}"></script>
    <script src="{{ asset('includes/jsTree/jstree.js') }}"></script>
    <script src="{{ asset('includes/jsTree/jstree.checkbox.js') }}"></script>
    <script src="{{ asset('includes/maskmoney/jquery.maskMoney.min.js') }}"></script>
    <script src="{{ asset('includes/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('js/helpers.js') }}?t={{ rand(0,100) }}"></script>
    <script src="{{ asset('includes/DataTables/RowGroup-2.0.0/dataTables.rowsGroup.js') }}"></script>
    <script src="{{ asset('js/jquery.fancybox-1.3.4.js') }}"></script>
    <script src="{{ asset('includes/Roadmap/jquery.roadmap.min.js') }}"></script>
    <script src="{{ asset('includes/chartjs/Chart.min.js') }}"></script>
    <script src="{{ asset('includes/chartjs/chartjs-plugin-datalabels.min.js') }}"></script>
    <script src="{{ asset('includes/colorpicker/jquery.colorpicker.js') }}"></script>
    <link href="{{ asset('includes/colorpicker/jquery.colorpicker.css') }}" rel="stylesheet" type="text/css"/>
    <script src="{{ asset('includes/colorpicker/i18n/jquery.ui.colorpicker-nl.js') }}"></script>
    <script src="{{ asset('includes/colorpicker/parts/jquery.ui.colorpicker-rgbslider.js') }}"></script>
    <script src="{{ asset('includes/colorpicker/parts/jquery.ui.colorpicker-memory.js') }}"></script>
    <script src="{{ asset('includes/bootstrap/js/bootstrap3-typeahead.min.js') }}"></script>
    <script src="{{ asset('includes/bootstrap/js/bootstrap-multiselect.js') }}"></script>

    <script type="text/javascript">
        moment.lang("pt-br");
        var table_filters, table_dialog, xhr;
        jQuery.extend( jQuery.fn.dataTableExt.oSort, {
            "date-uk-pre": function ( a ) {
                if (a == null || a == "") {
                    return 0;
                }
                var ukDatea = a.split('/');
                return (ukDatea[2] + ukDatea[1] + ukDatea[0]) * 1;
            },
            "date-uk-asc": function ( a, b ) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },
            "date-uk-desc": function ( a, b ) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        } );
        jQuery.extend( jQuery.fn.dataTableExt.oSort, {
            "numeric-comma-ftm-pre": function ( a ) {
                var x = a.replace( /['$£€¥\%\u2009\u202F\u20BD\u20a9\u20BArfkɃΞ\.]/ig, "" ).replace( /,/, "." );
                if(isNaN(x) || x == ''){
                    return 0;
                }
                else{
                    return parseFloat( x );
                }
            },
         
            "numeric-comma-ftm-asc": function ( a, b ) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },
         
            "numeric-comma-ftm-desc": function ( a, b ) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        } );
        jQuery.extend( jQuery.fn.dataTableExt.oSort, {
            "numeric-comma-pre": function ( a ) {
                var x = a.replace( /\./ig, "" ).replace( /,/, "." );
                if(isNaN(x) || x == ''){
                    return 0;
                }
                else{
                    return parseFloat( x );
                }
            },
         
            "numeric-comma-asc": function ( a, b ) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },
         
            "numeric-comma-desc": function ( a, b ) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        } );
        jQuery.extend( jQuery.fn.dataTableExt.oSort, {
            "html-numeric-comma-pre": function ( a ) {
                var x = a.replace( /(<([^>]+)>)/ig, '').replace( /\./ig, "" ).replace( /,/, "." );
                if(isNaN(x) || x == ''){
                    return 0;
                }
                else{
                    return parseFloat( x );
                }
            },
         
            "html-numeric-comma-asc": function ( a, b ) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },
         
            "html-numeric-comma-desc": function ( a, b ) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        } );
        jQuery.extend( jQuery.fn.dataTableExt.oSort, {
            "mes-ano-pre": function ( a ) {
                if (a == null || a == "") {
                    return 0;
                }
                var mesAno = a.split('/');
                return (String(mesAno[1]) + String(mesAno[0]) + '01') * 1;
            },
            "mes-ano-asc": function ( a, b ) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },
            "mes-ano-desc": function ( a, b ) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        } );
        $.fn.dataTable.ext.errMode = 'throw';
        $.fn.dataTable.moment('DD/MM/YYYY');
        $(document).ready( function () {
            $("body").on("keyup", function(e){
                if(e.keyCode == 27){
                    if($(".empresa-auth-change").length){
                        $(".empresa-auth-change").slideUp("fast");
                        $(".background_alterar_auth").hide();
                    }
                    if(xhr != null && xhr.length != 0){
                        xhr.abort();
                    }
                }
            });
            $('input').each(function(){
                if($(this).attr("autocomplete") != "off"){
                    $(this).attr("autocomplete","off");   
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
            if($('.data').length){
                $('.data').mask("99/99/9999");
            }
            $('[data-toggle="tooltip"]').tooltip();
            $(document).find('.programa-favorito').on('click', function(){
                var $this = $(this);
                var $programa = $(this).data('programa');
                $.ajax({
                    url: '{{ route("favoritos.salvar") }}',
                    dataType: 'json',
                    method: 'POST',
                    data: {_token: "{{ csrf_token() }}", programa: $programa},
                    success: function(callback){
                        if(callback.response.ativo == 1){
                            $this.addClass('ativo');
                        }else{
                            $this.removeClass('ativo');
                        }
                    }
                });
            });
            if($('#table-filters').length){
                table_filters = $('#table-filters').DataTable({
                    "searching": false,
                    "lengthChange": false,
                    "info": false,
                    "pageLength": 15,
                    //"responsive": true,
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
                            "targets": ($('#table-filters thead th').length - 1),
                            "orderable": false
                        },
                        {
                            "targets": (!$('#table-filters').hasClass("table-filter-clientes") ? ($('#table-filters thead th').length - 2) : ""),
                            "orderable": (!$('#table-filters').hasClass("table-filter-clientes") ? false: true)
                        },
                        {
                            'targets': 'number_format',
                            "className": 'number_format',
                        },
                        {
                            "targets": 'date_format',
                            "className": 'date_format',
                        },
                    ]
                });
            }
        });
        @yield('script-footer')
    </script>
</body>
</html>
