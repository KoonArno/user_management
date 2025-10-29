<?php
// เริ่ม Session
session_start();

// เปิดใช้งาน Error Reporting เพื่อดูข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่า Character Encoding เป็น UTF-8
header('Content-Type: text/html; charset=utf-8');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = "sql100.byethost12.com";
$user = "b12_39494522";
$password = "Nannaphat642423.";
$dbname = "b12_39494522_ict_67040460";

// สร้างการเชื่อมต่อ
require_once __DIR__ . '/config.php';
$conn = db_connect();

// ตั้งค่า Character Set เป็น UTF-8 สำหรับการเชื่อมต่อฐานข้อมูล
$conn->set_charset("utf8mb4");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ดึงข้อมูลแผนกที่มีอยู่จากฐานข้อมูล
$departments_sql = "SELECT DISTINCT department FROM members WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = $conn->query($departments_sql);
$departments = [];
if ($departments_result->num_rows > 0) {
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// ตรวจสอบว่ามีการส่งข้อมูลแบบ POST มาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์มและทำความสะอาดข้อมูลเพื่อป้องกัน SQL Injection
    $title_name = $conn->real_escape_string($_POST['title_name']);
    $name = $conn->real_escape_string($_POST['name']);
    $surname = $conn->real_escape_string($_POST['surname']);
    $age = (int)$_POST['age'];
    $address = $conn->real_escape_string($_POST['address']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $mail = $conn->real_escape_string($_POST['mail']);
    $status = $conn->real_escape_string($_POST['status']);
    $department = $conn->real_escape_string($_POST['department']);

    // เริ่ม transaction
    $conn->begin_transaction();

    try {
        // เพิ่มข้อมูลสมาชิกใหม่
        $stmt = $conn->prepare("INSERT INTO members (title_name, name, surname, age, address, phone, mail, status, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisssss", $title_name, $name, $surname, $age, $address, $phone, $mail, $status, $department);

        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูลสมาชิก: " . $stmt->error);
        }

        // ดึง ID ของสมาชิกที่เพิ่งสร้าง
        $new_member_id = $stmt->insert_id;
        $stmt->close();

        // ใช้ ID เป็นทั้ง username และ password
        $username = $new_member_id;
        $raw_password = $new_member_id;
        $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

        // เพิ่มข้อมูลผู้ใช้ในตาราง users (โดยไม่ใส่ role)
        $stmt = $conn->prepare("INSERT INTO users (member_id, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $new_member_id, $username, $hashed_password);

        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการสร้างบัญชีผู้ใช้: " . $stmt->error);
        }

        $stmt->close();

        // Commit transaction
        $conn->commit();

        // เก็บข้อความแจ้งเตือนใน Session
        $_SESSION['success_message'] = "เพิ่มข้อมูลสมาชิกใหม่เรียบร้อยแล้ว!<br>Username: $username<br>Password: $raw_password";

        // Redirect ไปหน้า dashboard.php
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction หากเกิดข้อผิดพลาด
        $conn->rollback();
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// ปิดการเชื่อมต่อ
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลพนักงานใหม่</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2563eb;
            --light-blue: #3b82f6;
            --sky-blue: #0ea5e9;
            --cyan-blue: #06b6d4;
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-200: #bfdbfe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --blue-800: #1e40af;
            --blue-900: #1e3a8a;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Sarabun", sans-serif;
            background: linear-gradient(135deg, var(--blue-50) 0%, var(--blue-100) 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Background Animation */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(37,99,235,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(37,99,235,0.08)"/><circle cx="40" cy="80" r="1" fill="rgba(37,99,235,0.05)"/></svg>');
            z-index: -1;
            animation: backgroundMove 20s ease-in-out infinite;
        }

        @keyframes backgroundMove {
            0%, 100% { transform: translateX(0) translateY(0); }
            33% { transform: translateX(-30px) translateY(-30px); }
            66% { transform: translateX(30px) translateY(-30px); }
        }

        /* Header Styles */
        .header { 
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--light-blue) 50%, var(--sky-blue) 100%);
            padding: 1.5rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></svg>');
            pointer-events: none;
        }
        
        .header h1 { 
            font-size: 1.5rem; 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            color: var(--white);
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }

        .header h1 i {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .form-container {
            background: var(--white);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.05), transparent);
            transition: left 0.5s;
        }

        .form-container:hover::before {
            left: 100%;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-title {
            color: var(--gray-800);
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
            font-size: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary-blue);
            background: var(--blue-100);
            padding: 0.75rem;
            border-radius: 12px;
            font-size: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: var(--gray-700);
            font-weight: 500;
            font-size: 0.95rem;
        }

        label i {
            color: var(--primary-blue);
            width: 16px;
            text-align: center;
        }

        input[type="text"],
        input[type="number"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--blue-200);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--gray-700);
            font-family: "Sarabun", sans-serif;
            background: var(--white);
            transition: all 0.3s ease;
            position: relative;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="email"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        select {
            cursor: pointer;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%232563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 12,15 18,9"></polyline></svg>');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            appearance: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--light-blue) 100%);
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            font-family: "Sarabun", sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
            margin-top: 2rem;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.75rem;
            border-radius: 8px;
        }

        .back-link:hover {
            background: var(--blue-50);
            color: var(--blue-700);
            transform: translateY(-2px);
        }

        /* Message Styles */
        .message {
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 12px;
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }

        .message.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #7f1d1d;
            border: 2px solid #ef4444;
        }

        .message i {
            font-size: 1.25rem;
        }

        /* Input Icons */
        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            font-size: 1rem;
            pointer-events: none;
        }

        .input-group input,
        .input-group select,
        .input-group textarea {
            padding-left: 3rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .page-title i {
                font-size: 1.25rem;
                padding: 0.5rem;
            }

            .header h1 {
                font-size: 1.25rem;
            }

            input[type="text"],
            input[type="number"],
            input[type="email"],
            textarea,
            select {
                padding: 0.875rem 1rem;
            }

            .input-group input,
            .input-group select,
            .input-group textarea {
                padding-left: 2.5rem;
            }

            .input-icon {
                left: 0.75rem;
                font-size: 0.9rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Form Validation Styles */
        input:invalid {
            border-color: #ef4444;
        }

        input:valid {
            border-color: #10b981;
        }

        /* Placeholder Styles */
        ::placeholder {
            color: var(--gray-400);
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <i class="fas fa-user-plus"></i>
            ระบบบริหารจัดการพนักงาน
        </h1>
    </div>

    <div class="container">
        <div class="form-container">
            <h1 class="page-title">
                <i class="fas fa-user-plus"></i>
                เพิ่มข้อมูลพนักงานใหม่
            </h1>

            <?php if (isset($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="UTF-8">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_name">
                            <i class="fas fa-user-tag"></i>
                            คำนำหน้า
                        </label>
                        <div class="input-group">
                            <select id="title_name" name="title_name" required>
                                <option value="">เลือกคำนำหน้า</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i>
                            ชื่อ
                        </label>
                        <div class="input-group">
                            <input type="text" id="name" name="name" required placeholder="กรอกชื่อจริง">
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="surname">
                            <i class="fas fa-user"></i>
                            นามสกุล
                        </label>
                        <div class="input-group">
                            <input type="text" id="surname" name="surname" required placeholder="กรอกนามสกุล">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="age">
                            <i class="fas fa-birthday-cake"></i>
                            อายุ
                        </label>
                        <div class="input-group">
                            <input type="number" id="age" name="age" required min="18" max="99" placeholder="กรอกอายุ">
                        </div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="address">
                        <i class="fas fa-map-marker-alt"></i>
                        ที่อยู่
                    </label>
                    <div class="input-group">
                        <textarea id="address" name="address" rows="3" required placeholder="กรอกที่อยู่ปัจจุบัน"></textarea>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i>
                            โทรศัพท์
                        </label>
                        <div class="input-group">
                            <input type="text" id="phone" name="phone" required pattern="[0-9]{10}" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก" placeholder="เช่น 0812345678">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="mail">
                            <i class="fas fa-envelope"></i>
                            อีเมล
                        </label>
                        <div class="input-group">
                            <input type="email" id="mail" name="mail" required placeholder="เช่น example@domain.com">
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-shield-alt"></i>
                            สถานะ
                        </label>
                        <div class="input-group">
                            <select id="status" name="status" required>
                                <option value="">เลือกสถานะ</option>
                                <option value="1">แอดมิน</option>
                                <option value="2">หัวหน้าแผนก</option>
                                <option value="3">พนักงาน</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="department">
                            <i class="fas fa-building"></i>
                            แผนก
                        </label>
                        <div class="input-group">
                            <select id="department" name="department" required>
                                <option value="">เลือกแผนก</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i>
                    <span>บันทึกข้อมูล</span>
                </button>
            </form>

            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                กลับไปหน้าแดชบอร์ด
            </a>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('span');
            const btnIcon = submitBtn.querySelector('i');
            
            // Show loading state
            btnIcon.className = 'loading';
            btnText.textContent = 'กำลังบันทึก...';
            submitBtn.disabled = true;
        });

        // Phone number validation
        document.getElementById('phone').addEventListener('input', function() {
            // Remove non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.substring(0, 10);
            }
            
            // Visual feedback
            if (this.value.length === 10) {
                this.style.borderColor = '#10b981';
            } else if (this.value.length > 0) {
                this.style.borderColor = '#f59e0b';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });

        // Age validation
        document.getElementById('age').addEventListener('input', function() {
            const age = parseInt(this.value);
            
            if (age < 18) {
                this.setCustomValidity('อายุต้องไม่น้อยกว่า 18 ปี');
                this.style.borderColor = '#ef4444';
            } else if (age > 99) {
                this.setCustomValidity('อายุต้องไม่เกิน 99 ปี');
                this.style.borderColor = '#ef4444';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '#10b981';
            }
        });

        // Email validation
        document.getElementById('mail').addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(this.value)) {
                this.style.borderColor = '#10b981';
            } else if (this.value.length > 0) {
                this.style.borderColor = '#f59e0b';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });

        // Form field animations
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            field.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Add loading animation for page load
        document.addEventListener('DOMContentLoaded', function() {
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    group.style.transition = 'all 0.4s ease';
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Add smooth transitions for all interactive elements
        document.querySelectorAll('button, a, input, select, textarea').forEach(element => {
            element.style.transition = 'all 0.3s ease';
        });

        // Form validation feedback
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
            field.addEventListener('invalid', function() {
                this.style.borderColor = '#ef4444';
                this.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
            });
            
            field.addEventListener('input', function() {
                if (this.validity.valid) {
                    this.style.borderColor = '#10b981';
                    this.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
                }
            });
        });

        // Enhanced form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            // Check if all required fields are filled
            const requiredFields = this.querySelectorAll('[required]');
            let allValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allValid = false;
                    field.style.borderColor = '#ef4444';
                    field.focus();
                }
            });
            
            if (!allValid) {
                e.preventDefault();
                return;
            }
            
            // Show success animation
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            submitBtn.innerHTML = '<div class="loading"></div><span>กำลังบันทึก...</span>';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>