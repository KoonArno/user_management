<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// สร้างการเชื่อมต่อ
require_once __DIR__ . '/config.php';
$conn = db_connect();
$conn->set_charset("utf8mb4");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // แก้ไข query เพื่อดึง profile_image ด้วย
    $stmt = $conn->prepare("SELECT u.*, m.name, m.surname, m.status, m.profile_image FROM users u JOIN members m ON u.member_id = m.id WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['status'] = $user['status']; // ใช้ status จากตาราง members แทน role
            $_SESSION['name'] = $user['name']; // เก็บชื่อแยก
            $_SESSION['surname'] = $user['surname']; // เก็บนามสกุลแยก
            $_SESSION['profile_image'] = $user['profile_image']; // แก้ไขตรงนี้
            
            $update_login_count = "UPDATE users SET login_count = login_count + 1 WHERE id = ?";
            $stmt_update = $conn->prepare($update_login_count);
            $stmt_update->bind_param("i",$user['id']);
            $stmt_update->execute();
            $stmt_update->close();
            
            $get_login_count = "SELECT login_count FROM users WHERE id = ?";
            $stmt_count = $conn->prepare($get_login_count);
            $stmt_count->bind_param("i",$user['id']);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $count_data = $result_count->fetch_assoc();
            $_SESSION['login_count'] = $count_data['login_count'];
            $stmt_count->close();
            
            $_SESSION['success_message'] = 'เข้าสู่ระบบเรียบร้อยแล้ว';
            header("Location: dashboard.php");
            exit();
        } else {
            $login_error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $login_error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบล็อกอิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }
        .login-btn {
            background-color: #28a745;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
            margin-bottom: 15px;
        }
        .login-btn:hover {
            background-color: #218838;
        }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 20px;
            }
            h1 {
                font-size: 1.3rem;
            }
            .login-btn {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>ระบบล็อกอิน</h1>
        
        <?php if (!empty($login_error)): ?>
            <div class="error-message"><?php echo $login_error; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required placeholder="กรอกชื่อผู้ใช้">
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required placeholder="กรอกรหัสผ่าน">
            </div>
            
            <button type="submit" class="login-btn">เข้าสู่ระบบ</button>
        </form>
        
        <div class="footer-text">ระบบบริหารจัดการพนักงาน</div>
    </div>
</body>
</html>