<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ProduceListing.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_json(["error" => "Method not allowed"], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$required = ["farmer_id", "commodity_id", "quantity_available", "availability_date"];
foreach ($required as $field) {
    if (empty($input[$field])) {
        send_json(["error" => "$field is required"], 400);
    }
}

$listingModel = new ProduceListing($pdo);

try {
    $listingId = $listingModel->create($input);
    send_json(["message" => "Listing created", "listing_id" => $listingId], 201);
} catch (PDOException $e) {
    send_json(["error" => "Failed to create listing: " . $e->getMessage()], 500);
}