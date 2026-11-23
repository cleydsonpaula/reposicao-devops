<?php
require 'inc/auth.php';
$movimentos = $pdo->query("
    SELECT m.data, m.quantidade, t.nome AS tipo, p.sku, e.nome AS endereco, u.usuario
    FROM movimentos m
    JOIN tipos_movimento t ON t.id = m.tipo_id
    JOIN produtos p ON p.id = m.produto_id
    JOIN enderecos e ON e.id = m.endereco_id
    JOIN usuarios u ON u.id = m.usuario_id
    ORDER BY m.id DESC LIMIT 300")->fetchAll();
$importacoes = $pdo->query("
    SELECT i.*, u.usuario FROM importacoes i JOIN usuarios u ON u.id = i.usuario_id
    ORDER BY i.id DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Histórico</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<?php include 'inc/menu.php'; ?>
<h1>Histórico</h1>

<h2>Arquivos importados</h2>
<table>
  <tr><th>Data</th><th>Arquivo</th><th>Linhas</th><th>Baixas</th><th>Repetidos</th><th>Usuário</th></tr>
  <?php foreach ($importacoes as $i): ?>
  <tr>
    <td><?= date('d/m/Y H:i', strtotime($i['data'])) ?></td>
    <td><?= htmlspecialchars($i['arquivo']) ?></td>
    <td><?= $i['linhas'] ?></td>
    <td><?= $i['baixas'] ?></td>
    <td><?= $i['repetidos'] ?></td>
    <td><?= htmlspecialchars($i['usuario']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<h2>Movimentos</h2>
<table>
  <tr><th>Data</th><th>Tipo</th><th>SKU</th><th>Endereço</th><th>Qtd</th><th>Usuário</th></tr>
  <?php foreach ($movimentos as $m): ?>
  <tr>
    <td><?= date('d/m/Y H:i', strtotime($m['data'])) ?></td>
    <td><?= htmlspecialchars($m['tipo']) ?></td>
    <td><?= htmlspecialchars($m['sku']) ?></td>
    <td><?= htmlspecialchars($m['endereco']) ?></td>
    <td class="<?= $m['quantidade'] < 0 ? 'falta' : '' ?>"><?= $m['quantidade'] > 0 ? '+' : '' ?><?= $m['quantidade'] ?></td>
    <td><?= htmlspecialchars($m['usuario']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<p class="pequeno">Últimos 300 movimentos</p>
</body>
</html>
