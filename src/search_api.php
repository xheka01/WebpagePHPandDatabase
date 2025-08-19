<?php
// search_api.php
session_start();
require 'config.php';

header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = 50;

    $where = '';
    $params = [];

    if ($q !== '') {
        $terms = preg_split('/\s+/', $q);
        $booleanQuery = [];
        foreach ($terms as $t) {
            $t = preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $t);
            $t = trim($t);
            if ($t !== '') $booleanQuery[] = '+' . $t . '*';
        }
        if (!empty($booleanQuery)) {
            $where = "WHERE MATCH(name, description, search_tags) AGAINST (? IN BOOLEAN MODE)";
            $params[] = implode(' ', $booleanQuery);
        }
    }

    $sql = "SELECT id, name, description, price, image_path FROM products";
    if ($where) $sql .= " $where";
    $sql .= " ORDER BY id DESC LIMIT $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'count' => count($products), 'products' => $products], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'SERVER_ERROR', 'message' => $e->getMessage()]);
}
