<?php
session_start();
include 'dbcon.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "Você precisa estar logado para acessar essa página.";
    exit;
}

$conn = conectar();
$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $acao == 'marcar') {
    if (isset($_POST['idade'], $_POST['data'], $_POST['hora'], $_POST['motivo'])) {
        $idade = $_POST['idade'];
        $data = $_POST['data'];
        $hora = $_POST['hora'];
        $motivo = $_POST['motivo'];

        $statement = $conn->prepare("
            SELECT COUNT(*) AS total FROM consultas
            WHERE data = :data
            AND (
                (hora <= :hora AND ADDTIME(hora, '1:00:00') > :hora) OR
                (:hora <= hora AND ADDTIME(:hora, '1:00:00') > hora)
            )
        ");
        $statement->bindParam(':data', $data);
        $statement->bindParam(':hora', $hora);
        $statement->execute();
        $resultado = $statement->fetch(PDO::FETCH_ASSOC);

        if ($resultado['total'] > 0) {
            $mensagem = "Já existe uma consulta marcada nesse horário ou há um conflito.";
        } else {
            $statement = $conn->prepare("INSERT INTO consultas (id_usuario, idade, data, hora, motivo) VALUES (:id_usuario, :idade, :data, :hora, :motivo)");
            $statement->bindParam(':id_usuario', $usuario_id);
            $statement->bindParam(':idade', $idade);
            $statement->bindParam(':data', $data);
            $statement->bindParam(':hora', $hora);
            $statement->bindParam(':motivo', $motivo);

            $mensagem = $statement->execute() ? "Consulta marcada com sucesso!" : "Erro ao marcar consulta.";
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos para marcar uma consulta.";
    }
} elseif ($acao == 'excluir') {
    if (isset($_POST['consulta_id'])) {
        $consulta_id = $_POST['consulta_id'];

        $statement = $conn->prepare("DELETE FROM consultas WHERE id=:id AND id_usuario=:id_usuario");
        $statement->bindParam(':id', $consulta_id);
        $statement->bindParam(':id_usuario', $usuario_id);

        $mensagem = $statement->execute() ? "Consulta excluída com sucesso!" : "Erro ao excluir consulta ou você não tem permissão.";
    } else {
        $mensagem = "ID da consulta não fornecido para exclusão.";
    }
} elseif ($acao == 'editar') {
    if (isset($_POST['consulta_id'], $_POST['idade'], $_POST['data'], $_POST['hora'], $_POST['motivo'])) {
        $consulta_id = $_POST['consulta_id'];
        $idade = $_POST['idade'];
        $data = $_POST['data'];
        $hora = $_POST['hora'];
        $motivo = $_POST['motivo'];

        $statement = $conn->prepare("
            SELECT COUNT(*) AS total FROM consultas
            WHERE data = :data
            AND id != :consulta_id
            AND (
                (hora <= :hora AND ADDTIME(hora, '1:00:00') > :hora) OR
                (:hora <= hora AND ADDTIME(:hora, '1:00:00') > hora)
            )
        ");
        $statement->bindParam(':data', $data);
        $statement->bindParam(':hora', $hora);
        $statement->bindParam(':consulta_id', $consulta_id);
        $statement->execute();
        $resultado = $statement->fetch(PDO::FETCH_ASSOC);

        if ($resultado['total'] > 0) {
            $mensagem = "Já existe uma consulta marcada nesse horário ou há um conflito.";
        } else {
            $statement = $conn->prepare("UPDATE consultas SET idade=:idade, data=:data, hora=:hora, motivo=:motivo WHERE id=:id AND id_usuario=:id_usuario");
            $statement->bindParam(':id', $consulta_id);
            $statement->bindParam(':id_usuario', $usuario_id);
            $statement->bindParam(':idade', $idade);
            $statement->bindParam(':data', $data);
            $statement->bindParam(':hora', $hora);
            $statement->bindParam(':motivo', $motivo);

            $mensagem = $statement->execute() ? "Consulta atualizada com sucesso!" : "Erro ao atualizar consulta ou você não tem permissão.";
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos para editar a consulta.";
    }
}

$statement = $conn->prepare("SELECT consultas.*, usuario.nome AS nome_usuario FROM consultas LEFT JOIN usuario ON consultas.id_usuario = usuario.id");
$statement->execute();
$consultas = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas</title>
    <link rel="stylesheet" href="Style/style.css">
</head>

<body>
    <header class="header">
        <h1>Consultas Medicas</h1>
    </header>
    <h1>Bem-vindo(a), <?= htmlspecialchars($usuario_nome) ?>!</h1>
    <?php if (isset($mensagem)): ?>
        <p><?= htmlspecialchars($mensagem) ?></p>
    <?php endif; ?>

    <div class="table-container">
        <h2>Consultas Marcadas</h2>
        <?php if (count($consultas) > 0): ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Idade</th>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Motivo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultas as $consulta): ?>
                        <tr>
                            <td><?= htmlspecialchars($consulta['nome_usuario']) ?></td>
                            <td><?= htmlspecialchars($consulta['idade']) ?></td>
                            <td><?= htmlspecialchars($consulta['data']) ?></td>
                            <td><?= htmlspecialchars($consulta['hora']) ?></td>
                            <td><?= htmlspecialchars($consulta['motivo']) ?></td>
                            <td>
                                <?php if ($consulta['id_usuario'] == $usuario_id): ?>
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($consulta)) ?>)">Editar</button>
                                    <form style="display:inline;" method="POST" action="?acao=excluir">
                                        <input type="hidden" name="consulta_id" value="<?= $consulta['id'] ?>">
                                        <button type="submit">Excluir</button>
                                    </form>
                                <?php else: ?>
                                    Sem ações
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Não há consultas marcadas.</p>
        <?php endif; ?>
        <button onclick="openCreateModal()">Marcar Nova Consulta</button>
    </div>


    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCreateModal()">&times;</span>
            <h2>Marcar Nova Consulta</h2>
            <form method="POST" action="?acao=marcar">
                <label for="idade">Idade:</label>
                <input type="number" name="idade" id="idade" required><br>
                <label for="data">Data:</label>
                <input type="date" name="data" id="data" required><br>
                <label for="hora">Hora:</label>
                <input type="time" name="hora" id="hora" required><br>
                <label for="motivo">Motivo:</label>
                <textarea name="motivo" id="motivo" required></textarea><br>
                <button type="submit">Marcar Consulta</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Editar Consulta</h2>
            <form method="POST" action="?acao=editar">
                <input type="hidden" name="consulta_id" id="editConsultaId">
                <label for="editIdade">Idade:</label>
                <input type="number" name="idade" id="editIdade" required><br>
                <label for="editData">Data:</label>
                <input type="date" name="data" id="editData" required><br>
                <label for="editHora">Hora:</label>
                <input type="time" name="hora" id="editHora" required><br>
                <label for="editMotivo">Motivo:</label>
                <textarea name="motivo" id="editMotivo" required></textarea><br>
                <button type="submit">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function openEditModal(consulta) {
            document.getElementById('editConsultaId').value = consulta.id;
            document.getElementById('editIdade').value = consulta.idade;
            document.getElementById('editData').value = consulta.data;
            document.getElementById('editHora').value = consulta.hora;
            document.getElementById('editMotivo').value = consulta.motivo;

            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');

            if (event.target === createModal) {
                closeCreateModal();
            } else if (event.target === editModal) {
                closeEditModal();
            }
        }
    </script>
    <footer class="simple-footer">
        <p>&copy; 2024 Minha Página. Todos os direitos reservados.</p>
    </footer>
</body>


</html>