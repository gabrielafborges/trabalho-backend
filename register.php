<?php
/**
 * register.php
 * Cadastro de Novos Usuários no MongoDB
 */
require_once __DIR__ . '/config.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'Aluno';

    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Insira um endereço de e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As confirmações de senha não coincidem.';
    } else {
        // Verificar se e-mail existe no Mongo
        $existente = $mongo->findOne('usuarios', ['email' => $email]);

        if ($existente) {
            $erro = 'Este e-mail já está cadastrado no sistema.';
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $novoId = 'usr_' . sprintf('%03d', rand(100, 999));

            $mongo->insertOne('usuarios', [
                '_id' => $novoId,
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha_hash,
                'perfil' => $perfil,
                'matricula' => '2026' . rand(1000, 9999),
                'criado_em' => date('Y-m-d H:i:s')
            ]);

            // Atualizar estatísticas e cache
            $redis->del("api:list:usuarios");

            header("Location: login.php?registrado=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta | Sistema de Achados e Perdidos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-brand-icon">✨</div>
                <h2>Criar Conta de Usuário</h2>
                <p>Cadastre-se para registrar e resgatar itens no campus</p>
            </div>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    ⚠️ <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" class="form-control" placeholder="Seu nome completo" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">E-mail Acadêmico</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="seu.nome@faculdade.edu.br" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="perfil">Perfil do Usuário</label>
                    <select id="perfil" name="perfil" class="form-control">
                        <option value="Aluno">Aluno(a)</option>
                        <option value="Professor">Professor(a)</option>
                        <option value="Funcionário">Funcionário(a)</option>
                    </select>
                </div>

                <div class="form-grid" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Mínimo 6 dígitos" required>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" placeholder="Repita a senha" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px;">Concluir Cadastro ➔</button>
            </form>

            <div class="auth-footer">
                Já possui uma conta? <a href="login.php">Fazer Login</a>
            </div>
        </div>
    </div>

</body>
</html>
