# Sistema de Achados e Perdidos
Sistema web desenvolvido para gerenciar objetos encontrados e perdidos, permitindo o cadastro, pesquisa, atualização, exclusão e registro de devoluções dos itens.

## 📋 Objetivo
O projeto tem como objetivo facilitar o gerenciamento de objetos encontrados em uma instituição ou empresa, centralizando todas as informações em um único sistema.

---

# 🚀 Funcionalidades

## Autenticação
- Login de usuários
- Acesso restrito apenas para usuários autenticados

## Gerenciamento de Objetos
- Cadastro de objetos encontrados
- Upload de imagem do objeto
- Edição das informações
- Exclusão de registros
- Pesquisa com filtros
- Registro da devolução ao proprietário

---

# 📌 Requisitos Funcionais

### RF01 – Login
- Realizar autenticação de usuários.
- Permitir acesso apenas após login.

### RF02 – Cadastro de Objetos
Campos cadastrados:
- Código (gerado automaticamente)
- Nome do objeto
- Descrição
- Categoria
- Local encontrado
- Data em que foi encontrado
- Foto
- Situação

### RF03 – Upload de Foto
Permitir:
- Selecionar imagem
- Salvar imagem no servidor
- Exibir imagem posteriormente

Formatos aceitos:
- JPG
- JPEG
- PNG

### RF04 – Categorias
Categorias disponíveis:
- Mochila
- Celular
- Notebook
- Chave
- Carteira
- Documento
- Garrafa
- Outros

### RF05 – Pesquisa
Pesquisar objetos por:
- Categoria
- Nome
- Situação
- Local

### RF06 – Alteração
Permitir editar:
- Descrição
- Categoria
- Situação
- Foto
- Local

### RF07 – Exclusão
Excluir objetos cadastrados incorretamente.

### RF08 – Registro de Devolução
Ao devolver um objeto registrar:
- Nome do proprietário
- Documento
- Telefone
- Data da devolução
- Observações

### RF09 – Consulta de Objetos
Exibir uma tabela contendo:
- Foto
- Nome
- Categoria
- Local
- Data
- Situação

Ações disponíveis:
- Editar
- Excluir
- Devolver

---

# 📖 Casos de Uso
### UC01 - Login
```
Usuário
   │
Login
   │
Tela Principal
```

### UC02 - Cadastrar Objeto
```
Cadastrar objeto
        │
Salvar no banco
        │
Mensagem de sucesso
```

### UC03 - Pesquisar Objeto
```
Pesquisar
    │
Exibir resultados
```

### UC04 - Editar Objeto
```
Editar informações
        │
Salvar alterações
```

### UC05 - Registrar Devolução
```
Registrar devolução
         │
Atualizar situação
         │
Salvar dados do proprietário
```

### UC06 - Excluir Objeto
```
Excluir objeto
      │
Remover do banco
      │
Excluir imagem
```

---

# 🖥️ Telas do Sistema
## 1. Login

Tela de autenticação dos usuários.

## 2. Dashboard
Indicadores:

- Quantidade de objetos encontrados
- Quantidade de objetos devolvidos
- Quantidade de objetos pendentes

## 3. Lista de Objetos
Tabela contendo:

- Foto
- Nome
- Categoria
- Local
- Data
- Situação

Ações:

- Editar
- Excluir
- Devolver

## 4. Cadastro de Objetos
Formulário para inserir novos objetos encontrados.

## 5. Edição de Objetos
Mesmo formulário do cadastro, preenchido com os dados existentes.

## 6. Pesquisa
Filtros por:

- Categoria
- Nome
- Situação
- Local

## 7. Registro de Devolução
Cadastro dos dados do proprietário para finalizar a entrega do objeto.

---

# 🛠️ Tecnologias
Este projeto pode ser desenvolvido utilizando:

- HTML5
- CSS3
- Bootstrap
- JavaScript
- PHP
- MySQL

---

# 📂 Estrutura Esperada
```
/
├── assets/
│   ├── css/
│   ├── js/
│   └── imagens/
├── uploads/
├── views/
├── controllers/
├── models/
├── config/
├── database/
└── README.md
```

---

# 📈 Fluxo do Sistema
```
Login
   │
Dashboard
   │
├── Cadastrar objeto
├── Listar objetos
├── Pesquisar
├── Editar
├── Excluir
└── Registrar devolução
```

---
# 👥 Equipe
Projeto desenvolvido durante a disciplina de Engenharia de Software como atividade da Sprint 1.
---

# 📄 Licença

Este projeto é destinado exclusivamente para fins acadêmicos.
