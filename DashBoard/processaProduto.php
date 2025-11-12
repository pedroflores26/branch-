<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// ===========================
// 1️⃣ CONEXÃO COM O BANCO
// ===========================
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "pecaaq"; // sem acento!

$conn = new mysqli($servidor, $usuario, $senha, $banco);

if ($conn->connect_error) {
    echo json_encode(["status" => "erro", "mensagem_erro" => "Erro de conexão: " . $conn->connect_error]);
    exit;
}

// ===========================
// 2️⃣ VERIFICA SE USUÁRIO ESTÁ LOGADO
// ===========================
if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(["status" => "erro", "mensagem_erro" => "Sessão expirada. Faça login novamente."]);
    exit;
}

$id_usuario = $_SESSION["id_usuario"];

// ===========================
// 3️⃣ RECEBE DADOS DO FORMULÁRIO
// ===========================
$nome = $_POST["nome"] ?? "";
$sku_universal = $_POST["sku_universal"] ?? "";
$marca = $_POST["marca"] ?? "";
$descricao_tecnica = $_POST["descricao_tecnica"] ?? "";
$preco = $_POST["preco"] ?? "";
$categoria = $_POST["categoria"] ?? "";
$id_categoria = $_POST["id_categoria"] ?? null;

// ===========================
// 4️⃣ VALIDAÇÃO BÁSICA
// ===========================
if (empty($nome) || empty($preco)) {
    echo json_encode(["status" => "erro", "mensagem_erro" => "Preencha pelo menos o nome e o preço."]);
    exit;
}

// ===========================
// 5️⃣ UPLOAD DA IMAGEM
// ===========================
$foto_principal = "";

if (isset($_FILES["foto_principal"]) && $_FILES["foto_principal"]["error"] === UPLOAD_ERR_OK) {
    $pasta = __DIR__ . "/uploads/";

    if (!file_exists($pasta)) {
        mkdir($pasta, 0777, true);
    }

    $extensao = pathinfo($_FILES["foto_principal"]["name"], PATHINFO_EXTENSION);
    $novoNome = uniqid("produto_") . "." . $extensao;
    $destino = $pasta . $novoNome;

    if (move_uploaded_file($_FILES["foto_principal"]["tmp_name"], $destino)) {
        $foto_principal = "uploads/" . $novoNome;
    } else {
        echo json_encode(["status" => "erro", "mensagem_erro" => "Erro ao salvar imagem."]);
        exit;
    }
} else {
    $foto_principal = "uploads/default.png"; // imagem padrão
}

// ===========================
// 6️⃣ INSERÇÃO NO BANCO
// ===========================
$sql = "INSERT INTO produtos 
        (id_usuario, id_categoria, nome, sku_universal, marca, descricao_tecnica, foto_principal, preco, categoria)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "erro", "mensagem_erro" => "Erro na preparação da query: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "iisssssss",
    $id_usuario,
    $id_categoria,
    $nome,
    $sku_universal,
    $marca,
    $descricao_tecnica,
    $foto_principal,
    $preco,
    $categoria
);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok", "mensagem" => "Produto cadastrado com sucesso!"]);
} else {
    echo json_encode(["status" => "erro", "mensagem_erro" => "Erro ao inserir produto: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
