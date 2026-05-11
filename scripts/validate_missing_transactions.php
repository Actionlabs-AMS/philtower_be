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

function batch(array $values, int $size): array
{
    return array_chunk($values, $size);
}

$xlsxPath = 'C:\\Users\\ebautista\\Downloads\\Missing Transactions after Deployment 2.xlsx';
$outCsvPath = 'C:\\Users\\ebautista\\Downloads\\Missing_Transactions_Validation_Report.csv';
$envPath = __DIR__ . '/../.env';

if (!file_exists($xlsxPath)) {
    fwrite(STDERR, "Workbook not found: {$xlsxPath}\n");
    exit(1);
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

$spreadsheet = IOFactory::load($xlsxPath);
$sheet = $spreadsheet->getSheet(0);
$rows = array_values($sheet->toArray(null, true, true, true));

if (count($rows) < 2) {
    fwrite(STDERR, "Workbook has no data rows.\n");
    exit(1);
}

$candidateHeaders = ['request_number', 'ticket_request_number', 'ticket_number', 'request_no', 'ticket_no'];
$headerRow = null;
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
    $hasCandidate = false;
    foreach ($candidateHeaders as $candidate) {
        if (isset($possibleMap[$candidate])) {
            $hasCandidate = true;
            break;
        }
    }
    if ($hasCandidate) {
        $headerRow = $possibleHeader;
        $headerMap = $possibleMap;
        $headerRowIndex = $i;
        break;
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
    fwrite(STDERR, "Could not find request number column. Headers: " . implode(', ', array_keys($headerMap)) . "\n");
    exit(1);
}

$records = [];
$duplicates = [];
$seen = [];
$rowNum = 0;
foreach ($rows as $idx => $row) {
    $rowNum = $idx + 1;
    if ($headerRowIndex !== null && $idx <= $headerRowIndex) {
        continue;
    }
    $raw = isset($row[$requestNumberCol]) ? (string) $row[$requestNumberCol] : '';
    $key = trim($raw);
    if ($key === '') {
        continue;
    }
    if (isset($seen[$key])) {
        $duplicates[$key] = ($duplicates[$key] ?? 1) + 1;
    } else {
        $seen[$key] = true;
    }
    $records[] = [
        'excel_row' => $rowNum,
        'request_number' => $key,
    ];
}

if (count($records) === 0) {
    fwrite(STDERR, "No non-empty request_number values found.\n");
    exit(1);
}

$uniqueRequestNumbers = array_values(array_unique(array_column($records, 'request_number')));
$ticketByRequest = [];

foreach (batch($uniqueRequestNumbers, 500) as $chunk) {
    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
    $sql = "SELECT id, request_number FROM ticket_requests WHERE request_number IN ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($chunk);
    foreach ($stmt->fetchAll() as $row) {
        $ticketByRequest[(string) $row['request_number']] = (int) $row['id'];
    }
}

$ticketIds = array_values(array_unique(array_values($ticketByRequest)));
$slaByEntityId = [];
if (!empty($ticketIds)) {
    foreach (batch($ticketIds, 500) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $sql = "SELECT DISTINCT entity_id FROM sla_clocks WHERE entity_type = 'ticket_request' AND entity_id IN ({$placeholders})";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_map('strval', $chunk));
        foreach ($stmt->fetchAll() as $row) {
            $slaByEntityId[(int) $row['entity_id']] = true;
        }
    }
}

$total = 0;
$existsBoth = 0;
$missingAny = 0;
$existsTicketOnly = 0;
$reportRows = [];

foreach ($records as $record) {
    $total++;
    $requestNumber = $record['request_number'];
    $ticketId = $ticketByRequest[$requestNumber] ?? null;
    $inTicket = $ticketId !== null;
    $inSla = $ticketId !== null && isset($slaByEntityId[$ticketId]);
    $status = ($inTicket && $inSla) ? 'EXISTS' : 'MISSING';

    if ($status === 'EXISTS') {
        $existsBoth++;
    } else {
        $missingAny++;
    }
    if ($inTicket && !$inSla) {
        $existsTicketOnly++;
    }

    $reportRows[] = [
        'excel_row' => $record['excel_row'],
        'request_number' => $requestNumber,
        'ticket_request_id' => $ticketId ?? '',
        'exists_in_ticket_requests' => $inTicket ? 'YES' : 'NO',
        'exists_in_sla_clocks' => $inSla ? 'YES' : 'NO',
        'status' => $status,
    ];
}

$fp = fopen($outCsvPath, 'wb');
if ($fp === false) {
    fwrite(STDERR, "Could not open output file: {$outCsvPath}\n");
    exit(1);
}

fputcsv($fp, ['excel_row', 'request_number', 'ticket_request_id', 'exists_in_ticket_requests', 'exists_in_sla_clocks', 'status']);
foreach ($reportRows as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

echo "Validation completed.\n";
echo "Input file: {$xlsxPath}\n";
echo "Output CSV: {$outCsvPath}\n";
echo "Rows checked: {$total}\n";
echo "Exists in both tables: {$existsBoth}\n";
echo "Missing in either table: {$missingAny}\n";
echo "Exists in ticket_requests only: {$existsTicketOnly}\n";
echo "Duplicate request_number values in Excel: " . count($duplicates) . "\n";

