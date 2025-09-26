<?php
// admin_reports.php
require_once 'config.php';
protectPage('admin'); 

// ------------------------------------------------
// 1. تحديد نطاق التقرير (الشهر والسنة)
// ------------------------------------------------
$current_year = date('Y');
$current_month = date('m');

// تحديد المدخلات من النموذج
$selected_year = $_GET['report_year'] ?? $current_year;
$selected_month = $_GET['report_month'] ?? 'all';

// بناء شرط SQL لتصفية التاريخ
$date_where_clause = "1=1";
$params = [];

if (is_numeric($selected_year)) {
    $date_where_clause = "YEAR(i.subscription_month) = :year";
    $params[':year'] = $selected_year;
}

if ($selected_month !== 'all' && is_numeric($selected_month)) {
    $date_where_clause .= " AND MONTH(i.subscription_month) = :month";
    $params[':month'] = $selected_month;
}


// ------------------------------------------------
// 2. جلب بيانات التقرير الرئيسية (مجمعة حسب الفرع)
// ------------------------------------------------
$report_data = [];
$sql_report = "
    SELECT 
        b.branch_name, 
        b.id AS branch_id,
        
        COUNT(i.id) AS total_invoices_count,
        SUM(CASE WHEN i.status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        
        SUM(CASE WHEN i.currency = 'SYP' AND i.status = 'paid' THEN i.amount ELSE 0 END) AS syp_paid_sum,
        SUM(CASE WHEN i.currency = 'USD' AND i.status = 'paid' THEN i.amount ELSE 0 END) AS usd_paid_sum,
        
        SUM(CASE WHEN i.currency = 'SYP' AND i.status != 'paid' THEN i.amount ELSE 0 END) AS syp_pending_sum,
        SUM(CASE WHEN i.currency = 'USD' AND i.status != 'paid' THEN i.amount ELSE 0 END) AS usd_pending_sum
        
    FROM 
        branches b
    LEFT JOIN 
        invoices i ON b.id = i.branch_id AND {$date_where_clause}
    GROUP BY 
        b.id, b.branch_name
    ORDER BY 
        b.branch_name
";

$stmt = $pdo->prepare($sql_report);
$stmt->execute($params);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ------------------------------------------------
// 3. حساب الإجماليات الكلية للتقرير
// ------------------------------------------------
$grand_totals = [
    'total_paid_syp' => 0, 'total_pending_syp' => 0,
    'total_paid_usd' => 0, 'total_pending_usd' => 0,
];

foreach ($report_data as $row) {
    $grand_totals['total_paid_syp'] += $row['syp_paid_sum'];
    $grand_totals['total_pending_syp'] += $row['syp_pending_sum'];
    $grand_totals['total_paid_usd'] += $row['usd_paid_sum'];
    $grand_totals['total_pending_usd'] += $row['usd_pending_sum'];
}


renderHeader('التقارير المالية', 'admin');
?>

<h1 class="mb-4">التقارير المالية والأداء</h1>

<div class="card p-4 mb-4">
    <h4 class="mb-3">تحديد نطاق التقرير</h4>
    <form action="admin_reports.php" method="GET" class="row g-3 align-items-end">
        
        <div class="col-md-4">
            <label for="report_year" class="form-label">السنة</label>
            <select id="report_year" name="report_year" class="form-select" required>
                <?php for($y = $current_year - 2; $y <= $current_year; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="col-md-4">
            <label for="report_month" class="form-label">الشهر</label>
            <select id="report_month" name="report_month" class="form-select">
                <option value="all" <?php echo $selected_month === 'all' ? 'selected' : ''; ?>>جميع الأشهر</option>
                <?php 
                $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                foreach ($months as $key => $month): 
                    $month_num = $key + 1;
                ?>
                    <option value="<?php echo $month_num; ?>" <?php echo $selected_month == $month_num ? 'selected' : ''; ?>>
                        <?php echo $month; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100">عرض التقرير</button>
        </div>
    </form>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card p-3 shadow-sm text-center border-success">
            <p class="text-muted mb-1">الإيرادات المحصلة (ل.س)</p>
            <h2 class="text-success"><?php echo number_format($grand_totals['total_paid_syp'], 0); ?> SYP</h2>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 shadow-sm text-center border-warning">
            <p class="text-muted mb-1">المستحقات المتبقية (ل.س)</p>
            <h2 class="text-danger"><?php echo number_format($grand_totals['total_pending_syp'], 0); ?> SYP</h2>
        </div>
    </div>
    </div>

<h2>الأداء المفصل حسب الفرع (للفترة المحددة)</h2>
<?php if (!empty($report_data)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>الفرع</th>
                    <th>إجمالي الفواتير</th>
                    <th>مدفوعة</th>
                    <th>معلقة</th>
                    <th>إيرادات محصلة (ل.س)</th>
                    <th>مستحقات متبقية (ل.س)</th>
                    <th>إيرادات محصلة ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                    <td><?php echo $row['total_invoices_count']; ?></td>
                    <td><span class="badge bg-success"><?php echo $row['paid_count']; ?></span></td>
                    <td><span class="badge bg-warning"><?php echo $row['pending_count']; ?></span></td>
                    <td><?php echo number_format($row['syp_paid_sum'], 0); ?></td>
                    <td><?php echo number_format($row['syp_pending_sum'], 0); ?></td>
                    <td><?php echo number_format($row['usd_paid_sum'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-info">
                <tr>
                    <th>الإجمالي الكلي</th>
                    <th>-</th>
                    <th>-</th>
                    <th>-</th>
                    <th><?php echo number_format($grand_totals['total_paid_syp'], 0); ?></th>
                    <th><?php echo number_format($grand_totals['total_pending_syp'], 0); ?></th>
                    <th><?php echo number_format($grand_totals['total_paid_usd'], 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">لا توجد بيانات متاحة للفترة المحددة.</div>
<?php endif; ?>

<?php renderFooter(); ?>