<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();
exigir_cargo(['GERENTE', 'PORTEIRO']);

header('Content-Type: application/json; charset=utf-8');

function responderRelatorioGerar($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderRelatorioGerar(405, ['ok' => false, 'message' => 'Método não permitido.']);
}

$usuario = $_GET['usuario'] ?? $_GET['visitante'] ?? '';
$salaEntrada = $_GET['sala'] ?? '';
$inicio = $_GET['data_inicial'] ?? '';
$fim = $_GET['data_final'] ?? '';
$status = $_GET['status'] ?? 'Todos';

if (!is_string($usuario) || !is_string($salaEntrada) || !is_string($inicio) ||
    !is_string($fim) || !is_string($status) || strlen($usuario) > 408 ||
    !in_array($status, ['Todos', 'Pendente', 'Emprestada', 'Atrasada', 'Devolvida'], true)) {
    responderRelatorioGerar(400, ['ok' => false, 'message' => 'Filtros inválidos.']);
}

$sala = null;
if ($salaEntrada !== '') {
    $sala = filter_var($salaEntrada, FILTER_VALIDATE_INT);
    if ($sala === false || $sala < 1) {
        responderRelatorioGerar(400, ['ok' => false, 'message' => 'Sala inválida.']);
    }
}

foreach (['data_inicial' => $inicio, 'data_final' => $fim] as $nome => $data) {
    if ($data !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data)) {
            responderRelatorioGerar(400, ['ok' => false, 'message' => 'Informe datas válidas no formato AAAA-MM-DD.']);
        }
        $validada = DateTime::createFromFormat('!Y-m-d', $data);
        $erros = DateTime::getLastErrors();
        if (!$validada || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0)) ||
            $validada->format('Y-m-d') !== $data) {
            responderRelatorioGerar(400, ['ok' => false, 'message' => 'Informe datas válidas no formato AAAA-MM-DD.']);
        }
    }
}

if ($inicio !== '' && $fim !== '' && $inicio > $fim) {
    responderRelatorioGerar(400, ['ok' => false, 'message' => 'A data inicial deve ser anterior ou igual à final.']);
}

$filtros = [];
$parametros = [];
if ($usuario !== '') {
    if (preg_match('/^usuario:([1-9][0-9]*)$/D', $usuario, $correspondencia)) {
        $filtros[] = 'm.id_usuario = :usuario_id';
        $parametros[':usuario_id'] = (int) $correspondencia[1];
    } elseif (strpos($usuario, 'externo:') === 0) {
        $nomeExterno = substr($usuario, 8);
        $comprimentoNome = preg_match_all('/./us', $nomeExterno, $caracteresNome);
        if ($nomeExterno === '' || $comprimentoNome === false || $comprimentoNome > 100) {
            responderRelatorioGerar(400, ['ok' => false, 'message' => 'Usuário inválido.']);
        }
        $filtros[] = 'm.id_usuario IS NULL AND m.nome_retirante = :usuario_externo';
        $parametros[':usuario_externo'] = $nomeExterno;
    } else {
        if (strlen($usuario) > 150) {
            responderRelatorioGerar(400, ['ok' => false, 'message' => 'Usuário inválido.']);
        }
        $filtros[] = '(COALESCE(m.nome_retirante, u.nome) LIKE :usuario_nome OR u.cpf LIKE :usuario_cpf)';
        $parametros[':usuario_nome'] = '%' . $usuario . '%';
        $parametros[':usuario_cpf'] = '%' . preg_replace('/\D/', '', $usuario) . '%';
    }
}
if ($sala !== null) {
    $filtros[] = 's.id_sala = :sala';
    $parametros[':sala'] = $sala;
}
if ($inicio !== '') {
    $filtros[] = 'm.data_retirada >= :data_inicial';
    $parametros[':data_inicial'] = $inicio . ' 00:00:00';
}
if ($fim !== '') {
    $filtros[] = 'm.data_retirada < DATE_ADD(:data_final, INTERVAL 1 DAY)';
    $parametros[':data_final'] = $fim;
}

switch ($status) {
    case 'Pendente':
    case 'Emprestada':
        $filtros[] = "m.data_devolucao IS NULL
                      AND (m.data_prevista IS NULL OR m.data_prevista >= NOW())";
        break;
    case 'Atrasada':
        $filtros[] = 'm.data_devolucao IS NULL AND m.data_prevista < NOW()';
        break;
    case 'Devolvida':
        $filtros[] = 'm.data_devolucao IS NOT NULL';
        break;
}

$where = $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';

try {
    $stmt = $pdo->prepare(
        "SELECT m.id_movimentacao AS id, m.id_chave AS keyId,
                k.nome_chave AS keyName, k.numero_chave AS keyNumber,
                s.id_sala AS roomId, s.nome AS roomName,
                s.numero AS roomNumber, s.bloco AS roomBlock,
                m.id_usuario AS userId,
                COALESCE(m.nome_retirante, u.nome) AS userName,
                COALESCE(m.cargo_retirante, u.cargo) AS userRole,
                m.id_porteiro AS porterId,
                p.nome AS porterName, p.cargo AS porterRole,
                m.data_retirada AS withdrawnAt,
                m.data_prevista AS dueAt, m.data_devolucao AS returnedAt,
                m.observacao AS notes,
                CASE
                    WHEN m.data_devolucao IS NOT NULL THEN 'Devolvida'
                    WHEN m.data_prevista IS NOT NULL AND m.data_prevista < NOW() THEN 'Atrasada'
                    ELSE 'Pendente'
                END AS status,
                CASE
                    WHEN m.data_prevista IS NULL THEN 0
                    ELSE GREATEST(
                        0,
                        TIMESTAMPDIFF(
                            SECOND,
                            m.data_prevista,
                            COALESCE(m.data_devolucao, NOW())
                        )
                    )
                END AS lateSeconds
         FROM movimentacoes m
         INNER JOIN chaves k ON k.id_chave = m.id_chave
         INNER JOIN salas s ON s.id_sala = k.id_sala
         LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
         INNER JOIN usuarios p ON p.id_usuario = m.id_porteiro
         $where
         ORDER BY m.data_retirada DESC, m.id_movimentacao DESC"
    );
    $stmt->execute($parametros);
    $salas = $pdo->query(
        "SELECT id_sala AS id, nome AS name, numero, bloco
         FROM salas
         ORDER BY nome ASC, numero ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    $usuarios = $pdo->query(
        "SELECT filterId, name, role
         FROM (
             SELECT CONCAT('usuario:', m.id_usuario) AS filterId,
                    MAX(COALESCE(m.nome_retirante, u.nome)) AS name,
                    MAX(COALESCE(m.cargo_retirante, u.cargo)) AS role
             FROM movimentacoes m
             LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
             WHERE m.id_usuario IS NOT NULL
             GROUP BY m.id_usuario
             UNION ALL
             SELECT CONCAT('externo:', m.nome_retirante) AS filterId,
                    m.nome_retirante AS name,
                    MAX(m.cargo_retirante) AS role
             FROM movimentacoes m
             WHERE m.id_usuario IS NULL
               AND m.nome_retirante IS NOT NULL
               AND m.nome_retirante <> ''
             GROUP BY m.nome_retirante
         ) AS pessoas
         ORDER BY name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    responderRelatorioGerar(200, [
        'ok' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'options' => ['salas' => $salas, 'usuarios' => $usuarios],
    ]);
} catch (PDOException $erro) {
    error_log('Falha ao gerar relatório: ' . $erro->getMessage());
    responderRelatorioGerar(500, ['ok' => false, 'message' => 'Não foi possível gerar o relatório.']);
}
