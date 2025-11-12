<?php
session_start();

// =================== CONFIG DB ===================
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "pecaaq";

$conn = new mysqli($servidor, $usuario, $senha, $banco);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// =================== RECEBE DADOS ===================
$tipo  = $_POST['tipo']  ?? '';
$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';

if (empty($tipo) || empty($login) || empty($senha)) {
    die("⚠ Todos os campos são obrigatórios!");
}

// Normaliza login (remove pontos e traços de CNPJ/CPF)
$loginLimpo = preg_replace('/\D/', '', $login);

// Define tipo conforme valor armazenado no banco
$tipo_map = strtolower($tipo) === 'empresa' ? 'Fornecedor' : ucfirst(strtolower($tipo));
if (!in_array($tipo_map, ['Cliente', 'Fornecedor'])) {
    die("Tipo de login inválido!");
}

// =================== CONSULTA USUÁRIO ===================
$sql = "SELECT id_usuario, nome_razao_social, email, senha_hash, tipo, documento 
        FROM usuarios 
        WHERE tipo = ? AND (email = ? OR documento = ?) 
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro na query: " . $conn->error);
}
$stmt->bind_param("sss", $tipo_map, $login, $loginLimpo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Usuário não encontrado!");
}

$usuario = $result->fetch_assoc();
$stmt->close();

// =================== VERIFICA SENHA ===================
if (!password_verify($senha, $usuario['senha_hash'])) {
    $conn->close();
    die("❌ Senha incorreta!");
}

// =================== CRIA SESSÃO ===================
$_SESSION['id_usuario'] = $usuario['id_usuario'];
$_SESSION['nome_razao_social'] = $usuario['nome_razao_social'];
$_SESSION['tipo_usuario'] = $usuario['tipo'];

// =================== PREPARA LOCALSTORAGE ===================
$usuarioParaLocal = [
    'id_usuario' => (int)$usuario['id_usuario'],
    'nome_razao_social' => $usuario['nome_razao_social'],
    'email' => $usuario['email'] ?? '',
    'tipo' => $usuario['tipo'] ?? 'Cliente'
];

// Se for empresa, renomeia o campo para id_empresa (para uso no cadastro de produtos)
if ($usuarioParaLocal['tipo'] === 'Fornecedor') {
    $usuarioParaLocal['id_empresa'] = $usuarioParaLocal['id_usuario'];
}

// =================== REDIRECIONAMENTO ===================
$destino = '../LaningPage/indexLandingPage.html';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Login realizado</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: Arial, Helvetica, sans-serif; padding:30px; text-align:center; background:#f5f5f5; }
    h2 { color:#2e7d32; }
    .msg { margin-top:20px; font-size:16px; }
  </style>
</head>
<body>
  <h2>✅ Login realizado com sucesso!</h2>
  <p class="msg">Você será redirecionado em instantes...</p>

  <script>
    (function(){
      try {
        var usuario = <?php echo json_encode($usuarioParaLocal, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        localStorage.setItem('usuarioLogado', JSON.stringify(usuario));
        console.log('Usuário logado:', usuario);
      } catch (e) {
        console.warn('Erro ao gravar localStorage:', e);
      }
      setTimeout(function(){
        window.location.href = '<?php echo addslashes($destino); ?>';
      }, 800);
    })();
  </script>
</body>
</html>
<?php
$conn->close();
exit;
?>
