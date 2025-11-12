<?php
// listarProdutos.php — retorna JSON apenas dos produtos da empresa logada
session_start();
header('Content-Type: application/json; charset=utf-8');

// 🔹 Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado']);
    exit;
}

$id_usuario = intval($_SESSION['id_usuario']);

// 🔹 Conexão com o banco
$servidor = "localhost";
$usuario  = "root";
$senha    = "";
$banco    = "pecaaq";

$conn = new mysqli($servidor, $usuario, $senha, $banco);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro de conexão']);
    exit;
}

// 🔹 Busca os produtos do usuário logado
$sql = "SELECT id_produto, nome, preco, foto_principal 
        FROM produtos 
        WHERE id_usuario = ? 
        ORDER BY id_produto DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$produtos = [];

// Caminho base correto (pasta uploads dentro do Dashboard)
$basePath = 'uploads/';


while ($row = $result->fetch_assoc()) {
    $foto = $row['foto_principal'] ?? '';

    if (!empty($foto) && file_exists(__DIR__ . '/uploads/' . $foto)) {
        $row['foto_principal'] = $basePath . $foto;
    } else {
        $row['foto_principal'] = $basePath . 'sem_imagem.png'; // imagem padrão
    }

    // Formata preço
    $row['preco'] = number_format((float)$row['preco'], 2, ',', '.');

    $produtos[] = $row;
}

echo json_encode([
    'status' => 'ok',
    'produtos' => $produtos
]);

$stmt->close();
$conn->close();
?>
