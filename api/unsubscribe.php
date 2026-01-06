<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Credentials
require_once 'db_config.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email)) {
    $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);

    try {
        // Prepare DELETE statement
        $query = "DELETE FROM subscribers WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(["message" => "Désabonnement réussi."]);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Email non trouvé."]);
            }
        } else {
            http_response_code(503);
            echo json_encode(["error" => "Erreur lors du désabonnement."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur de base de données."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Email requis."]);
}
?>
