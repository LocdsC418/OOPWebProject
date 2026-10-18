<?php
require __DIR__ . '/src/bootstrap.php';

$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = isset($_GET['cat']) && $_GET['cat'] !== '' ? (int) $_GET['cat'] : null;
$selfUrl = 'index.php' . (($query = http_build_query(array_filter(['q' => $search, 'cat' => $categoryId]))) ? "?$query" : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    try {
        switch ($_POST['action'] ?? '') {
            case 'borrow':
                $due = Loan::borrow(
                    (int) ($_POST['equipment_id'] ?? 0),
                    (int) ($_POST['member_id'] ?? 0),
                    (int) ($_POST['days'] ?? Loan::LOAN_DAYS)
                );
                Flash::success('ยืมสำเร็จ กำหนดคืนภายใน ' . thai_date($due));
                break;

            case 'return':
                $loan = Loan::returnLoan((int) ($_POST['loan_id'] ?? 0));
                $message = 'คืน ' . $loan->getEquipmentName() . ' เรียบร้อย';
                if ($loan->getFine() > 0) {
                    $message .= ' · เกินกำหนด ' . $loan->getOverdueDays() . ' วัน ค่าปรับ ' . number_format($loan->getFine()) . ' บาท';
                }
                Flash::success($message);
                break;

            case 'add_equipment':
                Equipment::add(
                    (string) ($_POST['code'] ?? ''),
                    (string) ($_POST['name'] ?? ''),
                    (int) ($_POST['category_id'] ?? 0),
                    (int) ($_POST['qty'] ?? 0)
                );
                Flash::success('เพิ่มอุปกรณ์ใหม่เรียบร้อย');
                break;
        }
    } catch (LoanException $e) {
        Flash::error($e->getMessage());
    }
    redirect($selfUrl);
}

$categories = Category::all();
$equipmentList = Equipment::all($search, $categoryId);
$members = Member::all();
$activeLoans = Loan::active();

$pageTitle = 'อุปกรณ์';
$activePage = 'index';
require __DIR__ . '/partials/header.php';
?>

<!-- ===== รายการอุปกรณ์ ===== -->
<section>
    <h2>รายการอุปกรณ์</h2>
    <p class="small">เลือกผู้ยืมและจำนวนวัน แล้วกด "ยืม" (สูงสุด <?= Loan::MAX_ACTIVE_PER_MEMBER ?> ชิ้นต่อคน)</p>

    <div class="filter">
        หมวดหมู่:
        <?php if ($categoryId === null): ?>
            <span class="current">ทั้งหมด</span>
        <?php else: ?>
            <a href="index.php<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>">ทั้งหมด</a>
        <?php endif; ?>
        <?php foreach ($categories as $category): ?>
            |
            <?php if ($categoryId === $category->getId()): ?>
                <span class="current"><?= e($category->getName()) ?></span>
            <?php else: ?>
                <a href="index.php?<?= e(http_build_query(array_filter(['q' => $search, 'cat' => $category->getId()]))) ?>"><?= e($category->getName()) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <form method="get" class="filter">
        <?php if ($categoryId !== null): ?>
            <input type="hidden" name="cat" value="<?= $categoryId ?>">
        <?php endif; ?>
        ค้นหา:
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="ชื่อหรือรหัส...">
        <button>ค้นหา</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>ชื่ออุปกรณ์</th>
                    <th>หมวดหมู่</th>
                    <th>สถานะ</th>
                    <th>คงเหลือ</th>
                    <th>ยืม</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$equipmentList): ?>
                    <tr><td colspan="6" class="center muted">ไม่พบอุปกรณ์ที่ค้นหา</td></tr>
                <?php endif; ?>
                <?php foreach ($equipmentList as $item): ?>
                    <tr>
                        <td class="code"><?= e($item->getCode()) ?></td>
                        <td><?= e($item->getName()) ?></td>
                        <td><?= e($item->getCategoryName()) ?></td>
                        <td class="<?= $item->isAvailable() ? 'ok' : 'bad' ?>"><?= $item->isAvailable() ? 'ว่าง' : 'ไม่ว่าง' ?></td>
                        <td class="code"><?= $item->getAvailableQty() ?>/<?= $item->getTotalQty() ?></td>
                        <td>
                            <form method="post" class="borrow-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="borrow">
                                <input type="hidden" name="equipment_id" value="<?= $item->getId() ?>">
                                <select name="member_id" required aria-label="ผู้ยืม">
                                    <option value="">-- ผู้ยืม --</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?= $member->getId() ?>"><?= e($member->getName()) ?><?= $member->canBorrow() ? '' : ' (ยืมไม่ได้)' ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="days" aria-label="จำนวนวัน">
                                    <?php foreach ([1, 3, 7, 14] as $days): ?>
                                        <option value="<?= $days ?>" <?= $days === Loan::LOAN_DAYS ? 'selected' : '' ?>><?= $days ?> วัน</option>
                                    <?php endforeach; ?>
                                </select>
                                <button <?= $item->isAvailable() ? '' : 'disabled' ?>>ยืม</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- ===== รายการที่กำลังยืม ===== -->
