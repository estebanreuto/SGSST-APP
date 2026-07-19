<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/storage_schema.php';
require_once 'config/document_control_schema.php';

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$u = require_auth($conn);
$role = $_SESSION['usuario_rol'] ?? '';
if (!in_array($role, ['sst', 'representante'], true)) {
    header('Location: dashboard');
    exit;
}

ensure_storage_schema($conn);
$userId = (int)($_SESSION['usuario_id'] ?? 0);
$companyId = storage_user_company_id($conn, $userId);
document_control_backfill_legalized_pdfs($conn, $companyId, $userId);
$storage = storage_company_context($conn, $companyId);
if (!$storage) {
    http_response_code(403);
    exit('No fue posible identificar la empresa asociada.');
}

storage_prepare_company_folders($companyId, (int)$storage['nivel_plan']);
$standards = storage_standard_catalog();
$substandards = storage_substandard_catalog();
$maxStandard = storage_max_standard((int)$storage['nivel_plan']);

function storage_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function storage_file_icon(string $extension): string
{
    return match (strtolower($extension)) {
        'pdf' => 'fa-file-pdf',
        'doc', 'docx' => 'fa-file-word',
        'xls', 'xlsx', 'csv' => 'fa-file-excel',
        'ppt', 'pptx' => 'fa-file-powerpoint',
        'jpg', 'jpeg', 'png', 'webp' => 'fa-file-image',
        'zip' => 'fa-file-zipper',
        default => 'fa-file-lines',
    };
}

