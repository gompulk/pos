<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/Auth.php';

$pdo = Database::connection();
$page = request('page', 'dashboard');
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate(request('_csrf'))) {
    http_response_code(419);
    exit('CSRF token tidak valid.');
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::attempt($pdo, (string) request('email'), (string) request('password'))) {
        redirect('index.php?page=dashboard');
    }
    set_flash('Login gagal, cek email/password.', 'danger');
    redirect('index.php?page=login');
}

if ($page === 'logout') {
    Auth::logout();
    redirect('index.php?page=login');
}

if (!Auth::check() && $page !== 'login' && $page !== 'ai-webhook') {
    redirect('index.php?page=login');
}

if ($page === 'products' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = request('action');

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO products (sku, name, category, price, stock, unit, is_active, created_at, updated_at) VALUES (:sku, :name, :category, :price, :stock, :unit, 1, NOW(), NOW())');
        $stmt->execute([
            'sku' => request('sku'), 'name' => request('name'), 'category' => request('category'), 'price' => request('price'),
            'stock' => request('stock'), 'unit' => request('unit', 'pcs'),
        ]);
        if (request('ai_review')) {
            create_ai_task($pdo, 'product_created', ['sku' => request('sku'), 'name' => request('name')]);
        }
        set_flash('Produk berhasil ditambahkan.');
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE products SET sku=:sku, name=:name, category=:category, price=:price, stock=:stock, unit=:unit, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => request('id'), 'sku' => request('sku'), 'name' => request('name'), 'category' => request('category'),
            'price' => request('price'), 'stock' => request('stock'), 'unit' => request('unit', 'pcs'),
        ]);
        if (request('ai_review')) {
            create_ai_task($pdo, 'product_updated', ['product_id' => request('id')]);
        }
        set_flash('Produk berhasil diupdate.');
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id=:id');
        $stmt->execute(['id' => request('id')]);
        set_flash('Produk dihapus.', 'warning');
    }
    redirect('index.php?page=products');
}

if ($page === 'inventory' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, qty, notes, created_at, user_id) VALUES (:product_id, :movement_type, :qty, :notes, NOW(), :user_id)');
    $stmt->execute([
        'product_id' => request('product_id'),
        'movement_type' => request('movement_type'),
        'qty' => request('qty'),
        'notes' => request('notes'),
        'user_id' => Auth::user()['id'],
    ]);

    $sign = request('movement_type') === 'in' ? '+' : '-';
    $pdo->prepare("UPDATE products SET stock = stock {$sign} :qty, updated_at=NOW() WHERE id=:id")
        ->execute(['qty' => request('qty'), 'id' => request('product_id')]);

    if (request('ai_review')) {
        create_ai_task($pdo, 'stock_adjustment', ['product_id' => request('product_id'), 'qty' => request('qty')]);
    }

    set_flash('Pergerakan stok tersimpan.');
    redirect('index.php?page=inventory');
}

if ($page === 'sales' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $pdo->beginTransaction();
    try {
        $total = 0;
        foreach ($items as $item) {
            $total += ((float) $item['price'] * (int) $item['qty']);
        }

        $pdo->prepare('INSERT INTO sales (invoice_no, customer_name, total_amount, paid_amount, payment_method, created_at, user_id) VALUES (:invoice_no, :customer_name, :total_amount, :paid_amount, :payment_method, NOW(), :user_id)')
            ->execute([
                'invoice_no' => 'INV-' . date('YmdHis'),
                'customer_name' => request('customer_name', 'Umum'),
                'total_amount' => $total,
                'paid_amount' => request('paid_amount', $total),
                'payment_method' => request('payment_method', 'cash'),
                'user_id' => Auth::user()['id'],
            ]);

        $saleId = (int) $pdo->lastInsertId();
        $insertItem = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, qty, price, subtotal) VALUES (:sale_id, :product_id, :qty, :price, :subtotal)');

        foreach ($items as $item) {
            $subtotal = ((float) $item['price'] * (int) $item['qty']);
            $insertItem->execute([
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
            ]);
            $pdo->prepare('UPDATE products SET stock = stock - :qty, updated_at=NOW() WHERE id=:id')
                ->execute(['qty' => $item['qty'], 'id' => $item['product_id']]);
        }

        if (request('ai_review')) {
            create_ai_task($pdo, 'sale_created', ['sale_id' => $saleId, 'total' => $total]);
        }

        $pdo->commit();
        set_flash('Transaksi berhasil dibuat.');
    } catch (Throwable $exception) {
        $pdo->rollBack();
        set_flash('Transaksi gagal: ' . $exception->getMessage(), 'danger');
    }
    redirect('index.php?page=sales');
}

if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'business_name' => request('business_name'),
        'business_type' => request('business_type'),
        'address' => request('address'),
        'phone' => request('phone'),
        'modules' => request('modules', []),
    ];

    $pdo->prepare('UPDATE settings SET settings_json=:settings_json, updated_at=NOW() WHERE id=1')
        ->execute(['settings_json' => json_encode($settings, JSON_UNESCAPED_UNICODE)]);

    set_flash('Pengaturan tersimpan.');
    redirect('index.php?page=settings');
}

