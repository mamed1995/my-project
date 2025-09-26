<?php
// branch_manage_invoices.php
require_once 'config.php';
protectPage('branch'); 

$branch_id = $_SESSION['branch_id'];
$branch_name = getBranchName($pdo, $branch_id);

// منطق جلب الفواتير (يمكن إضافة تصفية حسب الحالة والبحث هنا)
$sql = "SELECT id, customer_name, amount, currency, subscription_month, due_date, status FROM invoices WHERE branch_id = :bid ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':bid', $branch_id, PDO::PARAM_INT);
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('إدارة الفواتير', 'branch', $branch_name);
?>

<h1 class="mb-4">إدارة الفواتير</h1>

<div class="card p-3 mb-4">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">تصفية بالحالة</label>
            <select class="form-select">
                <option selected>جميع الحالات</option>
                <option>معلقة</option>
                <option>مدفوعة</option>
                <option>متأخرة</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">البحث باسم المشترك</label>
            <input type="text" class="form-control" placeholder="... ابحث عن مشترك">
        </div>
    </div>
</div>

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
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                <td><?php echo number_format($inv['amount'], 0) . ' ' . ($inv['currency'] == 'SYP' ? 'ل.س' : '$'); ?></td>
                <td><?php echo date('m/Y', strtotime($inv['subscription_month'])); ?></td>
                <td><span class="badge bg-<?php echo $inv['status'] == 'paid' ? 'success' : ($inv['status'] == 'late' ? 'danger' : 'warning'); ?>"><?php echo $inv['status']; ?></span></td>
                <td><?php echo date('Y/m/d', strtotime($inv['due_date'])); ?></td>
                <td>
                    <a href="branch_edit_invoice.php?id=<?php echo $inv['id']; ?>" class="text-primary">تعديل</a> |
                    <a href="branch_delete_invoice.php?id=<?php echo $inv['id']; ?>" class="text-danger" onclick="return confirm('هل أنت متأكد؟');">حذف</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>