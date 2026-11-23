# Reposição Simples

Sistema local para dar baixa nas vendas (CSV do Bling) e repor o endereço principal a partir do excesso.

## Subir

    docker compose up -d

Site: http://localhost:8081 — usuário `admin`, senha `admin`.

## Pastas

- `app/` — as telas (login, produtos, reposição, excesso, histórico)
- `app/inc/` — conexão, sessão, menu e leitura de CSV
- `app/css/` — estilo
- `db/init.sql` — tabelas (roda na primeira subida)

## Tabelas

- `usuarios` — login, senha, ativo
- `produtos` — SKU e descrição
- `enderecos` — Principal e Excesso
- `estoque` — quantidade e capacidade de cada produto em cada endereço (produtos, enderecos)
- `tipos_movimento` — venda, reposição, entrada no excesso, acerto
- `importacoes` — cada CSV de vendas subido (usuarios)
- `pedidos_baixados` — pedido e produto já baixados (importacoes, produtos)
- `movimentos` — cada mudança de quantidade (produtos, enderecos, tipos_movimento, usuarios)

Para recriar o banco do zero: `docker compose down -v && docker compose up -d`.

## Documentação

- `DOCUMENTACAO.md` — como o sistema funciona, tela por tela e tabela por tabela.
- `GUIA_EQUIPE.md` — o que cada membro fez e como foi feito.
- `TUTORIAL_GIT.md` — git do zero: criar ramo, commit, pull request, explicado passo a passo.
