<?php
require 'utils/colors.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-type: application/json');

$colorClass = new Colors();

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if (isset($_GET['code']) && $_GET['code'] != '') {
        $colors = $colorClass->get_color($_GET['code']);
        if ($colors) {
            print(
                json_encode(
                    array(
                        "colors" => $colors
                    )
                )
            );
        } else {
            http_response_code(404);
            print(
                json_encode(
                    array(
                        "error" => "The given share code is invalid."
                    )
                )
            );
        }
    } else {
        http_response_code(400);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
    $postBody = json_decode(file_get_contents("php://input"), true);
    if (isset($postBody['colors']) && $postBody['colors'] != '') {
        if (isset($postBody['__code']) && $postBody['__code'] != '') {
            $code = $colorClass->store_color($postBody['colors'], $postBody['__code']);
        } else {
            $code = $colorClass->store_color($postBody['colors']);
        }
        if ($code) {
            print(
                json_encode(
                    array(
                        "code" => $code
                    )
                )
            );
        } else {
            http_response_code(422);
            print(
                json_encode(
                    array(
                        "error" => "Unable to create share code."
                    )
                )
            );
        }
    } else {
        http_response_code(400);
    }
} else {
    http_response_code(405);
}