<?php
/** @var string $pageTitle */
/** @var string $activePage */
$navItems = [
    'index'   => ['href' => 'index.php',   'label' => 'อุปกรณ์'],
    'members' => ['href' => 'members.php', 'label' => 'สมาชิก'],
    'history' => ['href' => 'history.php', 'label' => 'ประวัติ & สถิติ'],
];
$stats = [
    ['value' => Equipment::count(),         'label' => 'รายการอุปกรณ์'],
    ['value' => Equipment::totalBorrowed(), 'label' => 'กำลังถูกยืม'],
    ['value' => Loan::overdueCount(),       'label' => 'เกินกำหนด', 'alert' => true],
];
$flash = Flash::take();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · EQ Lend</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="page">

<h1>EQ Lend</h1>
<div class="subtitle">ระบบยืม-คืนอุปกรณ์</div>

<nav>
    <?php foreach ($navItems as $key => $item): ?>
        <?= $key === array_key_first($navItems) ? '[' : '|' ?>
        <?php if ($key === $activePage): ?>
            <span class="current"><?= e($item['label']) ?></span>
        <?php else: ?>
            <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
    ]
</nav>
<hr>

<div class="small">
    <?php foreach ($stats as $index => $stat): ?>
        <?php $alert = !empty($stat['alert']) && $stat['value'] > 0; ?>
        <?= $index > 0 ? '|' : '' ?>
        <?= e($stat['label']) ?>: <b class="<?= $alert ? 'bad' : '' ?>"><?= (int) $stat['value'] ?></b>
    <?php endforeach; ?>
</div>

<main>
    <?php if ($flash): ?>
        <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
