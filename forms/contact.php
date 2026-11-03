<?php
declare(strict_types=1);

require __DIR__ . '/../admin/_bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, 'Método no permitido.');
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    respond(422, 'No se pudo procesar la solicitud.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if (
    $name === '' || mb_strlen($name) > 120 ||
    filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254 ||
    $subject === '' || mb_strlen($subject) > 200 ||
    $message === '' || mb_strlen($message) > 10000
) {
    respond(422, 'Revisa los campos del formulario e inténtalo nuevamente.');
}

try {
    $database = db();
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ipHash = hash_hmac('sha256', $ip, (string) getenv('GC_APP_KEY'));
    $database->exec('DELETE FROM request_rate_limits WHERE last_submission_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    $database->beginTransaction();
    $createLimit = $database->prepare(
        "INSERT IGNORE INTO request_rate_limits (ip_hash, last_submission_at)
         VALUES (:ip_hash, '1970-01-01 00:00:00')"
    );
    $createLimit->execute(['ip_hash' => $ipHash]);
    $rateLimit = $database->prepare('SELECT last_submission_at FROM request_rate_limits WHERE ip_hash = :ip_hash FOR UPDATE');
    $rateLimit->execute(['ip_hash' => $ipHash]);
    $lastSubmission = $rateLimit->fetchColumn();

    if (is_string($lastSubmission)) {
        $lastTime = new DateTimeImmutable($lastSubmission, new DateTimeZone('UTC'));
        if ($lastTime > new DateTimeImmutable('-30 seconds', new DateTimeZone('UTC'))) {
            $database->rollBack();
            respond(429, 'Espera un momento antes de enviar otra solicitud.');
        }
    }
    $saveLimit = $database->prepare('UPDATE request_rate_limits SET last_submission_at = UTC_TIMESTAMP() WHERE ip_hash = :ip_hash');
    $saveLimit->execute(['ip_hash' => $ipHash]);

    $insert = $database->prepare(
        'INSERT INTO service_requests (name, email, subject, message, status, created_at)
         VALUES (:name, :email, :subject, :message, :status, UTC_TIMESTAMP())'
    );
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'status' => 'pendiente',
    ]);
    $database->commit();
    respond(201, 'Tu solicitud fue recibida. Gracias.');
} catch (RuntimeException $exception) {
    if (isset($database) && $database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    error_log('No se pudo registrar una solicitud: ' . $exception->getMessage());
    respond(500, 'No se pudo guardar tu solicitud. Inténtalo más tarde.');
}
