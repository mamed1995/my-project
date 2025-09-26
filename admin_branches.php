<?php
// admin_branches.php
require_once 'config.php';
protectPage('admin');

// جلب الفروع
$branches = $pdo->query("SELECT * FROM branches")->fetchAll(PDO::FETCH_ASSOC);

renderHeader('إدارة الفروع', 'admin');
?>

<h1 class="mb-4">إدارة الفروع</h1>
<a href="admin_add_branch.php" class="btn btn-primary float-end mb-3">إضافة فرع جديد</a>
<div class="clearfix"></div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>اسم الفرع</th>
                <th>اسم المستخدم</th>
                <th>العملة الافتراضية</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($branches as $branch): ?>
                <tr>
                    <td><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                    <td><?php echo htmlspecialchars($branch['branch_username']); ?></td>
                    <td><?php echo $branch['default_currency'] == 'SYP' ? 'ليرة سورية' : 'دولار أمريكي'; ?></td>
                    <td><span class="badge bg-success">نشط</span></td>
                    <td>
                        <a href="admin_edit_branch.php?id=<?php echo $branch['id']; ?>" class="text-primary">تعديل</a> |
                        <a href="admin_delete_branch.php?id=<?php echo $branch['id']; ?>" class="text-danger" onclick="return confirm('هل أنت متأكد من حذف الفرع؟');">حذف</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>