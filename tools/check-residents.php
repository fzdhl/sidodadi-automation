<?php
$dbPath = realpath(__DIR__ . '/../database/database.sqlite');
$db = new PDO('sqlite:'.$dbPath);
$count = $db->query('select count(*) from residents')->fetchColumn();
echo "count={$count}\n";
if ($count > 0) {
    $stmt = $db->query('select nik,nama from residents limit 5');
    foreach ($stmt as $row) {
        echo $row['nik'] . ' | ' . $row['nama'] . "\n";
    }
}
