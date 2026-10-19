<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

// --- Add / update / delete cab ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO cabs (name, model, plate_number, driver_name, driver_phone, seats, fare_per_km, base_fare, image) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['name']), trim($_POST['model']), trim($_POST['plate_number']),
            trim($_POST['driver_name']), trim($_POST['driver_phone']),
            (int)$_POST['seats'], (float)$_POST['fare_per_km'], (float)$_POST['base_fare'],
            trim($_POST['image'])
        ]);
        header('Location: cabs.php?msg=Cab added successfully');
        exit;
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE cabs SET name=?, model=?, plate_number=?, driver_name=?, driver_phone=?, seats=?, fare_per_km=?, base_fare=?, image=?, status=? WHERE id=?');
        $stmt->execute([
            trim($_POST['name']), trim($_POST['model']), trim($_POST['plate_number']),
            trim($_POST['driver_name']), trim($_POST['driver_phone']),
            (int)$_POST['seats'], (float)$_POST['fare_per_km'], (float)$_POST['base_fare'],
            trim($_POST['image']), $_POST['status'] === 'busy' ? 'busy' : 'available',
            (int)$_POST['id']
        ]);
        header('Location: cabs.php?msg=Cab updated successfully');
        exit;
    }
}

if (isset($_GET['delete'])) {
    verifyCsrf();
    $pdo->prepare('DELETE FROM cabs WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: cabs.php?msg=Cab deleted');
    exit;
}

$editing = isset($_GET['edit']) ? getCab($pdo, (int)$_GET['edit']) : null;
$cabs = getCabs($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Cabs — DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container">
    <h2 class="section-title">Manage Cabs</h2>

    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:30px;">
        <h3><?= $editing ? 'Edit Cab #' . (int)$editing['id'] : 'Add New Cab' ?></h3>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
            <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
            <div class="form-row">
                <div><label>Cab Name</label><input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
                <div><label>Model</label><input type="text" name="model" value="<?= e($editing['model'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div><label>Plate Number</label><input type="text" name="plate_number" value="<?= e($editing['plate_number'] ?? '') ?>"></div>
                <div><label>Driver Name</label><input type="text" name="driver_name" value="<?= e($editing['driver_name'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div><label>Driver Phone</label><input type="text" name="driver_phone" value="<?= e($editing['driver_phone'] ?? '') ?>"></div>
                <div><label>Seats</label><input type="number" name="seats" min="1" value="<?= e($editing['seats'] ?? 4) ?>"></div>
            </div>
            <div class="form-row">
                <div><label>Base Fare (₹)</label><input type="number" name="base_fare" min="0" step="0.01" value="<?= e($editing['base_fare'] ?? 50) ?>" required></div>
                <div><label>Fare per km (₹)</label><input type="number" name="fare_per_km" min="0" step="0.01" value="<?= e($editing['fare_per_km'] ?? 12) ?>" required></div>
            </div>
            <div class="form-row">
                <div><label>Image URL (optional)</label><input type="text" name="image" value="<?= e($editing['image'] ?? '') ?>"></div>
                <div><label>Status</label>
                    <select name="status">
                        <option value="available" <?= ($editing['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="busy" <?= ($editing['status'] ?? '') === 'busy' ? 'selected' : '' ?>>Busy</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn"><?= $editing ? 'Update Cab' : 'Add Cab' ?></button>
            <?php if ($editing): ?><a href="cabs.php" class="btn" style="background:#888;color:#fff;">Cancel Edit</a><?php endif; ?>
        </form>
    </div>

    <table class="admin-table">
        <tr><th>ID</th><th>Name</th><th>Model</th><th>Plate</th><th>Driver</th><th>Seats</th><th>Fares</th><th>Status</th><th>Actions</th></tr>
        <?php foreach ($cabs as $cab): ?>
        <tr>
            <td>#<?= (int)$cab['id'] ?></td>
            <td><?= e($cab['name']) ?></td>
            <td><?= e($cab['model']) ?></td>
            <td><?= e($cab['plate_number']) ?></td>
            <td><?= e($cab['driver_name']) ?><br><small><?= e($cab['driver_phone']) ?></small></td>
            <td><?= (int)$cab['seats'] ?></td>
            <td>₹<?= number_format((float)$cab['base_fare'], 2) ?> + ₹<?= number_format((float)$cab['fare_per_km'], 2) ?>/km</td>
            <td><span class="badge <?= e($cab['status']) ?>"><?= e(ucfirst($cab['status'])) ?></span></td>
            <td class="actions">
                <a class="complete" href="cabs.php?edit=<?= (int)$cab['id'] ?>">Edit</a>
                <a class="delete" href="cabs.php?delete=<?= (int)$cab['id'] ?>&<?= csrfQuery() ?>" onclick="return confirm('Delete this cab and its bookings?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
