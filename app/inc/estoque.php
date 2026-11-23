<?php
function enderecoId($pdo, $tipo) {
    return (int) $pdo->query("SELECT id FROM enderecos WHERE tipo = '$tipo' LIMIT 1")->fetchColumn();
}

function tipoMovimentoId($pdo, $codigo) {
    $q = $pdo->prepare("SELECT id FROM tipos_movimento WHERE codigo = ?");
    $q->execute([$codigo]);
    return (int) $q->fetchColumn();
}

function registraMovimento($pdo, $produtoId, $enderecoId, $tipo, $qtd) {
    $pdo->prepare("INSERT INTO movimentos (produto_id, endereco_id, tipo_id, usuario_id, quantidade) VALUES (?,?,?,?,?)")
        ->execute([$produtoId, $enderecoId, tipoMovimentoId($pdo, $tipo), $_SESSION['usuario_id'], $qtd]);
}

function salvaEstoque($pdo, $produtoId, $enderecoId, $quantidade, $capacidade) {
    $pdo->prepare("INSERT INTO estoque (produto_id, endereco_id, quantidade, capacidade) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade), capacidade = VALUES(capacidade)")
        ->execute([$produtoId, $enderecoId, $quantidade, $capacidade]);
}

function listaProdutos($pdo, $busca = '', $soFalta = false) {
    $principal = enderecoId($pdo, 'principal');
    $excesso = enderecoId($pdo, 'excesso');
    $having = $soFalta ? 'HAVING falta > 0' : '';
    $q = $pdo->prepare("
        SELECT p.id, p.sku, p.descricao,
               COALESCE(ep.quantidade, 0) AS qtd_principal,
               COALESCE(ep.capacidade, 0) AS capacidade,
               COALESCE(ex.quantidade, 0) AS qtd_excesso,
               COALESCE(ep.capacidade, 0) - COALESCE(ep.quantidade, 0) AS falta
        FROM produtos p
        LEFT JOIN estoque ep ON ep.produto_id = p.id AND ep.endereco_id = $principal
        LEFT JOIN estoque ex ON ex.produto_id = p.id AND ex.endereco_id = $excesso
        WHERE p.sku LIKE ? OR p.descricao LIKE ?
        $having
        ORDER BY falta DESC, p.sku");
    $q->execute(["%$busca%", "%$busca%"]);
    return $q->fetchAll();
}
