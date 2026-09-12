<?php
declare(strict_types=1);

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function slugify_filename(string $name): string
{
    $name = trim($name);
    $name = preg_replace('/[^\p{L}\p{N}_\-]+/u', '_', $name) ?? '';
    $name = preg_replace('/_+/', '_', $name) ?? '';
    $name = trim($name, '_');
    if ($name === '') {
        $name = 'record_' . date('Ymd_His');
    }
    return $name;
}

function unique_record_path(string $dir, string $baseName, string $ext = 'png'): array
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $baseName = slugify_filename($baseName);
    $file = $baseName . '.' . $ext;
    $i = 2;
    while (file_exists($dir . '/' . $file)) {
        $file = $baseName . '_' . $i . '.' . $ext;
        $i++;
        if ($i > 9999) { break; }
    }
    return [$file, $dir . '/' . $file];
}

function extract_part(string $value, string $part): string
{
    $value = trim($value);
    if ($value === '') return '';

    $part = strtolower($part);

    $dateParts = ['year', 'yy', 'month', 'mm', 'day', 'dd'];
    if (in_array($part, $dateParts, true)) {
        $ts = strtotime($value);
        if ($ts !== false) {
            switch ($part) {
                case 'year':  return date('Y', $ts);
                case 'yy':    return date('y', $ts);
                case 'month':
                case 'mm':    return date('m', $ts);
                case 'day':
                case 'dd':    return date('d', $ts);
            }
        }
        if (preg_match('/(\d{4})[-\/\.\s](\d{1,2})[-\/\.\s](\d{1,2})/', $value, $m)) {
            switch ($part) {
                case 'year': return $m[1];
                case 'yy':   return substr($m[1], -2);
                case 'month':
                case 'mm':   return str_pad($m[2], 2, '0', STR_PAD_LEFT);
                case 'day':
                case 'dd':   return str_pad($m[3], 2, '0', STR_PAD_LEFT);
            }
        }
        return '';
    }

    switch ($part) {
        case 'upper':  return mb_strtoupper($value, 'UTF-8');
        case 'lower':  return mb_strtolower($value, 'UTF-8');
        case 'first':  return mb_substr($value, 0, 1, 'UTF-8');
        case 'last':   return mb_substr($value, -1, null, 'UTF-8');
        case 'initials': {
            $words = preg_split('/\s+/', $value) ?: [];
            $out = '';
            foreach ($words as $w) {
                if ($w === '') continue;
                $out .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
            }
            return $out;
        }
        case 'length': return (string)mb_strlen($value, 'UTF-8');
        case 'slug':   return slugify_filename($value);
        case 'trim':   return $value;
    }

    return $value;
}

function generate_locked_value(string $format, int $sequence, array $values = []): string
{
    if (trim($format) === '') $format = '{#####}';

    $format = preg_replace_callback(
        '/\{@([a-zA-Z0-9_]+)(?::([a-zA-Z0-9_]+))?\}/',
        function (array $m) use ($values): string {
            $key  = $m[1];
            $part = $m[2] ?? '';
            $val  = (string)($values[$key] ?? '');
            if ($part === '') return $val;
            return extract_part($val, $part);
        },
        $format
    ) ?? $format;

    date_default_timezone_set('Asia/Colombo');
    $now = new DateTime();
    $format = strtr($format, [
        '{YYYY}' => $now->format('Y'),
        '{YY}'   => $now->format('y'),
        '{MM}'   => $now->format('m'),
        '{DD}'   => $now->format('d'),
        '{HH}'   => $now->format('H'),
        '{MI}'   => $now->format('i'),
        '{SS}'   => $now->format('s'),
    ]);

    $format = preg_replace_callback('/\{(#+)\}/', function (array $m) use ($sequence): string {
        return str_pad((string)$sequence, strlen($m[1]), '0', STR_PAD_LEFT);
    }, $format) ?? $format;

    return $format;
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `qr_field_definitions` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `field_key` VARCHAR(64) NOT NULL UNIQUE,
          `label` VARCHAR(150) NOT NULL,
          `field_type` ENUM('text','textarea','number','email','tel','date','select','locked')
                       NOT NULL DEFAULT 'text',
          `placeholder` VARCHAR(200) DEFAULT NULL,
          `options_json` TEXT DEFAULT NULL,
          `auto_format` VARCHAR(200) DEFAULT NULL,
          `is_required` TINYINT(1) NOT NULL DEFAULT 1,
          `sort_order` INT NOT NULL DEFAULT 0,
          `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `qr_records` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `record_code` VARCHAR(120) DEFAULT NULL,
          `full_name` VARCHAR(220) NOT NULL,
          `record_data` LONGTEXT NOT NULL,
          `qr_image` VARCHAR(255) DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX `idx_full_name` (`full_name`),
          INDEX `idx_created`   (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM `qr_field_definitions`")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("
            INSERT INTO `qr_field_definitions`
              (`field_key`,`label`,`field_type`,`placeholder`,`is_required`,`sort_order`)
            VALUES
              ('full_name','Full Name','text','e.g. Kasun Perera Silva',1,1),
              ('name_with_initials','Name with Initials','text','e.g. K. P. Silva',1,2),
              ('age','Age','number','e.g. 25',1,3),
              ('address','Address','textarea','e.g. No. 24, Colombo 05',1,4);
        ");
    }
}