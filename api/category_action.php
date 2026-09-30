<?php
/**
 * Asset Category Action API Handler (2 Columns: category_id, category_name)
 * IT Asset & Support Management System
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

// Clean & Normalize Category Name (Trims whitespace, trailing/leading dots and punctuation)
function normalizeCategoryName(string $name): string {
    $name = trim($name);
    // Remove leading/trailing dots, commas, dashes, slashes, punctuation
    $name = trim($name, " .\t\n\r\0\x0B-_,;:!?/|\\#*@~`+=^%&()[]{}'\"<>");
    // Collapse multiple spaces into one
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

// Canonical alphanumeric key for duplicate detection (e.g. "Laptop." -> "LAPTOP")
function getCategoryCanonicalKey(string $name): string {
    return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $name));
}

// System Auto-Prefix Generator from Category Name
function generateCategoryPrefix(string $name): string {
    $clean = strtoupper(preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
    $words = array_values(array_filter(preg_split('/\s+/', trim($clean))));
    if (count($words) >= 3) {
        return substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1);
    } elseif (count($words) === 2) {
        return substr($words[0], 0, 2) . substr($words[1], 0, 1);
    } elseif (!empty($words[0])) {
        $w = $words[0];
        return strlen($w) >= 3 ? substr($w, 0, 3) : str_pad($w, 3, 'X');
    }
    return 'AST';
}

try {
    if ($action === 'save_category') {
        $categoryId = trim($input['category_id'] ?? ($input['id'] ?? ''));
        $rawCategoryName = $input['category_name'] ?? ($input['categories'] ?? '');
        $categoryName = normalizeCategoryName($rawCategoryName);
        $canonicalKey = getCategoryCanonicalKey($categoryName);

        if (empty($categoryName) || empty($canonicalKey) || strlen($canonicalKey) < 2) {
            echo json_encode([
                'success' => false, 
                'message' => 'Please enter a valid Category Name with at least 2 alphanumeric characters.'
            ]);
            exit;
        }

        // Fetch all categories to perform thorough normalized duplicate check
        $existingCats = $db->query("SELECT category_id, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existingCats as $ec) {
            // If editing, skip the current category itself
            if (!empty($categoryId) && strcasecmp($ec['category_id'], $categoryId) === 0) {
                continue;
            }

            $ecNorm = normalizeCategoryName($ec['category_name']);
            $ecCanonical = getCategoryCanonicalKey($ec['category_name']);

            // Strict check: either exact case-insensitive match or identical canonical key
            if (strcasecmp($categoryName, $ec['category_name']) === 0 || $canonicalKey === $ecCanonical) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Category '{$ec['category_name']}' already exists in system."
                ]);
                exit;
            }
        }

        if (!empty($categoryId)) {
            // Edit existing category (rename)
            $sql = "UPDATE categories SET category_name = ? WHERE category_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$categoryName, $categoryId]);

            echo json_encode([
                'success' => true,
                'message' => "Category '{$categoryName}' updated successfully."
            ]);
            exit;
        } else {
            // Auto-generate Category ID on behalf of Prefix + Numeric Value (2-digit: e.g. TAB-01, TAB-02)
            $prefix = generateCategoryPrefix($categoryName);
            
            $seq = 1;
            while (true) {
                $newId = sprintf("%s-%02d", $prefix, $seq);
                $chkId = $db->prepare("SELECT COUNT(*) FROM categories WHERE category_id = ?");
                $chkId->execute([$newId]);
                if ((int)$chkId->fetchColumn() === 0) {
                    $categoryId = $newId;
                    break;
                }
                $seq++;
            }

            $sql = "INSERT INTO categories (category_id, category_name) VALUES (?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$categoryId, $categoryName]);

            echo json_encode([
                'success' => true,
                'message' => "Category '{$categoryName}' ({$categoryId}) added successfully."
            ]);
            exit;
        }
    } elseif ($action === 'delete_category') {
        $categoryId = trim($input['category_id'] ?? ($input['id'] ?? ''));
        
        $stmt = $db->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$categoryId]);

        echo json_encode([
            'success' => true,
            'message' => "Category deleted successfully."
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    exit;
}
