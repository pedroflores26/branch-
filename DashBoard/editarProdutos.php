<?php
// DashBoard/editarProduto.php
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

// 🔹 Recebe dados
$id_produto  = intval($_POST['id_produto'] ?? 0);
$nome        = trim($_POST['nome'] ?? '');
$sku         = trim($_POST['sku'] ?? '');
$marca       = trim($_POST['marca'] ?? '');
$descricao   = trim($_POST['descricao'] ?? '');
$preco_raw   = trim($_POST['preco'] ?? '');
$categoria   = trim($_POST['categoria'] ?? '');
$id_categoria = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? intval($_POST['id_categoria']) : null;

if ($id_produto <= 0 || $nome === '' || $preco_raw === '' || $categoria === '') {
    echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

// 🔹 Normaliza preço
$preco_normalizado = preg_replace('/[^\d,\.]/', '', $preco_raw);
$preco_normalizado = str_replace(',', '.', $preco_normalizado);
$preco = (float) $preco_normalizado;

// 🔹 Verifica se o produto pertence ao usuário logado
$sqlCheck = "SELECT foto_principal FROM produtos WHERE id_produto = ? AND id_usuario = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $id_produto, $id_usuario);
$stmtCheck->execute();
$res = $stmtCheck->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Produto não encontrado ou não pertence a este usuário.']);
    exit;
}

$produto = $res->fetch_assoc();
$fotoAtual = $produto['foto_principal'] ?? null;
$stmtCheck->close();

// 🔹 Upload da nova imagem (opcional)
$foto_field = 'foto_principal';
$foto_db = $fotoAtual;

if (isset($_FILES[$foto_field]) && $_FILES[$foto_field]['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $ext = strtolower(pathinfo($_FILES[$foto_field]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'Formato de imagem inválido.']);
        exit;
    }

    $uniqueName = uniqid('prod_') . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;

    if (move_uploaded_file($_FILES[$foto_field]['tmp_name'], $targetPath)) {
        // remove antiga
        if ($fotoAtual && file_exists($uploadDir . $fotoAtual)) {
            unlink($uploadDir . $fotoAtual);
        }
        $foto_db = $uniqueName;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao mover a nova imagem.']);
        exit;
    }
}

// 🔹 Atualiza produto
$sql = "UPDATE produtos 
        SET id_categoria = ?, nome = ?, sku_universal = ?, marca = ?, descricao_tecnica = ?, foto_principal = ?, preco = ?, categoria = ? 
        WHERE id_produto = ? AND id_usuario = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssdsii", 
    $id_categoria, $nome, $sku, $marca, $descricao, $foto_db, $preco, $categoria, $id_produto, $id_usuario
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'ok', 'message' => 'Produto atualizado com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar produto: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
