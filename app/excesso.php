<?php
require 'inc/auth.php';
$msg = '';
$erro = '';
$excesso = enderecoId($pdo, 'excesso');

if (isset($_POST['entrada'])) {
    $sku = trim($_POST['sku']);
    $qtd = (int)$_POST['qtd'];
    $q = $pdo->prepare("SELECT p.id, COALESCE(e.quantidade, 0) AS quantidade FROM produtos p LEFT JOIN estoque e ON e.produto_id = p.id AND e.endereco_id = $excesso WHERE p.sku = ?");
    $q->execute([$sku]);
    $p = $q->fetch();
    if (!$p) {
        $erro = "SKU $sku não está cadastrado";
    } elseif ($qtd == 0) {
        $erro = 'Informe a quantidade';
    } elseif ($qtd < 0 && -$qtd > $p['quantidade']) {
        $erro = 'O excesso não tem essa quantidade para tirar';
    } else {
        $pdo->prepare("INSERT INTO estoque (produto_id, endereco_id, quantidade) VALUES (?, $excesso, ?) ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)")->execute([$p['id'], $qtd]);
        registraMovimento($pdo, $p['id'], $excesso, $qtd > 0 ? 'entrada_excesso' : 'acerto', $qtd);
        $msg = "$qtd peça(s) de $sku no excesso";
    }
}

$produtos = listaProdutos($pdo);
usort($produtos, fn($a, $b) => strcmp($a['sku'], $b['sku']));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Excesso</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<?php include 'inc/menu.php'; ?>
<h1>Excesso</h1>
<?php if ($msg) echo "<div class='ok'>$msg <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>
<?php if ($erro) echo "<div class='erro'>$erro <a href='#' class='fechar' onclick='this.parentNode.remove();return false'>x</a></div>"; ?>

<div class="caixa">
  <h2>Abastecer o excesso</h2>
  <form method="post">
    SKU <input type="text" name="sku" list="skus" required>
    <datalist id="skus">
      <?php foreach ($produtos as $p): ?><option value="<?= htmlspecialchars($p['sku']) ?>"><?php endforeach; ?>
    </datalist>
    Quantidade <input type="number" name="qtd" value="1">
    <input type="submit" name="entrada" value="Dar entrada">
  </form>
  <p class="pequeno">Quantidade negativa tira do excesso (acerto).</p>
</div>

<table>
  <tr><th>SKU</th><th>Descrição</th><th>Excesso</th><th>Principal</th></tr>
  <?php foreach ($produtos as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['sku']) ?></td>
    <td><?= htmlspecialchars($p['descricao']) ?></td>
    <td><?= $p['qtd_excesso'] ?></td>
    <td><?= $p['qtd_principal'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
