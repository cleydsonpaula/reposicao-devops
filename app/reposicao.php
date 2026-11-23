<?php
require 'inc/auth.php';
require 'inc/csv.php';
$msg = '';
$erro = '';
$naoCadastrados = [];
$semEstoque = [];
$principal = enderecoId($pdo, 'principal');
$excesso = enderecoId($pdo, 'excesso');

if (isset($_FILES['csv']) && $_FILES['csv']['tmp_name']) {
    list($cab, $linhas) = lerCsv($_FILES['csv']['tmp_name']);
    $colSku = 0; $colPedido = 1; $colQtd = 4;
    foreach ($cab as $i => $c) {
        $c = strtolower($c);
        if (strpos($c, 'digo do produto') !== false) $colSku = $i;
        if (strpos($c, 'pedido') !== false) $colPedido = $i;
        if (strpos($c, 'quantidade') !== false) $colQtd = $i;
    }
    $pdo->prepare("INSERT INTO importacoes (arquivo, usuario_id, linhas) VALUES (?,?,?)")
        ->execute([$_FILES['csv']['name'], $_SESSION['usuario_id'], count($linhas)]);
    $importacaoId = $pdo->lastInsertId();

    $acha = $pdo->prepare("SELECT p.id, COALESCE(e.quantidade, 0) AS quantidade FROM produtos p LEFT JOIN estoque e ON e.produto_id = p.id AND e.endereco_id = $principal WHERE p.sku = ?");
    $existe = $pdo->prepare("SELECT id FROM pedidos_baixados WHERE pedido = ? AND produto_id = ?");
    $grava = $pdo->prepare("INSERT INTO pedidos_baixados (importacao_id, produto_id, pedido, quantidade) VALUES (?,?,?,?)");
    $baixa = $pdo->prepare("INSERT INTO estoque (produto_id, endereco_id, quantidade) VALUES (?, $principal, 0) ON DUPLICATE KEY UPDATE quantidade = GREATEST(quantidade - ?, 0)");
    $baixados = 0; $repetidos = 0;
    foreach ($linhas as $l) {
        $sku = trim($l[$colSku]);
        $pedido = trim($l[$colPedido]);
        $qtd = numero($l[$colQtd]);
        if ($sku == '' || $pedido == '' || $qtd <= 0) continue;
        $acha->execute([$sku]);
        $prod = $acha->fetch();
        if (!$prod) { $naoCadastrados[$sku] = true; continue; }
        $existe->execute([$pedido, $prod['id']]);
        if ($existe->fetch()) { $repetidos++; continue; }
        if ($qtd > $prod['quantidade']) $semEstoque[$sku] = ($semEstoque[$sku] ?? 0) + ($qtd - $prod['quantidade']);
        $grava->execute([$importacaoId, $prod['id'], $pedido, $qtd]);
        $baixa->execute([$prod['id'], $qtd]);
        registraMovimento($pdo, $prod['id'], $principal, 'venda', -$qtd);
        $baixados++;
    }
    $pdo->prepare("UPDATE importacoes SET baixas = ?, repetidos = ? WHERE id = ?")->execute([$baixados, $repetidos, $importacaoId]);
    $msg = "$baixados baixa(s) feita(s), $repetidos pedido(s) já baixado(s) antes";
}

if (isset($_POST['repor'])) {
    $id = (int)$_POST['produto_id'];
    $qtd = (int)$_POST['qtd'];
    $q = $pdo->prepare("SELECT p.sku, COALESCE(e.quantidade, 0) AS qtd_excesso FROM produtos p LEFT JOIN estoque e ON e.produto_id = p.id AND e.endereco_id = $excesso WHERE p.id = ?");
    $q->execute([$id]);
    $p = $q->fetch();
    if (!$p) {
        $erro = 'Produto não encontrado';
    } elseif ($qtd <= 0) {
        $erro = 'Informe a quantidade';
    } elseif ($qtd > $p['qtd_excesso']) {
        $erro = "O excesso de {$p['sku']} só tem {$p['qtd_excesso']}";
    } else {
        $pdo->prepare("UPDATE estoque SET quantidade = quantidade - ? WHERE produto_id = ? AND endereco_id = $excesso")->execute([$qtd, $id]);
        $pdo->prepare("INSERT INTO estoque (produto_id, endereco_id, quantidade) VALUES (?, $principal, ?) ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)")->execute([$id, $qtd]);
        registraMovimento($pdo, $id, $excesso, 'reposicao', -$qtd);
        registraMovimento($pdo, $id, $principal, 'reposicao', $qtd);
        $msg = "$qtd peça(s) de {$p['sku']} repostas no principal";
    }
}

