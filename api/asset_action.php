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

// Check action from GET, POST, or JSON body
$rawInput = json_decode(file_get_contents('php://input'), true);
$input = is_array($rawInput) ? $rawInput : $_POST;
$action = $_GET['action'] ?? ($input['action'] ?? '');
$db = Database::getConnection();

// Direct Template Download Action (Handles GET/POST)
if ($action === 'download_template') {
    // Fetch available categories to include in the template reference
    $catList = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_id ASC")->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="asset_bulk_import_template.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Write UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers matching the database columns:
    // [Category_ID, Asset_Name, Description, Serial_Number, Source, Status, In_Date]
    fputcsv($output, [
        'Category_ID',
        'Asset_Name',
        'Description',
        'Serial_Number',
        'Source',
        'Status',
        'In_Date'
    ]);

    // Sample Data Rows for user guidance
    $sampleDate = date('Y-m-d');
    fputcsv($output, ['DTP-01', 'Dell OptiPlex 7090 Tower', 'Intel Core i7-11700, 16GB DDR4 RAM, 512GB NVMe SSD', 'SN-DTP-90218', 'Direct Purchase', 'Available', $sampleDate]);
    fputcsv($output, ['LTP-01', 'HP EliteBook 840 G8', 'Intel Core i5, 16GB RAM, 256GB SSD, 14-inch FHD Display', 'SN-HP840-7712', 'Vendor Procurement', 'Available', $sampleDate]);
    fputcsv($output, ['MON-01', 'Samsung 27" FHD Monitor', '27-inch Curved IPS, 75Hz Refresh Rate, HDMI/VGA Port', 'SN-MON-33419', 'Direct Purchase', 'Available', $sampleDate]);
    fputcsv($output, ['PRN-01', 'HP LaserJet Pro M404dn', 'High-Speed Monochrome Network Laser Printer', 'SN-PRN-55201', 'Rental / Lease', 'Available', $sampleDate]);
    fputcsv($output, ['KBD-01', 'Logitech MK270 Wireless Combo', 'Full-size Wireless Keyboard and Optical Mouse Bundle', '', 'Direct Purchase', 'Available', $sampleDate]);

    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

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

        // Base filter condition applied to both list and stats
        $baseWhere = " WHERE 1=1";
        $baseParams = [];

        if (!empty($search)) {
            $baseWhere .= " AND (a.asset_id LIKE ? OR a.asset_name LIKE ? OR a.serial_number LIKE ? OR a.source LIKE ? OR a.description LIKE ? OR c.category_name LIKE ?)";
            $term = "%{$search}%";
            $baseParams = array_merge($baseParams, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($category)) {
            $baseWhere .= " AND (a.category_id = ? OR c.category_name = ?)";
            $baseParams[] = $category;
            $baseParams[] = $category;
        }

        // Main Query (Includes optional statusFilter if user specifically clicked a status tab)
        $whereSql = $baseWhere;
        $params = $baseParams;

        if (!empty($statusFilter)) {
            if ($statusFilter === 'Available' || $statusFilter === 'In Stock') {
                $whereSql .= " AND a.status IN ('Available', 'In Stock')";
            } elseif ($statusFilter === 'In Use' || $statusFilter === 'Assigned') {
                $whereSql .= " AND a.status IN ('In Use', 'Assigned')";
            } else {
                $whereSql .= " AND a.status = ?";
                $params[] = $statusFilter;
            }
        }

        // Pagination parameters (Default 20 items per page)
        $page = max(1, (int)($input['page'] ?? 1));
        $limit = max(1, min(100, (int)($input['limit'] ?? 20)));

        // Total filtered count for pagination
        $countStmt = $db->prepare("SELECT COUNT(*) FROM assets a LEFT JOIN categories c ON a.category_id = c.category_id" . $whereSql);
        $countStmt->execute($params);
        $totalFiltered = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($totalFiltered / $limit));
        if ($page > $totalPages && $totalFiltered > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

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
                LEFT JOIN categories c ON a.category_id = c.category_id" . $whereSql;

        $sql .= " ORDER BY a.in_date DESC, a.created_at DESC OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Filter-Based Stats for the Top KPI Cards
        $stmtTotal = $db->prepare("SELECT COUNT(*) FROM assets a LEFT JOIN categories c ON a.category_id = c.category_id" . $baseWhere);
        $stmtTotal->execute($baseParams);
        $statTotal = (int)$stmtTotal->fetchColumn();

        $stmtInStock = $db->prepare("SELECT COUNT(*) FROM assets a LEFT JOIN categories c ON a.category_id = c.category_id" . $baseWhere . " AND a.status IN ('Available', 'In Stock')");
        $stmtInStock->execute($baseParams);
        $statInStock = (int)$stmtInStock->fetchColumn();

        $stmtInUse = $db->prepare("SELECT COUNT(*) FROM assets a LEFT JOIN categories c ON a.category_id = c.category_id" . $baseWhere . " AND a.status IN ('In Use', 'Assigned')");
        $stmtInUse->execute($baseParams);
        $statInUse = (int)$stmtInUse->fetchColumn();

        $stmtRepair = $db->prepare("SELECT COUNT(*) FROM assets a LEFT JOIN categories c ON a.category_id = c.category_id" . $baseWhere . " AND a.status = 'Under Repair'");
        $stmtRepair->execute($baseParams);
        $statRepair = (int)$stmtRepair->fetchColumn();

        $stmtScrapped = $db->prepare("SELECT COUNT(*) FROM assets a LEFT JOIN categories c ON a.category_id = c.category_id" . $baseWhere . " AND a.status = 'Scrapped'");
        $stmtScrapped->execute($baseParams);
        $statScrapped = (int)$stmtScrapped->fetchColumn();

        echo json_encode([
            'success' => true,
            'assets' => $assets,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_records' => $totalFiltered,
                'total_pages' => $totalPages,
                'start_record' => $totalFiltered > 0 ? $offset + 1 : 0,
                'end_record' => min($offset + count($assets), $totalFiltered)
            ],
            'counts' => [
                'total' => $statTotal,
                'in_stock' => $statInStock,
                'in_use' => $statInUse,
                'under_repair' => $statRepair,
                'scrapped' => $statScrapped,
                'filtered' => $totalFiltered
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

    elseif ($action === 'import_assets') {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please upload a valid CSV file.']);
            exit;
        }

        $fileTmp = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            echo json_encode(['success' => false, 'message' => 'Only .CSV file format is supported for bulk import.']);
            exit;
        }

        $handle = fopen($fileTmp, 'r');
        if (!$handle) {
            echo json_encode(['success' => false, 'message' => 'Unable to read the uploaded CSV file.']);
            exit;
        }

        // Fetch all existing categories for fast lookup by ID or Name
        $catRows = $db->query("SELECT category_id, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        $categoriesMap = []; // 'DTP-01' => 'DTP-01', 'DESKTOP COMPUTER' => 'DTP-01'
        foreach ($catRows as $cr) {
            $categoriesMap[strtoupper(trim($cr['category_id']))] = $cr['category_id'];
            $categoriesMap[strtoupper(trim($cr['category_name']))] = $cr['category_id'];
        }

        // Read header line
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            fclose($handle);
            echo json_encode(['success' => false, 'message' => 'CSV file appears to be empty.']);
            exit;
        }

        // Normalize header keys
        $colIndex = [];
        foreach ($rawHeader as $idx => $headerText) {
            $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $headerText));
            if (in_array($cleanKey, ['categoryid', 'category', 'catid'])) {
                $colIndex['category_id'] = $idx;
            } elseif (in_array($cleanKey, ['assetname', 'asset', 'model', 'name'])) {
                $colIndex['asset_name'] = $idx;
            } elseif (in_array($cleanKey, ['description', 'desc', 'specs', 'specification'])) {
                $colIndex['description'] = $idx;
            } elseif (in_array($cleanKey, ['serialnumber', 'serialno', 'serial', 'sn'])) {
                $colIndex['serial_number'] = $idx;
            } elseif (in_array($cleanKey, ['source', 'vendor', 'procurement'])) {
                $colIndex['source'] = $idx;
            } elseif (in_array($cleanKey, ['status', 'state'])) {
                $colIndex['status'] = $idx;
            } elseif (in_array($cleanKey, ['indate', 'date', 'purchase_date', 'inwarddate'])) {
                $colIndex['in_date'] = $idx;
            }
        }

        if (!isset($colIndex['category_id']) || !isset($colIndex['asset_name'])) {
            fclose($handle);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSV format. Header must contain Category_ID and Asset_Name columns. Please download the sample template.'
            ]);
            exit;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lineNum = 1;

        $insertStmt = $db->prepare("
            INSERT INTO assets (asset_id, asset_name, description, serial_number, category_id, source, status, in_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE())
        ");

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;
            // Check if row is completely empty
            if (empty(array_filter($row, fn($v) => trim($v) !== ''))) {
                continue;
            }

            $rawCat = trim($row[$colIndex['category_id']] ?? '');
            $assetName = trim($row[$colIndex['asset_name']] ?? '');
            $description = isset($colIndex['description']) ? trim($row[$colIndex['description']] ?? '') : '';
            $serialNumber = isset($colIndex['serial_number']) ? trim($row[$colIndex['serial_number']] ?? '') : '';
            $source = isset($colIndex['source']) ? trim($row[$colIndex['source']] ?? '') : '';
            $status = isset($colIndex['status']) ? trim($row[$colIndex['status']] ?? '') : 'Available';
            $inDate = isset($colIndex['in_date']) ? trim($row[$colIndex['in_date']] ?? '') : '';

            // Match Category
            $upperCat = strtoupper($rawCat);
            if (empty($rawCat) || !isset($categoriesMap[$upperCat])) {
                $skipped++;
                $errors[] = "Row #{$lineNum}: Category '{$rawCat}' not found in system.";
                continue;
            }
            $categoryId = $categoriesMap[$upperCat];

            if (empty($assetName)) {
                $skipped++;
                $errors[] = "Row #{$lineNum}: Asset Name is missing.";
                continue;
            }

            if (empty($description)) {
                $description = $assetName; // Fallback to asset name if empty
            }

            if (empty($status)) {
                $status = 'Available';
            }

            if (empty($inDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $inDate)) {
                $inDate = date('Y-m-d');
            }

            // Check duplicate serial number if provided
            if (!empty($serialNumber)) {
                $snChk = $db->prepare("SELECT COUNT(*) FROM assets WHERE serial_number = ?");
                $snChk->execute([$serialNumber]);
                if ((int)$snChk->fetchColumn() > 0) {
                    $skipped++;
                    $errors[] = "Row #{$lineNum}: Serial Number '{$serialNumber}' already exists in database.";
                    continue;
                }
            }

            // Generate Next Asset ID with Category ID + Serialization
            $assetId = generateNextAssetId($db, $categoryId);

            try {
                $insertStmt->execute([
                    $assetId,
                    $assetName,
                    $description,
                    $serialNumber !== '' ? $serialNumber : null,
                    $categoryId,
                    $source !== '' ? $source : null,
                    $status,
                    $inDate
                ]);
                $imported++;
            } catch (Exception $ex) {
                $skipped++;
                $errors[] = "Row #{$lineNum}: DB error inserting asset ({$ex->getMessage()}).";
            }
        }

        fclose($handle);

        echo json_encode([
            'success' => true,
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'errors' => $errors,
            'message' => "Bulk import completed: {$imported} asset(s) registered successfully." . ($skipped > 0 ? " ({$skipped} row(s) skipped)" : "")
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
