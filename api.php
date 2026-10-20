<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Configurações reais do banco de dados (InfinityFree)
$host = 'sql204.infinityfree.com';
$dbname = 'if0_43220683_dominio_bancodedados';
$username = 'if0_43220683';
$password = 'SUA_SENHA_AQUI'; // Substitua pela sua senha do MySQL mostrada no painel

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro de conexão: " . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_processos') {
    try {
        $stmt = $pdo->query("SELECT * FROM processos");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processos = [];
        foreach ($rows as $row) {
            $row['dados_json'] = json_decode($row['dados_json'], true);
            $processos[] = $row;
        }
        
        echo json_encode($processos);
    } catch (Exception $e) {
        echo json_encode([]);
    }
} 
elseif ($action === 'salvar_processo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "ID inválido"]);
        exit;
    }

    $id = $input['id'];
    $nome = $input['nome'] ?? '';
    $cpf = $input['cpf'] ?? '';
    $empreendimento = $input['empreendimento'] ?? '';
    $valor = $input['valor'] ?? '';
    $stage = $input['stage'] ?? 'cadastro';
    $subStatus = $input['subStatus'] ?? '';
    $isDistrato = isset($input['isDistrato']) && $input['isDistrato'] ? 1 : 0;
    $isFinalizado = isset($input['isFinalizado']) && $input['isFinalizado'] ? 1 : 0;
    $dataCriacao = $input['dataCriacao'] ?? date('Y-m-d');
    
    // Guardamos o restante dos dados estruturados em JSON
    $dadosJson = json_encode([
        'responsavelAnalise' => $input['responsavelAnalise'] ?? '',
        'dadosAprovacao' => $input['dadosAprovacao'] ?? [],
        'logs' => $input['logs'] ?? [],
        'documents' => $input['documents'] ?? []
    ], JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO processos (id, nome_proponente, cpf_proponente, empreendimento, valor_vgf, etapa_atual, sub_status, is_distrato, is_finalizado, dados_json, data_criacao) 
            VALUES (:id, :nome, :cpf, :empreendimento, :valor, :stage, :subStatus, :isDistrato, :isFinalizado, :dadosJson, :dataCriacao)
            ON DUPLICATE KEY UPDATE 
            nome_proponente = :nome, cpf_proponente = :cpf, empreendimento = :empreendimento, valor_vgf = :valor, 
            etapa_atual = :stage, sub_status = :subStatus, is_distrato = :isDistrato, is_finalizado = :isFinalizado, dados_json = :dadosJson";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':empreendimento' => $empreendimento,
        ':valor' => $valor,
        ':stage' => $stage,
        ':subStatus' => $subStatus,
        ':isDistrato' => $isDistrato,
        ':isFinalizado' => $isFinalizado,
        ':dadosJson' => $dadosJson,
        ':dataCriacao' => $dataCriacao
    ]);

    echo json_encode(["sucesso" => true, "mensagem" => "Processo salvo com sucesso no MySQL!"]);
}
?>
