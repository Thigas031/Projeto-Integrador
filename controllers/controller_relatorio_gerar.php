<?php
session_start();
require_once("../config/config.php");
require_once("../config/auth.php");

exigir_login();
exigir_cargo(['GERENTE']);

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
$inicio = $_GET['data_inicial'] ?? '';
$fim = $_GET['data_final'] ?? '';
$status = $_GET['status'] ?? 'Todos';

if (!is_string($usuario) || !is_string($inicio) || !is_string($fim) || !is_string($status) ||
    strlen($usuario) > 150 || !in_array($status, ['Todos', 'Emprestada', 'Atrasada', 'Devolvida'], true)) {
    responderRelatorioGerar(400, ['ok' => false, 'message' => 'Filtros inválidos.']);
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
    $filtros[] = '(COALESCE(m.nome_retirante, u.nome) LIKE :usuario_nome OR u.cpf LIKE :usuario_cpf)';
    $parametros[':usuario_nome'] = '%' . $usuario . '%';
    $parametros[':usuario_cpf'] = '%' . preg_replace('/\D/', '', $usuario) . '%';
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
                s.nome AS roomName,
                m.id_usuario AS userId,
                COALESCE(m.nome_retirante, u.nome) AS userName,
                COALESCE(m.cargo_retirante, u.cargo) AS userRole,
                u.cpf AS userCpf,
                p.nome AS porterName, m.data_retirada AS withdrawnAt,
                m.data_prevista AS dueAt, m.data_devolucao AS returnedAt,
                m.observacao AS notes,
                CASE
                    WHEN m.data_devolucao IS NOT NULL THEN 'Devolvida'
                    WHEN m.data_prevista IS NOT NULL AND m.data_prevista < NOW() THEN 'Atrasada'
                    ELSE 'Emprestada'
                END AS status
         FROM movimentacoes m
         INNER JOIN chaves k ON k.id_chave = m.id_chave
         INNER JOIN salas s ON s.id_sala = k.id_sala
         LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
         INNER JOIN usuarios p ON p.id_usuario = m.id_porteiro
         $where
         ORDER BY m.data_retirada DESC"
    );
    $stmt->execute($parametros);
    responderRelatorioGerar(200, ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $erro) {
    error_log('Falha ao gerar relatório: ' . $erro->getMessage());
    responderRelatorioGerar(500, ['ok' => false, 'message' => 'Não foi possível gerar o relatório.']);
}
