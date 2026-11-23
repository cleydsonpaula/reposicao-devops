<?php
$pdo = new PDO('mysql:host=db;dbname=simples;charset=utf8mb4', 'simples', 'simples');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
