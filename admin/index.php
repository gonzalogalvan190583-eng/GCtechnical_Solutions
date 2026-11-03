<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$user = require_login();
$date = (string) ($_GET['date'] ?? date('Y-m-d'));
if (!valid_date($date)) {
    $date = date('Y-m-d');
}

$localStart = new DateTimeImmutable($date . ' 00:00:00', new DateTimeZone(date_default_timezone_get()));
$monthStart = $localStart->modify('first day of this month')->setTime(0, 0);
$monthEnd = $monthStart->modify('first day of next month');
$previousMonth = $monthStart->modify('-1 month')->format('Y-m-d');
$nextMonth = $monthEnd->format('Y-m-d');
$monthNames = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
];
$weekdays = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
$weekdayAbbreviations = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
$year = (int) $monthStart->format('Y');

$holidays = [];
$addHoliday = static function (DateTimeImmutable $holiday, string $name) use (&$holidays): void {
    $holidays[$holiday->format('Y-m-d')] = $name;
};
$addTransferableHoliday = static function (string $monthDay, string $name) use ($year, $monthStart, $addHoliday): void {
    $holiday = new DateTimeImmutable($year . '-' . $monthDay, $monthStart->getTimezone());
    $weekday = (int) $holiday->format('N');
    if ($weekday === 2 || $weekday === 3) {
        $holiday = $holiday->modify('last monday');
    } elseif ($weekday === 4 || $weekday === 5) {
        $holiday = $holiday->modify('next monday');
    }
    $addHoliday($holiday, $name);
};
$fixedHolidays = [
    ['01-01', 'Año Nuevo'],
    ['05-01', 'Día del Trabajo'],
    ['05-21', 'Día de las Glorias Navales'],
    ['06-21', 'Día Nacional de los Pueblos Indígenas'],
    ['07-16', 'Virgen del Carmen'],
    ['08-15', 'Asunción de la Virgen'],
    ['09-18', 'Fiestas Patrias'],
    ['09-19', 'Día de las Glorias del Ejército'],
    ['10-31', 'Día Nacional de las Iglesias Evangélicas y Protestantes'],
    ['11-01', 'Día de Todos los Santos'],
    ['12-08', 'Inmaculada Concepción'],
    ['12-25', 'Navidad'],
];
foreach ($fixedHolidays as [$monthDay, $name]) {
    $addHoliday(new DateTimeImmutable($year . '-' . $monthDay, $monthStart->getTimezone()), $name);
}
$addTransferableHoliday('06-29', 'San Pedro y San Pablo');
$addTransferableHoliday('10-12', 'Día del Descubrimiento de Dos Mundos');

$a = $year % 19;
$b = intdiv($year, 100);
$c = $year % 100;
$d = intdiv($b, 4);
$e = $b % 4;
$f = intdiv($b + 8, 25);
$g = intdiv($b - $f + 1, 3);
$h = (19 * $a + $b - $d - $g + 15) % 30;
$i = intdiv($c, 4);
$k = $c % 4;
$l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
$m = intdiv($a + 11 * $h + 22 * $l, 451);
$easterMonth = intdiv($h + $l - 7 * $m + 114, 31);
$easterDay = (($h + $l - 7 * $m + 114) % 31) + 1;
$easter = new DateTimeImmutable(
    sprintf('%04d-%02d-%02d', $year, $easterMonth, $easterDay),
    $monthStart->getTimezone()
);
$addHoliday($easter->modify('-2 days'), 'Viernes Santo');
$addHoliday($easter->modify('-1 day'), 'Sábado Santo');

$monthHolidays = [];
foreach ($holidays as $holidayDate => $holidayName) {
    if ($holidayDate >= $monthStart->format('Y-m-d') && $holidayDate < $monthEnd->format('Y-m-d')) {
        $monthHolidays[$holidayDate] = $holidayName;
    }
}
ksort($monthHolidays);

$localEnd = $localStart->modify('+1 day');
$utc = new DateTimeZone('UTC');
$start = $localStart->setTimezone($utc)->format('Y-m-d H:i:s');
$end = $localEnd->setTimezone($utc)->format('Y-m-d H:i:s');

$statement = db()->prepare(
    'SELECT r.id, r.name, r.email, r.subject, r.message, r.created_at, r.status,
            r.responded_at, responder.display_name AS responder_name,
            r.completed_at, completer.display_name AS completer_name
     FROM service_requests r
     LEFT JOIN users responder ON responder.id = r.responded_by
     LEFT JOIN users completer ON completer.id = r.completed_by
     WHERE r.created_at >= :start AND r.created_at < :end
     ORDER BY r.created_at ASC, r.id ASC'
);
$statement->execute(['start' => $start, 'end' => $end]);
$requests = $statement->fetchAll();

