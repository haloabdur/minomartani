    </div>
    <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer no-print">
        <!-- To the right -->
        <div class="float-right d-none d-sm-inline">
            <a class="small text-muted" href="https://wa.me/6283869281843" target="_blank">By Tim IT RT 29</a>
        </div>
        <!-- Default to the left -->
        <strong>Copyright &copy; <?= date('Y') ?> <a>RT 29 Minomartani</a>.</strong> All rights reserved.
    </footer>
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->

    <!-- jQuery -->
    <script src="<?= base_url('public') ?>/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= base_url('public') ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= base_url('public') ?>/dist/js/adminlte.min.js"></script>

    <!-- DataTables -->
    <script src="<?= base_url('public') ?>/plugins/datatables/jquery.dataTables.js"></script>
    <script src="<?= base_url('public') ?>/plugins/datatables-bs4/js/dataTables.bootstrap4.js"></script>

    <!-- Select2 -->
    <script src="<?= base_url('public') ?>/plugins/select2/js/select2.full.min.js"></script>
    <!-- Summernote bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.summernote').summernote();
        });

        /** add active class and stay opened when selected */
        let url = window.location;

        function readURL(input) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#previewHolder').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#filePhoto").change(function() {
            readURL(this);
        });

        //Initialize Select2 Elements
        $('.select2').select2()

        $('.toast').toast('show');

        $(".datatable").DataTable({
            "ordering": false
        });

        // Input field Rupiah comma
        let rupiah = document.getElementById('rupiah');

        if (rupiah) {
            rupiah.addEventListener("keyup", function(e) {
                rupiah.value = convertRupiah(this.value);
            });
            rupiah.addEventListener('keydown', function(event) {
                return isNumberKey(event);
            });
        }

        /* Fungsi formatRupiah */
        function convertRupiah(angka, prefix) {
            let number_string = angka.replace(/[^,\d]/g, "").toString(),
                split = number_string.split(","),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
            return prefix == undefined ? rupiah : rupiah ? prefix + rupiah : "";
        }

        function isNumberKey(evt) {
            key = evt.which || evt.keyCode;
            if (key != 188 // Comma
                &&
                key != 8 // Backspace
                &&
                key != 17 && key != 86 & key != 67 // Ctrl c, ctrl v
                &&
                (key < 48 || key > 57) // Non digit
            ) {
                evt.preventDefault();
                return;
            }
        }

        // for sidebar menu entirely but not cover treeview
        $('ul.nav-sidebar .nav-item a').filter(function() {
            return this.href == url.href;
        }).addClass('active');

        // for treeview
        $('ul.nav-sidebar .treeview-menu a').filter(function() {
            return this.href == url;
        }).parents("li.treeview-menu").addClass('menu-open');

        // Global loading animation on form submit
        $('form').on('submit', function() {
            var btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        });
    </script>
    </body>

    </html>