<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

// tab config: table => [title, fields(type), labels]
$tabs = [
    'stats' => [
        'title' => 'Homepage Stats',
        'table' => 'stats',
        'fields' => ['svalue' => 'text', 'label' => 'text'],
        'labels' => ['svalue' => 'Value (e.g. 12,000+)', 'label' => 'Label (e.g. Rides Completed)'],
    ],
    'steps' => [
        'title' => 'How It Works',
        'table' => 'steps',
        'fields' => ['step_no' => 'number', 'title' => 'text', 'description' => 'textarea'],
        'labels' => ['step_no' => 'Step Number', 'title' => 'Step Title', 'description' => 'Description'],
    ],
    'features' => [
        'title' => 'Why Choose Us',
        'table' => 'features',
        'fields' => ['icon' => 'text', 'title' => 'text', 'description' => 'textarea'],
        'labels' => ['icon' => 'Icon (emoji)', 'title' => 'Title', 'description' => 'Description'],
    ],
    'testimonials' => [
        'title' => 'Testimonials',
        'table' => 'testimonials',
        'fields' => ['name' => 'text', 'city' => 'text', 'rating' => 'number', 'review' => 'textarea'],
        'labels' => ['name' => 'Customer Name', 'city' => 'City', 'rating' => 'Rating (1-5)', 'review' => 'Review'],
    ],
];

$tab = $_GET['tab'] ?? 'stats';
if (!isset($tabs[$tab])) $tab = 'stats';
$cfg = $tabs[$tab];
$table = $cfg['table']; // whitelisted via $tabs keys

// --- add / update / delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $values = [];
    foreach ($cfg['fields'] as $field => $type) {
        $v = trim($_POST[$field] ?? '');
        $values[] = $type === 'number' ? (int)$v : $v;
    }
    $sort = (int)($_POST['sort'] ?? 0);
    $cols = array_keys($cfg['fields']);

    if (($_POST['action'] ?? '') === 'add') {
        $pdo->prepare("INSERT INTO $table (" . implode(',', $cols) . ", sort) VALUES (" . rtrim(str_repeat('?,', count($cols)), ',') . ",?)")
            ->execute(array_merge($values, [$sort]));
    } elseif (($_POST['action'] ?? '') === 'update') {
        $pdo->prepare("UPDATE $table SET " . implode('=?,', $cols) . "=?, sort=? WHERE id=?")
            ->execute(array_merge($values, [$sort, (int)$_POST['id']]));
    }
    header("Location: sections.php?tab=$tab&msg=Saved successfully");
    exit;
}

if (isset($_GET['delete'])) {
    verifyCsrf();
    $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: sections.php?tab=$tab&msg=Item deleted");
    exit;
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$items = getContent($pdo, $table);
$pageTitle = 'Homepage Sections';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Homepage Sections — DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container" style="max-width:960px;">
    <h2 class="section-title">Homepage Sections</h2>
    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>

    <p>
        <?php foreach ($tabs as $key => $t): ?>
        <a class="btn" style="padding:8px 14px;font-size:.85rem;<?= $key === $tab ? '' : 'background:#ddd;color:#333;' ?>" href="sections.php?tab=<?= e($key) ?>"><?= e($t['title']) ?></a>
        <?php endforeach; ?>
    </p>

    <div class="card" style="margin:25px 0;">
        <h3><?= $editing ? 'Edit Item #' . (int)$editing['id'] : 'Add New — ' . e($cfg['title']) ?></h3>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
            <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
            <?php foreach ($cfg['fields'] as $field => $type): ?>
            <label><?= e($cfg['labels'][$field]) ?></label>
            <?php if ($type === 'textarea'): ?>
            <textarea name="<?= e($field) ?>" rows="3" style="width:100%;padding:11px;border:1px solid #ddd;border-radius:8px;font-size:1rem;font-family:inherit;"><?= e($editing[$field] ?? '') ?></textarea>
            <?php else: ?>
            <input type="<?= $type === 'number' ? 'number' : 'text' ?>" name="<?= e($field) ?>" value="<?= e($editing[$field] ?? '') ?>" <?= $field !== 'city' && $field !== 'icon' ? 'required' : '' ?>>
            <?php endif; ?>
            <?php endforeach; ?>
            <label>Sort Order (display order)</label>
            <input type="number" name="sort" value="<?= (int)($editing['sort'] ?? 0) ?>">
            <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Item' ?></button>
            <?php if ($editing): ?><a href="sections.php?tab=<?= e($tab) ?>" class="btn" style="background:#888;color:#fff;">Cancel</a><?php endif; ?>
        </form>
    </div>

    <table class="admin-table">
        <tr><th>ID</th><th>Summary</th><th>Sort</th><th>Actions</th></tr>
        <?php if (!$items): ?>
        <tr><td colspan="4" style="text-align:center;padding:25px;">No items yet — add one above.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $it): ?>
        <tr>
            <td>#<?= (int)$it['id'] ?></td>
            <td>
                <?php if (!empty($it['icon'])): ?><?= e($it['icon']) ?> <?php endif; ?>
                <?php if (!empty($it['step_no'])): ?><b>Step <?= (int)$it['step_no'] ?>:</b> <?php endif; ?>
                <?php if (!empty($it['svalue'])): ?><b><?= e($it['svalue']) ?></b> — <?php endif; ?>
                <b><?= e($it['title'] ?? $it['name'] ?? $it['label'] ?? '') ?></b>
                <?php if (!empty($it['city'])): ?> (<?= e($it['city']) ?>, <?= (int)$it['rating'] ?>⭐)<?php endif; ?>
                <br><small><?= e($it['description'] ?? $it['review'] ?? '') ?></small>
            </td>
            <td><?= (int)$it['sort'] ?></td>
            <td class="actions">
                <a class="complete" href="sections.php?tab=<?= e($tab) ?>&edit=<?= (int)$it['id'] ?>">Edit</a>
                <a class="delete" href="sections.php?tab=<?= e($tab) ?>&delete=<?= (int)$it['id'] ?>&<?= csrfQuery() ?>" onclick="return confirm('Delete this item?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
