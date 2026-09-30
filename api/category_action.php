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
        $categoryName = trim($input['category_name'] ?? ($input['categories'] ?? ''));

        if (empty($categoryName)) {
            echo json_encode(['success' => false, 'message' => 'Category Name is required.']);
            exit;
        }

        if (!empty($categoryId)) {
            // Edit existing category (rename)
            $chk = $db->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ? AND category_id != ?");
            $chk->execute([$categoryName, $categoryId]);
            if ((int)$chk->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'This Category Name already exists.']);
                exit;
            }

            $sql = "UPDATE categories SET category_name = ? WHERE category_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$categoryName, $categoryId]);

            echo json_encode([
                'success' => true,
                'message' => "Category '{$categoryName}' updated successfully."
            ]);
            exit;
        } else {
            // Check if name already exists
            $chk = $db->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
            $chk->execute([$categoryName]);
            if ((int)$chk->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'This Category Name already exists.']);
                exit;
            }

            // Auto-generate Category ID on behalf of Prefix + Numeric Value
            $prefix = generateCategoryPrefix($categoryName);
            
            // Find next available numeric value for this prefix (e.g. TAB-01, TAB-02)
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
                'message' => "Category '{$categoryName}' added successfully."
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
