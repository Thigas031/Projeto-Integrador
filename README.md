# Projeto Integrador — Gerenciamento de salas e chaves

## Login e sessão

O acesso ao sistema exige uma sessão PHP válida. A tela `pages/login.html` envia CPF de 11 dígitos e senha por `POST` para `controllers/controller_user_login.php`; o controller consulta `usuarios`, valida o hash com `password_verify`, regenera o ID da sessão e grava nome e cargo. A opção "Lembrar de mim" mantém o cookie por sete dias. Uma solicitação `GET` ao mesmo controller devolve os dados do usuário ativo da sessão ou HTTP 401. O endpoint `controller_sessao.php` devolve nome, cargo e mensagens flash de uso único. O menu encerra a sessão por `controller_user_logout.php`.

As páginas administrativas e operacionais usam PHP e validam sessão, usuário ativo e cargo no servidor antes de entregar o HTML. O frontend confirma a sessão para obter nome e cargo e configurar o menu; os controles no navegador complementam, mas não substituem, autorização em cada endpoint de dados.

### Executar localmente

1. Instale PHP com `pdo_mysql` e inicie MariaDB/MySQL.
2. Configure o banco `gerenciamento_chaves` e as credenciais em `config/config.php`. O dump `gerenciamento_chaves.sql` é antigo e difere do schema real: os controllers usam `chaves.status_chaves`, `usuarios.ativo` e `movimentacoes.data_prevista`. Use as definições reais confirmadas via `SHOW CREATE TABLE`; não importe o dump sem alinhá-lo.
3. Na raiz do projeto, inicie o servidor PHP embutido:

   ```sh
   php -S 127.0.0.1:8000
   ```

4. Abra `http://127.0.0.1:8000/pages/login.html`.

O dump SQL não contém usuários cadastrados e, portanto, não há credencial de teste disponível nele. Nenhum usuário foi criado durante esta alteração. Para testar, o responsável pelo banco precisa provisionar uma conta ativa `Gerente` ou `Porteiro`, com CPF de 11 dígitos, e-mail válido e senha armazenada com `password_hash`. Não grave a senha em texto puro no SQL. A integração depende do banco local estar acessível; executar as páginas via `file://` ou servidor estático não executa PHP e não autentica.

O `index.html` da raiz redireciona por caminho relativo a `pages/login.html`, preservando prefixos de publicação como `/nome-do-repositorio/`. Live Server e GitHub Pages são servidores estáticos e não executam PHP; a página de Login poderá ser exibida, mas a autenticação depende do backend em um servidor PHP. Para usar o sistema completo, inicie o PHP e o banco conforme as instruções acima.

## Perfil do usuário e menu

`js/app-profile.js` obtém a sessão do controller PHP em cada carregamento, sem aceitar cargo de `localStorage`, `sessionStorage` ou configuração global. Páginas protegidas aguardam essa validação e redirecionam sessões ausentes ou incompatíveis. A identificação inferior do menu reutiliza o ícone de casa já usado nas opções de navegação e exibe nome e cargo recebidos da sessão quando expandida.

O valor armazenado no ENUM `usuarios.cargo` como `Gerente` representa GERENTE (Gerente Geral); a sessão e a interface usam `GERENTE`. GERENTE administra usuários, salas e chaves, registra movimentações e consulta histórico, observações e dashboard. PORTEIRO consulta chaves/salas, registra retirada/devolução e observações. As páginas `.php` e os endpoints validam os cargos no servidor.

## Serviços e integração de dados

`js/app-services.js` centraliza as chamadas HTTP e aponta para os controllers PHP. As respostas seguem `{ "ok": true, "data": ... }`; erros usam status HTTP apropriado e `{ "ok": false, "message": "..." }`. As listas de chaves recebem `busca`, `status` e `pagina` e retornam cinco itens, `total` e `pagina`. As escritas são enviadas como formulário codificado para preencher `$_POST`.

Os campos consumidos pela interface são:

- Usuário: `id`, `name`, `cpf`, `role`, `phone`, `email`.
- Chave: `id`, `salaId`, `name`, `number`, `block`, `status`, `responsible`.
- Movimentação: `id`, `keyId`, `keyName`, `keyNumber`, `userId`, `userName`, `withdrawnAt`, `returnedAt`, `notes`.
- Ocorrência: `id`, `text`, `createdAt`, `createdBy`.

O backend aplica autorização de sessão em cada operação, unicidade, transições de estado e preservação do histórico. A tabela `chaves` usa `id_sala`, `numero_chave`, `status_chaves` e `ativo`; o nome e bloco vêm de `salas`. `movimentacoes.id_porteiro` recebe o usuário autenticado que registra a retirada, inclusive GERENTE. A data/hora prevista de devolução é informada no formulário de retirada e armazenada em `movimentacoes.data_prevista`; o horário efetivo da retirada é gerado pelo servidor. O status de movimentação é derivado de `data_devolucao`, sem coluna ou trigger.

Os controllers de salas e relatórios estão disponíveis no backend; o projeto ainda não inclui telas próprias de administração de salas ou relatórios.

## Cadastro de chaves e salas

Uma chave precisa estar associada a uma sala já cadastrada. No cadastro, use uma das sugestões com nome, número e bloco para que o formulário identifique o ID da sala; digitar apenas o nome funciona somente quando ele corresponde exatamente a uma sala única. O número da chave também precisa ser único. Não é necessário criar uma nova sala para cadastrar outra chave em uma sala existente.

O backend já oferece operações de cadastro, edição, listagem e exclusão de salas, mas ainda não existe uma tela de administração de salas. A especificação recomendada para melhorar a seleção da sala no cadastro de chaves e, opcionalmente, criar essa tela está em [#1](https://github.com/Thigas031/Projeto-Integrador/issues/1).

O Login só aceita os cargos GERENTE e PORTEIRO. A senha inicial pelos quatro primeiros dígitos do CPF, assim como uma possível troca obrigatória no primeiro acesso, deve ser implementada no backend; o navegador não gera nem armazena essa senha.
