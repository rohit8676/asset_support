<?php
/**
 * Asset Action API Handler
 * IT Asset & Support Management System
 * Microsoft SQL Server (PDO_SQLSRV)
 * 
 * Columns: [asset_id, asset_name, description, serial_number, category_id, source, status, in_date]
 * Asset ID Logic: Category ID + Serialization (e.g. DTP-001-0001)
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Require login
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';
$db = Database::getConnection();

/**
 * Generate Next Asset ID based on Category ID + Serialization
 * Example: For category DTP-01 -> DTP-01-001, DTP-01-002, etc.
 */
function generateNextAssetId(PDO $db, string $categoryId): string {
    $categoryId = trim($categoryId);
    if (empty($categoryId)) {
        return '';
    }

    // Prefix pattern: CATEGORY_ID-
    $prefixPattern = $categoryId . '-%';
    $stmt = $db->prepare("SELECT asset_id FROM assets WHERE asset_id LIKE ? OR category_id = ?");
    $stmt->execute([$prefixPattern, $categoryId]);
    $existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $maxSeq = 0;
    foreach ($existingIds as $id) {
        if (preg_match('/^' . preg_quote($categoryId, '/') . '-(\d+)$/i', $id, $matches)) {
            $num = (int)$matches[1];
            if ($num > $maxSeq) {
                $maxSeq = $num;
            }
        }
    }

    $seq = $maxSeq + 1;
    while (true) {
        $candidateId = sprintf("%s-%03d", $categoryId, $seq);
        $chk = $db->prepare("SELECT COUNT(*) FROM assets WHERE asset_id = ?");
        $chk->execute([$candidateId]);
        if ((int)$chk->fetchColumn() === 0) {
            return $candidateId;
        }
        $seq++;
    }
}

