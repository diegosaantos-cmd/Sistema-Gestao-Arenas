# ArenaPlay - Sistema de Gestão de Arenas e Quadras Esportivas

## Sobre o projeto

O **ArenaPlay** é um sistema Full Stack para gerenciamento de arenas e quadras esportivas, desenvolvido em equipe durante a disciplina de **Programação Web** do curso de **Sistemas de Informação da Universidade Federal do Pará (UFPA)**.

O projeto tem como objetivo solucionar problemas relacionados ao gerenciamento manual de arenas esportivas, como controle de agendamentos, organização de horários, dificuldade de acompanhamento remoto e ausência de uma plataforma centralizada para administração do negócio.

A aplicação busca oferecer uma solução web que permita automatizar processos, melhorar a experiência dos clientes e facilitar o gerenciamento das atividades da arena.

---

## Objetivo do projeto

O desenvolvimento do ArenaPlay teve como objetivo aplicar na prática conhecimentos adquiridos durante a graduação, integrando conceitos de:

- Desenvolvimento Web;
- Banco de Dados;
- Engenharia de Software;
- Programação Orientada a Objetos (POO);
- Arquitetura de software utilizando MVC.

Durante o desenvolvimento foram realizados levantamentos de requisitos, modelagem do banco de dados, criação de protótipos de interface e implementação das funcionalidades do sistema.

---

## Problema identificado

Muitas arenas e quadras esportivas realizam seu gerenciamento de forma manual, utilizando ferramentas inadequadas para controle de reservas e organização das informações.

Entre os principais problemas identificados estão:

- Falta de automação dos processos;
- Conflitos de horários nos agendamentos;
- Dificuldade de acompanhamento das atividades;
- Dependência de comunicação manual;
- Ausência de controle centralizado das informações.

---

## Solução proposta

O ArenaPlay propõe uma plataforma web capaz de centralizar a gestão das arenas esportivas, permitindo:

- Visualização dos espaços disponíveis;
- Gerenciamento de quadras;
- Organização de agendamentos;
- Controle de usuários;
- Administração das informações do negócio.

A solução busca reduzir erros, melhorar a organização e proporcionar uma experiência mais eficiente para administradores, funcionários e clientes.

---

# Usuários do sistema

## Administrador
Responsável pelo gerenciamento da arena, controle financeiro, usuários e tomada de decisões administrativas.

## Funcionário
Responsável pelo acompanhamento da agenda, pagamentos e gerenciamento das reservas.

## Cliente
Usuário que pode visualizar locais disponíveis, consultar horários e realizar agendamentos.

## Visitante
Usuário que acessa a plataforma para conhecer os serviços disponíveis.

---

# Arquitetura do sistema

O sistema utiliza o padrão arquitetural **MVC (Model-View-Controller)**, organizando a aplicação em três principais camadas:

### Model
Responsável pela representação dos dados e comunicação com o banco de dados.

### View
Responsável pela interface visual apresentada ao usuário.

### Controller
Responsável pelo controle das requisições e aplicação das regras de negócio.

A utilização do MVC facilita a organização, manutenção e evolução do sistema.

---

# Tecnologias utilizadas

## Backend
- Laravel
- PHP

## Frontend
- HTML5
- CSS3
- JavaScript
- Bootstrap

## Banco de dados
- MySQL

## Ferramentas utilizadas
- MySQL Workbench (Modelagem do banco de dados)
- Figma (Prototipação das interfaces)

---

# Funcionalidades

## Área pública
- Exibição da página inicial;
- Listagem de locais disponíveis;
- Visualização de informações das arenas;
- Visualização das quadras e horários disponíveis.

## Área administrativa
- Cadastro de locais;
- Cadastro de espaços esportivos;
- Gerenciamento de funcionários;
- Controle de agendamentos;
- Controle financeiro;
- Gerenciamento de usuários.

## Área do cliente
- Cadastro e login;
- Consulta de horários;
- Solicitação de reservas;
- Histórico de agendamentos.

## Área do funcionário
- Visualização da agenda;
- Atualização do status das reservas;
- Controle de pagamentos.

---

# Requisitos do sistema

O sistema foi desenvolvido considerando requisitos funcionais e não funcionais.

## Requisitos funcionais

Entre as principais funcionalidades:

- Cadastro de usuários;
- Login e autenticação;
- Gerenciamento de arenas;
- Gerenciamento de quadras;
- Agendamento de horários;
- Controle de pagamentos;
- Histórico de reservas.

## Requisitos não funcionais

O sistema considera:

- Usabilidade;
- Responsividade;
- Segurança;
- Desempenho;
- Disponibilidade;
- Integridade dos dados;
- Manutenibilidade utilizando arquitetura MVC.

---

#  Banco de dados

A modelagem do banco de dados foi desenvolvida utilizando o **MySQL Workbench**, com criação do modelo entidade-relacionamento (DER) para estruturar as entidades e seus relacionamentos.

---

#  Protótipos

Os protótipos iniciais das interfaces foram desenvolvidos utilizando o **Figma**, servindo como base para criação do design da aplicação.

---

#  Documentação

A documentação completa do projeto contém:

- Contexto do problema;
- Solução proposta;
- Análise de usuários e personas;
- Requisitos funcionais;
- Requisitos não funcionais;
- Modelagem do banco de dados;
- Protótipos das telas.

A documentação está disponível na pasta:

```
/docs
```

---

# Desenvolvimento

Projeto desenvolvido em equipe durante a disciplina de **Programação Web** do curso de **Sistemas de Informação - UFPA**.

## Equipe

- Diego dos Santos Lopes
- Eduardo da Silva Mugo
- Renato da Silva
- Tanilo Vulcão de Freitas

---

Projeto acadêmico desenvolvido para aplicação prática de conceitos de Engenharia de Software e Desenvolvimento Web.
