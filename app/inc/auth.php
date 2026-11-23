<?php
session_start();
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'inc/conexao.php';
require 'inc/estoque.php';
