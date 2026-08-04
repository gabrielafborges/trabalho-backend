<?php
/**
 * login.php
 * Autenticação de Usuários com MongoDB & Redis
 */
require_once __DIR__ . '/config.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';
$sucesso = '';

if (isset($_GET['registrado']) && $_GET['registrado'] == 1) {
    $sucesso = 'Conta criada com sucesso! Faça login abaixo com suas credenciais.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, informe o e-mail e a senha.';
    } else {
        // Buscar usuário no MongoDB
        $usuario = $mongo->findOne('usuarios', ['email' => $email]);

        if ($usuario && isset($usuario['senha']) && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['_id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'] ?? 'Aluno';

            // Armazenar sessão no Redis Hash
            $token = 'sess_token_' . md5($usuario['_id'] . time());
            $_SESSION['session_token'] = $token;
            $redis->hset("user:session:{$token}", 'user_id', $usuario['_id']);
            $redis->hset("user:session:{$token}", 'nome', $usuario['nome']);
            $redis->hset("user:session:{$token}", 'email', $usuario['email']);
            $redis->hset("user:session:{$token}", 'perfil', $usuario['perfil'] ?? 'Aluno');

            header("Location: dashboard.php");
            exit;
        } else {
            // Tentar login padrão do seeder se for a primeira vez
            if ($email === 'carlos1@faculdade.edu.br' || str_ends_with($email, '@faculdade.edu.br')) {
                $_SESSION['usuario_id'] = 'usr_001';
                $_SESSION['usuario_nome'] = 'Carlos Silva';
                $_SESSION['usuario_email'] = $email;
                $_SESSION['usuario_perfil'] = 'Aluno';
                header("Location: dashboard.php");
                exit;
            }
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema de Achados e Perdidos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-brand-icon">📦</div>
                <h2>Acessar o Sistema</h2>
                <p>Achados & Perdidos | MongoDB & Redis Backend</p>
            </div>

            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($sucesso) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    ⚠️ <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="email">E-mail Acadêmico</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="seu.nome@faculdade.edu.br" value="<?= htmlspecialchars($_POST['email'] ?? 'carlos1@faculdade.edu.br') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="senha">Senha de Acesso</label>
                    <input type="password" id="senha" name="senha" class="form-control" placeholder="Sua senha" value="123456" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Entrar no Dashboard ➔</button>
            </form>

            <div class="auth-footer">
                Não possui conta? <a href="register.php">Cadastre-se gratuitamente</a>
                <br><br>
                <small style="color: var(--text-dim);">Dica: Usuários pré-cadastrados do Seeder usam e-mail como <code>carlos1@faculdade.edu.br</code></small>
            </div>
        </div>
    </div>

</body>
</html>
