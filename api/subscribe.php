<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load PHPMailer
require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Database Credentials
require_once 'db_config.php';

try {
    // Connect to MySQL
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && isset($data->consent)) {
    $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
    $consent = $data->consent ? 1 : 0;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Email invalide."]);
        exit();
    }

    try {
        // Check if email exists
        $checkQuery = "SELECT id FROM subscribers WHERE email = :email LIMIT 1";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            http_response_code(409); // Conflict
            echo json_encode(["error" => "Cet email est déjà inscrit."]);
            exit();
        }

        // Insert new subscriber
        $query = "INSERT INTO subscribers (email, consent_given, created_at) VALUES (:email, :consent, NOW())";
        $stmt = $conn->prepare($query);

        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':consent', $consent);

        if ($stmt->execute()) {
            // Send Welcome Email
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = $smtp_host ?? 'smtp.hostinger.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_username ?? 'contact@dysapp.com';
                $mail->Password   = $smtp_password ?? '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
                $mail->Port       = $smtp_port ?? 465;

                // Recipients
                $mail->setFrom($smtp_username ?? 'contact@dysapp.com', 'DysAdapt');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Bienvenue dans la communauté DysAdapt !';
                $mail->Body    = "
                <html>
                <head>
                    <title>Bienvenue chez DysAdapt</title>
                </head>
                <body>
                    <h1>Merci pour votre inscription !</h1>
                    <p>Nous sommes ravis de vous compter parmi nous.</p>
                    <p>Vous recevrez bientôt nos actualités et astuces pour l'adaptation scolaire.</p>
                    <br>
                    <p>L'équipe DysAdapt</p>
                    <hr>
                    <small><a href='https://dysapp.com/unsubscribe.html?email=" . urlencode($email) . "'>Se désabonner</a></small>
                </body>
                </html>";

                $mail->send();
            } catch (Exception $e) {
                // Log error but don't fail the subscription
                error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            }

            http_response_code(201);
            echo json_encode(["message" => "Inscription réussie ! Un email de confirmation a été envoyé."]);
        } else {
            http_response_code(503);
            echo json_encode(["error" => "Impossible d'enregistrer l'utilisateur."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur de base de données."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Données incomplètes."]);
}
?>
