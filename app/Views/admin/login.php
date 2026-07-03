<!DOCTYPE html>
<html>

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="RT 29 - Minomartani Ngaglik Sleman">
  <meta name="keywords" content="HTML5, bootstrap, mobile, app, landing, ios, android, responsive">
  <meta name="theme-color" content="#1A75CF" />
  <link rel="icon" href="<?php echo base_url('public/home/') ?>assets/logo-sleman.jpg" type="image/x-icon">
  <title>Admin - RT 29 Minomartani</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo base_url('public') ?>/plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo base_url('public') ?>/dist/css/adminlte.min.css">
  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <?php if (config('Turnstile')->enabled): ?>
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  <?php endif; ?>
</head>

<body class="hold-transition login-page">
  <div class="login-box">
    <?php echo loadFlashData(); ?>
    <div class="login-logo">
      <div class="row justify-content-center mb-4">
        <a href="<?php echo base_url() ?>"><img src="<?php echo base_url('public/home/assets/img/logo-dark.png') ?>"></a>
      </div>
    </div>
    <!-- /.login-logo -->
    <div class="card">
      <div class="card-body login-card-body">
        <p class="login-box-msg">Login untuk masuk ke sistem</p>

        <!-- Pesan Error Shield -->
        <?php if (session('error') !== null) : ?>
          <div class="alert alert-danger" role="alert"><?= session('error') ?></div>
        <?php elseif (session('errors') !== null) : ?>
          <div class="alert alert-danger" role="alert">
            <?php if (is_array(session('errors'))) : ?>
              <?php foreach (session('errors') as $error) : ?>
                <?= $error ?>
                <br>
              <?php endforeach ?>
            <?php else : ?>
              <?= session('errors') ?>
            <?php endif ?>
          </div>
        <?php endif ?>

        <?php if (session('message') !== null) : ?>
          <div class="alert alert-success" role="alert"><?= session('message') ?></div>
        <?php endif ?>

        <form action="<?= url_to('login') ?>" method="post">
          <?= csrf_field() ?>

          <div class="input-group mb-3">
            <input type="email" class="form-control" name="email" inputmode="email" autocomplete="email" placeholder="Email" value="<?= old('email') ?>" required="">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>
          <div class="input-group mb-3">
            <input type="password" class="form-control" name="password" inputmode="text" autocomplete="current-password" placeholder="Password" required="">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>
          <!-- Implementasi remember me dari Shield -->
          <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
            <div class="row mb-3">
              <div class="col-8">
                <div class="icheck-primary">
                  <input type="checkbox" name="remember" id="remember" <?php if (old('remember')): ?> checked <?php endif ?>>
                  <label for="remember">
                    Ingat Saya
                  </label>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if (config('Turnstile')->enabled): ?>
            <div class="row mb-3 justify-content-center">
              <div class="cf-turnstile" data-sitekey="<?= esc(config('Turnstile')->siteKey) ?>"></div>
            </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-12">
              <button type="submit" id="btnLogin" class="btn btn-primary btn-block">
                <span id="btnText">Masuk</span>
                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              </button>
            </div>
          </div>
        </form>
      </div>
      <!-- /.login-card-body -->
    </div>
  </div>
  <!-- /.login-box -->

  <nav class="navbar fixed-bottom navbar-expand-sm">
    <a class="text-muted ml-auto small" target="_blank" href="https://wa.me/6283869281843">By Tim IT RT 29</a>
  </nav>

  <!-- jQuery -->
  <script src="<?php echo base_url('public') ?>/plugins/jquery/jquery.min.js"></script>
  <!-- Bootstrap 4 -->
  <script src="<?php echo base_url('public') ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- AdminLTE App -->
  <script src="<?php echo base_url('public') ?>/dist/js/adminlte.min.js"></script>

  <script type="text/javascript">
    $('.toast').toast('show');

    // Animasi loading tombol Masuk
    $('form').on('submit', function() {
      // Pastikan form valid (HTML5)
      if (this.checkValidity()) {
        $('#btnLogin').prop('disabled', true);
        $('#btnText').text('Memproses...');
        $('#btnSpinner').removeClass('d-none');
      }
    });
  </script>

</body>

</html>