function storage_clean_name(string $value, int $maxLength = 180): string
{
    $value = trim((string)preg_replace('/\s+/u', ' ', $value));
    $value = str_replace(["\0", '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $value);
    $value = trim($value, ". \t\n\r\0\x0B");
    if ($value === '') {
        throw new RuntimeException('Escribe un nombre valido.');
    }
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function storage_context_url(int $standard, string $substandard, int $folderId = 0): string
{
    $params = [];
    if ($standard > 0) {
        $params['estandar'] = $standard;
    }
    if ($substandard !== '') {
        $params['subestandar'] = $substandard;
    }
    if ($folderId > 0) {
        $params['carpeta'] = $folderId;
    }
    return 'almacenamiento' . ($params ? '?' . http_build_query($params) : '');
}

function storage_load_folder(PDO $conn, int $companyId, int $folderId): ?array
{
    if ($folderId <= 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM almacenamiento_carpetas WHERE id = ? AND empresa_id = ? LIMIT 1');
    $stmt->execute([$folderId, $companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function storage_delete_file_record(PDO $conn, array $file, int $companyId): void
{
    $root = realpath(storage_company_root($companyId));
    $absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$file['ruta_relativa']);
    $realFile = is_file($absolute) ? realpath($absolute) : false;
    if ($root && $realFile && storage_path_is_within($realFile, $root)) {
        @unlink($realFile);
    }

    $stmt = $conn->prepare("DELETE FROM almacenamiento_compartidos WHERE empresa_id = ? AND tipo_objeto = 'archivo' AND objeto_id = ?");
    $stmt->execute([$companyId, (int)$file['id']]);
    $stmt = $conn->prepare('DELETE FROM almacenamiento_archivos WHERE id = ? AND empresa_id = ?');
    $stmt->execute([(int)$file['id'], $companyId]);
}

$selectedStandard = isset($_GET['estandar']) ? (int)$_GET['estandar'] : 0;
$selectedSub = trim((string)($_GET['subestandar'] ?? ''));
$selectedFolderId = isset($_GET['carpeta']) ? (int)$_GET['carpeta'] : 0;
$selectedFolder = storage_load_folder($conn, $companyId, $selectedFolderId);

if ($selectedFolder) {
    $selectedStandard = (int)$selectedFolder['estandar_numero'];
    $selectedSub = trim((string)($selectedFolder['subestandar_slug'] ?? ''));
} elseif ($selectedFolderId > 0) {
    $selectedFolderId = 0;
}

if ($selectedStandard < 1 || $selectedStandard > $maxStandard) {
    $selectedStandard = 0;
    $selectedSub = '';
    $selectedFolderId = 0;
    $selectedFolder = null;
}
if ($selectedSub !== '' && !isset($substandards[$selectedStandard][$selectedSub])) {
    $selectedSub = '';
}

if (empty($_SESSION['storage_csrf'])) {
    $_SESSION['storage_csrf'] = bin2hex(random_bytes(24));
}

$contextUrl = storage_context_url($selectedStandard, $selectedSub, $selectedFolderId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['accion'] ?? 'subir_archivos'));
    try {
        if (!hash_equals((string)$_SESSION['storage_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('La sesion del formulario vencio. Recarga la pagina.');
        }

        if ($selectedStandard === 0 && !in_array($action, ['eliminar_objetos', 'renombrar_objeto', 'compartir_objeto'], true)) {
            throw new RuntimeException('Abre primero una carpeta de estandar.');
        }

        if ($action === 'crear_carpeta') {
            $name = storage_clean_name((string)($_POST['nombre'] ?? ''));
            $parentId = $selectedFolderId > 0 ? $selectedFolderId : null;
            $duplicate = $conn->prepare("
                SELECT COUNT(*) FROM almacenamiento_carpetas
                WHERE empresa_id = ? AND estandar_numero = ? AND subestandar_slug <=> ?
                  AND parent_id <=> ? AND LOWER(nombre) = LOWER(?)
            ");
            $duplicate->execute([$companyId, $selectedStandard, $selectedSub !== '' ? $selectedSub : null, $parentId, $name]);
            if ((int)$duplicate->fetchColumn() > 0) {
                throw new RuntimeException('Ya existe una carpeta con ese nombre en esta ubicacion.');
            }

            $stmt = $conn->prepare("
                INSERT INTO almacenamiento_carpetas
                    (empresa_id, estandar_numero, subestandar_slug, parent_id, nombre, nombre_guardado, usuario_id)
                VALUES (?, ?, ?, ?, ?, 'pendiente', ?)
            ");
            $stmt->execute([$companyId, $selectedStandard, $selectedSub !== '' ? $selectedSub : null, $parentId, $name, $userId]);
            $folderId = (int)$conn->lastInsertId();
            $savedFolder = 'carpeta-' . $folderId . '-' . storage_slug($name);
            $conn->prepare('UPDATE almacenamiento_carpetas SET nombre_guardado = ? WHERE id = ?')->execute([$savedFolder, $folderId]);
            $physicalFolder = storage_custom_folder_path($companyId, $folderId);
            if (!is_dir($physicalFolder) && !mkdir($physicalFolder, 0775, true) && !is_dir($physicalFolder)) {
                $conn->prepare('DELETE FROM almacenamiento_carpetas WHERE id = ? AND empresa_id = ?')->execute([$folderId, $companyId]);
                throw new RuntimeException('No fue posible crear la carpeta fisica.');
            }
            $success = 'Carpeta creada correctamente.';
        } elseif ($action === 'subir_archivos') {
            if (!isset($_FILES['archivos'])) {
                throw new RuntimeException('Selecciona uno o varios archivos para continuar.');
            }

            $upload = $_FILES['archivos'];
            $names = is_array($upload['name']) ? $upload['name'] : [$upload['name']];
            $tmpNames = is_array($upload['tmp_name']) ? $upload['tmp_name'] : [$upload['tmp_name']];
            $sizes = is_array($upload['size']) ? $upload['size'] : [$upload['size']];
            $errors = is_array($upload['error']) ? $upload['error'] : [$upload['error']];
            if (count($names) > 20) {
                throw new RuntimeException('Puedes subir maximo 20 archivos por lote.');
            }

            $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','webp','txt','csv','zip'];
            $currentStorage = storage_company_context($conn, $companyId);
            $usedBytes = (int)($currentStorage['usado_bytes'] ?? 0);
            $quotaBytes = (int)($currentStorage['cuota_bytes'] ?? 0);
            $folderPath = $selectedFolderId > 0
                ? storage_custom_folder_path($companyId, $selectedFolderId)
                : storage_folder_path($companyId, $selectedStandard, $selectedSub !== '' ? $selectedSub : null);
            if (!is_dir($folderPath) && !mkdir($folderPath, 0775, true) && !is_dir($folderPath)) {
                throw new RuntimeException('No fue posible preparar la carpeta seleccionada.');
            }

            $uploadedCount = 0;
            $createdFiles = [];
            try {
                $conn->beginTransaction();
                foreach ($names as $index => $name) {
                    if ((int)($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $size = (int)($sizes[$index] ?? 0);
                    if ($size <= 0 || $size > 50 * 1024 * 1024) {
                        throw new RuntimeException('Cada archivo debe pesar menos de 50 MB.');
                    }
                    if (($usedBytes + $size) > $quotaBytes) {
                        throw new RuntimeException('La empresa no tiene espacio suficiente en su plan.');
                    }

                    $original = storage_clean_name(basename((string)$name), 255);
                    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
                    if (!in_array($extension, $allowed, true)) {
                        throw new RuntimeException('El archivo "' . $original . '" usa un formato no permitido.');
                    }
                    $tmp = (string)($tmpNames[$index] ?? '');
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream';
                    $savedName = date('Ymd-His') . '-' . bin2hex(random_bytes(7)) . '.' . $extension;
                    $destination = $folderPath . DIRECTORY_SEPARATOR . $savedName;
                    if (!move_uploaded_file($tmp, $destination)) {
                        throw new RuntimeException('No fue posible guardar "' . $original . '".');
                    }
                    $createdFiles[] = $destination;
                    $relative = str_replace('\\', '/', substr($destination, strlen(dirname(__DIR__)) + 1));
                    $stmt = $conn->prepare("
                        INSERT INTO almacenamiento_archivos
                            (empresa_id, estandar_numero, estandar_nombre, subestandar_slug, subestandar_nombre,
                             carpeta_id, nombre_original, nombre_guardado, ruta_relativa, tipo_mime, extension,
                             tamano_bytes, usuario_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $companyId, $selectedStandard, $standards[$selectedStandard],
                        $selectedSub !== '' ? $selectedSub : null,
                        $selectedSub !== '' ? $substandards[$selectedStandard][$selectedSub] : null,
                        $selectedFolderId > 0 ? $selectedFolderId : null,
                        $original, $savedName, $relative, $mime, $extension, $size, $userId,
                    ]);
                    $usedBytes += $size;
                    $uploadedCount++;
                }
                $conn->commit();
            } catch (Throwable $uploadError) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                foreach ($createdFiles as $createdFile) {
                    if (is_file($createdFile)) {
                        @unlink($createdFile);
                    }
                }
                throw $uploadError;
            }

            if ($uploadedCount === 0) {
                throw new RuntimeException('No se encontro ningun archivo valido para subir.');
            }
            $success = $uploadedCount . ($uploadedCount === 1 ? ' archivo cargado correctamente.' : ' archivos cargados correctamente.');
        } elseif ($action === 'renombrar_objeto') {
            $type = (string)($_POST['tipo_objeto'] ?? '');
            $objectId = (int)($_POST['objeto_id'] ?? 0);
            $newName = storage_clean_name((string)($_POST['nombre_nuevo'] ?? ''), 255);
            if ($type === 'archivo') {
                $stmt = $conn->prepare('SELECT * FROM almacenamiento_archivos WHERE id = ? AND empresa_id = ? LIMIT 1');
                $stmt->execute([$objectId, $companyId]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$file) {
                    throw new RuntimeException('El archivo ya no esta disponible.');
                }
                $currentExtension = strtolower((string)$file['extension']);
                $newExtension = strtolower(pathinfo($newName, PATHINFO_EXTENSION));
                if ($newExtension === '') {
                    $newName .= '.' . $currentExtension;
                } elseif ($newExtension !== $currentExtension) {
                    throw new RuntimeException('Para proteger el documento, conserva la extension .' . $currentExtension . '.');
                }
                $conn->prepare('UPDATE almacenamiento_archivos SET nombre_original = ? WHERE id = ? AND empresa_id = ?')->execute([$newName, $objectId, $companyId]);
                $success = 'Archivo renombrado correctamente.';
            } elseif ($type === 'carpeta') {
                $folder = storage_load_folder($conn, $companyId, $objectId);
                if (!$folder) {
                    throw new RuntimeException('La carpeta ya no esta disponible.');
                }
                $duplicate = $conn->prepare("
                    SELECT COUNT(*) FROM almacenamiento_carpetas
                    WHERE empresa_id = ? AND estandar_numero = ? AND subestandar_slug <=> ?
                      AND parent_id <=> ? AND LOWER(nombre) = LOWER(?) AND id <> ?
                ");
                $duplicate->execute([$companyId, (int)$folder['estandar_numero'], $folder['subestandar_slug'], $folder['parent_id'], $newName, $objectId]);
                if ((int)$duplicate->fetchColumn() > 0) {
                    throw new RuntimeException('Ya existe otra carpeta con ese nombre.');
                }
                $savedFolder = 'carpeta-' . $objectId . '-' . storage_slug($newName);
                $conn->prepare('UPDATE almacenamiento_carpetas SET nombre = ?, nombre_guardado = ? WHERE id = ? AND empresa_id = ?')->execute([$newName, $savedFolder, $objectId, $companyId]);
                $success = 'Carpeta renombrada correctamente.';
            } else {
                throw new RuntimeException('Selecciona un archivo o carpeta valido.');
            }
        } elseif ($action === 'eliminar_objetos') {
            $fileIds = json_decode((string)($_POST['archivo_ids'] ?? '[]'), true);
            $folderIds = json_decode((string)($_POST['carpeta_ids'] ?? '[]'), true);
            $fileIds = array_values(array_unique(array_filter(array_map('intval', is_array($fileIds) ? $fileIds : []))));
            $folderIds = array_values(array_unique(array_filter(array_map('intval', is_array($folderIds) ? $folderIds : []))));
            if (!$fileIds && !$folderIds) {
                throw new RuntimeException('Selecciona al menos un elemento para eliminar.');
            }

            foreach ($folderIds as $folderId) {
                if (!storage_load_folder($conn, $companyId, $folderId)) {
                    continue;
                }
                $descendants = storage_folder_descendant_ids($conn, $companyId, $folderId);
                if (!$descendants) {
                    continue;
                }
                $placeholders = implode(',', array_fill(0, count($descendants), '?'));
                $stmt = $conn->prepare("SELECT * FROM almacenamiento_archivos WHERE empresa_id = ? AND carpeta_id IN ($placeholders)");
                $stmt->execute([$companyId, ...$descendants]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
                    storage_delete_file_record($conn, $file, $companyId);
                }
                $conn->prepare("DELETE FROM almacenamiento_compartidos WHERE empresa_id = ? AND tipo_objeto = 'carpeta' AND objeto_id IN ($placeholders)")->execute([$companyId, ...$descendants]);
                $conn->prepare("DELETE FROM almacenamiento_carpetas WHERE empresa_id = ? AND id IN ($placeholders)")->execute([$companyId, ...$descendants]);
                rsort($descendants);
                $root = realpath(storage_company_root($companyId));
                foreach ($descendants as $descendantId) {
                    $directory = storage_custom_folder_path($companyId, $descendantId);
                    $realDirectory = is_dir($directory) ? realpath($directory) : false;
                    if ($root && $realDirectory && storage_path_is_within($realDirectory, $root)) {
                        @rmdir($realDirectory);
                    }
                }
            }

            if ($fileIds) {
                $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
                $stmt = $conn->prepare("SELECT * FROM almacenamiento_archivos WHERE empresa_id = ? AND id IN ($placeholders)");
                $stmt->execute([$companyId, ...$fileIds]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
                    storage_delete_file_record($conn, $file, $companyId);
                }
            }
            $success = 'Los elementos seleccionados fueron eliminados.';
        } elseif ($action === 'compartir_objeto') {
            $type = (string)($_POST['tipo_objeto'] ?? '');
            $objectId = (int)($_POST['objeto_id'] ?? 0);
            if ($type === 'archivo') {
                $stmt = $conn->prepare('SELECT COUNT(*) FROM almacenamiento_archivos WHERE id = ? AND empresa_id = ?');
            } elseif ($type === 'carpeta') {
                $stmt = $conn->prepare('SELECT COUNT(*) FROM almacenamiento_carpetas WHERE id = ? AND empresa_id = ?');
            } else {
                throw new RuntimeException('Selecciona un elemento valido para compartir.');
            }
            $stmt->execute([$objectId, $companyId]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new RuntimeException('El elemento ya no esta disponible.');
            }

            $plainToken = bin2hex(random_bytes(32));
            $stmt = $conn->prepare("
                INSERT INTO almacenamiento_compartidos
                    (empresa_id, tipo_objeto, objeto_id, token_hash, creado_por, vence_en)
                VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
            ");
            $stmt->execute([$companyId, $type, $objectId, hash('sha256', $plainToken), $userId]);
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            $scheme = $https ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/SGSST-APP/almacenamiento.php')), '/');
            $_SESSION['storage_share_url'] = $scheme . '://' . $host . $base . '/archivo_compartido?token=' . $plainToken;
            $success = 'Enlace seguro creado. Vence en 7 dias.';
        } else {
            throw new RuntimeException('La accion solicitada no es valida.');
        }

        $_SESSION['storage_flash'] = ['tipo' => 'ok', 'mensaje' => $success ?? 'Operacion completada.'];
    } catch (Throwable $error) {
        $_SESSION['storage_flash'] = ['tipo' => 'error', 'mensaje' => $error->getMessage()];
    }

    header('Location: ' . $contextUrl);
    exit;
}

$flash = $_SESSION['storage_flash'] ?? null;
unset($_SESSION['storage_flash']);
$shareUrl = (string)($_SESSION['storage_share_url'] ?? '');
unset($_SESSION['storage_share_url']);

$storage = storage_company_context($conn, $companyId);
$folderCounts = [];
$stmtCounts = $conn->prepare('SELECT estandar_numero, COUNT(*) total, COALESCE(SUM(tamano_bytes),0) bytes FROM almacenamiento_archivos WHERE empresa_id = ? GROUP BY estandar_numero');
$stmtCounts->execute([$companyId]);
foreach ($stmtCounts->fetchAll(PDO::FETCH_ASSOC) as $count) {
    $folderCounts[(int)$count['estandar_numero']] = $count;
}

$customFolders = [];
$files = [];
if ($selectedStandard > 0) {
    if ($selectedFolderId > 0) {
        $stmtFolders = $conn->prepare('SELECT * FROM almacenamiento_carpetas WHERE empresa_id = ? AND parent_id = ? ORDER BY nombre ASC');
        $stmtFolders->execute([$companyId, $selectedFolderId]);
        $stmtFiles = $conn->prepare('SELECT * FROM almacenamiento_archivos WHERE empresa_id = ? AND carpeta_id = ? ORDER BY actualizado_en DESC, id DESC');
        $stmtFiles->execute([$companyId, $selectedFolderId]);
    } else {
        $stmtFolders = $conn->prepare('SELECT * FROM almacenamiento_carpetas WHERE empresa_id = ? AND estandar_numero = ? AND subestandar_slug <=> ? AND parent_id IS NULL ORDER BY nombre ASC');
        $stmtFolders->execute([$companyId, $selectedStandard, $selectedSub !== '' ? $selectedSub : null]);
        $stmtFiles = $conn->prepare('SELECT * FROM almacenamiento_archivos WHERE empresa_id = ? AND estandar_numero = ? AND subestandar_slug <=> ? AND carpeta_id IS NULL ORDER BY actualizado_en DESC, id DESC');
        $stmtFiles->execute([$companyId, $selectedStandard, $selectedSub !== '' ? $selectedSub : null]);
    }
    $customFolders = $stmtFolders->fetchAll(PDO::FETCH_ASSOC);
    $files = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);
}

$folderTrail = [];
if ($selectedFolder) {
    $cursor = $selectedFolder;
    while ($cursor) {
        array_unshift($folderTrail, $cursor);
        $parentId = (int)($cursor['parent_id'] ?? 0);
        $cursor = $parentId > 0 ? storage_load_folder($conn, $companyId, $parentId) : null;
    }
}

$usagePercentage = min(100, max(0, (float)$storage['porcentaje']));
$availableBytes = max(0, (int)$storage['cuota_bytes'] - (int)$storage['usado_bytes']);
$currentLocationName = $selectedFolder
    ? (string)$selectedFolder['nombre']
    : ($selectedSub !== '' ? (string)$substandards[$selectedStandard][$selectedSub] : ($selectedStandard > 0 ? 'Estandar ' . $selectedStandard : 'Mis archivos'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almacenamiento documental | PreventWork</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--orange:#ff7500;--orange-soft:#fff4e8;--blue:#173b8f;--blue-2:#2563eb;--text:#14233e;--muted:#64748b;--border:#dbe4ef;--surface:#fff;--bg:#f3f7fc;--danger:#dc2626}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;min-height:100vh;display:flex;background:linear-gradient(180deg,#edf4fb,#f8fafc);font-family:Inter,sans-serif;color:var(--text);font-size:.79rem}button,input{font:inherit}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh;transition:all .3s ease}.content-area{width:100%;max-width:none;margin:0;padding:14px clamp(18px,2.3vw,42px) 42px}
        .drive-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:8px 0 12px}.heading-copy{display:flex;align-items:center;gap:12px;min-width:0}.heading-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:10px;background:#eaf2ff;color:var(--blue-2);font-size:1.15rem}.heading-copy h1{margin:0 0 3px;color:var(--blue);font-size:1.2rem;letter-spacing:-.025em}.heading-copy p{margin:0;color:var(--muted);font-size:.7rem}.quota-compact{display:grid;grid-template-columns:auto 118px auto;align-items:center;gap:10px;min-width:300px;padding:9px 11px;border:1px solid var(--border);border-radius:10px;background:#fff;color:inherit;text-decoration:none;box-shadow:0 5px 16px rgba(15,35,70,.035)}.quota-copy{display:flex;flex-direction:column;gap:2px}.quota-copy strong{color:var(--blue);font-size:.68rem}.quota-copy span{color:var(--muted);font-size:.58rem}.quota-bar{height:5px;overflow:hidden;border-radius:99px;background:#e5ebf3}.quota-bar span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#2563eb,#22c55e)}.quota-percent{color:var(--blue-2);font-size:.78rem;font-weight:800}
        .drive-toolbar{position:sticky;top:83px;z-index:30;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0;background:rgba(243,247,252,.94);backdrop-filter:blur(8px)}.toolbar-left,.toolbar-right,.selection-actions{display:flex;align-items:center;gap:7px}.drive-btn{height:36px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 11px;border:1px solid #d4deea;border-radius:8px;background:#fff;color:#36506f;font-size:.68rem;font-weight:800;cursor:pointer;text-decoration:none;transition:.18s}.drive-btn:hover{border-color:#f8b77f;color:#c2410c;background:#fffaf5}.drive-btn.primary{border-color:var(--orange);background:var(--orange);color:#fff}.drive-btn.primary:hover{background:#e96800}.drive-btn.icon-only{width:36px;padding:0}.drive-btn.danger{color:var(--danger)}.drive-btn:disabled{opacity:.45;cursor:not-allowed}.search-box{position:relative}.search-box i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8}.search-box input{width:min(330px,32vw);height:36px;padding:0 34px;border:1px solid #d4deea;border-radius:8px;background:#fff;outline:none;color:var(--text);font-size:.69rem}.search-box input:focus{border-color:#93b4eb;box-shadow:0 0 0 3px rgba(37,99,235,.08)}.search-clear{position:absolute;right:6px;top:6px;width:24px;height:24px;border:0;border-radius:6px;background:transparent;color:#94a3b8;cursor:pointer;display:none}.search-clear.visible{display:block}
        .selection-bar{display:none;align-items:center;justify-content:space-between;gap:12px;margin:0 0 8px;padding:8px 10px;border:1px solid #bfdbfe;border-radius:9px;background:#eff6ff}.selection-bar.visible{display:flex}.selection-count{color:#1d4ed8;font-weight:800;font-size:.68rem}
        .breadcrumbs{display:flex;align-items:center;gap:7px;min-height:38px;padding:7px 0 10px;border-bottom:1px solid var(--border);overflow-x:auto;white-space:nowrap}.breadcrumbs a,.breadcrumbs span{display:inline-flex;align-items:center;gap:6px;color:#60718d;text-decoration:none;font-size:.68rem;font-weight:700}.breadcrumbs a:hover{color:var(--orange)}.breadcrumbs .current{color:var(--blue);font-weight:800}.breadcrumbs>i{color:#a2afc0;font-size:.55rem}
        .alert{margin:10px 0 0;padding:10px 12px;border-radius:8px;font-size:.68rem;font-weight:700}.alert.ok{border:1px solid #bbf7d0;background:#ecfdf5;color:#047857}.alert.error{border:1px solid #fed7aa;background:#fff7ed;color:#c2410c}
        .drive-section{padding:18px 0 4px}.section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.section-head h2{margin:0;color:var(--blue);font-size:.83rem}.section-head span{color:var(--muted);font-size:.62rem}.folder-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px}.folder-card{position:relative;min-width:0;border:1px solid var(--border);border-radius:9px;background:#fff;transition:.18s;overflow:visible}.folder-card:hover,.folder-card.selected{border-color:#93b4eb;background:#f8fbff;box-shadow:0 6px 15px rgba(37,99,235,.07)}.folder-main{display:flex;align-items:center;gap:9px;min-height:58px;padding:9px 34px 9px 10px;color:inherit;text-decoration:none;min-width:0}.folder-main>i{font-size:1.15rem;color:#f59e0b;flex:0 0 auto}.folder-card.custom .folder-main>i{color:#3b82f6}.folder-text{min-width:0}.folder-text strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#243b67;font-size:.68rem}.folder-text small{display:block;margin-top:3px;color:#7a8aa1;font-size:.57rem}.folder-select{position:absolute;left:7px;top:7px;z-index:3;opacity:0;pointer-events:none}.folder-card.custom .folder-main{padding-left:34px}.folder-card.custom .folder-select{opacity:1;pointer-events:auto}.item-check{width:16px;height:16px;margin:0;accent-color:var(--blue-2);cursor:pointer}.item-menu-button{position:absolute;right:6px;top:50%;width:28px;height:28px;border:0;border-radius:7px;background:transparent;color:#7d8da4;cursor:pointer;transform:translateY(-50%)}.item-menu-button:hover{background:#eef3f8;color:#1e40af}.item-menu{position:absolute;right:5px;top:48px;z-index:60;display:none;width:172px;padding:5px;border:1px solid var(--border);border-radius:9px;background:#fff;box-shadow:0 14px 32px rgba(15,23,42,.16)}.item-menu.open{display:block}.item-menu.floating{position:fixed;right:auto;top:auto;z-index:2200;max-height:calc(100vh - 16px);overflow-y:auto;overscroll-behavior:contain}.item-menu button,.item-menu a{width:100%;display:flex;align-items:center;gap:8px;padding:8px;border:0;border-radius:6px;background:transparent;color:#3c506e;text-decoration:none;text-align:left;font-size:.64rem;font-weight:700;cursor:pointer}.item-menu button:hover,.item-menu a:hover{background:#f1f5f9}.item-menu .delete-action{color:var(--danger)}
        .file-table{width:100%;border-collapse:separate;border-spacing:0;background:transparent}.file-table th{padding:8px 10px;border-bottom:1px solid var(--border);color:#7a8aa1;text-align:left;font-size:.58rem;text-transform:uppercase}.file-table td{height:54px;padding:7px 10px;border-bottom:1px solid #e5ebf2;background:rgba(255,255,255,.62);font-size:.64rem}.file-table tbody tr:hover td,.file-table tbody tr.selected td{background:#f5f9ff}.file-table .check-cell{width:38px}.file-table .icon-cell{width:35px;padding-right:0}.file-type-icon{font-size:1.05rem;color:#2563eb}.file-type-icon.fa-file-pdf{color:#dc2626}.file-type-icon.fa-file-excel{color:#059669}.file-type-icon.fa-file-word{color:#2563eb}.file-name{max-width:460px}.file-name strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#203967;font-size:.67rem}.file-name small{display:none;color:#7a8aa1;margin-top:2px}.file-actions{width:42px;position:relative}.file-actions .item-menu-button{position:static;transform:none}.empty-state{display:grid;place-items:center;min-height:220px;padding:34px;text-align:center}.empty-state i{margin-bottom:10px;color:#b6c5d8;font-size:2rem}.empty-state strong{display:block;color:#465b79;font-size:.76rem}.empty-state span{display:block;margin-top:5px;color:#7a8aa1;font-size:.66rem}.no-results{display:none;padding:24px;color:#64748b;text-align:center}.no-results.visible{display:block}
        .modal-backdrop{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.46);backdrop-filter:blur(3px)}.modal-backdrop.open{display:flex}.drive-modal{width:min(520px,100%);max-height:92vh;overflow:auto;border:1px solid #dbe4ef;border-radius:14px;background:#fff;box-shadow:0 24px 60px rgba(15,23,42,.24)}.modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid var(--border)}.modal-head h3{margin:0 0 3px;color:var(--blue);font-size:.9rem}.modal-head p{margin:0;color:var(--muted);font-size:.65rem}.modal-close{width:30px;height:30px;border:0;border-radius:8px;background:#f1f5f9;color:#64748b;cursor:pointer}.modal-body{padding:17px 18px}.modal-actions{display:flex;justify-content:flex-end;gap:8px;padding:13px 18px;border-top:1px solid var(--border)}.form-field label{display:block;margin-bottom:6px;color:#304869;font-size:.62rem;font-weight:800;text-transform:uppercase}.form-field input[type=text]{width:100%;height:42px;padding:0 12px;border:1px solid #ccd8e6;border-radius:8px;outline:none}.form-field input:focus{border-color:#7ba5ec;box-shadow:0 0 0 3px rgba(37,99,235,.08)}.drop-zone{position:relative;display:grid;place-items:center;min-height:190px;padding:24px;border:1.5px dashed #8db5f5;border-radius:12px;background:linear-gradient(180deg,#f5f9ff,#fff);text-align:center;cursor:pointer;transition:.18s}.drop-zone.dragging{border-color:var(--orange);background:#fff8f1}.drop-zone input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.drop-zone i{margin-bottom:12px;color:#2563eb;font-size:2rem}.drop-zone strong{display:block;color:var(--blue);font-size:.77rem}.drop-zone span{display:block;margin-top:5px;color:#718199;font-size:.64rem}.selected-files{display:grid;gap:6px;margin-top:10px}.selected-file{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 9px;border-radius:8px;background:#f3f6fa;color:#435775;font-size:.63rem}.selected-file span:first-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.share-box{display:flex;gap:7px}.share-box input{flex:1;min-width:0;height:40px;padding:0 10px;border:1px solid #ccd8e6;border-radius:8px;background:#f8fafc;color:#475569;font-size:.63rem}.share-note,.delete-note{margin:10px 0 0;color:#6b7c94;font-size:.63rem;line-height:1.5}.delete-visual{display:flex;align-items:center;gap:11px}.delete-visual i{width:42px;height:42px;display:grid;place-items:center;border-radius:10px;background:#fef2f2;color:#dc2626;font-size:1rem}.details-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.detail-box{padding:10px;border-radius:8px;background:#f6f8fb}.detail-box span{display:block;color:#8190a6;font-size:.56rem;text-transform:uppercase;font-weight:800}.detail-box strong{display:block;margin-top:4px;color:#334966;font-size:.66rem;word-break:break-word}
        @media(max-width:1280px){.folder-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}@media(max-width:980px){.folder-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.file-table .date-column{display:none}.quota-compact{min-width:260px;grid-template-columns:auto 85px auto}}
        @media(max-width:768px){body{display:block}.main-wrapper{margin-left:0;width:100%}.content-area{padding:12px 12px 34px}.drive-heading{align-items:flex-start;flex-direction:column}.quota-compact{width:100%;grid-template-columns:auto 1fr auto}.drive-toolbar{top:76px;align-items:stretch;flex-direction:column}.toolbar-left,.toolbar-right{width:100%}.toolbar-left{overflow-x:auto}.toolbar-right .search-box,.search-box input{width:100%}.folder-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.file-table .size-column{display:none}.selection-bar{align-items:flex-start;flex-direction:column}.selection-actions{width:100%}.selection-actions .drive-btn{flex:1}}
        @media(max-width:520px){.heading-copy{align-items:flex-start}.heading-copy h1{font-size:1.05rem}.quota-compact{grid-template-columns:1fr auto}.quota-bar{grid-column:1/-1;grid-row:2}.folder-grid{grid-template-columns:1fr}.file-table th{display:none}.file-table td{height:auto;padding-top:10px;padding-bottom:10px}.file-table .icon-cell{width:28px}.file-table .file-name small{display:block}.file-table .type-column{display:none}.details-grid{grid-template-columns:1fr}.modal-actions .drive-btn{flex:1}}
        @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content-area">
        <section class="drive-heading">
            <div class="heading-copy">
                <span class="heading-icon"><i class="fa-solid fa-cloud"></i></span>
                <div>
                    <h1>Archivos de <?php echo storage_h($storage['empresa_nombre']); ?></h1>
                    <p>Documentos organizados por estandares, subestandares y carpetas de trabajo.</p>
                </div>
            </div>
            <a class="quota-compact" href="almacenamiento" title="Ver almacenamiento de la empresa">
                <span class="quota-copy"><strong>Plan <?php echo storage_h($storage['plan_nombre']); ?></strong><span><?php echo storage_format_bytes((int)$storage['usado_bytes']); ?> de <?php echo number_format((float)$storage['cuota_gb'], 0, ',', '.'); ?> GB</span></span>
                <span class="quota-bar"><span style="width:<?php echo $usagePercentage; ?>%"></span></span>
                <strong class="quota-percent"><?php echo number_format($usagePercentage, 1, ',', '.'); ?>%</strong>
            </a>
        </section>

        <div class="drive-toolbar">
            <div class="toolbar-left">
                <?php if ($selectedStandard > 0): ?>
                    <button class="drive-btn primary" type="button" data-open-modal="uploadModal"><i class="fa-solid fa-cloud-arrow-up"></i> Subir archivos</button>
                    <button class="drive-btn" type="button" data-open-modal="folderModal"><i class="fa-solid fa-folder-plus"></i> Nueva carpeta</button>
                <?php else: ?>
                    <span class="drive-btn" style="cursor:default"><i class="fa-solid fa-folder-tree"></i> <?php echo $maxStandard; ?> estandares incluidos</span>
                <?php endif; ?>
                <?php if ($selectedStandard > 0): ?>
                    <a class="drive-btn icon-only" href="<?php echo $selectedFolder && (int)$selectedFolder['parent_id'] > 0 ? storage_context_url($selectedStandard, $selectedSub, (int)$selectedFolder['parent_id']) : ($selectedSub !== '' || $selectedFolder ? storage_context_url($selectedStandard, '', 0) : 'almacenamiento'); ?>" title="Subir un nivel"><i class="fa-solid fa-arrow-left"></i></a>
                <?php endif; ?>
            </div>
            <div class="toolbar-right">
                <label class="search-box" for="storageSearch"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="storageSearch" autocomplete="off" placeholder="Buscar en esta ubicacion..."><button type="button" class="search-clear" id="searchClear">×</button></label>
            </div>
        </div>

        <div class="selection-bar" id="selectionBar">
            <span class="selection-count" id="selectionCount">0 elementos seleccionados</span>
            <div class="selection-actions">
                <button class="drive-btn" type="button" id="selectionInfo"><i class="fa-solid fa-circle-info"></i> Informacion</button>
                <button class="drive-btn" type="button" id="selectionShare"><i class="fa-solid fa-share-nodes"></i> Compartir</button>
                <button class="drive-btn" type="button" id="selectionRename"><i class="fa-solid fa-pen"></i> Renombrar</button>
                <button class="drive-btn danger" type="button" id="selectionDelete"><i class="fa-solid fa-trash"></i> Eliminar</button>
                <button class="drive-btn icon-only" type="button" id="selectionClear" title="Limpiar seleccion"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>

        <nav class="breadcrumbs" aria-label="Ruta actual">
            <a href="almacenamiento"><i class="fa-solid fa-building"></i> <?php echo storage_h($storage['empresa_nombre']); ?></a>
            <?php if ($selectedStandard > 0): ?><i class="fa-solid fa-chevron-right"></i><a href="almacenamiento?estandar=<?php echo $selectedStandard; ?>">Estandar <?php echo $selectedStandard; ?></a><?php endif; ?>
            <?php if ($selectedSub !== ''): ?><i class="fa-solid fa-chevron-right"></i><a href="<?php echo storage_context_url($selectedStandard, $selectedSub); ?>"><?php echo storage_h($substandards[$selectedStandard][$selectedSub]); ?></a><?php endif; ?>
            <?php foreach ($folderTrail as $index => $trailFolder): ?><i class="fa-solid fa-chevron-right"></i><?php if ($index === count($folderTrail) - 1): ?><span class="current"><?php echo storage_h($trailFolder['nombre']); ?></span><?php else: ?><a href="<?php echo storage_context_url($selectedStandard, $selectedSub, (int)$trailFolder['id']); ?>"><?php echo storage_h($trailFolder['nombre']); ?></a><?php endif; ?><?php endforeach; ?>
            <?php if ($selectedStandard === 0): ?><span class="current"><i class="fa-solid fa-hard-drive"></i> Mis archivos</span><?php endif; ?>
        </nav>

        <?php if ($flash): ?><div class="alert <?php echo ($flash['tipo'] ?? '') === 'error' ? 'error' : 'ok'; ?>"><?php echo storage_h($flash['mensaje'] ?? ''); ?></div><?php endif; ?>

        <?php if ($selectedStandard === 0): ?>
            <section class="drive-section">
                <div class="section-head"><h2>Carpetas del SG-SST</h2><span><?php echo $maxStandard; ?> carpetas disponibles segun tu plan</span></div>
                <div class="folder-grid" id="storageItems">
                    <?php for ($i = 1; $i <= $maxStandard; $i++): $count = $folderCounts[$i] ?? ['total' => 0, 'bytes' => 0]; ?>
                        <article class="folder-card storage-item" data-search="<?php echo storage_h(mb_strtolower($standards[$i], 'UTF-8')); ?>">
                            <a class="folder-main" href="almacenamiento?estandar=<?php echo $i; ?>">
                                <i class="fa-solid fa-folder"></i>
                                <span class="folder-text"><strong><?php echo str_pad((string)$i, 2, '0', STR_PAD_LEFT); ?> · <?php echo storage_h($standards[$i]); ?></strong><small><?php echo (int)$count['total']; ?> archivos · <?php echo storage_format_bytes((int)$count['bytes']); ?></small></span>
                            </a>
                        </article>
                    <?php endfor; ?>
                </div>
            </section>
        <?php else: ?>
            <?php if ($selectedSub === '' && !$selectedFolder && !empty($substandards[$selectedStandard])): ?>
                <section class="drive-section">
                    <div class="section-head"><h2>Subestandares</h2><span>Carpetas protegidas del estandar <?php echo $selectedStandard; ?></span></div>
                    <div class="folder-grid">
                        <?php foreach ($substandards[$selectedStandard] as $slug => $label): ?>
                            <article class="folder-card storage-item" data-search="<?php echo storage_h(mb_strtolower($label, 'UTF-8')); ?>">
                                <a class="folder-main" href="<?php echo storage_context_url($selectedStandard, $slug); ?>"><i class="fa-solid fa-folder-tree" style="color:#3b82f6"></i><span class="folder-text"><strong><?php echo storage_h($label); ?></strong><small>Subcarpeta del SG-SST</small></span></a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($customFolders): ?>
                <section class="drive-section">
                    <div class="section-head"><h2>Carpetas</h2><span><?php echo count($customFolders); ?> carpeta<?php echo count($customFolders) === 1 ? '' : 's'; ?></span></div>
                    <div class="folder-grid">
                        <?php foreach ($customFolders as $folder): ?>
                            <article class="folder-card custom storage-item selectable-item" data-search="<?php echo storage_h(mb_strtolower($folder['nombre'], 'UTF-8')); ?>" data-type="carpeta" data-id="<?php echo (int)$folder['id']; ?>" data-name="<?php echo storage_h($folder['nombre']); ?>" data-created="<?php echo storage_h(date('d/m/Y · h:i a', strtotime($folder['creado_en']))); ?>" data-location="<?php echo storage_h($currentLocationName); ?>">
                                <label class="folder-select" title="Seleccionar"><input class="item-check" type="checkbox" aria-label="Seleccionar <?php echo storage_h($folder['nombre']); ?>"></label>
                                <a class="folder-main" href="<?php echo storage_context_url($selectedStandard, $selectedSub, (int)$folder['id']); ?>"><i class="fa-solid fa-folder"></i><span class="folder-text"><strong><?php echo storage_h($folder['nombre']); ?></strong><small>Carpeta creada · <?php echo date('d/m/Y', strtotime($folder['creado_en'])); ?></small></span></a>
                                <button class="item-menu-button" type="button" aria-label="Acciones de carpeta"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                <div class="item-menu">
                                    <button type="button" data-item-action="info"><i class="fa-solid fa-circle-info"></i> Ver informacion</button>
                                    <button type="button" data-item-action="rename"><i class="fa-solid fa-pen"></i> Cambiar nombre</button>
                                    <button type="button" data-item-action="share"><i class="fa-solid fa-share-nodes"></i> Compartir</button>
                                    <button class="delete-action" type="button" data-item-action="delete"><i class="fa-solid fa-trash"></i> Eliminar</button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="drive-section">
                <div class="section-head"><h2>Archivos</h2><span><?php echo count($files); ?> documento<?php echo count($files) === 1 ? '' : 's'; ?> · <?php echo storage_format_bytes($availableBytes); ?> libres</span></div>
                <?php if ($files): ?>
                    <div style="overflow-x:auto">
                        <table class="file-table">
                            <thead><tr><th class="check-cell"><input class="item-check" id="selectAllFiles" type="checkbox" aria-label="Seleccionar todos los archivos"></th><th class="icon-cell"></th><th>Nombre</th><th class="type-column">Tipo</th><th class="size-column">Tamano</th><th class="date-column">Modificado</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr class="storage-item selectable-item" data-search="<?php echo storage_h(mb_strtolower($file['nombre_original'], 'UTF-8')); ?>" data-type="archivo" data-id="<?php echo (int)$file['id']; ?>" data-name="<?php echo storage_h($file['nombre_original']); ?>" data-size="<?php echo storage_h(storage_format_bytes((int)$file['tamano_bytes'])); ?>" data-created="<?php echo storage_h(date('d/m/Y · h:i a', strtotime($file['creado_en']))); ?>" data-updated="<?php echo storage_h(date('d/m/Y · h:i a', strtotime($file['actualizado_en'] ?? $file['creado_en']))); ?>" data-location="<?php echo storage_h($currentLocationName); ?>">
                                    <td class="check-cell"><input class="item-check" type="checkbox" aria-label="Seleccionar <?php echo storage_h($file['nombre_original']); ?>"></td>
                                    <td class="icon-cell"><i class="fa-solid <?php echo storage_file_icon((string)$file['extension']); ?> file-type-icon"></i></td>
                                    <td class="file-name"><strong><?php echo storage_h($file['nombre_original']); ?></strong><small><?php echo strtoupper(storage_h($file['extension'])); ?> · <?php echo storage_format_bytes((int)$file['tamano_bytes']); ?></small></td>
                                    <td class="type-column"><?php echo strtoupper(storage_h($file['extension'])); ?></td>
                                    <td class="size-column"><?php echo storage_format_bytes((int)$file['tamano_bytes']); ?></td>
                                    <td class="date-column"><?php echo date('d/m/Y · h:i a', strtotime($file['actualizado_en'] ?? $file['creado_en'])); ?></td>
                                    <td class="file-actions">
                                        <button class="item-menu-button" type="button" aria-label="Acciones del archivo"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                        <div class="item-menu">
                                            <a href="descargar_archivo?id=<?php echo (int)$file['id']; ?>"><i class="fa-solid fa-download"></i> Descargar</a>
                                            <button type="button" data-item-action="info"><i class="fa-solid fa-circle-info"></i> Ver informacion</button>
                                            <button type="button" data-item-action="rename"><i class="fa-solid fa-pen"></i> Cambiar nombre</button>
                                            <button type="button" data-item-action="share"><i class="fa-solid fa-share-nodes"></i> Compartir</button>
                                            <button class="delete-action" type="button" data-item-action="delete"><i class="fa-solid fa-trash"></i> Eliminar</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif (!$customFolders && ($selectedSub !== '' || $selectedFolder || empty($substandards[$selectedStandard]))): ?>
                    <div class="empty-state"><div><i class="fa-regular fa-folder-open"></i><strong>Esta ubicacion esta vacia</strong><span>Crea una carpeta o sube varios archivos desde la barra superior.</span></div></div>
                <?php endif; ?>
                <div class="no-results" id="noResults"><i class="fa-solid fa-magnifying-glass"></i> No encontramos carpetas ni archivos con ese nombre.</div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php if ($selectedStandard > 0): ?>
<div class="modal-backdrop" id="uploadModal" role="dialog" aria-modal="true" aria-labelledby="uploadTitle">
    <form class="drive-modal" method="post" enctype="multipart/form-data">
        <div class="modal-head"><div><h3 id="uploadTitle">Subir archivos</h3><p><?php echo storage_h($currentLocationName); ?> · hasta 20 archivos por lote</p></div><button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body">
            <label class="drop-zone" id="dropZone"><input type="file" id="storageFiles" name="archivos[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.txt,.csv,.zip"><span><i class="fa-solid fa-cloud-arrow-up"></i><strong>Arrastra tus archivos aqui</strong><span>o haz clic para seleccionarlos · maximo 50 MB por archivo</span></span></label>
            <div class="selected-files" id="selectedFiles"></div>
            <input type="hidden" name="csrf" value="<?php echo storage_h($_SESSION['storage_csrf']); ?>"><input type="hidden" name="accion" value="subir_archivos">
        </div>
        <div class="modal-actions"><button class="drive-btn" type="button" data-close-modal>Cancelar</button><button class="drive-btn primary" id="uploadSubmit" type="submit" disabled><i class="fa-solid fa-arrow-up-from-bracket"></i> Subir seleccionados</button></div>
    </form>
</div>

<div class="modal-backdrop" id="folderModal" role="dialog" aria-modal="true" aria-labelledby="folderTitle">
    <form class="drive-modal" method="post">
        <div class="modal-head"><div><h3 id="folderTitle">Crear nueva carpeta</h3><p>Se creara dentro de <?php echo storage_h($currentLocationName); ?>.</p></div><button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body"><div class="form-field"><label for="folderName">Nombre de la carpeta</label><input id="folderName" type="text" name="nombre" maxlength="180" autocomplete="off" placeholder="Ej. Evidencias julio 2026" required></div><input type="hidden" name="csrf" value="<?php echo storage_h($_SESSION['storage_csrf']); ?>"><input type="hidden" name="accion" value="crear_carpeta"></div>
        <div class="modal-actions"><button class="drive-btn" type="button" data-close-modal>Cancelar</button><button class="drive-btn primary" type="submit"><i class="fa-solid fa-folder-plus"></i> Crear carpeta</button></div>
    </form>
</div>
<?php endif; ?>

<div class="modal-backdrop" id="renameModal" role="dialog" aria-modal="true" aria-labelledby="renameTitle">
    <form class="drive-modal" method="post">
        <div class="modal-head"><div><h3 id="renameTitle">Cambiar nombre</h3><p id="renameDescription">Actualiza el nombre del elemento seleccionado.</p></div><button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body"><div class="form-field"><label for="renameInput">Nuevo nombre</label><input id="renameInput" type="text" name="nombre_nuevo" maxlength="255" required></div><input type="hidden" name="csrf" value="<?php echo storage_h($_SESSION['storage_csrf']); ?>"><input type="hidden" name="accion" value="renombrar_objeto"><input type="hidden" name="tipo_objeto" id="renameType"><input type="hidden" name="objeto_id" id="renameId"></div>
        <div class="modal-actions"><button class="drive-btn" type="button" data-close-modal>Cancelar</button><button class="drive-btn primary" type="submit"><i class="fa-solid fa-check"></i> Guardar nombre</button></div>
    </form>
</div>

<div class="modal-backdrop" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
    <form class="drive-modal" method="post">
        <div class="modal-head"><div><h3 id="deleteTitle">Eliminar elementos</h3><p>Esta accion no se puede deshacer.</p></div><button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body"><div class="delete-visual"><i class="fa-solid fa-trash"></i><div><strong id="deleteDescription">Confirma los elementos que deseas eliminar.</strong><p class="delete-note">Si eliminas una carpeta tambien se eliminaran todos sus archivos y subcarpetas.</p></div></div><input type="hidden" name="csrf" value="<?php echo storage_h($_SESSION['storage_csrf']); ?>"><input type="hidden" name="accion" value="eliminar_objetos"><input type="hidden" name="archivo_ids" id="deleteFileIds" value="[]"><input type="hidden" name="carpeta_ids" id="deleteFolderIds" value="[]"></div>
        <div class="modal-actions"><button class="drive-btn" type="button" data-close-modal>Cancelar</button><button class="drive-btn danger" type="submit"><i class="fa-solid fa-trash"></i> Eliminar definitivamente</button></div>
    </form>
</div>

<div class="modal-backdrop" id="shareConfirmModal" role="dialog" aria-modal="true" aria-labelledby="shareConfirmTitle">
    <form class="drive-modal" method="post">
        <div class="modal-head"><div><h3 id="shareConfirmTitle">Crear enlace para compartir</h3><p>El enlace permitira consultar el elemento sin iniciar sesion.</p></div><button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body"><p class="share-note">Por seguridad, el enlace vencera automaticamente en 7 dias. Compartelo solo con personas autorizadas.</p><input type="hidden" name="csrf" value="<?php echo storage_h($_SESSION['storage_csrf']); ?>"><input type="hidden" name="accion" value="compartir_objeto"><input type="hidden" name="tipo_objeto" id="shareType"><input type="hidden" name="objeto_id" id="shareId"></div>
        <div class="modal-actions"><button class="drive-btn" type="button" data-close-modal>Cancelar</button><button class="drive-btn primary" type="submit"><i class="fa-solid fa-link"></i> Crear enlace seguro</button></div>
    </form>
</div>

<div class="modal-backdrop <?php echo $shareUrl !== '' ? 'open' : ''; ?>" id="shareResultModal" role="dialog" aria-modal="true" aria-labelledby="shareResultTitle">
    <div class="drive-modal">
        <div class="modal-head"><div><h3 id="shareResultTitle">Enlace listo para compartir</h3><p>Disponible durante los proximos 7 dias.</p></div><button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body"><div class="share-box"><input id="shareUrlInput" type="text" readonly value="<?php echo storage_h($shareUrl); ?>"><button class="drive-btn primary" id="copyShareUrl" type="button"><i class="fa-solid fa-copy"></i> Copiar</button></div><p class="share-note" id="copyShareStatus">Cualquier persona que tenga este enlace podra acceder al elemento compartido.</p></div>
    </div>
</div>

<div class="modal-backdrop" id="infoModal" role="dialog" aria-modal="true" aria-labelledby="infoTitle">
    <div class="drive-modal">
        <div class="modal-head"><div><h3 id="infoTitle">Informacion del elemento</h3><p id="infoSubtitle">Detalle del archivo o carpeta.</p></div><button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div class="modal-body"><div class="details-grid"><div class="detail-box"><span>Nombre</span><strong id="infoName">—</strong></div><div class="detail-box"><span>Tipo</span><strong id="infoType">—</strong></div><div class="detail-box"><span>Tamano</span><strong id="infoSize">—</strong></div><div class="detail-box"><span>Ubicacion</span><strong id="infoLocation">—</strong></div><div class="detail-box"><span>Creado</span><strong id="infoCreated">—</strong></div><div class="detail-box"><span>Modificado</span><strong id="infoUpdated">—</strong></div></div></div>
    </div>
</div>

<script>
(function(){
    var modals=Array.prototype.slice.call(document.querySelectorAll('.modal-backdrop'));
    function openModal(id){var modal=document.getElementById(id);if(!modal)return;modal.classList.add('open');document.body.style.overflow='hidden';var input=modal.querySelector('input[type=text]:not([readonly])');if(input)setTimeout(function(){input.focus();input.select();},50)}
    function closeModal(modal){modal.classList.remove('open');if(!document.querySelector('.modal-backdrop.open'))document.body.style.overflow=''}
    document.querySelectorAll('[data-open-modal]').forEach(function(button){button.addEventListener('click',function(){openModal(button.dataset.openModal)})});
    document.querySelectorAll('[data-close-modal]').forEach(function(button){button.addEventListener('click',function(){closeModal(button.closest('.modal-backdrop'))})});
    modals.forEach(function(modal){modal.addEventListener('click',function(event){if(event.target===modal)closeModal(modal)})});
    document.addEventListener('keydown',function(event){if(event.key==='Escape'){var open=document.querySelector('.modal-backdrop.open');if(open)closeModal(open)}});

    var menus=Array.prototype.slice.call(document.querySelectorAll('.item-menu'));
    function restoreMenu(menu){if(!menu._storageHome)return;if(menu._storageNext&&menu._storageNext.parentNode===menu._storageHome)menu._storageHome.insertBefore(menu,menu._storageNext);else menu._storageHome.appendChild(menu)}
    function closeMenus(){menus.forEach(function(menu){menu.classList.remove('open','floating');menu.style.removeProperty('left');menu.style.removeProperty('top');restoreMenu(menu)});document.querySelectorAll('.item-menu-button[aria-expanded="true"]').forEach(function(button){button.setAttribute('aria-expanded','false')})}
    function openMenu(button,menu){
        menu._storageOwner=button.closest('.selectable-item');
        document.body.appendChild(menu);
        menu.classList.add('open','floating');
        menu.style.left='0px';
        menu.style.top='0px';
        var margin=8,gap=6,buttonRect=button.getBoundingClientRect(),menuRect=menu.getBoundingClientRect();
        var left=Math.min(Math.max(margin,buttonRect.right-menuRect.width),Math.max(margin,window.innerWidth-menuRect.width-margin));
        var roomBelow=window.innerHeight-buttonRect.bottom-gap-margin;
        var roomAbove=buttonRect.top-gap-margin;
        var top=roomBelow>=menuRect.height||roomBelow>=roomAbove?buttonRect.bottom+gap:buttonRect.top-menuRect.height-gap;
        top=Math.min(Math.max(margin,top),Math.max(margin,window.innerHeight-menuRect.height-margin));
        menu.style.left=Math.round(left)+'px';
        menu.style.top=Math.round(top)+'px';
        button.setAttribute('aria-expanded','true');
    }
    document.querySelectorAll('.item-menu-button').forEach(function(button){var menu=button.parentElement.querySelector('.item-menu');if(!menu)return;menu._storageHome=menu.parentNode;menu._storageNext=menu.nextSibling;button._storageMenu=menu;button.setAttribute('aria-haspopup','menu');button.setAttribute('aria-expanded','false');button.addEventListener('click',function(event){event.stopPropagation();var willOpen=!menu.classList.contains('open');closeMenus();if(willOpen)openMenu(button,menu)})});
    document.addEventListener('click',closeMenus);
    window.addEventListener('resize',closeMenus);
    function positionOpenMenus(){document.querySelectorAll('.item-menu-button[aria-expanded="true"]').forEach(function(button){if(button._storageMenu&&button._storageMenu.classList.contains('open'))openMenu(button,button._storageMenu)})}
    document.addEventListener('scroll',positionOpenMenus,{passive:true,capture:true});

    var selectable=Array.prototype.slice.call(document.querySelectorAll('.selectable-item'));
    var selectionBar=document.getElementById('selectionBar'),selectionCount=document.getElementById('selectionCount');
    function getSelected(){return selectable.filter(function(item){var check=item.querySelector('.item-check');return check&&check.checked})}
    function refreshSelection(){var selected=getSelected();selectable.forEach(function(item){var check=item.querySelector('.item-check');item.classList.toggle('selected',!!check&&check.checked)});if(selectionBar)selectionBar.classList.toggle('visible',selected.length>0);if(selectionCount)selectionCount.textContent=selected.length+(selected.length===1?' elemento seleccionado':' elementos seleccionados');['selectionInfo','selectionShare','selectionRename'].forEach(function(id){var button=document.getElementById(id);if(button)button.disabled=selected.length!==1});var all=document.getElementById('selectAllFiles');var fileChecks=selectable.filter(function(item){return item.dataset.type==='archivo'}).map(function(item){return item.querySelector('.item-check')});if(all){all.checked=fileChecks.length>0&&fileChecks.every(function(check){return check.checked});all.indeterminate=fileChecks.some(function(check){return check.checked})&&!all.checked}}
    selectable.forEach(function(item){var check=item.querySelector('.item-check');if(check)check.addEventListener('change',refreshSelection)});
    document.getElementById('selectAllFiles')?.addEventListener('change',function(){var checked=this.checked;selectable.filter(function(item){return item.dataset.type==='archivo'&&!item.hidden}).forEach(function(item){item.querySelector('.item-check').checked=checked});refreshSelection()});
    document.getElementById('selectionClear')?.addEventListener('click',function(){selectable.forEach(function(item){var check=item.querySelector('.item-check');if(check)check.checked=false});refreshSelection()});

    function itemData(item){return{type:item.dataset.type,id:Number(item.dataset.id),name:item.dataset.name||'',size:item.dataset.size||'No aplica',created:item.dataset.created||'—',updated:item.dataset.updated||item.dataset.created||'—',location:item.dataset.location||'—'}}
    function prepareRename(item){var data=itemData(item);document.getElementById('renameType').value=data.type;document.getElementById('renameId').value=data.id;document.getElementById('renameInput').value=data.name;document.getElementById('renameDescription').textContent=(data.type==='archivo'?'Archivo: ':'Carpeta: ')+data.name;openModal('renameModal')}
    function prepareShare(item){var data=itemData(item);document.getElementById('shareType').value=data.type;document.getElementById('shareId').value=data.id;openModal('shareConfirmModal')}
    function prepareInfo(item){var data=itemData(item);document.getElementById('infoName').textContent=data.name;document.getElementById('infoType').textContent=data.type==='archivo'?'Archivo':'Carpeta';document.getElementById('infoSize').textContent=data.size;document.getElementById('infoLocation').textContent=data.location;document.getElementById('infoCreated').textContent=data.created;document.getElementById('infoUpdated').textContent=data.updated;openModal('infoModal')}
    function prepareDelete(items){var files=[],folders=[];items.forEach(function(item){var data=itemData(item);(data.type==='archivo'?files:folders).push(data.id)});document.getElementById('deleteFileIds').value=JSON.stringify(files);document.getElementById('deleteFolderIds').value=JSON.stringify(folders);document.getElementById('deleteDescription').textContent='Vas a eliminar '+items.length+(items.length===1?' elemento.':' elementos.');openModal('deleteModal')}
    document.querySelectorAll('[data-item-action]').forEach(function(button){button.addEventListener('click',function(event){event.stopPropagation();var menu=button.closest('.item-menu');var item=button.closest('.selectable-item')||(menu&&menu._storageOwner);var action=button.dataset.itemAction;closeMenus();if(!item)return;if(action==='rename')prepareRename(item);if(action==='share')prepareShare(item);if(action==='info')prepareInfo(item);if(action==='delete')prepareDelete([item])})});
    document.getElementById('selectionRename')?.addEventListener('click',function(){var items=getSelected();if(items.length===1)prepareRename(items[0])});
    document.getElementById('selectionShare')?.addEventListener('click',function(){var items=getSelected();if(items.length===1)prepareShare(items[0])});
    document.getElementById('selectionInfo')?.addEventListener('click',function(){var items=getSelected();if(items.length===1)prepareInfo(items[0])});
    document.getElementById('selectionDelete')?.addEventListener('click',function(){var items=getSelected();if(items.length)prepareDelete(items)});

    var search=document.getElementById('storageSearch'),clear=document.getElementById('searchClear'),noResults=document.getElementById('noResults');
    function normalize(value){return(value||'').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'')}
    function filterItems(){var term=normalize(search.value.trim()),visible=0;document.querySelectorAll('.storage-item').forEach(function(item){var show=!term||normalize(item.dataset.search).indexOf(term)!==-1;item.hidden=!show;if(show)visible++});if(clear)clear.classList.toggle('visible',search.value.length>0);if(noResults)noResults.classList.toggle('visible',visible===0&&term.length>0)}
    search?.addEventListener('input',filterItems);clear?.addEventListener('click',function(){search.value='';search.focus();filterItems()});

    var input=document.getElementById('storageFiles'),drop=document.getElementById('dropZone'),fileList=document.getElementById('selectedFiles'),submit=document.getElementById('uploadSubmit');
    function formatBytes(bytes){if(bytes>=1048576)return(bytes/1048576).toFixed(1)+' MB';if(bytes>=1024)return(bytes/1024).toFixed(1)+' KB';return bytes+' B'}
    function renderFiles(){if(!input||!fileList)return;var files=Array.prototype.slice.call(input.files||[]);fileList.innerHTML='';files.slice(0,20).forEach(function(file){var row=document.createElement('div');row.className='selected-file';var name=document.createElement('span');name.textContent=file.name;var size=document.createElement('span');size.textContent=formatBytes(file.size);row.append(name,size);fileList.appendChild(row)});if(submit)submit.disabled=files.length===0||files.length>20}
    input?.addEventListener('change',renderFiles);['dragenter','dragover'].forEach(function(name){drop?.addEventListener(name,function(event){event.preventDefault();drop.classList.add('dragging')})});['dragleave','drop'].forEach(function(name){drop?.addEventListener(name,function(event){event.preventDefault();drop.classList.remove('dragging')})});drop?.addEventListener('drop',function(event){if(!input||!event.dataTransfer.files.length)return;var transfer=new DataTransfer();Array.prototype.slice.call(event.dataTransfer.files,0,20).forEach(function(file){transfer.items.add(file)});input.files=transfer.files;renderFiles()});

    document.getElementById('copyShareUrl')?.addEventListener('click',async function(){var field=document.getElementById('shareUrlInput'),status=document.getElementById('copyShareStatus');try{await navigator.clipboard.writeText(field.value);status.textContent='Enlace copiado al portapapeles.';this.innerHTML='<i class="fa-solid fa-check"></i> Copiado'}catch(error){field.select();document.execCommand('copy');status.textContent='Enlace copiado al portapapeles.'}});
    refreshSelection();
}());
</script>
</body>
</html>
