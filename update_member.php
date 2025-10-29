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
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูล']);
    exit();
}

require_once __DIR__ . '/config.php';
$conn = db_connect();
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit();
}

// รับข้อมูลจากฟอร์ม
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title_name = isset($_POST['title_name']) ? $conn->real_escape_string($_POST['title_name']) : '';
$name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
$surname = isset($_POST['surname']) ? $conn->real_escape_string($_POST['surname']) : '';
$age = isset($_POST['age']) ? intval($_POST['age']) : 0;
$address = isset($_POST['address']) ? $conn->real_escape_string($_POST['address']) : '';
$phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : '';
$mail = isset($_POST['mail']) ? $conn->real_escape_string($_POST['mail']) : '';
$status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '3';
$department = isset($_POST['department']) ? $conn->real_escape_string($_POST['department']) : '';

// ตรวจสอบข้อมูล
if ($id <= 0 || empty($name) || empty($surname) || $age <= 0 || empty($phone) || empty($mail)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit();
}

// จัดการการอัพโหลดรูปภาพ
$profile_image = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/profile_images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array(strtolower($fileExtension), $allowedExtensions)) {
        $fileName = uniqid('profile_') . '.' . $fileExtension;
        $destination = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
            $profile_image = $fileName;
            
            // ลบรูปภาพเก่าถ้ามี
            $sql = "SELECT profile_image FROM members WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['profile_image'] && file_exists($uploadDir . $row['profile_image'])) {
                    unlink($uploadDir . $row['profile_image']);
                }
            }
            $stmt->close();
        }
    }
}

// อัปเดตข้อมูล
$sql = "UPDATE members SET 
        title_name = ?, 
        name = ?, 
        surname = ?, 
        age = ?, 
        address = ?, 
        phone = ?, 
        mail = ?, 
        status = ?, 
        department = ?";
        
if ($profile_image) {
    $sql .= ", profile_image = ?";
}

$sql .= " WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($profile_image) {
    $stmt->bind_param("sssissssssi", 
        $title_name, 
        $name, 
        $surname, 
        $age, 
        $address, 
        $phone, 
        $mail, 
        $status, 
        $department,
        $profile_image,
        $id
    );
} else {
    $stmt->bind_param("sssisssssi", 
        $title_name, 
        $name, 
        $surname, 
        $age, 
        $address, 
        $phone, 
        $mail, 
        $status, 
        $department, 
        $id
    );
}

if ($stmt->execute()) {
	if($id == $_SESSION['member_id']){
		if ($profile_image) {
			$_SESSION['profile_image'] = $profile_image;
		}
		$_SESSION['name'] = $name;
		$_SESSION['surname'] = $surname;
	}
    echo json_encode(['success' => true, 'message' => 'อัปเดตข้อมูลเรียบร้อยแล้ว']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล']);
}

$stmt->close();
$conn->close();
?>