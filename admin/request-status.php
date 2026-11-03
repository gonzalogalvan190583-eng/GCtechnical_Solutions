<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verify_csrf();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($_POST['status'] ?? '');
$date = (string) ($_POST['date'] ?? date('Y-m-d'));
if ($id === false || $id === null || !in_array($status, ['pendiente', 'respondida', 'realizada'], true) || !valid_date($date)) {
    http_response_code(400);
    exit('Los datos recibidos no son válidos.');
}

$statement = db()->prepare(
    'UPDATE service_requests
     SET status = :status,
         responded_at = CASE WHEN :responded_at_flag = 1 THEN COALESCE(responded_at, UTC_TIMESTAMP()) ELSE responded_at END,
         responded_by = CASE WHEN :responded_by_flag = 1 THEN :responded_by_user ELSE responded_by END,
         completed_at = CASE WHEN :completed_at_flag = 1 THEN COALESCE(completed_at, UTC_TIMESTAMP()) ELSE NULL END,
         completed_by = CASE WHEN :completed_by_flag = 1 THEN :completed_by_user ELSE NULL END
     WHERE id = :id'
);
$responded = $status === 'respondida';
$completed = $status === 'realizada';
$statement->execute([
    'status' => $status,
    'responded_at_flag' => $responded ? 1 : 0,
    'responded_by_flag' => $responded ? 1 : 0,
    'responded_by_user' => $user['id'],
    'completed_at_flag' => $completed ? 1 : 0,
    'completed_by_flag' => $completed ? 1 : 0,
    'completed_by_user' => $user['id'],
    'id' => $id,
]);

$exists = db()->prepare('SELECT 1 FROM service_requests WHERE id = :id');
$exists->execute(['id' => $id]);
if ($exists->fetchColumn() === false) {
    http_response_code(404);
    exit('La solicitud ya no existe.');
}

flash('El estado de la solicitud se actualizó.');
redirect('index.php?date=' . rawurlencode($date));
