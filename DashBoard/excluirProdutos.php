<?php
// DashBoard/excluirProduto.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 🔹 Verifica sessão
if (empty($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sessão expirada ou usuário não autenticado.']);
    exit;
}
$id_usuario = intval($_SESSION['id_usuario']);

// 🔹 Conexão com o banco
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pecaaq";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro de conexão com o banco.']);
    exit;
}

// 🔹 Recebe ID do produto
$id_produto = intval($_POST['id_produto'] ?? 0);
if ($id_produto <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID do produto inválido.']);
    exit;
}

// 🔹 Busca imagem e verifica se pertence ao usuário
$sqlCheck = "SELECT foto_principal FROM produtos WHERE id_produto = ? AND id_usuario = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $id_produto, $id_usuario);
$stmtCheck->execute();
$res = $stmtCheck->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Produto não encontrado ou não pertence a este usuário.']);
    exit;
}

$foto = $res->fetch_assoc()['foto_principal'] ?? null;
$stmtCheck->close();

// 🔹 Exclui produto
$sql = "DELETE FROM produtos WHERE id_produto = ? AND id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_produto, $id_usuario);

if ($stmt->execute()) {
    if ($foto) {
        $fotoPath = __DIR__ . '/uploads/' . $foto;
        if (file_exists($fotoPath)) unlink($fotoPath);
    }
    echo json_encode(['status' => 'ok', 'message' => 'Produto excluído com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir produto: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
