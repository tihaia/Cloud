
<?php

require "config-master.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = $_POST["title"];
    $status = $_POST["status"];
    $category = $_POST["category"];

    $sql = "INSERT INTO todos (title, status, category_id) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $status, $category]);

    header("Location: index.php");
    exit;
}

$cats = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Создание задачи</h2>

<form method="POST">
    <p>
        Название: <br>
        <input type="text" name="title" required>
 </p>

    <p>
        Категория: <br>
        <select name="category">
            <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        Статус: <br>
        <select name="status">
            <option value="pending">В ожидании</option>
            <option value="in_progress">В процессе</option>
            <option value="completed">Завершено</option>
        </select>
    </p>

    <button type="submit">Создать</button>
</form>

<p><a href="index.php">Вернуться назад</a></p>