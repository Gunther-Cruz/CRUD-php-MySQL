<?php
session_start();
include 'dbcon.php'; 


$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($acao == 'cadastro') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $conn = conectar();

        $statement = $conn->prepare("SELECT * FROM usuario WHERE email=:email");
        $statement->bindParam(':email', $email);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            $mensagem = "Email já cadastrado";
        } else {
            $senha_hash = md5($senha);

            $statement = $conn->prepare("INSERT INTO usuario (nome, email, senha) VALUES (:nome, :email, :senha)");
            $statement->bindParam(':nome', $nome);
            $statement->bindParam(':email', $email);
            $statement->bindParam(':senha', $senha_hash);

            if ($statement->execute()) {
                $mensagem = "Cadastro realizado com sucesso!";
            } else {
                $mensagem = "Erro ao cadastrar usuário";
            }
        }
    } elseif ($acao == 'login') {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $conn = conectar();

        $statement = $conn->prepare("SELECT * FROM usuario WHERE email=:email");
        $statement->bindParam(':email', $email);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            $usuario_db = $statement->fetch(PDO::FETCH_ASSOC);

            $senha_hash = md5($senha);

            if ($senha_hash == $usuario_db['senha']) {
                $_SESSION['usuario_id'] = $usuario_db['id'];
                $_SESSION['usuario_nome'] = $usuario_db['nome'];
                $_SESSION['usuario_email'] = $usuario_db['email'];
                header('Location: consulta.php');
                exit;
                // $mensagem = "Login bem sucedido! Bem vindo(a), " . $usuario_db['nome'];
            } else {
                $mensagem = "Senha incorreta";
            }
        } else {
            $mensagem = "Usuário não encontrado";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro e Login</title>
    <link rel="stylesheet" href="Style/style.css">

</head>

<header class="header">
    <h1>Consultas Medicas</h1>
</header>

<body>
    <?php
    if (isset($mensagem)) {
        echo "<p>$mensagem</p>";
    }
    ?>

    <div class="form-container">
        <div class="form-section">
            <h2>Cadastro de Usuário</h2>
            <form method="POST" action="?acao=cadastro">
                <label for="nome">Nome:</label>
                <input type="text" name="nome" id="nome" required><br>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required><br>
                <label for="senha">Senha:</label>
                <input type="password" name="senha" id="senha" required><br>

                <button class="btn" type="submit">Cadastrar</button>
            </form>
        </div>

        <div class="form-divider"></div>

        <div class="form-section">
            <h2>Login</h2>
            <form method="POST" action="?acao=login">
                <label for="email">Email:</label>
                <input type="text" name="email" id="email" required><br>
                <label for="senha">Senha:</label>
                <input type="password" name="senha" id="senha" required><br>

                <button type="submit">Entrar</button>
            </form>
        </div>
    </div>



    <footer class="simple-footer">
        <p>&copy; Por Gunther da Cruz. IFRS-Web 1. 2024</p>
    </footer>
</body>

</html>