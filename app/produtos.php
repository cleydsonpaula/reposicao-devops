<?php
require 'inc/auth.php';
require 'inc/csv.php';
$msg = '';
$erro = '';
$principal = enderecoId($pdo, 'principal');
$excesso = enderecoId($pdo, 'excesso');

if (isset($_POST['cadastrar'])) {
    $sku = trim($_POST['sku']);
    $q = $pdo->prepare("SELECT id FROM produtos WHERE sku = ?");
    $q->execute([$sku]);
    if ($sku == '') {
        $erro = 'Informe o SKU';
    } elseif ($q->fetch()) {
        $erro = "O SKU $sku já existe. Use o botão Editar na lista.";
    } else {
        $pdo->prepare("INSERT INTO produtos (sku, descricao) VALUES (?,?)")->execute([$sku, trim($_POST['descricao'])]);
        $id = $pdo->lastInsertId();
        salvaEstoque($pdo, $id, $principal, (int)$_POST['qtd_principal'], (int)$_POST['capacidade']);
        salvaEstoque($pdo, $id, $excesso, (int)$_POST['qtd_excesso'], 0);
        $msg = "Produto $sku cadastrado";
    }
}

if (isset($_POST['editar'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE produtos SET descricao = ? WHERE id = ?")->execute([trim($_POST['descricao']), $id]);
    salvaEstoque($pdo, $id, $principal, (int)$_POST['qtd_principal'], (int)$_POST['capacidade']);
    salvaEstoque($pdo, $id, $excesso, (int)$_POST['qtd_excesso'], 0);
    $msg = 'Produto ' . htmlspecialchars($_POST['sku']) . ' atualizado';
}

if (isset($_POST['apagar'])) {
    $pdo->prepare("DELETE FROM produtos WHERE id = ?")->execute([(int)$_POST['apagar']]);
    $msg = 'Produto apagado';
}

if (isset($_FILES['csv']) && $_FILES['csv']['tmp_name']) {
    list($cab, $linhas) = lerCsv($_FILES['csv']['tmp_name']);
    $ins = $pdo->prepare("INSERT INTO produtos (sku, descricao) VALUES (?,?) ON DUPLICATE KEY UPDATE descricao = VALUES(descricao), id = LAST_INSERT_ID(id)");
    $n = 0;
    foreach ($linhas as $l) {
        $sku = trim($l[0]);
        if ($sku == '') continue;
        $ins->execute([$sku, trim($l[1] ?? '')]);
        $id = $pdo->lastInsertId();
        salvaEstoque($pdo, $id, $principal, numero($l[2] ?? 0), numero($l[4] ?? 0));
        salvaEstoque($pdo, $id, $excesso, numero($l[3] ?? 0), 0);
        $n++;
    }
    $msg = "$n produto(s) importado(s)";
}

$editando = null;
if (isset($_GET['editar'])) {
    foreach (listaProdutos($pdo) as $p) if ($p['id'] == (int)$_GET['editar']) $editando = $p;
}

$busca = trim($_GET['busca'] ?? '');
$produtos = listaProdutos($pdo, $busca);
usort($produtos, fn($a, $b) => strcmp($a['sku'], $b['sku']));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Produtos</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<?php include 'inc/menu.php'; ?>
<h1>Produtos</h1>
<?php if ($msg) echo "<div class='ok'>$msg <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>
<?php if ($erro) echo "<div class='erro'>$erro <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>

<?php if ($editando): ?>
<div class="caixa editar">
  <h2>Editar <?= htmlspecialchars($editando['sku']) ?></h2>
  <form method="post">
    <input type="hidden" name="id" value="<?= $editando['id'] ?>">
    <input type="hidden" name="sku" value="<?= htmlspecialchars($editando['sku']) ?>">
    Descrição <input type="text" name="descricao" size="40" value="<?= htmlspecialchars($editando['descricao']) ?>">
    Principal <input type="number" name="qtd_principal" value="<?= $editando['qtd_principal'] ?>" size="5">
    Excesso <input type="number" name="qtd_excesso" value="<?= $editando['qtd_excesso'] ?>" size="5">
    Capacidade <input type="number" name="capacidade" value="<?= $editando['capacidade'] ?>" size="5">
    <input type="submit" name="editar" value="Salvar alterações">
    <a href="produtos.php" class="pequeno">cancelar</a>
  </form>
  <p class="pequeno">O SKU não muda. Para trocar o SKU, apague e cadastre de novo.</p>
</div>
<?php else: ?>
<div class="caixa">
  <h2>Cadastrar produto novo</h2>
  <form method="post">
    SKU <input type="text" name="sku" required>
    Descrição <input type="text" name="descricao" size="40">
    Principal <input type="number" name="qtd_principal" value="0" size="5">
    Excesso <input type="number" name="qtd_excesso" value="0" size="5">
    Capacidade <input type="number" name="capacidade" value="0" size="5">
    <input type="submit" name="cadastrar" value="Cadastrar">
  </form>
  <p class="pequeno">SKU que já existe não é aceito aqui. Para mudar um produto, use o botão Editar na lista.</p>
</div>
<?php endif; ?>

<div class="caixa">
  <h2>Importar CSV</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="csv" accept=".csv" required>
    <input type="submit" value="Importar">
  </form>
  <p class="pequeno">Colunas separadas por ponto e vírgula, com cabeçalho: SKU;Descrição;Principal;Excesso;Capacidade</p>
</div>

<div class="caixa busca">
  <form method="get">
    &#128269; <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar SKU ou descrição">
    <input type="submit" value="Buscar">
  </form>
</div>

<table>
  <tr><th>SKU</th><th>Descrição</th><th>Principal</th><th>Excesso</th><th>Capacidade</th><th></th></tr>
  <?php foreach ($produtos as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['sku']) ?></td>
    <td><?= htmlspecialchars($p['descricao']) ?></td>
    <td><?= $p['qtd_principal'] ?></td>
    <td><?= $p['qtd_excesso'] ?></td>
    <td><?= $p['capacidade'] ?></td>
    <td class="acoes">
      <a href="produtos.php?editar=<?= $p['id'] ?>&busca=<?= urlencode($busca) ?>" class="botao">Editar</a>
      <form method="post" onsubmit="return confirm('Apagar <?= htmlspecialchars($p['sku']) ?>?')">
        <input type="hidden" name="apagar" value="<?= $p['id'] ?>">
        <input type="submit" value="Apagar" class="vermelho">
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<p class="pequeno"><?= count($produtos) ?> produto(s)</p>
</body>
</html>
