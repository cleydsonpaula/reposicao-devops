<?php
session_start();
require 'inc/conexao.php';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $q->execute([$_POST['usuario']]);
    $u = $q->fetch();
    if ($u && !$u['ativo']) {
        $erro = 'Usuário inativo';
    } elseif ($u && password_verify($_POST['senha'], $u['senha'])) {
        $_SESSION['usuario_id'] = $u['id'];
        $_SESSION['usuario'] = $u['usuario'];
        header('Location: reposicao.php');
        exit;
    } else {
        $erro = 'Usuário ou senha errados';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Login</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<div class="caixa login">
  <h1>Reposição Simples</h1>
  <?php if ($erro) echo "<div class='erro'>$erro</div>"; ?>
  <form method="post">
    <input type="text" name="usuario" placeholder="Usuário" required>
    <input type="password" name="senha" placeholder="Senha" required>
    <input type="submit" value="Entrar">
  </form>
</div>
</body>
</html>
