
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
    <script src="{{ asset('includes/DataTables/datatables.min.js') }}"></script>
    <script src="{{ asset('includes/DataTables/Buttons-1.5.2/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('includes/moment/datetime-moment.js') }}"></script>
    <script src="{{ asset('includes/jsTree/jstree.js') }}"></script>
    <script src="{{ asset('includes/jsTree/jstree.checkbox.js') }}"></script>
    <script src="{{ asset('includes/maskmoney/jquery.maskMoney.min.js') }}"></script>
    <script src="{{ asset('js/helpers.js') }}"></script>
    <script src="{{ asset('js/jquery.fancybox-1.3.4.js') }}"></script>

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
            if($('.data').length){
                $('.data').mask("99/99/9999");
            }
            $('[data-toggle="tooltip"]').tooltip();
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
                        }
                    ]
                });
            }
        } );
        @yield('script-footer')
    </script>
</body>
</html>
