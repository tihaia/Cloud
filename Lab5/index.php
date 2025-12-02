
<?php

require "config-master.php";

$query = $pdo->query("
    SELECT t.id, t.title, t.status, c.name AS category
    FROM todos t
    LEFT JOIN categories c ON c.id = t.category_id
    ORDER BY t.id ASC
");

$tasks = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Задачи</h2>

<p><a href="create.php">Добавить новую задачу</a></p>

<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Категория</th>
        <th>Статус</th>
        <th></th>
    </tr>
<?php foreach ($tasks as $t): ?>
        <tr>
            <td><?= $t['id'] ?></td>
            <td><?= htmlspecialchars($t['title']) ?></td>
            <td><?= $t['category'] ?></td>
            <td><?= $t['status'] ?></td>
            <td><a href="delete.php?id=<?= $t['id'] ?>">Удалить</a></td>
        </tr>
    <?php endforeach; ?>
</table>