<?php
// branch_add_invoice.php
require_once 'config.php';
protectPage('branch'); 

$branch_id = $_SESSION['branch_id'];
$branch_name = getBranchName($pdo, $branch_id);
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_invoice'])) {
    $customer_name = trim($_POST['customer_name']);
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $due_date = $_POST['due_date'];

    $subscription_month = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
    
    // التحقق الأساسي
    if (!empty($customer_name) && is_numeric($amount) && $amount > 0) {
        $sql = "INSERT INTO invoices (branch_id, customer_name, amount, currency, subscription_month, due_date, status) VALUES (:bid, :cname, :amount, :currency, :smonth, :ddate, 'pending')";
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(':bid', $branch_id, PDO::PARAM_INT);
            $stmt->bindParam(':cname', $customer_name, PDO::PARAM_STR);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':currency', $currency, PDO::PARAM_STR);
            $stmt->bindParam(':smonth', $subscription_month, PDO::PARAM_STR);
            $stmt->bindParam(':ddate', $due_date, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">تم إضافة الفاتورة بنجاح!</div>';
            } else {
                $message = '<div class="alert alert-danger">حدث خطأ ما. يرجى المحاولة مرة أخرى.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning">يرجى تعبئة جميع الحقول بشكل صحيح.</div>';
    }
}

renderHeader('إضافة فاتورة', 'branch', $branch_name);
?>

<h1 class="mb-4">إضافة فاتورة جديدة</h1>
<?php echo $message; ?>

<div class="card p-4">
    <form action="branch_add_invoice.php" method="POST">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">* اسم المشترك</label>
                <input type="text" name="customer_name" class="form-control" placeholder="أدخل اسم المشترك" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">* المبلغ</label>
                <input type="number" step="0.01" name="amount" class="form-control" placeholder="أدخل المبلغ" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-bold">العملة</label>
                <select name="currency" class="form-select" required>
                    <option value="SYP" selected>ليرة سورية (ل.س)</option>
                    <option value="USD">دولار ($)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">الشهر</label>
                <select name="month" class="form-select" required>
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (int)date('m') == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">السنة</label>
                <select name="year" class="form-select" required>
                    <?php for($y=date('Y'); $y<=date('Y')+2; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo date('Y') == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">تاريخ الاستحقاق</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
            </div>
        </div>
        
        <button type="submit" name="add_invoice" class="btn btn-success mt-4 float-end">إضافة الفاتورة</button>
    </form>
</div>

<?php renderFooter(); ?>