# Projeto Integrador — Gerenciamento de salas e chaves

## Login e sessão

O acesso ao sistema exige uma sessão PHP válida. A tela `pages/login.html` envia CPF ou e-mail e senha por `POST` para `controllers/controller_user_login.php`; o controller consulta `usuarios`, valida o hash com `password_verify`, regenera o ID da sessão e retorna o nome e o cargo. Uma solicitação `GET` ao mesmo controller devolve os dados do usuário da sessão ou HTTP 401. Ao abrir o Login com uma sessão ativa, a tela continua disponível para autenticar outra conta ou encerrar a sessão. O botão "Sair" no menu e o botão na tela de Login encerram a sessão no servidor.

As páginas administrativas e operacionais agora usam PHP e validam sessão, usuário ativo e cargo no servidor antes de entregar o HTML. O frontend também confirma a sessão para obter nome e cargo e configurar o menu; os controles no navegador complementam, mas não substituem, autorização em cada endpoint de dados.

### Executar localmente

1. Instale PHP com `pdo_mysql` e inicie MariaDB/MySQL.
2. Importe `gerenciamento_chaves.sql` e ajuste as credenciais de conexão em `config/config.php`.
3. Na raiz do projeto, inicie o servidor PHP embutido:

   ```sh
   php -S 127.0.0.1:8000
   ```

4. Abra `http://127.0.0.1:8000/pages/login.html`.

O dump SQL não contém usuários cadastrados e, portanto, não há credencial de teste disponível nele. Nenhum usuário foi criado durante esta alteração. Para testar, o responsável pelo banco precisa provisionar uma conta ativa `Gerente` ou `Porteiro`, com CPF de 11 dígitos, e-mail válido e senha armazenada com `password_hash`. Não grave a senha em texto puro no SQL. A integração depende do banco local estar acessível; executar as páginas via `file://` ou servidor estático não executa PHP e não autentica.

O `index.html` da raiz redireciona por caminho relativo a `pages/login.html`, preservando prefixos de publicação como `/nome-do-repositorio/`. Live Server e GitHub Pages são servidores estáticos e não executam PHP; a página de Login poderá ser exibida, mas a autenticação depende do backend em um servidor PHP. Para usar o sistema completo, inicie o PHP e o banco conforme as instruções acima.

## Perfil do usuário e menu

`js/app-profile.js` obtém a sessão do controller PHP em cada carregamento, sem aceitar cargo de `localStorage`, `sessionStorage` ou configuração global. Páginas protegidas aguardam essa validação e redirecionam sessões ausentes ou incompatíveis. A identificação inferior do menu reutiliza o ícone de casa já usado nas opções de navegação e exibe nome e cargo recebidos da sessão quando expandida.

As permissões de interface permanecem: GERENTE administra usuários e chaves, movimenta pela lista e consulta ocorrências; PORTEIRO cadastra/consulta chaves, usa retirada/devolução e registra/consulta ocorrências. As páginas `.php` também verificam os cargos permitidos no servidor. Os endpoints de dados ainda precisam aplicar autorização no próprio backend.

## Serviços e integração de dados

`js/app-services.js` centraliza as chamadas HTTP. Não há mais modo demonstrativo, dados locais, perfis simulados ou fallback sem backend. Os endpoints de listagem e CRUD abaixo ainda não existem no projeto; até serem configurados e implementados, as operações de dados exibem erros em vez de aparentar sucesso.

O frontend espera configurar `window.APP_CONFIG.endpoints` antes de `app-services.js`:

```js
window.APP_CONFIG = {
    endpoints: {
        listUsers: "/api/users",
        listMovementUsers: "/api/users/for-key-movement",
        createUser: "/api/users",
        updateUser: "/api/users/{id}",
        deleteUser: "/api/users/{id}",
        listKeys: "/api/keys",
        listReturnableKeys: "/api/keys/returnable",
        createKey: "/api/keys",
        getKey: "/api/keys/{id}",
        updateKey: "/api/keys/{id}",
        deleteKey: "/api/keys/{id}",
        withdrawKey: "/api/keys/withdraw",
        returnKey: "/api/keys/{id}/return",
        listMovements: "/api/movements",
        listOccurrences: "/api/occurrences",
        createOccurrence: "/api/occurrences"
    }
};
```

As respostas devem ser JSON `{ "ok": true, "data": ... }`; erros devem usar status HTTP apropriado e `{ "ok": false, "message": "..." }`. Listagens retornam `data` como array. Os campos consumidos pela interface são:

- Usuário: `id`, `name`, `cpf`, `role`, `phone`, `email`.
- Chave: `id`, `name`, `number`, `block`, `status`, `responsible`.
- Movimentação: `id`, `keyId`, `keyName`, `keyNumber`, `userId`, `userName`, `withdrawnAt`, `returnedAt`, `notes`.
- Ocorrência: `id`, `text`, `createdAt`, `createdBy`.

O backend precisa aplicar autorização de sessão em cada operação, unicidade, transições de estado e preservação do histórico. A tabela `chaves` usa `id_sala` e `numero_chave`; o nome e bloco vêm de `salas`. `movimentacoes.id_porteiro` é obrigatório mesmo para movimentações feitas por GERENTE, e a regra de `data_prevista` ainda precisa ser definida. O controller de cadastro de usuários existente e o esquema SQL precisam ser alinhados pelo responsável pelo backend antes de habilitar esses endpoints.

O Login só aceita os cargos GERENTE e PORTEIRO. A senha inicial pelos quatro primeiros dígitos do CPF, assim como uma possível troca obrigatória no primeiro acesso, deve ser implementada no backend; o navegador não gera nem armazena essa senha.
