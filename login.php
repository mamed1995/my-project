<?php
// login.php
require_once 'config.php';

// توجيه إذا كان المستخدم مسجل دخوله مسبقاً
if (isset($_SESSION['user_type'])) {
    redirect($_SESSION['user_type'] === 'admin' ? 'admin_dashboard.php' : 'branch_dashboard.php');
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST['user_type'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "الرجاء إدخال جميع البيانات المطلوبة.";
    } else {
        $sql = "SELECT id, password, user_type, branch_id FROM users WHERE username = :username AND user_type = :user_type AND status = 'active'";
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR);
            
            if ($stmt->execute() && $stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // هنا يتم التحقق من كلمة المرور المشفرة
                if (password_verify($password, $user['password'])) { 
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['branch_id'] = $user['branch_id'];

                    redirect($user['user_type'] === 'admin' ? 'admin_dashboard.php' : 'branch_dashboard.php');
                } else {
                    $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
                }
            } else {
                $error = "المستخدم غير موجود أو غير نشط.";
            }
            unset($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظام إدارة شركة الإنترنت - تسجيل الدخول</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <style>
        body { background: linear-gradient(135deg, #4e54c8, #8f94fb); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .login-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); width: 100%; max-width: 400px; }
        h2 { font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <h2 class="mb-1">نظام إدارة شركة الإنترنت</h2>
        <p class="mb-4 text-muted">مرحباً بك في نظام إدارة الفروع والفواتير</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="user_type" class="form-label d-block text-end">نوع المستخدم</label>
                <select name="user_type" id="user_type" class="form-select" required>
                    <option value="admin">مدير النظام</option>
                    <option value="branch">فرع</option>
                </select>
            </div>
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="أدخل اسم المستخدم" required>
            </div>
            <div class="mb-4">
                <input type="password" name="password" class="form-control" placeholder="أدخل كلمة المرور" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg">تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>