if ($page === 'ai-webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret = $_SERVER['HTTP_X_POS_AI_SECRET'] ?? '';
    if ($secret !== app_config()['ai']['webhook_secret']) {
        http_response_code(401);
        exit('Unauthorized');
    }
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $stmt = $pdo->prepare('INSERT INTO ai_suggestions (task_id, provider_name, suggestion_text, created_at) VALUES (:task_id, :provider_name, :suggestion_text, NOW())');
    $stmt->execute([
        'task_id' => $payload['task_id'] ?? null,
        'provider_name' => $payload['provider'] ?? 'unknown',
        'suggestion_text' => $payload['suggestion'] ?? '',
    ]);
    exit('OK');
}

$title = ucfirst((string) $page);
include __DIR__ . '/../templates/header.php';

if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif;

if ($page === 'login'): ?>
    <div class="row justify-content-center">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Masuk ke POS Flex</h4>
                    <form method="post" action="<?= base_url('index.php?page=login') ?>">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
                        <button class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($page === 'dashboard'):
    $summary = [
        'produk' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'stok_rendah' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 5')->fetchColumn(),
        'penjualan_hari_ini' => (float) $pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
        'task_ai_pending' => (int) $pdo->query("SELECT COUNT(*) FROM ai_tasks WHERE status='pending'")->fetchColumn(),
    ];
?>
    <h3>Dashboard</h3>
    <div class="row g-3 mt-1">
        <div class="col-md-3"><div class="card p-3 stat-card"><div>Total Produk</div><h2><?= $summary['produk'] ?></h2></div></div>
        <div class="col-md-3"><div class="card p-3 stat-card"><div>Stok Rendah</div><h2><?= $summary['stok_rendah'] ?></h2></div></div>
        <div class="col-md-3"><div class="card p-3 stat-card"><div>Omzet Hari Ini</div><h2><?= rupiah($summary['penjualan_hari_ini']) ?></h2></div></div>
        <div class="col-md-3"><div class="card p-3 stat-card"><div>AI Tasks</div><h2><?= $summary['task_ai_pending'] ?></h2></div></div>
    </div>
<?php elseif ($page === 'products'):
    $products = $pdo->query('SELECT * FROM products ORDER BY id DESC LIMIT 50')->fetchAll();
?>
    <div class="d-flex justify-content-between align-items-center mb-3"><h3>Produk</h3></div>
    <div class="card mb-4"><div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="create">
            <div class="col-md-2"><input class="form-control" name="sku" placeholder="SKU" required></div>
            <div class="col-md-3"><input class="form-control" name="name" placeholder="Nama produk" required></div>
            <div class="col-md-2"><input class="form-control" name="category" placeholder="Kategori"></div>
            <div class="col-md-2"><input class="form-control" type="number" name="price" placeholder="Harga" required></div>
            <div class="col-md-1"><input class="form-control" type="number" name="stock" placeholder="Stok" required></div>
            <div class="col-md-1"><input class="form-control" name="unit" value="pcs"></div>
            <div class="col-md-1 d-grid"><button class="btn btn-primary">Tambah</button></div>
            <div class="col-12 form-check"><input class="form-check-input" type="checkbox" name="ai_review" value="1" id="ai1"><label class="form-check-label" for="ai1">Trigger AI review</label></div>
        </form>
    </div></div>
    <div class="table-responsive"><table class="table table-striped align-middle">
        <tr><th>SKU</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= htmlspecialchars($product['sku']) ?></td><td><?= htmlspecialchars($product['name']) ?></td><td><?= htmlspecialchars($product['category']) ?></td>
                <td><?= rupiah((float) $product['price']) ?></td><td><?= (int) $product['stock'] ?> <?= htmlspecialchars($product['unit']) ?></td>
                <td>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus produk ini?')">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table></div>
<?php elseif ($page === 'inventory'):
    $products = $pdo->query('SELECT id, name, stock, unit FROM products ORDER BY name')->fetchAll();
    $movements = $pdo->query('SELECT sm.*, p.name AS product_name, u.name AS user_name FROM stock_movements sm JOIN products p ON p.id=sm.product_id LEFT JOIN users u ON u.id=sm.user_id ORDER BY sm.id DESC LIMIT 20')->fetchAll();
