<?php
// admin_add_branch.php
require_once 'config.php';
protectPage('admin'); 

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_branch'])) {
    $branch_name = trim($_POST['branch_name']);
    $branch_username = trim($_POST['branch_username']);
    $password = $_POST['password'];
    $default_currency = $_POST['default_currency'];

    // 1. تشفير كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $pdo->beginTransaction();

        // 2. إدخال الفرع في جدول branches
        $sql_branch = "INSERT INTO branches (branch_name, branch_username, default_currency) VALUES (:bname, :buser, :currency)";
        $stmt_branch = $pdo->prepare($sql_branch);
        $stmt_branch->bindParam(':bname', $branch_name, PDO::PARAM_STR);
        $stmt_branch->bindParam(':buser', $branch_username, PDO::PARAM_STR);
        $stmt_branch->bindParam(':currency', $default_currency, PDO::PARAM_STR);
        $stmt_branch->execute();
        
        $new_branch_id = $pdo->lastInsertId(); // جلب رقم الفرع الجديد

        // 3. إدخال المستخدم في جدول users
        $sql_user = "INSERT INTO users (username, password, user_type, branch_id) VALUES (:user, :pass, 'branch', :bid)";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->bindParam(':user', $branch_username, PDO::PARAM_STR);
        $stmt_user->bindParam(':pass', $hashed_password, PDO::PARAM_STR);
        $stmt_user->bindParam(':bid', $new_branch_id, PDO::PARAM_INT);
        $stmt_user->execute();

        $pdo->commit();
        $message = '<div class="alert alert-success">تم إضافة الفرع والمستخدم بنجاح! يمكن للفرع الدخول باسم: ' . htmlspecialchars($branch_username) . '</div>';

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // كود خطأ SQL للتكرار
             $message = '<div class="alert alert-danger">خطأ: اسم الفرع أو اسم المستخدم موجود مسبقاً.</div>';
        } else {
             $message = '<div class="alert alert-danger">حدث خطأ في قاعدة البيانات: ' . $e->getMessage() . '</div>';
        }
    }
}

renderHeader('إضافة فرع جديد', 'admin');
?>

<h1 class="mb-4">إضافة فرع جديد</h1>
<?php echo $message; ?>

<div class="card p-4">
    <form action="admin_add_branch.php" method="POST">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">اسم الفرع *</label>
                <input type="text" name="branch_name" class="form-control" placeholder="مثال: فرع دمشق" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">اسم مستخدم الفرع للدخول *</label>
                <input type="text" name="branch_username" class="form-control" placeholder="مثال: damascus_branch" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-bold">كلمة المرور للفرع *</label>
                <input type="password" name="password" class="form-control" placeholder="كلمة مرور الفرع" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">العملة الافتراضية</label>
                <select name="default_currency" class="form-select" required>
                    <option value="SYP" selected>ليرة سورية (SYP)</option>
                    <option value="USD">دولار (USD)</option>
                </select>
            </div>
        </div>
        
        <button type="submit" name="add_branch" class="btn btn-success mt-4 float-end">إنشاء الفرع وحساب الدخول</button>
    </form>
</div>

<?php renderFooter(); ?>