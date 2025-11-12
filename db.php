<?php
$mysqli = new mysqli('b8y9eccnoennbc03jxfn-mysql.services.clever-cloud.com', 'uonk3hj3tnpjdpfq', 'jywVsjXDFGN6cHLfQscM', 'b8y9eccnoennbc03jxfn');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la BD: ' . $mysqli->connect_error]);
    exit;
}
