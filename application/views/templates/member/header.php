<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= isset($title) ? $title : 'Namua Member' ?></title>

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">

  <!-- Favicons -->
  <link rel="icon" type="image/x-icon" href="<?= base_url('assets/member/favicon.ico') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/member/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('assets/member/favicon-16x16.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/member/apple-touch-icon.png') ?>">

  <!-- Framework7 Core CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/framework7@8/framework7-bundle.min.css">

  <!-- Framework7 Icons (WAJIB kalau pakai <i class="f7-icons">) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/framework7-icons/css/framework7-icons.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/member/namua.css') ?>?v=<?= time() ?>">

  <style>
    /* FORCE MOBILE LOOK */
    html, body { background:#e6f0f2; }
    body { max-width:420px; margin:0 auto; min-height:100vh; background:#f3f6f8; box-shadow:0 0 24px rgba(0,0,0,.15); }
  </style>
</head>

<body>
<div id="app">
  <div class="view view-main">
    <!-- pastikan page-current supaya tidak hidden -->
    <div class="page page-current">
