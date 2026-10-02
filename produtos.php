<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = '';

// Exclusão
if (isset($_GET['excluir'])) {
    try {
        $conn->prepare("DELETE FROM produtos WHERE id = ?")->execute([(int)$_GET['excluir']]);
        $mensagem = 'Produto Excluído com Sucesso.'; $tipoMensagem = 'sucesso';
    } catch (PDOException $e) { $mensagem = 'Erro ao excluir: ' . $e->getMessage(); $tipoMensagem = 'erro'; }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $preco = filter_var($_POST['preco'] ?? '', FILTER_VALIDATE_FLOAT);
    $estoqueAtual = filter_var($_POST['estoque_atual'] ?? '0', FILTER_VALIDATE_INT);
    $estoqueMinimo = filter_var($_POST['estoque_minimo'] ?? '0', FILTER_VALIDATE_INT);
    $peso = filter_var($_POST['peso'] ?? '0', FILTER_VALIDATE_FLOAT);
    $data = [
        'codigo'               => trim($_POST['codigo'] ?? ''),
        'nome'                 => trim($_POST['nome'] ?? ''),
        'fabricante'           => trim($_POST['fabricante'] ?? 'Geral'),
        'preco'                => $preco === false ? 0 : $preco,
        'estoque_atual'        => $estoqueAtual === false ? 0 : $estoqueAtual,
        'estoque_minimo'       => $estoqueMinimo === false ? 0 : $estoqueMinimo,
        'ativo'                => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1,
        'peso'                 => $peso === false ? 0 : $peso,
        'categoria'            => trim($_POST['categoria'] ?? '') ?: null,
        'unidade'              => trim($_POST['unidade'] ?? 'un'),
        'material_fabricacao'  => trim($_POST['material_fabricacao'] ?? '') ?: null,
        'espessura'            => trim($_POST['espessura'] ?? '') ?: null,
        'dimensoes'            => trim($_POST['dimensoes'] ?? '') ?: null,
        'acabamento'           => trim($_POST['acabamento'] ?? '') ?: null,
        'aplicacao'            => trim($_POST['aplicacao'] ?? '') ?: null,
    ];

    if (!$data['codigo'] || !$data['nome'] || !$data['categoria'] || $preco === false || $preco <= 0
        || $estoqueAtual === false || $estoqueAtual < 0 || $estoqueMinimo === false || $estoqueMinimo < 0
        || $peso === false || $peso < 0) {
        $mensagem = 'Informe código, nome e categoria; preço deve ser maior que zero e estoque/peso não podem ser negativos.';
        $tipoMensagem = 'erro';
    } else {
        try {
            if ($id > 0) {
                $sql = "UPDATE produtos SET codigo=:codigo, nome=:nome, fabricante=:fabricante, preco=:preco, estoque_atual=:estoque_atual, estoque_minimo=:estoque_minimo, ativo=:ativo, peso=:peso, categoria=:categoria, unidade=:unidade, material_fabricacao=:material_fabricacao, espessura=:espessura, dimensoes=:dimensoes, acabamento=:acabamento, aplicacao=:aplicacao WHERE id=:id";
                $data['id'] = $id;
            } else {
                $sql = "INSERT INTO produtos (codigo, nome, fabricante, preco, estoque_atual, estoque_minimo, ativo, peso, categoria, unidade, material_fabricacao, espessura, dimensoes, acabamento, aplicacao) VALUES (:codigo, :nome, :fabricante, :preco, :estoque_atual, :estoque_minimo, :ativo, :peso, :categoria, :unidade, :material_fabricacao, :espessura, :dimensoes, :acabamento, :aplicacao)";
            }
            $conn->prepare($sql)->execute($data);
            $mensagem = $id > 0 ? 'Produto atualizado!' : 'Produto cadastrado!'; $tipoMensagem = 'sucesso';
        } catch (PDOException $e) { $mensagem = 'Erro: ' . $e->getMessage(); $tipoMensagem = 'erro'; }
    }
}

// Buscar produto para edição
$produtoEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $produtoEditar = $stmt->fetch();
}

