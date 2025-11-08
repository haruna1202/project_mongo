<?php
// test_mongo.php
require __DIR__ . '/vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $dbs = $client->listDatabases();
    echo "Kết nối OK. Danh sách DB:<br><pre>";
    foreach ($dbs as $db) {
        echo $db->getName() . "\n";
    }
    echo "</pre>";
} catch (Throwable $e) {
    echo "Lỗi: " . $e->getMessage();
}
