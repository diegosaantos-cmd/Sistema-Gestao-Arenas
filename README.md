# ArenaPlay - Sistema de Gestão de Arenas e Quadras Esportivas

<p align="center">
  <img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/pagina-inicial.png" width="800">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black">
  <img src="https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white">
</p>
> Também desenvolvi individualmente um projeto no mesmo domínio, evoluindo partes do trabalho aqui realizado: [ArenaPlay](https://github.com/diegosaantos-cmd/ArenaPlay). 


## Índice

- [Sobre o projeto](#sobre-o-projeto)
- [Status do projeto](#status-do-projeto)
- [Objetivo do projeto](#objetivo-do-projeto)
- [Problema identificado](#problema-identificado)
- [Solução proposta](#solução-proposta)
- [Usuários do sistema](#usuários-do-sistema)
- [Telas do Sistema](#telas-do-sistema)
- [Arquitetura do sistema](#arquitetura-do-sistema)
- [Tecnologias utilizadas](#tecnologias-utilizadas)
- [Ambiente de Desenvolvimento](#ambiente-de-desenvolvimento)
- [Ferramentas utilizadas](#ferramentas-utilizadas)
- [Funcionalidades](#funcionalidades)
- [Requisitos do sistema](#requisitos-do-sistema)
- [Protótipos](#protótipos)
- [Documentação do projeto](#documentação-do-projeto)
- [Desenvolvimento](#desenvolvimento)
- [Conclusão](#conclusão)

---

## Sobre o projeto

O **ArenaPlay** é um sistema Full Stack para gerenciamento de arenas e quadras esportivas, desenvolvido em equipe durante a disciplina de **Programação Web** do curso de **Sistemas de Informação da Universidade Federal do Pará (UFPA)**.

O projeto tem como objetivo solucionar problemas relacionados ao gerenciamento manual de arenas esportivas, como controle de agendamentos, organização de horários, dificuldade de acompanhamento remoto e ausência de uma plataforma centralizada para administração do negócio.

A aplicação busca oferecer uma solução web que permita automatizar processos, melhorar a experiência dos clientes e facilitar o gerenciamento das atividades da arena.

---

## Status do projeto

O ArenaPlay encontra-se em fase de desenvolvimento.

A versão atual contempla a estrutura principal do sistema, incluindo funcionalidades de gerenciamento, organização da aplicação utilizando arquitetura MVC, integração com banco de dados e implementação das principais regras de negócio.

Algumas funcionalidades ainda estão em processo de desenvolvimento ou utilizando simulações para validação do fluxo do sistema, como:

- Integração com sistemas de pagamento;
- Envio automático de e-mails;
- Algumas automações do sistema;
- Funcionalidades necessárias para disponibilização em ambiente de produção.

Novas implementações e melhorias serão realizadas conforme a evolução do projeto, visando tornar o sistema totalmente preparado para testes reais e utilização em ambiente produtivo.

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

## Usuários do sistema

### Administrador

Responsável pelo gerenciamento da arena, controle financeiro, usuários e tomada de decisões administrativas.

### Funcionário

Responsável pelo acompanhamento da agenda, pagamentos e gerenciamento das reservas.

### Cliente

Usuário que pode visualizar locais disponíveis, consultar horários e realizar agendamentos.

### Visitante

Usuário que acessa a plataforma para conhecer os serviços disponíveis.

---

## Telas do Sistema

Confira abaixo as principais telas do sistema, destacando as funcionalidades de gerenciamento de usuários, arenas, quadras e agendamentos, com interfaces desenvolvidas para diferentes perfis de acesso.

### Página Inicial
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/pagina-inicial.png" width="800">

### Tela de Login
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/tela-login.png" width="800">

### Tela de Criar Conta
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/tela-criar-conta.png" width="800">

### Painel do Administrador
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/painel-administrador.png" width="800">

### Painel do Proprietário
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/painel-proprietario.png" width="800">

### Painel do Gerente
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/painel-gerente.png" width="800">

### Painel do Atendente
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/painel-atendente.png" width="800">

### Painel do Cliente
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/painel-cliente.png" width="800">

### Tela de Arenas
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/tela-arenas.png" width="800">

### Tela de Quadras
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/tela-quadras.png" width="800">

### Tela de Agendamento
<img src="https://raw.githubusercontent.com/diegosaantos-cmd/Sistema-Gestao-Arenas/main/docs/imagens/tela-agendamento.png" width="800">

---

## Arquitetura do sistema

O sistema utiliza o padrão arquitetural **MVC (Model-View-Controller)**, organizando a aplicação em três principais camadas:

### Model

Responsável pela representação dos dados e comunicação com o banco de dados.

### View

Responsável pela interface visual apresentada ao usuário.

### Controller

Responsável pelo controle das requisições e aplicação das regras de negócio.

A utilização do MVC facilita a organização, manutenção e evolução do sistema.

---

## Tecnologias utilizadas

### Backend

- Laravel
- PHP

### Frontend

- HTML5
- CSS3
- JavaScript
- Bootstrap

### Banco de Dados

O banco de dados do sistema foi desenvolvido utilizando o **MySQL**, sendo administrado localmente através do phpMyAdmin integrado ao ambiente WampServer.

A modelagem do banco de dados foi realizada utilizando o **MySQL Workbench**, com desenvolvimento do modelo entidade-relacionamento (DER) para definição das entidades, atributos e relacionamentos do sistema.

- MySQL
- phpMyAdmin (gerenciamento e administração do banco)
- MySQL Workbench (modelagem do banco de dados)

---

## Ambiente de Desenvolvimento

- WampServer (ambiente local para execução do Apache, PHP e MySQL)

---

## Ferramentas utilizadas

- MySQL Workbench (Modelagem do banco de dados)
- Figma (Protótipos das interfaces)

---

## Funcionalidades

O sistema contempla funcionalidades para diferentes tipos de usuários:

### Área pública

- Visualização da página inicial;
- Consulta dos locais disponíveis;
- Visualização das informações das arenas e quadras.

### Área administrativa

- Cadastro e gerenciamento de arenas;
- Gerenciamento de espaços esportivos;
- Controle de usuários;
- Gerenciamento de agendamentos;
- Administração das informações do negócio.

### Área do cliente

- Cadastro e autenticação;
- Consulta de horários disponíveis;
- Realização de agendamentos;
- Visualização do histórico de reservas.

### Área do funcionário

- Gerenciamento da agenda;
- Atualização de reservas;
- Controle de pagamentos.

---

## Requisitos do sistema

O desenvolvimento do sistema envolveu o levantamento e análise de requisitos funcionais e não funcionais.

### Requisitos funcionais

Entre as principais funcionalidades estão:

- Cadastro de usuários;
- Autenticação no sistema;
- Gerenciamento de arenas;
- Gerenciamento de quadras;
- Agendamento de horários;
- Controle de informações da arena.

### Requisitos não funcionais

Foram considerados requisitos como:

- Usabilidade;
- Responsividade;
- Segurança;
- Desempenho;
- Integridade dos dados;
- Manutenibilidade utilizando arquitetura MVC.

---

## Protótipos

Os protótipos iniciais das interfaces foram desenvolvidos utilizando o **Figma**, servindo como referência para criação do design e estrutura visual da aplicação.

---

## Documentação do projeto

A documentação completa do sistema contém:

- Contexto do problema;
- Solução proposta;
- Usuários do sistema;
- Requisitos funcionais;
- Requisitos não funcionais;
- Modelagem do banco de dados;
- Protótipos das interfaces;
- Manual de instalação;
- Manual de utilização.

Para instruções detalhadas de instalação e configuração do ambiente, consulte a documentação completa do projeto.

Resumo dos passos:

```bash
git clone https://github.com/diegosaantos-cmd/Sistema-Gestao-Arenas.git
cd Sistema-Gestao-Arenas
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve

```
---
## Desenvolvimento

Projeto desenvolvido em equipe durante a disciplina de **Programação Web** do curso de **Sistemas de Informação da Universidade Federal do Pará (UFPA)**.

### Equipe

- [Diego dos Santos Lopes](https://github.com/diegosaantos-cmd)
- Eduardo da Silva Mugo
- Renato da Silva
- Tanilo Vulcão de Freitas

Projeto acadêmico desenvolvido para aplicação prática de conceitos de Desenvolvimento Web, Banco de Dados, Engenharia de Software e Programação Orientada a Objetos.

---

## Conclusão

O desenvolvimento do ArenaPlay permitiu aplicar na prática conhecimentos fundamentais da área de Sistemas de Informação, envolvendo análise de requisitos, modelagem de banco de dados, desenvolvimento Full Stack e utilização de padrões de arquitetura de software.

Por meio da construção do sistema, foi possível compreender a importância de um processo organizado de desenvolvimento, desde o levantamento das necessidades até a implementação das funcionalidades.

Além dos conhecimentos técnicos, o projeto proporcionou experiência com trabalho em equipe, divisão de atividades, organização do código e aplicação de conceitos de Engenharia de Software no desenvolvimento de uma solução real.
