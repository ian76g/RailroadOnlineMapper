<?php
$db = new SQLite3('data.sqlite3');
$db->exec("CREATE TABLE IF NOT EXISTS saves (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    filename     VARCHAR,
    track_length REAL,
    switch_count INTEGER,
    loc_count    INTEGER,
    cart_count   INTEGER,
    max_slope    REAL
);");

function getSaves() {
    global $db;
    $query = "SELECT * FROM saves ORDER BY id DESC";
    return $db->query($query);
}

function addSave($filename, $track_length, $switch_count, $loc_count, $cart_count, $max_slope) {
    global $db;
    $stm = $db->prepare("INSERT INTO saves (filename, track_length, switch_count, loc_count, cart_count, max_slope) VALUES (?, ?, ?, ?, ?, ?)");
    $stm->bindParam(1, $filename);
    $stm->bindParam(2, $track_length);
    $stm->bindParam(3, $switch_count);
    $stm->bindParam(4, $loc_count);
    $stm->bindParam(5, $cart_count);
    $stm->bindParam(6, $max_slope);
    return $stm->execute();
}