<?php
// admin_all_invoices.php
require_once 'config.php';
protectPage('admin'); 

// ------------------------------------------------
// 1. معالجة التصفية والبحث
// ------------------------------------------------

// تحديد المتغيرات الافتراضية للتصفية
$filter_branch = $_GET['branch'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search_customer = $_GET['search'] ?? '';

$where_clauses = ["1=1"]; // نقطة بداية الاستعلام
$params = [];

// تصفية حسب الفرع
if ($filter_branch !== 'all' && is_numeric($filter_branch)) {
    $where_clauses[] = "i.branch_id = :branch_id";
    $params[':branch_id'] = $filter_branch;
}

// تصفية حسب الحالة
if ($filter_status !== 'all' && in_array($filter_status, ['pending', 'paid', 'late'])) {
    $where_clauses[] = "i.status = :status";
    $params[':status'] = $filter_status;
}

// البحث باسم المشترك
if (!empty($search_customer)) {
    $where_clauses[] = "i.customer_name LIKE :search_customer";
    $params[':search_customer'] = '%' . $search_customer . '%';
}

$where_sql = implode(' AND ', $where_clauses);


// ------------------------------------------------
// 2. جلب جميع الفواتير المطابقة للتصفية
// ------------------------------------------------
$invoices = [];
$sql_invoices = "
    SELECT 
        i.*, b.branch_name 
    FROM 
        invoices i
    JOIN 
        branches b ON i.branch_id = b.id
    WHERE 
        {$where_sql}
    ORDER BY 
        i.invoice_date DESC
";
$stmt = $pdo->prepare($sql_invoices);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------
// 3. جلب الإجماليات (بغض النظر عن التصفية الحالية)
// ------------------------------------------------
$totals = [
    'total_count' => 0, 'syp_sum' => 0, 'usd_sum' => 0
];
$sql_totals = "
    SELECT 
        COUNT(id) AS total_count,
        SUM(CASE WHEN currency = 'SYP' THEN amount ELSE 0 END) AS syp_sum,
        SUM(CASE WHEN currency = 'USD' THEN amount ELSE 0 END) AS usd_sum
    FROM 
        invoices
";
$totals_result = $pdo->query($sql_totals)->fetch(PDO::FETCH_ASSOC);
$totals = array_merge($totals, $totals_result);

// جلب قائمة الأفرع لملء قائمة التصفية المنسدلة
$all_branches = $pdo->query("SELECT id, branch_name FROM branches ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

renderHeader('جميع الفواتير', 'admin');
?>

<h1 class="mb-4">جميع الفواتير</h1>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm text-center bg-light">
            <p class="text-muted mb-1">إجمالي عدد الفواتير</p>
            <h2 class="text-primary"><?php echo $totals['total_count']; ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm text-center bg-light">
            <p class="text-muted mb-1">المجموع الكلي (ل.س)</p>
            <h2 class="text-success"><?php echo number_format($totals['syp_sum'], 0); ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm text-center bg-light">
            <p class="text-muted mb-1">المجموع الكلي ($)</p>
            <h2 class="text-warning"><?php echo number_format($totals['usd_sum'], 2); ?></h2>
        </div>
    </div>
</div>

<div class="card p-4 mb-4">
    <h4 class="mb-3">خيارات التصفية والبحث</h4>
    <form action="admin_all_invoices.php" method="GET" class="row g-3 align-items-end">
        
        <div class="col-md-4">
            <label for="branch_filter" class="form-label">تصفية حسب الفرع</label>
            <select id="branch_filter" name="branch" class="form-select">
                <option value="all" <?php echo $filter_branch === 'all' ? 'selected' : ''; ?>>جميع الأفرع</option>
                <?php foreach ($all_branches as $branch): ?>
                    <option value="<?php echo $branch['id']; ?>" <?php echo $filter_branch == $branch['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="status_filter" class="form-label">تصفية حسب الحالة</label>
            <select id="status_filter" name="status" class="form-select">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>جميع الحالات</option>
                <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>مدفوعة</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>معلقة</option>
                <option value="late" <?php echo $filter_status === 'late' ? 'selected' : ''; ?>>متأخرة</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="search_customer" class="form-label">البحث باسم المشترك</label>
            <input type="text" id="search_customer" name="search" class="form-control" value="<?php echo htmlspecialchars($search_customer); ?>" placeholder="أدخل اسم المشترك">
        </div>
        
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">تطبيق</button>
        </div>
    </form>
</div>

<h2>نتائج البحث (عدد الفواتير المعروضة: <?php echo count($invoices); ?>)</h2>
<?php if (!empty($invoices)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>المشترك</th>
                    <th>الفرع</th>
                    <th>المبلغ</th>
                    <th>الشهر</th>
                    <th>تاريخ الاستحقاق</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($inv['branch_name']); ?></td>
                    <td><?php echo number_format($inv['amount'], 2) . ' ' . $inv['currency']; ?></td>
                    <td><?php echo date('Y/m', strtotime($inv['subscription_month'])); ?></td>
                    <td><?php echo date('Y/m/d', strtotime($inv['due_date'])); ?></td>
                    <td><span class="badge bg-<?php 
                        echo $inv['status'] == 'paid' ? 'success' : 
                             ($inv['status'] == 'late' ? 'danger' : 'warning'); 
                    ?>"><?php echo $inv['status']; ?></span></td>
                    <td>
                        <a href="#" class="text-primary">عرض</a> | 
                        <a href="#" class="text-danger">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">لا توجد فواتير مطابقة لمعايير البحث المحددة.</div>
<?php endif; ?>

<?php renderFooter(); ?>