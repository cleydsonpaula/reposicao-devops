<?php
function lerCsv($arquivo) {
    $linhas = [];
    $f = fopen($arquivo, 'r');
    $cabecalho = fgetcsv($f, 0, ';');
    while (($l = fgetcsv($f, 0, ';')) !== false) {
        if (count($l) < 2) continue;
        $linhas[] = $l;
    }
    fclose($f);
    return [$cabecalho, $linhas];
}

function numero($texto) {
    return (int) round((float) str_replace(',', '.', trim($texto)));
}
