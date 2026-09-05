<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Aggregator.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_json(["error" => "Method not allowed"], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

if (empty($input["requirement_id"])) {
    send_json(["error" => "requirement_id is required"], 400);
}

$aggregator = new Aggregator($pdo);

try {
    $aggregationId = $aggregator->proposeAggregation((int) $input["requirement_id"]);

    if ($aggregationId) {
        send_json(["message" => "Aggregation proposed", "aggregation_id" => $aggregationId], 201);
    } else {
        send_json(["message" => "Not enough combined supply available yet"], 200);
    }
} catch (Exception $e) {
    send_json(["error" => "Aggregation failed: " . $e->getMessage()], 500);
}