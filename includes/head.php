<?php
/**
 * Project: qblockx
 * Include: head.php — shared <head> + opening <body>
 * Set $pageTitle before requiring this file.
 * Set $pageDescription, $pageKeywords to override defaults.
 */
$pageTitle       = $pageTitle       ?? 'Qblockx';
$pageDescription = $pageDescription ?? 'Qblockx is a cold wallet storage platform that keeps your private keys completely offline. Air-gapped security protects your crypto from remote hacks, malware, and phishing.';
$pageKeywords    = $pageKeywords    ?? 'Qblockx, cold wallet, cold storage, offline crypto storage, air-gapped wallet, private key security, hardware wallet, self-custody';

// Cache-bust static assets by file modification time so updates always load
if (!function_exists('assetVer')) {
    function assetVer(string $rel): string {
        $path = dirname(__DIR__) . $rel;
        return $rel . '?v=' . (@filemtime($path) ?: time());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- SEO -->
  <meta name="description"  content="<?= htmlspecialchars($pageDescription) ?>">
  <meta name="keywords"     content="<?= htmlspecialchars($pageKeywords) ?>">
  <meta name="author"       content="Qblockx">
  <meta name="robots"       content="index, follow">

  <!-- Open Graph -->
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="Qblockx">
  <meta property="og:title"       content="<?= htmlspecialchars($pageTitle) ?> — Qblockx">
  <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">

  <title><?= htmlspecialchars($pageTitle) ?> — Qblockx</title>

  <!-- Preload critical assets -->
  <link rel="preload" href="/assets/css/main.css" as="style">

  <!-- Google Fonts — Qblockx brand typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Fragment+Mono&display=swap" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="<?= assetVer('/assets/css/main.css') ?>">
  <link rel="stylesheet" href="<?= assetVer('/assets/css/responsive.css') ?>">
  <link rel="stylesheet" href="<?= assetVer('/assets/icons/style.css') ?>">

  <!-- LightRays WebGL -->
  <script src="<?= assetVer('/assets/js/light-rays.js') ?>" defer></script>

  <!-- Main JS -->
  <script src="<?= assetVer('/assets/js/main.js') ?>" defer></script>

  <!-- Favicon -->
  <link rel="icon"             type="image/x-icon"   href="/assets/favicon/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180"        href="/assets/favicon/apple-touch-icon.png">
  <link rel="icon"             type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
  <link rel="icon"             type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">

  <!-- Page-specific stylesheets (set $extraHeadLinks before requiring this file) -->
  <?php foreach ($extraHeadLinks ?? [] as $href): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
  <?php endforeach; ?>

  <!-- Page-specific deferred scripts (set $extraHeadScripts before requiring this file) -->
  <?php foreach ($extraHeadScripts ?? [] as $src): ?>
  <script src="<?= htmlspecialchars($src) ?>" defer></script>
  <?php endforeach; ?>
  <!-- Strip URL hash on load so the page always starts at the top -->
  <script>if(window.location.hash)history.replaceState(null,'',location.pathname+location.search);</script>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '') ?>">
