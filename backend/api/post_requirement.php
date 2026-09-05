<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/BuyerRequirement.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_json(["error" => "Method not allowed"], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$required = ["buyer_id", "commodity_id", "quantity_required"];
foreach ($required as $field) {
    if (empty($input[$field])) {
        send_json(["error" => "$field is required"], 400);
    }
}

$requirementModel = new BuyerRequirement($pdo);

try {
    $requirementId = $requirementModel->create($input);

    // Trigger matching engine immediately (Workflow 3)
    $matchUrl = "http://localhost:8000/api/run_matching.php";
    // In production, queue this instead of a blocking HTTP call.

    send_json([
        "message" => "Requirement posted",
        "requirement_id" => $requirementId,
        "next" => "call run_matching.php with this requirement_id"
    ], 201);
} catch (PDOException $e) {
    send_json(["error" => "Failed to post requirement: " . $e->getMessage()], 500);
}