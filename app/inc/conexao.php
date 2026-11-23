<?php
// Os dados do banco vêm de variáveis de ambiente: cada ambiente (dev, homologação, pré-produção,
// produção) tem o seu banco e a sua senha. Os valores depois do ?: valem só para o ambiente local.
$host  = getenv('DB_HOST') ?: 'db';
$banco = getenv('DB_NAME') ?: 'simples';
$user  = getenv('DB_USER') ?: 'simples';
$senha = getenv('DB_PASS') ?: 'simples';

$pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $user, $senha);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