try {
    if ($action === 'get_next_id') {
        $categoryId = trim($input['category_id'] ?? '');
        if (empty($categoryId)) {
            echo json_encode(['success' => false, 'message' => 'Category ID is required.', 'asset_id' => '']);
            exit;
        }

        $nextId = generateNextAssetId($db, $categoryId);
        echo json_encode(['success' => true, 'asset_id' => $nextId]);
        exit;
    }

    elseif ($action === 'save_asset') {
        $isEdit = !empty($input['is_edit']);
        $assetId = trim($input['asset_id'] ?? '');
        $categoryId = trim($input['category_id'] ?? '');
        $assetName = trim($input['asset_name'] ?? ($input['asset'] ?? ''));
        $description = trim($input['description'] ?? '');
        $serialNumber = trim($input['serial_number'] ?? ($input['serial_no'] ?? ''));
        $source = trim($input['source'] ?? '');
        $status = $isEdit ? trim($input['status'] ?? 'Available') : 'Available';
        $inDate = trim($input['in_date'] ?? date('Y-m-d'));

        // Basic Validation
        if (empty($categoryId)) {
            echo json_encode(['success' => false, 'message' => 'Category selection is required.']);
            exit;
        }

        // Verify category exists
        $catCheck = $db->prepare("SELECT COUNT(*) FROM categories WHERE category_id = ?");
        $catCheck->execute([$categoryId]);
        if ((int)$catCheck->fetchColumn() === 0) {
            echo json_encode(['success' => false, 'message' => 'Selected Category does not exist.']);
            exit;
        }

        if (empty($assetName)) {
            echo json_encode(['success' => false, 'message' => 'Asset Name is required.']);
            exit;
        }

        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Description is required.']);
            exit;
        }

        if (empty($inDate)) {
            $inDate = date('Y-m-d');
        }

        if (empty($status)) {
            $status = 'Available';
        }

        // Check Serial Number uniqueness if provided
        if (!empty($serialNumber)) {
            if ($isEdit) {
                $snCheck = $db->prepare("SELECT COUNT(*) FROM assets WHERE serial_number = ? AND asset_id != ?");
                $snCheck->execute([$serialNumber, $assetId]);
            } else {
                $snCheck = $db->prepare("SELECT COUNT(*) FROM assets WHERE serial_number = ?");
                $snCheck->execute([$serialNumber]);
            }
            if ((int)$snCheck->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => "Serial Number '{$serialNumber}' is already registered with another asset."]);
                exit;
            }
        }

        if ($isEdit) {
            if (empty($assetId)) {
                echo json_encode(['success' => false, 'message' => 'Asset ID is required for editing.']);
                exit;
            }

            // Update existing asset
            $sql = "UPDATE assets 
                    SET asset_name = ?, 
                        description = ?, 
                        serial_number = ?, 
                        category_id = ?, 
                        source = ?, 
                        status = ?, 
                        in_date = ?, 
                        updated_at = GETDATE() 
                    WHERE asset_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $assetName,
                $description,
                $serialNumber !== '' ? $serialNumber : null,
                $categoryId,
                $source !== '' ? $source : null,
                $status,
                $inDate,
                $assetId
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Asset '{$assetId}' updated successfully.",
                'asset_id' => $assetId
            ]);
            exit;
        } else {
            // Auto Generate Asset ID: Category ID + Serialization
            if (empty($assetId)) {
                $assetId = generateNextAssetId($db, $categoryId);
            } else {
                // Verify provided Asset ID is not taken
                $chk = $db->prepare("SELECT COUNT(*) FROM assets WHERE asset_id = ?");
                $chk->execute([$assetId]);
                if ((int)$chk->fetchColumn() > 0) {
                    $assetId = generateNextAssetId($db, $categoryId);
                }
            }

            $sql = "INSERT INTO assets (asset_id, asset_name, description, serial_number, category_id, source, status, in_date, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $assetId,
                $assetName,
                $description,
                $serialNumber !== '' ? $serialNumber : null,
                $categoryId,
                $source !== '' ? $source : null,
                $status,
                $inDate
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Asset '{$assetId}' ({$assetName}) registered successfully.",
                'asset_id' => $assetId
            ]);
            exit;
        }
    }

    elseif ($action === 'list_assets') {
        $search = trim($input['search'] ?? '');
        $category = trim($input['category'] ?? '');
        $statusFilter = trim($input['status'] ?? '');

        $sql = "SELECT a.asset_id, 
                       a.asset_name, 
                       a.description, 
                       a.serial_number, 
                       a.category_id, 
                       c.category_name, 
                       a.source, 
                       a.status, 
                       CONVERT(VARCHAR(10), a.in_date, 120) AS in_date,
                       CONVERT(VARCHAR(19), a.created_at, 120) AS created_at
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.category_id
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (a.asset_id LIKE ? OR a.asset_name LIKE ? OR a.serial_number LIKE ? OR a.source LIKE ? OR a.description LIKE ? OR c.category_name LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($category)) {
            $sql .= " AND (a.category_id = ? OR c.category_name = ?)";
            $params[] = $category;
            $params[] = $category;
        }

        if (!empty($statusFilter)) {
            if ($statusFilter === 'Available' || $statusFilter === 'In Stock') {
                $sql .= " AND a.status IN ('Available', 'In Stock')";
            } else {
                $sql .= " AND a.status = ?";
                $params[] = $statusFilter;
            }
        }

        $sql .= " ORDER BY a.in_date DESC, a.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Stats
        $statTotal = (int)$db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
        $statInStock = (int)$db->query("SELECT COUNT(*) FROM assets WHERE status IN ('Available', 'In Stock')")->fetchColumn();
        $statInUse = (int)$db->query("SELECT COUNT(*) FROM assets WHERE status = 'In Use'")->fetchColumn();
        $statRepair = (int)$db->query("SELECT COUNT(*) FROM assets WHERE status = 'Under Repair'")->fetchColumn();

        echo json_encode([
            'success' => true,
            'assets' => $assets,
            'counts' => [
                'total' => $statTotal,
                'in_stock' => $statInStock,
                'in_use' => $statInUse,
                'under_repair' => $statRepair,
                'filtered' => count($assets)
            ]
        ]);
        exit;
    }

    elseif ($action === 'get_asset') {
        $assetId = trim($input['asset_id'] ?? '');
        if (empty($assetId)) {
            echo json_encode(['success' => false, 'message' => 'Asset ID is required.']);
            exit;
        }

        $stmt = $db->prepare("SELECT a.asset_id, 
                                     a.asset_name, 
                                     a.description, 
                                     a.serial_number, 
                                     a.category_id, 
                                     c.category_name, 
                                     a.source, 
                                     a.status, 
                                     CONVERT(VARCHAR(10), a.in_date, 120) AS in_date
                              FROM assets a
                              LEFT JOIN categories c ON a.category_id = c.category_id
                              WHERE a.asset_id = ?");
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$asset) {
            echo json_encode(['success' => false, 'message' => 'Asset not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'asset' => $asset]);
        exit;
    }

    elseif ($action === 'delete_asset') {
        $assetId = trim($input['asset_id'] ?? '');
        if (empty($assetId)) {
            echo json_encode(['success' => false, 'message' => 'Asset ID is required.']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM assets WHERE asset_id = ?");
        $stmt->execute([$assetId]);

        echo json_encode([
            'success' => true,
            'message' => "Asset '{$assetId}' deleted successfully."
        ]);
        exit;
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    exit;
}
