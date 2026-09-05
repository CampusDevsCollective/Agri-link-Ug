<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ProduceListing.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    send_json(["error" => "Method not allowed"], 405);
}

$farmerId = $_GET["farmer_id"] ?? null;
if (!$farmerId) {
    send_json(["error" => "farmer_id query param is required"], 400);
}

$listingModel = new ProduceListing($pdo);
send_json(["listings" => $listingModel->getByFarmer((int) $farmerId)]);