// Listar
$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT * FROM produtos" . ($busca ? " WHERE nome LIKE :b OR codigo LIKE :b OR categoria LIKE :b" : "") . " ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->execute($busca ? ['b' => "%$busca%"] : []);
$produtos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produtos - Industria Metalica</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Cadastro de Produtos - Industria Metalica</h1>
    <p><a href="index.php">⬅️Voltar</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p role="<?= $tipoMensagem === 'sucesso' ? 'status' : 'alert' ?>" style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>"><?= htmlspecialchars($mensagem) ?></p>
        <hr>
    <?php endif; ?>

    <h2><?= $produtoEditar ? 'Editar' : 'Novo' ?> Produto</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $produtoEditar['id'] ?? 0 ?>">
        <label>Código: * <input type="text" name="codigo" value="<?= htmlspecialchars($produtoEditar['codigo'] ?? '') ?>" required></label><br>
        <label>Nome: * <input type="text" name="nome" size="40" value="<?= htmlspecialchars($produtoEditar['nome'] ?? '') ?>" required></label><br>
        <label>Fabricante: <input type="text" name="fabricante" value="<?= htmlspecialchars($produtoEditar['fabricante'] ?? '') ?>"></label><br>
        <label>Preço de Venda: * <input type="number" step="0.01" name="preco" value="<?= htmlspecialchars($produtoEditar['preco'] ?? '') ?>" required></label><br>
        <label>Estoque Atual: <input type="number" name="estoque_atual" min="0" value="<?= $produtoEditar['estoque_atual'] ?? 0 ?>"></label><br>
        <label>Estoque Mínimo: <input type="number" name="estoque_minimo" min="0" value="<?= $produtoEditar['estoque_minimo'] ?? 5 ?>"></label><br>
        <label>Status (Ativo): 
            <select name="ativo">
                <option value="1" <?= ($produtoEditar['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>1 - Ativo</option>
                <option value="0" <?= ($produtoEditar['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>0 - Inativo</option>
            </select>
        </label><br>
        <label>Peso (kg): <input type="number" step="0.01" min="0" name="peso" value="<?= $produtoEditar['peso'] ?? 0.00 ?>"></label><br>
        <label>Categoria:
            <select name="categoria" required>
                <?php foreach (['Chapas', 'Treliças', 'Perfis metálicos', 'Barras e tubos', 'Telhas metálicas', 'Acessórios'] as $categoria): ?>
                    <option value="<?= htmlspecialchars($categoria) ?>" <?= ($produtoEditar['categoria'] ?? 'Chapas') === $categoria ? 'selected' : '' ?>><?= htmlspecialchars($categoria) ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Unidade de venda:
            <select name="unidade">
                <?php foreach (['un' => 'Unidade', 'm' => 'Metro', 'm²' => 'Metro quadrado', 'kg' => 'Quilograma', 'barra' => 'Barra'] as $valor => $rotulo): ?>
                    <option value="<?= htmlspecialchars($valor) ?>" <?= ($produtoEditar['unidade'] ?? 'un') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Material de Fabricação: <input type="text" name="material_fabricacao" value="<?= htmlspecialchars($produtoEditar['material_fabricacao'] ?? '') ?>"></label><br>
        <label>Espessura: <input type="text" name="espessura" placeholder="Ex.: 2 mm" value="<?= htmlspecialchars($produtoEditar['espessura'] ?? '') ?>"></label><br>
        <label>Dimensões: <input type="text" name="dimensoes" placeholder="Ex.: 1,20 x 2,00 m" value="<?= htmlspecialchars($produtoEditar['dimensoes'] ?? '') ?>"></label><br>
        <label>Acabamento: <input type="text" name="acabamento" placeholder="Ex.: galvanizado" value="<?= htmlspecialchars($produtoEditar['acabamento'] ?? '') ?>"></label><br>
        <label>Aplicação: <input type="text" name="aplicacao" value="<?= htmlspecialchars($produtoEditar['aplicacao'] ?? '') ?>"></label><br><br>

        <button type="submit">Salvar</button>
        <?php if ($produtoEditar): ?><a href="produtos.php"><button type="button">Cancelar</button></a><?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Produtos</h2>
    <form method="GET">
        <label>Buscar <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome, Código ou Categoria..."></label>
        <button type="submit">Buscar</button>
        <?php if ($busca): ?><a href="produtos.php">Limpar</a><?php endif; ?>
    </form>
    <br>

    <?php if ($produtos): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Código</th><th>Produto</th><th>Fabricante</th><th>Categoria</th><th>Material</th><th>Espessura</th><th>Dimensões</th><th>Acabamento</th><th>Unidade</th><th>Preço</th><th>Estoque</th><th>Mín.</th><th>Peso</th><th>Aplicação</th><th>Ações</th>
            </tr>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['fabricante'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['material_fabricacao'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['espessura'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['dimensoes'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['acabamento'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['unidade'] ?? 'un') ?></td>
                    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                    <td><?= $p['estoque_atual'] ?></td>
                    <td><?= $p['estoque_minimo'] ?></td>
                    <td><?= number_format((float)$p['peso'], 2, ',', '.') ?> kg</td>
                    <td><?= htmlspecialchars($p['aplicacao'] ?? '-') ?></td>
                    <td>
                        <a href="produtos.php?editar=<?= $p['id'] ?>">Editar</a>
                        <a href="produtos.php?excluir=<?= $p['id'] ?>" onclick="return confirm('Deseja realmente excluir este produto?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum produto cadastrado.</p>
    <?php endif; ?>
</body>
</html>