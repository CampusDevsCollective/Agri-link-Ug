<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Buyer.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_json(["error" => "Method not allowed"], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

if (empty($input["full_name"]) || empty($input["phone_number"]) || empty($input["password"])) {
    send_json(["error" => "full_name, phone_number and password are required"], 400);
}

$buyerModel = new Buyer($pdo);

try {
    $userId = $buyerModel->register($input);
    send_json(["message" => "Buyer registered", "user_id" => $userId], 201);
} catch (PDOException $e) {
    send_json(["error" => "Registration failed: " . $e->getMessage()], 500);
}