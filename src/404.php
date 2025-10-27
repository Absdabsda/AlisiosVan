<?php
http_response_code(404);
$lang = htmlspecialchars($_GET['lang'] ?? ($_COOKIE['lang'] ?? 'es'));
?><!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <title>404 — Página no encontrada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/src/css/estilos.css">
</head>
<body>
<main style="max-width:720px;margin:4rem auto;padding:1rem;text-align:center">
    <h1>404</h1>
    <p>La página que buscas no existe.</p>
    <p><a href="/<?= $lang ?>/">Volver al inicio</a></p>
</main>
</body>
</html>
