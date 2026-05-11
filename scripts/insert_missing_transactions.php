<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/../vendor/autoload.php';

function envValue(string $path, string $key, ?string $default = null): ?string
{
    static $env = null;
    if ($env === null) {
        $env = [];
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
    return $env[$key] ?? $default;
}

function normalizeHeader(string $header): string
{
    $header = strtolower(trim($header));
    $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
    return trim($header, '_');
}

function scanRequestNumbers(string $xlsxPath): array
{
    $spreadsheet = IOFactory::load($xlsxPath);
    $sheet = $spreadsheet->getSheet(0);
    $rows = array_values($sheet->toArray(null, true, true, true));

    $candidateHeaders = ['request_number', 'ticket_request_number', 'ticket_number', 'request_no', 'ticket_no'];
    $headerMap = [];
    $headerRowIndex = null;
    $maxHeaderScan = min(25, count($rows));

    for ($i = 0; $i < $maxHeaderScan; $i++) {
        $possibleHeader = $rows[$i];
        $possibleMap = [];
        foreach ($possibleHeader as $col => $header) {
            $normalized = normalizeHeader((string) $header);
            if ($normalized !== '') {
                $possibleMap[$normalized] = $col;
            }
        }
        foreach ($candidateHeaders as $candidate) {
            if (isset($possibleMap[$candidate])) {
                $headerMap = $possibleMap;
                $headerRowIndex = $i;
                break 2;
            }
        }
    }

    $requestNumberCol = null;
    foreach ($candidateHeaders as $candidate) {
        if (isset($headerMap[$candidate])) {
            $requestNumberCol = $headerMap[$candidate];
            break;
        }
    }
    if ($requestNumberCol === null) {
        throw new RuntimeException(
            'Could not find request number column. Headers detected: ' . implode(', ', array_keys($headerMap))
        );
    }

    $keys = [];
    foreach ($rows as $idx => $row) {
        if ($headerRowIndex !== null && $idx <= $headerRowIndex) {
            continue;
        }
        $key = trim((string) ($row[$requestNumberCol] ?? ''));
        if ($key !== '') {
            $keys[] = $key;
        }
    }

    return array_values(array_unique($keys));
}

function batch(array $values, int $size): array
{
    return array_chunk($values, $size);
}

$xlsxPath = 'C:\\Users\\ebautista\\Downloads\\Missing Transactions after Deployment 2.xlsx';
$envPath = __DIR__ . '/../.env';

if (!file_exists($xlsxPath)) {
    throw new RuntimeException("Workbook not found: {$xlsxPath}");
}

$dbHost = envValue($envPath, 'DB_HOST', '127.0.0.1');
$dbPort = envValue($envPath, 'DB_PORT', '3306');
$dbName = envValue($envPath, 'DB_DATABASE', 'philtower');
$dbUser = envValue($envPath, 'DB_USERNAME', 'root');
$dbPass = envValue($envPath, 'DB_PASSWORD', '');

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$requestNumbers = scanRequestNumbers($xlsxPath);
if (count($requestNumbers) === 0) {
    throw new RuntimeException('No request numbers found in workbook.');
}

$defaultStatusId = 1;
$defaultSlaId = 3;
$defaultForApproval = 3;
$now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

$insertedTickets = 0;
$insertedSlaClocks = 0;

$pdo->beginTransaction();
try {
    $existingByRequest = [];
    foreach (batch($requestNumbers, 500) as $chunk) {
        $marks = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $pdo->prepare("SELECT id, request_number, slas_id, submitted_at, created_at FROM ticket_requests WHERE request_number IN ({$marks})");
        $stmt->execute($chunk);
        foreach ($stmt->fetchAll() as $row) {
            $existingByRequest[(string) $row['request_number']] = $row;
        }
    }

    $insertTicketStmt = $pdo->prepare(
        'INSERT INTO ticket_requests (request_number, ticket_status_id, slas_id, for_approval, submitted_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($requestNumbers as $requestNumber) {
        if (isset($existingByRequest[$requestNumber])) {
            continue;
        }
        $insertTicketStmt->execute([
            $requestNumber,
            (string) $defaultStatusId,
            (string) $defaultSlaId,
            (string) $defaultForApproval,
            $now,
            $now,
            $now,
        ]);
        $insertedTickets++;
    }

    // Refresh ticket map after insert.
    $ticketsByRequest = [];
    foreach (batch($requestNumbers, 500) as $chunk) {
        $marks = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $pdo->prepare("SELECT id, request_number, slas_id, submitted_at, created_at FROM ticket_requests WHERE request_number IN ({$marks})");
        $stmt->execute($chunk);
        foreach ($stmt->fetchAll() as $row) {
            $ticketsByRequest[(string) $row['request_number']] = $row;
        }
    }

    $ticketIds = array_map(static fn ($row) => (int) $row['id'], array_values($ticketsByRequest));
    $existingSlaByEntity = [];
    if (!empty($ticketIds)) {
        foreach (batch($ticketIds, 500) as $chunk) {
            $marks = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = $pdo->prepare(
                "SELECT entity_id FROM sla_clocks WHERE entity_type = 'ticket_request' AND entity_id IN ({$marks})"
            );
            $stmt->execute(array_map('strval', $chunk));
            foreach ($stmt->fetchAll() as $row) {
                $existingSlaByEntity[(int) $row['entity_id']] = true;
            }
        }
    }

    $slaDefs = [];
    $slaRows = $pdo->query('SELECT id, response_minutes, resolution_minutes FROM slas')->fetchAll();
    foreach ($slaRows as $slaRow) {
        $slaDefs[(int) $slaRow['id']] = [
            'response_minutes' => $slaRow['response_minutes'] !== null ? (int) $slaRow['response_minutes'] : null,
            'resolution_minutes' => $slaRow['resolution_minutes'] !== null ? (int) $slaRow['resolution_minutes'] : null,
        ];
    }

    $insertSlaStmt = $pdo->prepare(
        "INSERT INTO sla_clocks
        (entity_type, entity_id, sla_id, started_at, due_at, response_due_at, total_paused_minutes, status, created_at, updated_at)
        VALUES ('ticket_request', ?, ?, ?, ?, ?, 0, 'running', ?, ?)"
    );

    foreach ($ticketsByRequest as $ticket) {
        $entityId = (int) $ticket['id'];
        if (isset($existingSlaByEntity[$entityId])) {
            continue;
        }

        $slaId = (int) ($ticket['slas_id'] ?? $defaultSlaId);
        if (!isset($slaDefs[$slaId])) {
            $slaId = $defaultSlaId;
        }
        $slaDef = $slaDefs[$slaId] ?? ['response_minutes' => null, 'resolution_minutes' => null];

        $startedAt = $ticket['submitted_at'] ?? $ticket['created_at'] ?? $now;
        $base = new DateTimeImmutable((string) $startedAt);
        $dueAt = $slaDef['resolution_minutes'] !== null
            ? $base->modify('+' . $slaDef['resolution_minutes'] . ' minutes')->format('Y-m-d H:i:s')
            : null;
        $responseDueAt = $slaDef['response_minutes'] !== null
            ? $base->modify('+' . $slaDef['response_minutes'] . ' minutes')->format('Y-m-d H:i:s')
            : null;

        $insertSlaStmt->execute([
            (string) $entityId,
            (string) $slaId,
            $base->format('Y-m-d H:i:s'),
            $dueAt,
            $responseDueAt,
            $now,
            $now,
        ]);
        $insertedSlaClocks++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo "Insertion completed.\n";
echo "Workbook keys scanned: " . count($requestNumbers) . "\n";
echo "ticket_requests inserted: {$insertedTickets}\n";
echo "sla_clocks inserted: {$insertedSlaClocks}\n";