page_start('Solicitudes');
show_flash();
?>
<section class="calendar-panel" aria-label="Calendario de solicitudes">
  <div class="calendar-heading">
    <a class="btn btn-outline-primary" href="index.php?date=<?= h($previousMonth) ?>" aria-label="Mes anterior">‹</a>
    <h2><?= h(ucfirst($monthNames[(int) $monthStart->format('n')])) ?> <?= h($monthStart->format('Y')) ?></h2>
    <a class="btn btn-outline-primary" href="index.php?date=<?= h($nextMonth) ?>" aria-label="Mes siguiente">›</a>
    <?php if ($monthStart->format('Y-m') !== date('Y-m')): ?><a class="today-link" href="index.php">Volver a hoy</a><?php endif; ?>
  </div>
  <nav class="day-strip" aria-label="Días del mes">
    <?php for ($dayNumber = 1, $daysInMonth = (int) $monthStart->format('t'); $dayNumber <= $daysInMonth; $dayNumber++): ?>
      <?php
      $day = $monthStart->modify('+' . ($dayNumber - 1) . ' days');
      $dayDate = $day->format('Y-m-d');
      $weekdayName = $weekdays[(int) $day->format('N') - 1];
      $weekdayAbbreviation = $weekdayAbbreviations[(int) $day->format('N') - 1];
      $holidayName = $holidays[$dayDate] ?? null;
      ?>
      <a class="day-link<?= $dayDate === $date ? ' is-selected' : '' ?><?= $holidayName !== null ? ' is-holiday' : '' ?>"
         href="index.php?date=<?= h($dayDate) ?>"
         <?= $dayDate === $date ? 'aria-current="date"' : '' ?>
         <?= $holidayName !== null ? 'title="' . h($holidayName) . '"' : '' ?>>
        <span class="day-weekday"><?= h(ucfirst($weekdayAbbreviation)) ?></span>
        <span class="day-number"><?= h($day->format('d')) ?></span>
        <?php if ($holidayName !== null): ?><span class="holiday-dot" aria-label="Feriado: <?= h($holidayName) ?>"></span><?php endif; ?>
      </a>
    <?php endfor; ?>
  </nav>
  <?php if ($monthHolidays !== []): ?>
    <div class="holiday-list" aria-label="Feriados nacionales de Chile">
      <strong>Feriados de Chile:</strong>
      <?php foreach ($monthHolidays as $holidayDate => $holidayName): ?>
        <span><?= h((new DateTimeImmutable($holidayDate))->format('d')) ?> · <?= h($holidayName) ?></span>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="holiday-list">No hay feriados nacionales incluidos para este mes.</p>
  <?php endif; ?>
  <form method="get" class="date-picker">
    <label for="date">Ir a una fecha</label>
    <input class="form-control" type="date" id="date" name="date" value="<?= h($date) ?>" required>
    <button class="btn btn-primary" type="submit">Ver fecha</button>
  </form>
</section>

<div class="request-summary">
  <strong><?= count($requests) ?></strong> solicitud<?= count($requests) === 1 ? '' : 'es' ?> · <?= h(ucfirst($weekdays[(int) $localStart->format('N') - 1])) ?> <?= h($localStart->format('d/m/Y')) ?>
</div>

<?php if ($requests === []): ?>
  <section class="empty-state"><h2>No hay solicitudes para esta fecha</h2><p>Prueba con otro día usando el calendario.</p></section>
<?php else: ?>
  <section class="timeline" aria-label="Solicitudes del día">
    <?php foreach ($requests as $request): ?>
      <?php
      $createdLocal = (new DateTimeImmutable($request['created_at'], $utc))->setTimezone(new DateTimeZone(date_default_timezone_get()));
      $safeSubject = preg_replace('/[\x00-\x1F\x7F]/', ' ', $request['subject']);
      $mailtoSubject = 'Re: ' . trim($safeSubject ?? '');
      $mailtoBody = "Hola {$request['name']},\r\n\r\nGracias por contactarnos. ";
      $mailto = 'mailto:' . rawurlencode($request['email']) . '?subject=' . rawurlencode($mailtoSubject) . '&body=' . rawurlencode($mailtoBody);
      ?>
      <article class="request-card">
        <div class="timeline-time"><?= h($createdLocal->format('H:i')) ?></div>
        <div class="request-content">
          <header class="request-heading">
            <div>
              <h2><?= h($request['subject']) ?></h2>
              <p><?= h($request['name']) ?> · <a href="mailto:<?= h($request['email']) ?>"><?= h($request['email']) ?></a></p>
            </div>
            <span class="status-badge status-<?= h($request['status']) ?>"><?= h(ucfirst($request['status'])) ?></span>
          </header>
          <p class="request-message"><?= nl2br(h($request['message'])) ?></p>
          <footer class="request-actions">
            <a class="btn btn-primary" href="<?= h($mailto) ?>">Responder en Outlook</a>
            <form method="post" action="request-status.php" class="status-form">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
              <input type="hidden" name="date" value="<?= h($date) ?>">
              <label class="visually-hidden" for="status-<?= (int) $request['id'] ?>">Estado</label>
              <select class="form-select" id="status-<?= (int) $request['id'] ?>" name="status">
                <option value="pendiente" <?= $request['status'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="respondida" <?= $request['status'] === 'respondida' ? 'selected' : '' ?>>Respondida</option>
                <option value="realizada" <?= $request['status'] === 'realizada' ? 'selected' : '' ?>>Realizada</option>
              </select>
              <button class="btn btn-outline-secondary" type="submit">Guardar estado</button>
            </form>
          </footer>
          <?php if ($request['responded_at'] !== null): ?>
            <small class="request-audit">Respondida<?= $request['responder_name'] ? ' por ' . h($request['responder_name']) : '' ?>.</small>
          <?php endif; ?>
          <?php if ($request['completed_at'] !== null): ?>
            <small class="request-audit">Realizada<?= $request['completer_name'] ? ' por ' . h($request['completer_name']) : '' ?>.</small>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
<?php page_end(); ?>
