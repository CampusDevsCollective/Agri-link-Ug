<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/MarketRecommendation.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    send_json(["error" => "Method not allowed"], 405);
}

$commodityId = $_GET["commodity_id"] ?? null;
$districtId = $_GET["district_id"] ?? null;

if (!$commodityId || !$districtId) {
    send_json(["error" => "commodity_id and district_id query params are required"], 400);
}

$recommender = new MarketRecommendation($pdo);
$results = $recommender->recommend((int) $commodityId, (int) $districtId);

send_json(["recommendations" => $results]);