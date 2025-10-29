<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการล็อกอินและสิทธิ์
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

if ($_SESSION['status'] != '1') {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์เข้าถึง']);
    exit();
}

require_once __DIR__ . '/config.php';
$conn = db_connect();
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit();
}

// รับ ID จากคำขอ
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
    exit();
}

// ดึงข้อมูลสมาชิก
$sql = "SELECT m.*, u.username, u.password FROM members m 
        JOIN users u ON m.id = u.member_id 
        WHERE m.id = ?";$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $member = $result->fetch_assoc();
    echo json_encode(['success' => true, 'member' => $member]);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลสมาชิก']);
}

$stmt->close();
$conn->close();
?>