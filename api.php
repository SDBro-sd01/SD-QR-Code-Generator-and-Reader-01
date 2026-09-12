<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/helpers.php';

/** @var PDO $pdo */

$RECORD_DIR = __DIR__ . '/record';

ensure_schema($pdo);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        /* ============================================================
         |  FIELD DEFINITIONS
         * ========================================================== */
        case 'list_fields': {
            $rows = $pdo->query(
                "SELECT * FROM qr_field_definitions
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, id ASC"
            )->fetchAll();

            foreach ($rows as &$r) {
                $r['options'] = $r['options_json']
                    ? (json_decode($r['options_json'], true) ?: [])
                    : [];
                $r['is_required'] = (bool)$r['is_required'];
            }
            unset($r);

            json_response(['ok' => true, 'fields' => $rows]);
        }

        /* ============================================================
         |  REORDER  (drag & drop sorting)
         * ========================================================== */
        case 'reorder_fields': {
            $orderRaw = (string)($_POST['order'] ?? '[]');
            $order    = json_decode($orderRaw, true);
            if (!is_array($order) || empty($order)) {
                json_response(['ok'=>false,'error'=>'Invalid order payload.'], 422);
            }
            $stmt = $pdo->prepare("UPDATE qr_field_definitions SET sort_order = ? WHERE id = ?");
            foreach ($order as $i => $id) {
                $stmt->execute([$i + 1, (int)$id]);
            }
            json_response(['ok'=>true, 'count'=>count($order)]);
        }

        /* ============================================================
         |  PEEK NEXT ID  →  preview locked fields before saving
         * ========================================================== */
        case 'peek_next_id': {
            $nextId = (int)$pdo->query(
                "SELECT COALESCE(MAX(id), 0) + 1 FROM qr_records"
            )->fetchColumn();

            $valuesRaw = (string)($_POST['values'] ?? '{}');
            $values    = json_decode($valuesRaw, true);
            if (!is_array($values)) $values = [];

            $fields = $pdo->query(
                "SELECT field_key, field_type, auto_format
                 FROM qr_field_definitions
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, id ASC"
            )->fetchAll();

            $preview = [];
            $working = $values;
            foreach ($fields as $f) {
                if ($f['field_type'] === 'locked') {
                    $val = generate_locked_value(
                        $f['auto_format'] ?: '{#####}',
                        $nextId,
                        $working
                    );
                    $preview[$f['field_key']] = $val;
                    $working[$f['field_key']] = $val;
                }
            }

            json_response([
                'ok'             => true,
                'next_id'        => $nextId,
                'preview_values' => $preview,
            ]);
        }

        /* ============================================================
         |  SAVE FIELD
         * ========================================================== */
        case 'save_field': {
            $id          = (int)($_POST['id'] ?? 0);
            $label       = trim((string)($_POST['label'] ?? ''));
            $key         = trim((string)($_POST['field_key'] ?? ''));
            $type        = (string)($_POST['field_type'] ?? 'text');
            $placeholder = trim((string)($_POST['placeholder'] ?? ''));
            $autoFormat  = trim((string)($_POST['auto_format'] ?? ''));
            $required    = isset($_POST['is_required']) && $_POST['is_required'] !== '0' ? 1 : 0;
            $sortOrder   = (int)($_POST['sort_order'] ?? 0);
            $optionsRaw  = trim((string)($_POST['options'] ?? ''));

            $allowed = ['text','textarea','number','email','tel','date','select','locked'];
            if ($label === '') json_response(['ok'=>false,'error'=>'Label is required.'], 422);
            if (!in_array($type, $allowed, true)) $type = 'text';

            if ($key === '') {
                $key = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $label) ?? '', '_'));
                if ($key === '') $key = 'field_' . time();
            }
            $key = preg_replace('/[^a-z0-9_]/i', '_', $key) ?? $key;

            $optionsJson = null;
            if ($type === 'select') {
                $opts = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $optionsRaw) ?: [])));
                $optionsJson = json_encode($opts, JSON_UNESCAPED_UNICODE);
            }
            if ($type !== 'locked') $autoFormat = '';

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE qr_field_definitions SET
                        field_key = :k, label = :l, field_type = :t,
                        placeholder = :p, options_json = :o, auto_format = :a,
                        is_required = :r, sort_order = :s
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':k'=>$key, ':l'=>$label, ':t'=>$type,
                    ':p'=>$placeholder ?: null, ':o'=>$optionsJson,
                    ':a'=>$autoFormat ?: null, ':r'=>$required,
                    ':s'=>$sortOrder, ':id'=>$id,
                ]);
                json_response(['ok'=>true,'id'=>$id,'message'=>'Field updated.']);
            } else {
                $exists = $pdo->prepare("SELECT id FROM qr_field_definitions WHERE field_key = ?");
                $exists->execute([$key]);
                if ($exists->fetchColumn()) {
                    $key .= '_' . substr((string)time(), -4);
                }
                if ($sortOrder === 0) {
                    $sortOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM qr_field_definitions")->fetchColumn();
                }
                $stmt = $pdo->prepare(
                    "INSERT INTO qr_field_definitions
                     (field_key,label,field_type,placeholder,options_json,auto_format,is_required,sort_order)
                     VALUES (:k,:l,:t,:p,:o,:a,:r,:s)"
                );
                $stmt->execute([
                    ':k'=>$key, ':l'=>$label, ':t'=>$type,
                    ':p'=>$placeholder ?: null, ':o'=>$optionsJson,
                    ':a'=>$autoFormat ?: null, ':r'=>$required, ':s'=>$sortOrder,
                ]);
                json_response(['ok'=>true,'id'=>(int)$pdo->lastInsertId(),'message'=>'Field added.']);
            }
        }

        case 'delete_field': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_response(['ok'=>false,'error'=>'Invalid id.'], 422);
            $pdo->prepare("DELETE FROM qr_field_definitions WHERE id = ?")->execute([$id]);
            json_response(['ok'=>true]);
        }

        /* ============================================================
         |  RECORDS
         * ========================================================== */
        case 'list_records': {
            $rows = $pdo->query(
                "SELECT id, record_code, full_name, qr_image, created_at
                 FROM qr_records
                 ORDER BY id DESC
                 LIMIT 100"
            )->fetchAll();
            json_response(['ok'=>true,'records'=>$rows]);
        }

        case 'get_record': {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_response(['ok'=>false,'error'=>'Invalid id.'], 422);
            $stmt = $pdo->prepare("SELECT * FROM qr_records WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) json_response(['ok'=>false,'error'=>'Record not found.'], 404);
            $row['record_data'] = json_decode($row['record_data'], true) ?: [];
            json_response(['ok'=>true,'record'=>$row]);
        }

        case 'save_record': {
            $fields = $pdo->query(
                "SELECT * FROM qr_field_definitions
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, id ASC"
            )->fetchAll();

            if (!$fields) json_response(['ok'=>false,'error'=>'No fields defined.'], 422);

            $values = [];
            $nameFieldKey = null;
            foreach ($fields as $f) {
                $k = $f['field_key'];
                if ($f['field_type'] === 'locked') {
                    $values[$k] = null;
                    continue;
                }
                $v = trim((string)($_POST[$k] ?? ''));
                if ($f['is_required'] && $v === '') {
                    json_response(['ok'=>false,'error'=>$f['label'].' is required.'], 422);
                }
                if ($f['field_type'] === 'number' && $v !== '' && !preg_match('/^\d+(\.\d+)?$/', $v)) {
                    json_response(['ok'=>false,'error'=>$f['label'].' must be a number.'], 422);
                }
                $values[$k] = $v;

                if ($nameFieldKey === null && $k === 'full_name') $nameFieldKey = $k;
                if ($nameFieldKey === null && stripos($k, 'name') !== false) $nameFieldKey = $k;
            }
            if ($nameFieldKey === null) {
                foreach ($fields as $f) {
                    if ($f['field_type'] !== 'locked') { $nameFieldKey = $f['field_key']; break; }
                }
            }
            $fullName = $values[$nameFieldKey] ?? 'record';

            $placeholder = json_encode($values, JSON_UNESCAPED_UNICODE);
            $ins = $pdo->prepare(
                "INSERT INTO qr_records (record_code, full_name, record_data)
                 VALUES (:c, :n, :d)"
            );
            $ins->execute([
                ':c' => null,
                ':n' => $fullName,
                ':d' => $placeholder,
            ]);
            $recordId = (int)$pdo->lastInsertId();

            $recordCode = null;
            foreach ($fields as $f) {
                if ($f['field_type'] === 'locked') {
                    $val = generate_locked_value($f['auto_format'] ?: '{#####}', $recordId, $values);
                    $values[$f['field_key']] = $val;
                    if ($recordCode === null) $recordCode = $val;
                }
            }

            $payload = [];
            foreach ($fields as $f) {
                $payload[$f['label']] = (string)($values[$f['field_key']] ?? '');
            }
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

            $upd = $pdo->prepare(
                "UPDATE qr_records
                 SET record_code = :c, record_data = :d
                 WHERE id = :id"
            );
            $upd->execute([
                ':c' => $recordCode,
                ':d' => $payloadJson,
                ':id'=> $recordId,
            ]);

            json_response([
                'ok' => true,
                'record' => [
                    'id'          => $recordId,
                    'record_code' => $recordCode,
                    'full_name'   => $fullName,
                    'created_at'  => date('Y-m-d H:i:s'),
                ],
                'payload' => $payload,
                'qr_payload_string' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        }

        case 'upload_qr': {
            $recordId = (int)($_POST['record_id'] ?? 0);
            $dataUrl  = (string)($_POST['image'] ?? '');

            if ($recordId <= 0) json_response(['ok'=>false,'error'=>'Invalid record id.'], 422);
            if (!preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $dataUrl)) {
                json_response(['ok'=>false,'error'=>'Invalid image payload.'], 422);
            }

            $stmt = $pdo->prepare("SELECT id, full_name, qr_image FROM qr_records WHERE id = ?");
            $stmt->execute([$recordId]);
            $rec = $stmt->fetch();
            if (!$rec) json_response(['ok'=>false,'error'=>'Record not found.'], 404);

            $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
            if ($binary === false) json_response(['ok'=>false,'error'=>'Base64 decode failed.'], 422);

            [$fileName, $absPath] = unique_record_path($RECORD_DIR, $rec['full_name'], 'png');

            if (!empty($rec['qr_image'])) {
                $old = __DIR__ . '/' . ltrim($rec['qr_image'], '/');
                if (is_file($old)) @unlink($old);
            }

            if (@file_put_contents($absPath, $binary) === false) {
                json_response(['ok'=>false,'error'=>'Could not write QR file. Check /record permissions.'], 500);
            }

            $relPath = 'record/' . $fileName;

            $pdo->prepare("UPDATE qr_records SET qr_image = :p WHERE id = :id")
                ->execute([':p' => $relPath, ':id' => $recordId]);

            json_response(['ok'=>true, 'qr_image' => $relPath]);
        }

        case 'delete_record': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_response(['ok'=>false,'error'=>'Invalid id.'], 422);

            $stmt = $pdo->prepare("SELECT qr_image FROM qr_records WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) json_response(['ok'=>false,'error'=>'Record not found.'], 404);

            if (!empty($row['qr_image'])) {
                $abs = __DIR__ . '/' . ltrim($row['qr_image'], '/');
                if (is_file($abs)) @unlink($abs);
            }

            $pdo->prepare("DELETE FROM qr_records WHERE id = ?")->execute([$id]);
            json_response(['ok'=>true]);
        }

        default:
            json_response(['ok'=>false,'error'=>'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    json_response(['ok'=>false,'error'=>'Server error: '.$e->getMessage()], 500);
}