?>
    <h3>Inventory</h3>
    <div class="card mb-3"><div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="col-md-4"><select name="product_id" class="form-select"><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (stok: <?= $p['stock'] ?>)</option><?php endforeach; ?></select></div>
            <div class="col-md-2"><select name="movement_type" class="form-select"><option value="in">Barang Masuk</option><option value="out">Barang Keluar</option></select></div>
            <div class="col-md-2"><input type="number" name="qty" class="form-control" min="1" required></div>
            <div class="col-md-3"><input type="text" name="notes" class="form-control" placeholder="Catatan"></div>
            <div class="col-md-1 d-grid"><button class="btn btn-success">Simpan</button></div>
            <div class="col-12 form-check"><input class="form-check-input" type="checkbox" name="ai_review" value="1" id="ai2"><label class="form-check-label" for="ai2">Trigger AI optimization</label></div>
        </form>
    </div></div>
    <div class="table-responsive"><table class="table"><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>User</th><th>Catatan</th></tr><?php foreach($movements as $m): ?><tr><td><?= $m['created_at'] ?></td><td><?= htmlspecialchars($m['product_name']) ?></td><td><?= $m['movement_type'] ?></td><td><?= $m['qty'] ?></td><td><?= htmlspecialchars($m['user_name'] ?? '-') ?></td><td><?= htmlspecialchars($m['notes']) ?></td></tr><?php endforeach; ?></table></div>
<?php elseif ($page === 'sales'):
    $products = $pdo->query('SELECT id, name, price, stock FROM products WHERE is_active=1 ORDER BY name')->fetchAll();
    $recentSales = $pdo->query('SELECT * FROM sales ORDER BY id DESC LIMIT 15')->fetchAll();
?>
    <h3>Penjualan</h3>
    <div class="card mb-4"><div class="card-body">
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="row g-2 mb-2"><div class="col-md-6"><input class="form-control" name="customer_name" placeholder="Nama pelanggan (opsional)"></div><div class="col-md-3"><select class="form-select" name="payment_method"><option value="cash">Cash</option><option value="qris">QRIS</option><option value="transfer">Transfer</option></select></div><div class="col-md-3"><input class="form-control" type="number" name="paid_amount" placeholder="Jumlah dibayar"></div></div>
            <p class="text-muted small mb-1">Pilih item transaksi (maks 3 item untuk versi ringkas ini):</p>
            <?php for($i=0; $i<3; $i++): ?>
                <div class="row g-2 mb-2">
                    <div class="col-md-6"><select class="form-select" name="items[<?= $i ?>][product_id]"><option value="">-- Pilih produk --</option><?php foreach($products as $p): ?><option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['name']) ?> (stok <?= $p['stock'] ?>)</option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><input class="form-control" type="number" name="items[<?= $i ?>][qty]" placeholder="Qty"></div>
                    <div class="col-md-3"><input class="form-control" type="number" name="items[<?= $i ?>][price]" placeholder="Harga"></div>
                </div>
            <?php endfor; ?>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="ai_review" value="1" id="ai3"><label class="form-check-label" for="ai3">Trigger AI audit transaksi</label></div>
            <button class="btn btn-primary">Buat Transaksi</button>
        </form>
    </div></div>
    <div class="table-responsive"><table class="table table-striped"><tr><th>Invoice</th><th>Pelanggan</th><th>Total</th><th>Bayar</th><th>Metode</th><th>Tanggal</th></tr><?php foreach($recentSales as $s): ?><tr><td><?= $s['invoice_no'] ?></td><td><?= htmlspecialchars($s['customer_name']) ?></td><td><?= rupiah((float) $s['total_amount']) ?></td><td><?= rupiah((float) $s['paid_amount']) ?></td><td><?= $s['payment_method'] ?></td><td><?= $s['created_at'] ?></td></tr><?php endforeach; ?></table></div>
<?php elseif ($page === 'settings'):
    $settingsRaw = $pdo->query('SELECT settings_json FROM settings WHERE id=1')->fetchColumn();
    $settings = json_decode((string)$settingsRaw, true) ?: [];
?>
    <h3>Pengaturan</h3>
    <div class="card"><div class="card-body">
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nama Bisnis</label><input class="form-control" name="business_name" value="<?= htmlspecialchars($settings['business_name'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Tipe Bisnis</label><select class="form-select" name="business_type"><option>Kopi Shop</option><option>Minimarket</option><option>Kantin Sekolah</option><option>Retail Umum</option></select></div>
                <div class="col-md-8"><label class="form-label">Alamat</label><input class="form-control" name="address" value="<?= htmlspecialchars($settings['address'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label">No. Telepon</label><input class="form-control" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>"></div>
                <div class="col-12">
                    <label class="form-label">Modul Aktif</label>
                    <?php $mods = $settings['modules'] ?? []; ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="inventory" <?= in_array('inventory', $mods) ? 'checked' : '' ?>> Inventory</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="sales" <?= in_array('sales', $mods) ? 'checked' : '' ?>> Sales</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="ai" <?= in_array('ai', $mods) ? 'checked' : '' ?>> AI Assistant Trigger</div>
                </div>
            </div>
            <button class="btn btn-success mt-3">Simpan Pengaturan</button>
        </form>
    </div></div>
<?php else: ?>
    <div class="alert alert-warning">Halaman tidak ditemukan.</div>
<?php endif;

include __DIR__ . '/../templates/footer.php';
