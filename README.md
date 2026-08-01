# ArenaPlay - Sistema de Gestão de Arenas e Quadras Esportivas

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

## Banco de Dados

O banco de dados do sistema foi desenvolvido utilizando o MySQL, sendo administrado localmente através do phpMyAdmin integrado ao ambiente WampServer.

A modelagem do banco de dados foi realizada utilizando o MySQL Workbench, com desenvolvimento do modelo entidade-relacionamento (DER) para definição das entidades, atributos e relacionamentos do sistema.

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

#  Funcionalidades

O sistema contempla funcionalidades para diferentes tipos de usuários:

## Área pública

- Visualização da página inicial;
- Consulta dos locais disponíveis;
- Visualização das informações das arenas e quadras.

## Área administrativa

- Cadastro e gerenciamento de arenas;
- Gerenciamento de espaços esportivos;
- Controle de usuários;
- Gerenciamento de agendamentos;
- Administração das informações do negócio.

## Área do cliente

- Cadastro e autenticação;
- Consulta de horários disponíveis;
- Realização de agendamentos;
- Visualização do histórico de reservas.

## Área do funcionário

- Gerenciamento da agenda;
- Atualização de reservas;
- Controle de pagamentos.

---

#  Requisitos do sistema

O desenvolvimento do sistema envolveu o levantamento e análise de requisitos funcionais e não funcionais.

## Requisitos funcionais

Entre as principais funcionalidades estão:

- Cadastro de usuários;
- Autenticação no sistema;
- Gerenciamento de arenas;
- Gerenciamento de quadras;
- Agendamento de horários;
- Controle de informações da arena.

## Requisitos não funcionais

Foram considerados requisitos como:

- Usabilidade;
- Responsividade;
- Segurança;
- Desempenho;
- Integridade dos dados;
- Manutenibilidade utilizando arquitetura MVC.

---

# Banco de dados

A modelagem do banco de dados foi realizada utilizando o **MySQL Workbench**, com desenvolvimento do modelo entidade-relacionamento (DER) para definição das entidades, atributos e relacionamentos do sistema.

---

# Protótipos

Os protótipos iniciais das interfaces foram desenvolvidos utilizando o **Figma**, servindo como referência para criação do design e estrutura visual da aplicação.

---

#  Documentação do projeto

A documentação completa do sistema contém:

- Contexto do problema;
- Solução proposta;
- Usuários do sistema;
- Requisitos funcionais;
- Requisitos não funcionais;
- Modelagem do banco de dados;
- Protótipos das interfaces.
- Manual de instalação.
- Manual de ultilização
  
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

Acesse a documentação completa:

[Documentação completa do ArenaPlay](docs/Documentacao-ArenaPlay.pdf)

---

# Desenvolvimento

Projeto desenvolvido em equipe durante a disciplina de **Programação Web** do curso de **Sistemas de Informação da Universidade Federal do Pará (UFPA)**.

## Equipe

- Diego dos Santos Lopes
- Eduardo da Silva Mugo
- Renato da Silva
- Tanilo Vulcão de Freitas

---

Projeto acadêmico desenvolvido para aplicação prática de conceitos de Desenvolvimento Web, Banco de Dados, Engenharia de Software e Programação Orientada a Objetos.

---

# Conclusão

O desenvolvimento do ArenaPlay permitiu aplicar na prática conhecimentos fundamentais da área de Sistemas de Informação, envolvendo análise de requisitos, modelagem de banco de dados, desenvolvimento Full Stack e utilização de padrões de arquitetura de software.

Por meio da construção do sistema, foi possível compreender a importância de um processo organizado de desenvolvimento, desde o levantamento das necessidades até a implementação das funcionalidades.

Além dos conhecimentos técnicos, o projeto proporcionou experiência com trabalho em equipe, divisão de atividades, organização do código e aplicação de conceitos de Engenharia de Software no desenvolvimento de uma solução real.
