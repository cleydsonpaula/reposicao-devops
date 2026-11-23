<?php
require 'inc/auth.php';
$msg = '';
$erro = '';

if (isset($_POST['criar'])) {
    $nome = trim($_POST['usuario']);
    $senha = $_POST['senha'];
    $q = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $q->execute([$nome]);
    if ($nome == '' || $senha == '') {
        $erro = 'Informe usuário e senha';
    } elseif ($q->fetch()) {
        $erro = "O usuário $nome já existe";
    } else {
        $pdo->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?,?)")->execute([$nome, password_hash($senha, PASSWORD_DEFAULT)]);
        $msg = "Usuário $nome criado";
    }
}

if (isset($_POST['ativo'])) {
    $id = (int)$_POST['id'];
    if ($id == $_SESSION['usuario_id']) {
        $erro = 'Você não pode inativar o próprio usuário';
    } else {
        $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?")->execute([(int)$_POST['ativo'], $id]);
        $msg = 'Usuário ' . ($_POST['ativo'] ? 'reativado' : 'inativado');
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY usuario")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Usuários</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<?php include 'inc/menu.php'; ?>
<h1>Usuários</h1>
<?php if ($msg) echo "<div class='ok'>$msg <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>
<?php if ($erro) echo "<div class='erro'>$erro <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>

<div class="caixa">
  <h2>Criar usuário</h2>
  <form method="post">
    Login <input type="text" name="usuario" required>
    Senha <input type="password" name="senha" required>
    <input type="submit" name="criar" value="Criar">
  </form>
</div>

<table>
  <tr><th>Login</th><th>Situação</th><th>Criado em</th><th></th></tr>
  <?php foreach ($usuarios as $u): ?>
  <tr>
    <td><?= htmlspecialchars($u['usuario']) ?></td>
    <td><?= $u['ativo'] ? 'Ativo' : '<span class="falta">Inativo</span>' ?></td>
    <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
    <td class="acoes">
      <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
      <form method="post">
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        <input type="hidden" name="ativo" value="<?= $u['ativo'] ? 0 : 1 ?>">
        <input type="submit" value="<?= $u['ativo'] ? 'Inativar' : 'Reativar' ?>" class="<?= $u['ativo'] ? 'vermelho' : '' ?>">
      </form>
      <?php else: ?>
      <span class="pequeno">você</span>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
