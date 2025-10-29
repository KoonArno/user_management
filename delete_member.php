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
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ลบข้อมูล']);
    exit();
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'วิธีการร้องขอไม่ถูกต้อง']);
    exit();
}

require_once __DIR__ . '/config.php';
$conn = db_connect();
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit();
}

// รับ ID จากฟอร์ม
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
    exit();
}

// ตรวจสอบว่ามี member นี้จริงหรือไม่
$check_sql = "SELECT id FROM members WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสมาชิกที่ต้องการลบ']);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// เริ่ม Transaction
$conn->begin_transaction();

try {
    // ลบข้อมูลจากตาราง users ก่อน (เนื่องจากมี foreign key constraint)
    $sql1 = "DELETE FROM users WHERE member_id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("i", $id);
    
    if (!$stmt1->execute()) {
        throw new Exception("ไม่สามารถลบข้อมูลผู้ใช้ได้");
    }
    $stmt1->close();

    // ลบข้อมูลจากตาราง members
    $sql2 = "DELETE FROM members WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $id);
    
    if (!$stmt2->execute()) {
        throw new Exception("ไม่สามารถลบข้อมูลสมาชิกได้");
    }
    $stmt2->close();

    // Commit Transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
} catch (Exception $e) {
    // Rollback Transaction หากเกิดข้อผิดพลาด
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
    ]);
}

$conn->close();
?>