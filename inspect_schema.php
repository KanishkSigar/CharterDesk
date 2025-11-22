<?php
require_once __DIR__ . '/db.php';

function showColumns($pdo, $table) {
    try {
        $sth = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            echo "Table {$table} not found or empty\n";
            return;
        }
        echo "Columns for {$table}:\n";
        foreach ($rows as $r) {
            echo sprintf(" - %s: %s%s\n", $r['Field'], $r['Type'], isset($r['Null']) ? (" null={$r['Null']}") : '');
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Error reading columns for {$table}: " . $e->getMessage() . "\n";
    }
}

showColumns($pdo, 'threads');
showColumns($pdo, 'offers');

?>
