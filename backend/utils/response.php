<?php
function send_json($data, $status = 200)
{
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}