<?php
require __DIR__ . '/src/bootstrap.php';

$history = Loan::history();
$topEquipment = Loan::topEquipment();
$summary = [
    ['label' => 'การยืมทั้งหมด',     'value' => number_format(Loan::count()),      'unit' => 'ครั้ง'],
    ['label' => 'สมาชิก',           'value' => number_format(Member::count()),    'unit' => 'คน'],
    ['label' => 'ค่าปรับที่เก็บได้',   'value' => number_format(Loan::totalFines()), 'unit' => 'บาท'],
];

$pageTitle = 'ประวัติ & สถิติ';
$activePage = 'history';
require __DIR__ . '/partials/header.php';
?>

<section>
    <h2>สรุป</h2>
    <table class="form-table">
        <?php foreach ($summary as $card): ?>
            <tr>
                <th><?= e($card['label']) ?></th>
                <td><b><?= e($card['value']) ?></b> <?= e($card['unit']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

<section>
    <h2>อุปกรณ์ยอดนิยม</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>อันดับ</th>
                    <th>อุปกรณ์</th>
                    <th>รหัส</th>
                    <th class="right">จำนวนครั้ง</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$topEquipment): ?>
                    <tr><td colspan="4" class="center muted">ยังไม่มีข้อมูล</td></tr>
                <?php endif; ?>
                <?php foreach ($topEquipment as $rank => $row): ?>
                    <tr>
                        <td class="center"><?= $rank + 1 ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td class="code"><?= e($row['code']) ?></td>
                        <td class="right"><?= (int) $row['times'] ?> ครั้ง</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section>
    <h2>ประวัติการยืม-คืน</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>อุปกรณ์</th>
                    <th>ผู้ยืม</th>
                    <th>วันที่ยืม</th>
                    <th>กำหนดคืน</th>
                    <th>วันที่คืน</th>
                    <th class="right">ค่าปรับ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$history): ?>
                    <tr><td colspan="6" class="center muted">ยังไม่มีประวัติ</td></tr>
                <?php endif; ?>
                <?php foreach ($history as $loan): ?>
                    <tr>
                        <td>
                            <?= e($loan->getEquipmentName()) ?>
                            <span class="code small muted">(<?= e($loan->getEquipmentCode()) ?>)</span>
                        </td>
                        <td><?= e($loan->getMemberName()) ?></td>
                        <td><?= thai_date($loan->getBorrowedAt()) ?></td>
                        <td><?= thai_date($loan->getDueDate()) ?></td>
                        <td>
                            <?php if ($loan->isReturned()): ?>
                                <?= thai_date($loan->getReturnedAt()) ?>
                            <?php elseif ($loan->isOverdue()): ?>
                                <span class="bad">ยังไม่คืน (เกินกำหนด)</span>
                            <?php else: ?>
                                <span class="warn">กำลังยืม</span>
                            <?php endif; ?>
                        </td>
                        <td class="right <?= $loan->getFine() > 0 ? 'bad' : 'muted' ?>">
                            <?= $loan->getFine() > 0 ? number_format($loan->getFine()) . ' ฿' : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
