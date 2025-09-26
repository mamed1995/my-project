<?php
// config.php

session_start();

// تعريف ثوابت قاعدة البيانات
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');     
define('DB_NAME', 'internet_management_system'); 

// إنشاء اتصال PDO
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// دالة للتوجيه
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// دالة حماية الصفحات
function protectPage($required_type) {
    if (!isset($_SESSION['user_type'])) {
        redirect('login.php');
    }
    if ($required_type === 'admin' && $_SESSION['user_type'] !== 'admin') {
        redirect('branch_dashboard.php'); 
    }
    if ($required_type === 'branch' && $_SESSION['user_type'] !== 'branch') {
        redirect('admin_dashboard.php'); 
    }
}

// جلب اسم الفرع
function getBranchName($pdo, $branch_id) {
    $sql = "SELECT branch_name FROM branches WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $branch_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() ?? 'غير معروف';
}

// قالب الرأس المشترك
function renderHeader($title, $type, $branchName = '') {
    $colorClass = $type === 'admin' ? 'bg-primary' : 'bg-success';
    $links = $type === 'admin' ? [
        'الرئيسية' => 'admin_dashboard.php',
        'إدارة الفروع' => 'admin_branches.php',
        'جميع الفواتير' => 'admin_all_invoices.php',
        'التقارير' => 'admin_reports.php'
    ] : [
        'الرئيسية' => 'branch_dashboard.php',
        'إدارة الفواتير' => 'branch_manage_invoices.php',
        'إضافة فاتورة' => 'branch_add_invoice.php'
    ];
    $userDisplay = $type === 'admin' ? 'admin' : "فرع: $branchName";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg <?php echo $colorClass; ?> navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand text-white"><?php echo $userDisplay; ?></span>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php foreach ($links as $name => $url): ?>
                        <li class="nav-item"><a class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == $url) ? 'active' : ''; ?>" href="<?php echo $url; ?>"><?php echo $name; ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <a href="logout.php" class="btn btn-danger">تسجيل الخروج</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
<?php
}

// قالب تذييل الصفحة
function renderFooter() {
?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>