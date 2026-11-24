# Reposição Simples — esteira DevOps

Trabalho de Ciência da Computação: esteira de integração e entrega contínuas (CI/CD) para uma aplicação
web PHP + MySQL, com **quatro ambientes por branch**, **Docker**, **Kubernetes** (kind), **Jenkins**
(Multibranch Pipeline) e **logs centralizados** (Elasticsearch, Fluent Bit e Kibana).

📄 **Relatório completo, com prints de cada etapa:** [docs/relatorio.md](docs/relatorio.md)

## Fluxo

```
dev  →  homologacao  →  pre-producao  →  main (produção, com aprovação manual)
          (cada promoção por pull request; o Jenkins publica sozinho no ambiente da branch)
```

| Branch | Namespace | Endereço local |
|---|---|---|
| `dev` | dev | http://localhost:8101 |
| `homologacao` | homologacao | http://localhost:8102 |
| `pre-producao` | pre-producao | http://localhost:8103 |
| `main` | producao | http://localhost:8104 |

## Estrutura

| Caminho | Conteúdo |
|---|---|
| `app/` | A aplicação (PHP): baixa de vendas e reposição do estoque; `saude.php` = verificação de saúde |
| `db/init.sql` | Tabelas do banco (criadas na primeira subida) |
| `Dockerfile` | Imagem da aplicação (PHP 8.3 + Apache) |
| `docker-compose.yml` | A aplicação sozinha, com banco, para desenvolvimento (porta 8090) |
| `k8s/` | Cluster kind, manifestos Kubernetes (base + overlays por ambiente) e `deploy.sh` |
| `k8s/logs/` | Pilha de logs: Elasticsearch, Fluent Bit e Kibana |
| `jenkins/` | Imagem do Jenkins com Docker, kubectl e kind |
| `Jenkinsfile` | O pipeline: build → testes → carregar no cluster → aprovação (só `main`) → publicar |
| `docs/` | Relatório e prints |

Como montar tudo do zero: seção [Como reproduzir](docs/relatorio.md#como-reproduzir) do relatório.

Aplicação sozinha: `docker compose up -d --build` → http://localhost:8090 (usuário `admin`, senha `admin`).
