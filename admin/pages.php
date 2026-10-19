<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $slug = strtolower(trim($_POST['slug']));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug !== '') {
        $pdo->prepare('INSERT INTO pages (slug, title, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content)')
            ->execute([$slug, trim($_POST['title']), trim($_POST['content'])]);
    }
    header('Location: pages.php?msg=Page saved successfully');
    exit;
}

$slug = $_GET['slug'] ?? 'about';
$pages = $pdo->query('SELECT slug, title FROM pages ORDER BY slug')->fetchAll();
$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ?');
$stmt->execute([$slug]);
$page = $stmt->fetch();
$pageTitle = 'Edit Pages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Pages — DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container" style="max-width:860px;">
    <h2 class="section-title">Edit Page Content</h2>
    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>

    <p>
        <?php foreach ($pages as $p): ?>
        <a class="btn" style="padding:8px 16px;font-size:.85rem;<?= $p['slug'] === $slug ? '' : 'background:#ddd;color:#333;' ?>" href="pages.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a>
        <?php endforeach; ?>
        <a class="btn" style="padding:8px 16px;font-size:.85rem;background:#ddd;color:#333;" href="pages.php?slug=new">+ New Page</a>
    </p>

    <?php if (isset($_GET['slug']) && $_GET['slug'] === 'new'): ?>
    <div class="card">
        <h3>Create New Page</h3>
        <form method="post" action="pages.php">
            <?= csrfField() ?>
            <input type="hidden" name="slug" value="">
            <p style="color:#b71c1c;margin-bottom:10px;">Type the new page's URL slug in the field below (e.g. <b>careers</b>, <b>gallery</b>). The page file must be created by the developer; use this for editing existing pages first.</p>
            <label>Page Title</label>
            <input type="text" name="title" required>
            <label>Content (HTML allowed)</label>
            <textarea name="content" rows="8" style="width:100%;padding:11px;border:1px solid #ddd;border-radius:8px;font-size:1rem;font-family:inherit;"></textarea>
            <button type="submit" class="btn" onclick="this.form.slug.value=prompt('Enter URL slug for the new page (letters and dashes only):')||''; if(!this.form.slug.value) return false;">Create & Save</button>
        </form>
    </div>
    <?php elseif ($page): ?>
    <div class="card">
        <h3>Editing: <?= e($page['title']) ?> (slug: <code><?= e($page['slug']) ?></code>)</h3>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="slug" value="<?= e($page['slug']) ?>">
            <label>Page Title</label>
            <input type="text" name="title" value="<?= e($page['title']) ?>" required>
            <label>Content (HTML allowed)</label>
            <textarea name="content" rows="14" style="width:100%;padding:11px;border:1px solid #ddd;border-radius:8px;font-size:1rem;font-family:inherit;"><?= e($page['content']) ?></textarea>
            <button type="submit" class="btn">Save Page</button>
        </form>
    </div>
    <?php else: ?>
    <div class="card"><p>Page not found. Choose one above.</p></div>
    <?php endif; ?>
</div>

</body>
</html>
