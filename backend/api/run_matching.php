<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Matcher.php";
require_once __DIR__ . "/../utils/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    send_json(["error" => "Method not allowed"], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

if (empty($input["requirement_id"])) {
    send_json(["error" => "requirement_id is required"], 400);
}

$matcher = new Matcher($pdo);
$matchId = $matcher->attemptDirectMatch((int) $input["requirement_id"]);

if ($matchId) {
    send_json(["message" => "Direct match created", "match_id" => $matchId], 201);
} else {
    send_json([
        "message" => "No direct match available — try aggregation",
        "next" => "call run_aggregation.php with this requirement_id"
    ], 200);
}