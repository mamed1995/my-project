<?php
// admin_dashboard.php
require_once 'config.php';
protectPage('admin'); 

// جلب إجماليات اللوحة الرئيسية
$stats = [
    'total_branches' => 0, 'total_invoices' => 0,
    'total_syp_revenue' => 0, 'total_usd_revenue' => 0
];
$sql_stats = "
    SELECT 
        (SELECT COUNT(*) FROM branches) as total_branches,
        (SELECT COUNT(*) FROM invoices) as total_invoices,
        (SELECT SUM(amount) FROM invoices WHERE currency = 'SYP' AND status = 'paid') as total_syp_revenue,
        (SELECT SUM(amount) FROM invoices WHERE currency = 'USD' AND status = 'paid') as total_usd_revenue
";
$stats_result = $pdo->query($sql_stats)->fetch(PDO::FETCH_ASSOC);
$stats = array_merge($stats, $stats_result);

// جلب آخر الفواتير
$latest_invoices = [];
$sql_latest = "SELECT i.*, b.branch_name FROM invoices i JOIN branches b ON i.branch_id = b.id ORDER BY i.invoice_date DESC LIMIT 5";
$latest_invoices = $pdo->query($sql_latest)->fetchAll(PDO::FETCH_ASSOC);

renderHeader('لوحة تحكم المدير', 'admin');
?>

<h1 class="mb-4">لوحة تحكم المدير</h1>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">إجمالي الفروع</p>
            <h2 class="text-primary"><?php echo $stats['total_branches']; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">إجمالي الفواتير</p>
            <h2 class="text-info"><?php echo $stats['total_invoices']; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">الإيرادات (ل.س)</p>
            <h2 class="text-success"><?php echo number_format($stats['total_syp_revenue'], 0); ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">الإيرادات ($)</p>
            <h2 class="text-warning"><?php echo number_format($stats['total_usd_revenue'], 2); ?></h2>
        </div>
    </div>
</div>

<div class="card p-4">
    <h2 class="mb-4">الفواتير الأخيرة</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>المشترك</th>
                    <th>الفرع</th>
                    <th>المبلغ</th>
                    <th>الشهر</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest_invoices as $inv): ?>
                <tr>
                    <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($inv['branch_name']); ?></td>
                    <td><?php echo number_format($inv['amount'], 0) . ' ' . ($inv['currency'] == 'SYP' ? 'ل.س' : '$'); ?></td>
                    <td><?php echo date('m/Y', strtotime($inv['subscription_month'])); ?></td>
                    <td><span class="badge bg-<?php echo $inv['status'] == 'paid' ? 'success' : ($inv['status'] == 'late' ? 'danger' : 'warning'); ?>"><?php echo $inv['status']; ?></span></td>
                    <td><?php echo date('Y/m/d', strtotime($inv['invoice_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>