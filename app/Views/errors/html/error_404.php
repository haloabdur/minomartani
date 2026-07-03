<?php helper('url'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#15C269">
    <title><?= lang('Errors.pageNotFound') ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f9;
            font-family: "Source Sans Pro", "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #444;
        }

        .card {
            width: 100%;
            max-width: 480px;
            margin: 1.5rem;
            padding: 2.5rem 2rem;
            background: #fff;
            border-top: 3px solid #dc3545;
            border-radius: 0.35rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.06);
            text-align: center;
        }

        .logo {
            width: 56px;
            height: 56px;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .code {
            font-size: 4.5rem;
            font-weight: 700;
            line-height: 1;
            color: #dc3545;
            margin: 0;
        }

        h1 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #343a40;
            margin: 0.75rem 0 0.5rem;
        }

        p.message {
            font-size: 0.95rem;
            color: #6c757d;
            margin: 0 0 1.5rem;
            word-break: break-word;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: #15C269;
            color: #fff;
            text-decoration: none;
            border-radius: 0.25rem;
            font-size: 0.95rem;
            transition: background 0.15s ease-in-out;
        }

        .btn:hover {
            background: #10a058;
        }

        .footer {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="<?= base_url('public/img/logo.png') ?>" alt="RT 29 Minomartani" class="logo">
        <p class="code">404</p>
        <h1><?= lang('Errors.pageNotFound') ?></h1>
        <p class="message">
            <?php if (ENVIRONMENT !== 'production') : ?>
                <?= nl2br(esc($message)) ?>
            <?php else : ?>
                <?= lang('Errors.sorryCannotFind') ?>
            <?php endif; ?>
        </p>
        <a href="<?= base_url('/') ?>" class="btn">Kembali ke Beranda</a>
        <div class="footer">RT 29 Minomartani</div>
    </div>
</body>
</html>
