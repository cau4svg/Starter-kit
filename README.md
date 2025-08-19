<img width="995" height="294" alt="capa-project" src="https://github.com/user-attachments/assets/b1c23b3f-4fb7-49e4-a156-92e1a7b0b961" />

# 🚀 Starter Kit - Integração APIBrasil

O **Starter Kit** é um projeto pronto para uso que permite integrar e revender os serviços da **APIBrasil** com **apenas um comando**.  
A proposta é oferecer uma API unificada e simples para consultas de dados, envio de mensagens e integrações diversas, de forma rápida e escalável.

## Descrição
Com este projeto, você terá:
- Login e autenticação via Bearer Token.
- Consultas de **CPF**, **CNPJ**, **placas**, **veículos**, **recalls** e mais.
- Envio de mensagens **WhatsApp** e **SMS**.
- Consulta e atualização de perfil.
- Registro de transações e adição de saldo.
- Sistema pronto para **revenda** de serviços da APIBrasil.

## Como usar

1. Faça login com `/api/login` para obter o token.
2. Use o token nas próximas requisições no header `Authorization: Bearer TOKEN`.
3. Chame os endpoints desejados.
4. Para encerrar a sessão, use `/api/logout`.

## Docker

Este projeto inclui arquivos de configuração do Docker para simplificar o desenvolvimento local.

```
docker-compose up -d
```

O comando acima iniciará a aplicação juntamente com os serviços de banco de dados MySQL e Redis.
As migrações do banco de dados serão executadas automaticamente na inicialização do contêiner.

## Licença

Este projeto segue a licença MIT.
Sinta-se livre para usar, modificar e distribuir.
