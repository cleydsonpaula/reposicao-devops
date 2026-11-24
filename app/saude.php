<?php
// Verificação de saúde: responde "ok" sem depender do banco.
// Usada pelo teste do pipeline (Jenkins) e pela livenessProbe do Kubernetes.
header('Content-Type: text/plain; charset=utf-8');
echo "ok\n";
