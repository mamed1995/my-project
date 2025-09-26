<?php
// branch_dashboard.php (الإصدار النهائي بإضافة المستحقات)
require_once 'config.php';
protectPage('branch'); 

// جلب ID الفرع الحالي من الجلسة
$branch_id = $_SESSION['branch_id'];
$branch_name = getBranchName($pdo, $branch_id);

// ------------------------------------------------
// 1. جلب إجماليات الفرع الحالي
// ------------------------------------------------
$stats = [
    'total_invoices' => 0, 'paid_invoices' => 0, 'pending_invoices' => 0, 'late_invoices' => 0,
    'syp_paid_revenue' => 0, 'usd_paid_revenue' => 0, // الإيرادات المحصلة (المدفوعة)
    'syp_outstanding_revenue' => 0, 'usd_outstanding_revenue' => 0 // المبالغ المستحقة (غير المدفوعة)
];
$sql_stats = "
    SELECT 
        COUNT(id) as total_invoices,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_invoices,
        
        -- الإيرادات المحصلة (المدفوعة)
        SUM(CASE WHEN currency = 'SYP' AND status = 'paid' THEN amount ELSE 0 END) as syp_paid_revenue,
        SUM(CASE WHEN currency = 'USD' AND status = 'paid' THEN amount ELSE 0 END) as usd_paid_revenue,
        
        -- المبالغ المستحقة (غير المدفوعة)
        SUM(CASE WHEN currency = 'SYP' AND status != 'paid' THEN amount ELSE 0 END) as syp_outstanding_revenue,
        SUM(CASE WHEN currency = 'USD' AND status != 'paid' THEN amount ELSE 0 END) as usd_outstanding_revenue
        
    FROM 
        invoices 
    WHERE 
        branch_id = :bid
"; 
$stmt = $pdo->prepare($sql_stats);
$stmt->bindParam(':bid', $branch_id, PDO::PARAM_INT);
$stmt->execute();
$stats_result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats = array_merge($stats, $stats_result);
unset($stmt);


// ------------------------------------------------
// 2. جلب آخر 5 فواتير لهذا الفرع
// ------------------------------------------------
$latest_invoices = [];
$sql_latest = "
    SELECT 
        id, customer_name, amount, currency, subscription_month, status, invoice_date, due_date
    FROM 
        invoices 
    WHERE 
        branch_id = :bid 
    ORDER BY 
        invoice_date DESC 
    LIMIT 5
";
$stmt_latest = $pdo->prepare($sql_latest);
$stmt_latest->bindParam(':bid', $branch_id, PDO::PARAM_INT);
$stmt_latest->execute();
$latest_invoices = $stmt_latest->fetchAll(PDO::FETCH_ASSOC);
unset($stmt_latest);


// ------------------------------------------------
// 3. جلب معلومات الفرع
// ------------------------------------------------
$branch_info = [];
$sql_info = "SELECT default_currency, creation_date FROM branches WHERE id = :bid";
$stmt_info = $pdo->prepare($sql_info);
$stmt_info->bindParam(':bid', $branch_id, PDO::PARAM_INT);
$stmt_info->execute();
$branch_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
unset($stmt_info);


renderHeader('لوحة تحكم الفرع', 'branch', $branch_name);
?>

<h1 class="mb-4">لوحة تحكم الفرع</h1>

<div class="row g-4 mb-5">
    <div class="col-md-2">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">إجمالي الفواتير</p>
            <h2 class="text-primary"><?php echo number_format($stats['total_invoices']); ?></h2>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">فواتير مدفوعة</p>
            <h2 class="text-success"><?php echo number_format($stats['paid_invoices']); ?></h2>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">إيرادات محصلة (ل.س)</p>
            <h2 class="text-info"><?php echo number_format($stats['syp_paid_revenue'], 0); ?> SYP</h2>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">المستحقات (ل.س)</p>
            <h2 class="text-danger"><?php echo number_format($stats['syp_outstanding_revenue'], 0); ?> SYP</h2>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">إيرادات محصلة ($)</p>
            <h2 class="text-warning"><?php echo number_format($stats['usd_paid_revenue'], 2); ?> USD</h2>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm text-center">
            <p class="text-muted mb-1">المستحقات ($)</p>
            <h2 class="text-danger"><?php echo number_format($stats['usd_outstanding_revenue'], 2); ?> USD</h2>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card p-4 shadow-sm">
            <h4 class="mb-3">حالة الفواتير</h4>
            <ul class="list-unstyled mb-0">
                <li class="d-flex justify-content-between">
                    <span>معلقة:</span> 
                    <span class="text-warning fw-bold"><?php echo number_format($stats['pending_invoices']); ?></span>
                </li>
                <li class="d-flex justify-content-between">
                    <span>متأخرة:</span> 
                    <span class="text-danger fw-bold"><?php echo number_format($stats['late_invoices']); ?></span>
                </li>
                <li class="d-flex justify-content-between">
                    <span>مدفوعة:</span> 
                    <span class="text-success fw-bold"><?php echo number_format($stats['paid_invoices']); ?></span>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-4 shadow-sm">
            <h4 class="mb-3">معلومات الفرع</h4>
            <div class="d-flex justify-content-between mb-2">
                <span>العملة الافتراضية:</span>
                <span class="fw-bold"><?php echo htmlspecialchars($branch_info['default_currency'] ?? 'N/A'); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>الشهر الحالي:</span>
                <span class="fw-bold"><?php echo date('m/Y'); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>تاريخ الإنشاء:</span>
                <span class="fw-bold"><?php echo date('Y/m/d', strtotime($branch_info['creation_date'] ?? date('Y-m-d'))); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card p-4">
    <h2 class="mb-4">الفواتير الأخيرة</h2>
    <?php if (!empty($latest_invoices)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>المشترك</th>
                    <th>المبلغ</th>
                    <th>الشهر</th>
                    <th>الحالة</th>
                    <th>تاريخ الاستحقاق</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest_invoices as $inv): ?>
                <tr>
                    <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                    <td><?php echo number_format($inv['amount'], 0) . ' ' . $inv['currency']; ?></td>
                    <td><?php echo date('m/Y', strtotime($inv['subscription_month'])); ?></td>
                    <td><span class="badge bg-<?php 
                        echo $inv['status'] == 'paid' ? 'success' : 
                             ($inv['status'] == 'late' ? 'danger' : 'warning'); 
                    ?>"><?php echo $inv['status']; ?></span></td>
                    <td><?php echo date('Y/m/d', strtotime($inv['due_date'])); ?></td>
                    <td>
                        <a href="branch_edit_invoice.php?id=<?php echo $inv['id']; ?>" class="text-primary">تعديل</a> | 
                        <a href="branch_delete_invoice.php?id=<?php echo $inv['id']; ?>" class="text-danger">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-info text-center">لم يتم إضافة فواتير لهذا الفرع بعد.</div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>