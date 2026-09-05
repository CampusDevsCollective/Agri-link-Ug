<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    send_json(["error" => "Method not allowed"], 405);
}

$userId = $_GET["user_id"] ?? null;
if (!$userId) {
    send_json(["error" => "user_id query param is required"], 400);
}

$stmt = $pdo->prepare(
    "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 50"
);
$stmt->execute([":user_id" => $userId]);

send_json(["notifications" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);