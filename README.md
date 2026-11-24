# Projeto Integrador — Gerenciamento de salas e chaves

Aplicação web para administrar salas, chaves e empréstimos. O backend usa PHP e MySQL/MariaDB; o frontend usa HTML, CSS e JavaScript.

## Requisitos

- XAMPP (Apache, MySQL/MariaDB e PHP) ou ambiente equivalente.
- PHP com `PDO`, `pdo_mysql` e `session` habilitados.
- Navegador moderno.

O projeto precisa ser servido por um servidor PHP. Abrir arquivos diretamente (`file://`) ou usar Live Server/GitHub Pages não executa os controllers PHP.

## Instalação local com XAMPP

1. Copie ou clone o repositório para `C:\xampp\htdocs\Projeto-Integrador`.
2. No XAMPP Control Panel, inicie **Apache** e **MySQL**.
3. Em `http://localhost/phpmyadmin`, abra **Importar** e importe `gerenciamento_chaves_atualizado.sql`. O script cria o banco `gerenciamento_chaves`, as tabelas e os dados de demonstração.
4. Acesse `http://localhost/Projeto-Integrador/` ou `http://localhost/Projeto-Integrador/pages/login.html`.

Também é possível usar o servidor embutido do PHP com `pdo_mysql`: na raiz do projeto, execute `php -S 127.0.0.1:8000` e abra `http://127.0.0.1:8000/pages/login.html`.

### Acesso por outros computadores na rede local

Para várias pessoas usarem a mesma instalação e o mesmo banco, mantenha Apache, PHP e MySQL em um computador servidor:

1. Conecte o servidor e os computadores clientes à mesma rede local.
2. No servidor, descubra o endereço IPv4 com `ipconfig`.
3. Nos clientes, acesse `http://<IP-DO-SERVIDOR>/Projeto-Integrador/` (por exemplo, substituindo o marcador pelo IPv4 encontrado). `localhost` aponta para o próprio computador cliente, não para o servidor.
4. Se a conexão for bloqueada, permita conexões de entrada para Apache/HTTP na porta 80 no Firewall do Windows e use uma rede confiável.

Com Apache, PHP e MySQL no mesmo servidor, mantenha `DB_HOST=127.0.0.1`: é o PHP no servidor que conecta ao banco; os navegadores clientes não precisam acessar MySQL diretamente. Não exponha a porta 3306 à rede sem uma necessidade específica e configuração segura. Instalar o projeto separadamente em cada computador cria bancos independentes; para compartilhar os mesmos dados, use um único servidor central.

### Conexão com o banco

`config/config.php` aceita estas variáveis de ambiente:

| Variável | Padrão |
| --- | --- |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORTA` | `3306` |
| `DB_NOME` | `gerenciamento_chaves` |
| `DB_USER` | `root` |
| `DB_SENHA` | vazia |

Defina-as para o processo do PHP/Apache antes de iniciar o servidor se sua instalação usa outras credenciais. Não grave credenciais reais no repositório.

## Login, contas de teste e sessão

O login aceita CPF ou e-mail. Para novos usuários cadastrados pela aplicação, a senha inicial é formada pelos quatro primeiros dígitos do CPF (incluindo zeros à esquerda). A recuperação de senha redefine a senha usando os mesmos quatro dígitos. As senhas existentes não mudam automaticamente.

| Perfil | CPF | Senha |
| --- | --- | --- |
| Gerente de Teste | `11111111111` | `1111` |
| Porteiro Teste 1 | `22222222222` | `2222` |
| Porteiro Teste 2 | `33333333333` | `3333` |
| Porteiro Teste 3 | `44444444444` | `4444` |

Use as contas listadas para testar a regra dos quatro primeiros dígitos. Não use contas de demonstração em produção. As senhas são armazenadas como hashes e verificadas com `password_verify`.

O formulário de cadastro permite selecionar os cargos Gerente, Porteiro, Professor e Outro. Embora `Professor` exista no `ENUM` e possa ser cadastrado, ele não está mapeado na política de permissões e não pode entrar no sistema. `Outro` é reconhecido e recebe a união das permissões de gerente e porteiro.

O login regenera o ID da sessão; a opção **Lembrar de mim** mantém o cookie por sete dias. As páginas protegidas e os endpoints validam no servidor se a sessão ainda pertence a um usuário ativo e autorizado. Os controles de perfil no JavaScript ajustam a interface, mas não substituem a autorização do backend.

## Funcionalidades e perfis

- **GERENTE:** administra usuários e salas, cadastra/edita/desativa chaves, consulta o dashboard e ocorrências e acessa o histórico e relatórios.
- **PORTEIRO:** consulta salas e chaves, cadastra chaves, registra retiradas/devoluções, ocorrências e consulta o histórico.
- **OUTRO:** perfil reconhecido com a união das permissões de gerente e porteiro. Não aparece na busca de pessoas para retirada nem na tela administrativa de usuários.

### Histórico e retirada para pessoa externa

A tela `pages/historico.php` permite filtrar movimentações por sala, pessoa, período e status. O relatório pode ser impresso ou salvo como PDF. A Dashboard calcula atrasos com base na data prevista e na devolução registrada.

Na retirada, o porteiro pode selecionar um usuário cadastrado ou informar uma pessoa externa usando a opção **Outro**. Pessoas externas não recebem conta; nome e cargo são armazenados como snapshots da movimentação.

## Banco de dados e migrações

O schema contém `salas`, `usuarios`, `chaves`, `movimentacoes` e `observacoes`. Uma chave pertence a uma sala; movimentações guardam a chave, o usuário que a retirou, o porteiro responsável e as datas.

O SQL usa `CREATE TABLE IF NOT EXISTS` e `ON DUPLICATE KEY UPDATE` para permitir reaplicar os dados de demonstração. **Atenção:** ao reimportar, registros de exemplo com IDs ou chaves únicas conflitantes podem ser atualizados para os valores do arquivo. Faça backup e não reimporte em um banco com dados que deseja preservar.

Para um banco existente, faça backup e aplique apenas as migrações cujas alterações ainda não estejam presentes:

- `migrations/20261118_add_nome_chave.sql`: adiciona o nome descritivo da chave.
- `migrations/20261120_add_external_borrower.sql`: permite retirada por pessoa externa e preserva nome/cargo nas movimentações. Esta migração altera a tabela; não a execute novamente após aplicá-la.
- `migrations/20261118_issue1_salas_constraints.sql`: exige número e bloco e cria a restrição de unicidade da combinação.

Uma instalação nova deve usar o SQL principal, que já contém as colunas atuais.

## Estrutura

- `config/`: conexão ao banco e autenticação.
- `controllers/`: endpoints de login, sessão e operações do sistema.
- `pages/`: telas.
- `js/` e `css/`: interface e estilos.
- `assets/`: ícones e imagens.
- `gerenciamento_chaves_atualizado.sql`: schema e dados de demonstração.
- `migrations/`: alterações para bancos já existentes.

## Solução de problemas

- **Erro de banco:** confirme que MySQL está iniciado, o banco foi importado, as credenciais estão corretas e `pdo_mysql` está ativo.
- **PHP não executa:** acesse pela URL do Apache/PHP, não abra o arquivo diretamente.
- **Login negado:** confira CPF/e-mail, senha, status ativo da conta e se o perfil é `GERENTE`, `PORTEIRO` ou `OUTRO`.
- **Erro em uma tela ou operação:** verifique os logs do Apache/PHP e as abas **Console** e **Rede** do navegador.