$busca = trim($_GET['busca'] ?? '');
$produtos = listaProdutos($pdo, $busca, !isset($_GET['todos']) && $busca == '');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Reposição</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<?php include 'inc/menu.php'; ?>
<h1>Reposição</h1>
<?php if ($msg) echo "<div class='ok'>$msg <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>
<?php if ($erro) echo "<div class='erro'>$erro <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>
<?php if ($semEstoque): ?>
<div class="erro">Principal não tinha estoque para toda a baixa (ficou em zero): <?php foreach ($semEstoque as $k => $v) echo htmlspecialchars($k) . " (faltaram $v) "; ?> <a href="#" class="fechar" onclick="this.parentNode.remove();return false">x</a></div>
<?php endif; ?>
<?php if ($naoCadastrados): ?>
<div class="erro">SKUs do CSV que não estão cadastrados (sem baixa): <?= htmlspecialchars(implode(', ', array_keys($naoCadastrados))) ?> <a href="#" class="fechar" onclick="this.parentNode.remove();return false">x</a></div>
<?php endif; ?>

<div class="caixa">
  <h2>Dar baixa nas vendas (CSV do Bling)</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="csv" accept=".csv" required>
    <input type="submit" value="Dar baixa">
  </form>
  <p class="pequeno">Pedido que já deu baixa antes é ignorado, pode subir o mesmo relatório de novo.</p>
</div>

<div class="caixa busca">
  <form method="get">
    &#128269; <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar SKU ou descrição">
    <input type="submit" value="Buscar">
    <?php if ($busca != ''): ?><a href="reposicao.php" class="pequeno">limpar</a><?php endif; ?>
  </form>
</div>

<h2><?= $busca != '' ? 'Resultado da busca' : 'O que falta no principal' ?>
  <?php if ($busca == ''): ?>
    <?php if (isset($_GET['todos'])): ?>
      <a href="reposicao.php" class="pequeno">(mostrar só o que falta)</a>
    <?php else: ?>
      <a href="reposicao.php?todos=1" class="pequeno">(mostrar todos)</a>
    <?php endif; ?>
  <?php endif; ?>
</h2>
<table>
  <tr><th>SKU</th><th>Descrição</th><th>Principal</th><th>Capacidade</th><th>Falta</th><th>Excesso</th><th>Repor do excesso</th></tr>
  <?php foreach ($produtos as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['sku']) ?></td>
    <td><?= htmlspecialchars($p['descricao']) ?></td>
    <td><?= $p['qtd_principal'] ?></td>
    <td><?= $p['capacidade'] ?></td>
    <td class="<?= $p['falta'] > 0 ? 'falta' : '' ?>"><?= $p['falta'] > 0 ? $p['falta'] : '-' ?></td>
    <td><?= $p['qtd_excesso'] ?></td>
    <td class="repor">
      <?php if ($p['qtd_excesso'] > 0): ?>
      <form method="post">
        <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
        <input type="number" name="qtd" value="<?= min(max($p['falta'], 0), $p['qtd_excesso']) ?>" min="1" max="<?= $p['qtd_excesso'] ?>">
        <input type="submit" name="repor" value="Repor">
      </form>
      <?php else: ?>
      <span class="pequeno">sem excesso</span>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<p class="pequeno"><?= count($produtos) ?> produto(s)</p>
</body>
</html>
