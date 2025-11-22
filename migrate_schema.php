<?php
require_once __DIR__ . '/db.php';

function columnExists($pdo, $table, $column) {
    $sth = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $sth->execute([$column]);
    return (bool)$sth->fetch();
}

echo "Starting schema migration...\n";

// 1) threads.locked_fields
if (!columnExists($pdo, 'threads', 'locked_fields')) {
    echo "Adding threads.locked_fields...\n";
    $pdo->exec("ALTER TABLE threads ADD COLUMN locked_fields LONGTEXT DEFAULT '[]'");
    echo "Added locked_fields.\n";
} else { echo "threads.locked_fields already exists.\n"; }

// 2) offers.thread_uuid
if (!columnExists($pdo, 'offers', 'thread_uuid')) {
    echo "Adding offers.thread_uuid...\n";
    $pdo->exec("ALTER TABLE offers ADD COLUMN thread_uuid VARCHAR(64) DEFAULT NULL");
    // populate from thread_id if possible
    try {
        echo "Populating offers.thread_uuid from offers.thread_id -> threads.id...\n";
        $pdo->exec("UPDATE offers o JOIN threads t ON o.thread_id = t.id SET o.thread_uuid = t.thread_uuid WHERE o.thread_id IS NOT NULL");
        echo "Populated thread_uuid.\n";
    } catch (Exception $e) {
        echo "Warning: could not populate thread_uuid automatically: " . $e->getMessage() . "\n";
    }
    // add index
    echo "Adding index on offers.thread_uuid...\n";
    $pdo->exec("ALTER TABLE offers ADD INDEX (thread_uuid)");
    echo "Index added.\n";
} else { echo "offers.thread_uuid already exists.\n"; }

// 3) offers.accepted_by and accepted_at
if (!columnExists($pdo, 'offers', 'accepted_by')) {
    echo "Adding offers.accepted_by...\n";
    $pdo->exec("ALTER TABLE offers ADD COLUMN accepted_by VARCHAR(100) DEFAULT NULL");
    echo "Added accepted_by.\n";
} else { echo "offers.accepted_by already exists.\n"; }

if (!columnExists($pdo, 'offers', 'accepted_at')) {
    echo "Adding offers.accepted_at...\n";
    $pdo->exec("ALTER TABLE offers ADD COLUMN accepted_at DATETIME DEFAULT NULL");
    echo "Added accepted_at.\n";
} else { echo "offers.accepted_at already exists.\n"; }

echo "Schema migration complete.\n";

?>