<section>
    <h2>รายการที่กำลังยืม</h2>
    <p class="small">คืนเกินกำหนดคิดค่าปรับวันละ <?= Loan::FINE_PER_DAY ?> บาท</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>อุปกรณ์</th>
                    <th>ผู้ยืม</th>
                    <th>วันที่ยืม</th>
                    <th>กำหนดคืน</th>
                    <th>สถานะ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$activeLoans): ?>
                    <tr><td colspan="6" class="center muted">ยังไม่มีรายการยืม</td></tr>
                <?php endif; ?>
                <?php foreach ($activeLoans as $loan): ?>
                    <tr class="<?= $loan->isOverdue() ? 'overdue' : '' ?>">
                        <td>
                            <?= e($loan->getEquipmentName()) ?>
                            <span class="code small muted">(<?= e($loan->getEquipmentCode()) ?>)</span>
                        </td>
                        <td><?= e($loan->getMemberName()) ?></td>
                        <td><?= thai_date($loan->getBorrowedAt()) ?></td>
                        <td><?= thai_date($loan->getDueDate()) ?></td>
                        <td>
                            <?php if ($loan->isOverdue()): ?>
                                <span class="bad">เกิน <?= $loan->getOverdueDays() ?> วัน · ปรับ <?= number_format($loan->getFine()) ?> ฿</span>
                            <?php else: ?>
                                <span class="ok">ปกติ</span>
                            <?php endif; ?>
                        </td>
                        <td class="right">
                            <form method="post" onsubmit="return confirm(<?= e(json_encode('ยืนยันการคืน ' . $loan->getEquipmentName() . ' ?', JSON_UNESCAPED_UNICODE)) ?>)">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="loan_id" value="<?= $loan->getId() ?>">
                                <button>คืนอุปกรณ์</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- ===== เพิ่มอุปกรณ์ใหม่ ===== -->
<section>
    <h2>เพิ่มอุปกรณ์ใหม่</h2>
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="add_equipment">
        <table class="form-table">
            <tr>
                <th><label for="code">รหัส</label></th>
                <td><input id="code" name="code" required maxlength="20" placeholder="เช่น PH-103" class="code code-input"></td>
            </tr>
            <tr>
                <th><label for="name">ชื่ออุปกรณ์</label></th>
                <td><input id="name" name="name" required maxlength="150" placeholder="ชื่ออุปกรณ์"></td>
            </tr>
            <tr>
                <th><label for="category_id">หมวดหมู่</label></th>
                <td>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category->getId() ?>"><?= e($category->getName()) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="qty">จำนวน</label></th>
                <td><input type="number" id="qty" name="qty" min="1" max="999" value="1" required></td>
            </tr>
            <tr>
                <th></th>
                <td><button>เพิ่มอุปกรณ์</button></td>
            </tr>
        </table>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
