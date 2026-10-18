<?php
require __DIR__ . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    try {
        Member::add(
            (string) ($_POST['student_id'] ?? ''),
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['phone'] ?? '')
        );
        Flash::success('ลงทะเบียนสมาชิกใหม่เรียบร้อย');
    } catch (LoanException $e) {
        Flash::error($e->getMessage());
    }
    redirect('members.php');
}

$members = Member::all();

$pageTitle = 'สมาชิก';
$activePage = 'members';
require __DIR__ . '/partials/header.php';
?>

<section>
    <h2>สมาชิกทั้งหมด</h2>
    <p class="small">สมาชิกที่มีอุปกรณ์ค้างเกินกำหนด หรือยืมครบ <?= Loan::MAX_ACTIVE_PER_MEMBER ?> ชิ้น จะยืมเพิ่มไม่ได้</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>รหัสนักศึกษา</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>เบอร์โทร</th>
                    <th>กำลังยืม</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$members): ?>
                    <tr><td colspan="5" class="center muted">ยังไม่มีสมาชิก</td></tr>
                <?php endif; ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td class="code"><?= e($member->getStudentId()) ?></td>
                        <td><?= e($member->getName()) ?></td>
                        <td><?= e($member->getPhone() ?? '-') ?></td>
                        <td class="code"><?= $member->getActiveLoans() ?>/<?= Loan::MAX_ACTIVE_PER_MEMBER ?></td>
                        <td>
                            <?php if ($member->getOverdueLoans() > 0): ?>
                                <span class="bad">ค้างเกินกำหนด <?= $member->getOverdueLoans() ?> ชิ้น</span>
                            <?php elseif (!$member->canBorrow()): ?>
                                <span class="warn">ยืมครบแล้ว</span>
                            <?php else: ?>
                                <span class="ok">ยืมได้</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section>
    <h2>ลงทะเบียนสมาชิกใหม่</h2>
    <form method="post">
        <?= Csrf::field() ?>
        <table class="form-table">
            <tr>
                <th><label for="student_id">รหัสนักศึกษา</label></th>
                <td><input id="student_id" name="student_id" required pattern="\d{13}" maxlength="13" inputmode="numeric" placeholder="13 หลัก" class="code"></td>
            </tr>
            <tr>
                <th><label for="name">ชื่อ-นามสกุล</label></th>
                <td><input id="name" name="name" required maxlength="100" placeholder="ชื่อ-นามสกุล"></td>
            </tr>
            <tr>
                <th><label for="phone">เบอร์โทร</label></th>
                <td><input id="phone" name="phone" maxlength="20" placeholder="ไม่บังคับ"></td>
            </tr>
            <tr>
                <th></th>
                <td><button>ลงทะเบียน</button></td>
            </tr>
        </table>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
