<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// สร้างการเชื่อมต่อ
require_once __DIR__ . '/config.php';
$conn = db_connect();
$conn->set_charset("utf8mb4");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// Function สำหรับแปลงสถานะ
function getStatusText($status)
{
    switch ($status) {
        case '1':
            return 'แอดมิน';
        case '2':
            return 'หัวหน้าแผนก';
        case '3':
            return 'พนักงาน';
        default:
            return $status;
    }
}

// ดึงข้อมูล login_count
if (!isset($_SESSION['login_count'])) {
    $stmt = $conn->prepare("SELECT login_count FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $_SESSION['login_count'] = $result->fetch_assoc()['login_count'];
    $stmt->close();
}

// ประมวลผลการดาวน์โหลดไฟล์ (เฉพาะ admin)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['download']) && $_SESSION['status'] == '1') {
    $file_type = $_GET['type'] ?? 'xls';
    $export_sql = "SELECT m.*, u.login_count FROM members m 
                  LEFT JOIN users u ON m.id = u.member_id 
                  ORDER BY m.id";
    $export_result = $conn->query($export_sql);

    if ($file_type == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="members_data_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['รหัส', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'อายุ', 'ที่อยู่', 'โทรศัพท์', 'อีเมล', 'สถานะ', 'แผนก', 'สถานะพนักงาน']);

        while ($row = $export_result->fetch_assoc()) {
            $phone = "'" . $row['phone'];
            $employee_status = (isset($row['login_count']) && $row['login_count'] <= 1) ? 'พนักงานใหม่' : 'พนักงานเก่า';
            fputcsv($output, [
                $row['id'],
                $row['title_name'],
                $row['name'],
                $row['surname'],
                $row['age'],
                $row['address'],
                $phone,
                $row['mail'],
                getStatusText($row['status']),
                $row['department'],
                $employee_status
            ]);
        }
        fclose($output);
    } elseif ($file_type == 'pdf') {
        if (!file_exists('tcpdf/tcpdf.php')) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>รายชื่อสมาชิก</title>
            <style>body{font-family:sans-serif;margin:20px}table{width:100%;border-collapse:collapse}
            th,td{border:1px solid #ddd;padding:8px}th{background:#f2f2f2}</style></head><body>
            <h1>รายชื่อสมาชิก</h1><p>วันที่: ' . date('d/m/Y H:i:s') . '</p><table>';
            echo '<tr><th>รหัส</th><th>คำนำหน้า</th><th>ชื่อ</th><th>นามสกุล</th><th>อายุ</th>
                <th>ที่อยู่</th><th>โทรศัพท์</th><th>อีเมล</th><th>สถานะ</th><th>แผนก</th><th>สถานะพนักงาน</th></tr>';

            while ($row = $export_result->fetch_assoc()) {
                $employee_status = (isset($row['login_count']) && $row['login_count'] <= 1) ? 'พนักงานใหม่' : 'พนักงานเก่า';
                echo '<tr><td>' . htmlspecialchars($row['id']) . '</td><td>' . htmlspecialchars($row['title_name']) . '</td>
                    <td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['surname']) . '</td>
                    <td>' . htmlspecialchars($row['age']) . '</td><td>' . htmlspecialchars($row['address']) . '</td>
                    <td>' . htmlspecialchars($row['phone']) . '</td><td>' . htmlspecialchars($row['mail']) . '</td>
                    <td>' . htmlspecialchars(getStatusText($row['status'])) . '</td>
                    <td>' . htmlspecialchars($row['department']) . '</td>
                    <td>' . htmlspecialchars($employee_status) . '</td></tr>';
            }
            echo '</table><script>window.print()</script></body></html>';
        } else {
            require_once('tcpdf/tcpdf.php');
            $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('Employee Management System');
            $pdf->SetAuthor('Admin');
            $pdf->SetTitle('รายชื่อสมาชิก - ' . date('d/m/Y'));
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();
            $pdf->SetFont('freeserif', '', 10);

            $html = '<h1>รายชื่อสมาชิก</h1><p>วันที่: ' . date('d/m/Y H:i:s') . '</p>
                <table border="1" cellpadding="3"><tr>
                <th>รหัส</th><th>คำนำหน้า</th><th>ชื่อ</th><th>นามสกุล</th><th>อายุ</th>
                <th>ที่อยู่</th><th>โทรศัพท์</th><th>อีเมล</th><th>สถานะ</th><th>แผนก</th><th>สถานะพนักงาน</th></tr>';

            while ($row = $export_result->fetch_assoc()) {
                $employee_status = (isset($row['login_count']) && $row['login_count'] <= 1) ? 'พนักงานใหม่' : 'พนักงานเก่า';
                $html .= '<tr><td>' . $row['id'] . '</td><td>' . $row['title_name'] . '</td>
                    <td>' . $row['name'] . '</td><td>' . $row['surname'] . '</td><td>' . $row['age'] . '</td>
                    <td>' . $row['address'] . '</td><td>' . $row['phone'] . '</td><td>' . $row['mail'] . '</td>
                    <td>' . getStatusText($row['status']) . '</td><td>' . $row['department'] . '</td>
                    <td>' . $employee_status . '</td></tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html);
            $pdf->Output('members_data_' . date('Y-m-d') . '.pdf', 'D');
        }
        exit();
    } else {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="members_data_' . date('Y-m-d') . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body><table border="1">';
        echo '<tr><th>รหัส</th><th>คำนำหน้า</th><th>ชื่อ</th><th>นามสกุล</th><th>อายุ</th>
            <th>ที่อยู่</th><th>โทรศัพท์</th><th>อีเมล</th><th>สถานะ</th><th>แผนก</th><th>สถานะพนักงาน</th></tr>';

        while ($row = $export_result->fetch_assoc()) {
            $employee_status = (isset($row['login_count']) && $row['login_count'] <= 1) ? 'พนักงานใหม่' : 'พนักงานเก่า';
            echo '<tr><td>' . htmlspecialchars($row['id']) . '</td><td>' . htmlspecialchars($row['title_name']) . '</td>
                <td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['surname']) . '</td>
                <td>' . htmlspecialchars($row['age']) . '</td><td>' . htmlspecialchars($row['address']) . '</td>
                <td>' . htmlspecialchars($row['phone']) . '</td><td>' . htmlspecialchars($row['mail']) . '</td>
                <td>' . htmlspecialchars(getStatusText($row['status'])) . '</td>
                <td>' . htmlspecialchars($row['department']) . '</td>
                <td>' . htmlspecialchars($employee_status) . '</td></tr>';
        }
        echo '</table></body></html>';
        exit();
    }
}

// สร้างคำสั่ง SQL ตามบทบาทของผู้ใช้
if ($_SESSION['status'] == '1') {
    $sql = "SELECT m.*, u.login_count FROM members m 
            LEFT JOIN users u ON m.id = u.member_id 
            ORDER BY m.id";
} elseif ($_SESSION['status'] == '2') {
    $sql = "SELECT m.*, u.login_count FROM members m
            LEFT JOIN users u ON m.id = u.member_id
            WHERE m.status IN ('2', '3')
            ORDER BY m.id";
} else {
    $sql = "SELECT m.*, u.login_count FROM members m
            LEFT JOIN users u ON m.id = u.member_id
            WHERE m.id = " . $_SESSION['member_id'];
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title><?= $_SESSION['status'] == '3' ? 'โปรไฟล์ของฉัน' : 'แดชบอร์ด' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
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
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Sarabun", sans-serif;
            background: linear-gradient(135deg, var(--blue-50) 0%, var(--blue-100) 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
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
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        .header h1 i {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--white) 0%, var(--blue-100) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .role-badge {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .new-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }

        .logout-btn {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.9) 0%, rgba(220, 38, 38, 0.9) 100%);
            color: var(--white);
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        /* Profile Card Styles */
        .profile-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.12);
            border: 1px solid rgba(37, 99, 235, 0.1);
            backdrop-filter: blur(20px);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--light-blue) 50%, var(--sky-blue) 100%);
            padding: 3rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></svg>');
            pointer-events: none;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin: 0 auto 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-image i {
            font-size: 4rem;
            color: var(--primary-blue);
        }

        .profile-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }

        .profile-role {
            background: rgba(255, 255, 255, 0.25);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }

        .profile-body {
            padding: 3rem 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .info-item {
            background: linear-gradient(135deg, var(--blue-50) 0%, var(--white) 100%);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--blue-100);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.12);
            border-color: var(--primary-blue);
        }

        .info-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-blue), var(--sky-blue));
            border-radius: 0 2px 2px 0;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-label i {
            color: var(--primary-blue);
            width: 16px;
            text-align: center;
        }

        .info-value {
            font-size: 1.1rem;
            color: var(--gray-800);
            font-weight: 600;
            word-break: break-word;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            color: var(--white);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .status-1 {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .status-2 {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .status-3 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .employee-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .employee-new {
            background: linear-gradient(135deg, var(--blue-100) 0%, var(--blue-200) 100%);
            color: var(--blue-800);
            border: 1px solid var(--blue-300);
        }

        .employee-old {
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        /* Table Container with Header Actions (สำหรับ Admin/Manager) */
        .table-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.1);
            backdrop-filter: blur(20px);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-title {
            color: var(--gray-800);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-title i {
            color: var(--primary-blue);
            background: var(--blue-100);
            padding: 0.5rem;
            border-radius: 8px;
        }

        .table-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--light-blue) 100%);
            color: var(--white);
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
            font-size: 0.875rem;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .action-btn.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }

        .action-btn.green:hover {
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .action-btn.cyan {
            background: linear-gradient(135deg, var(--cyan-blue) 0%, var(--sky-blue) 100%);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
        }

        .action-btn.cyan:hover {
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.3);
        }

        /* Download Section */
        .download-section {
            position: relative;
        }

        .download-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: var(--white);
            border: 1px solid var(--blue-200);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.15);
            min-width: 200px;
            display: none;
            overflow: hidden;
            backdrop-filter: blur(20px);
            z-index: 1000;
        }

        .download-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .download-option {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            text-align: left;
            cursor: pointer;
            border: none;
            background: none;
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--gray-700);
            transition: all 0.2s ease;
        }

        .download-option:hover {
            background: var(--blue-50);
            color: var(--primary-blue);
        }

        .download-option i {
            width: 20px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--blue-100);
        }

        th {
            background: linear-gradient(135deg, var(--blue-50) 0%, var(--blue-100) 100%);
            font-weight: 600;
            color: var(--gray-800);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: var(--blue-50);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        td {
            color: var(--gray-700);
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.2);
            padding: 1rem;
            z-index: 1000;
            max-width: 300px;
            display: none;
        }

        .notification.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            border-left: 4px solid #10b981;
            background: linear-gradient(135deg, rgba(220, 252, 231, 0.95), rgba(187, 247, 208, 0.95));
        }

        .notification.error {
            border-left: 4px solid #ef4444;
            background: linear-gradient(135deg, rgba(254, 226, 226, 0.95), rgba(252, 165, 165, 0.95));
        }

        .notification.info {
            border-left: 4px solid #06b6d4;
            background: linear-gradient(135deg, rgba(240, 249, 255, 0.95), rgba(224, 242, 254, 0.95));
        }

        .notification i {
            color: var(--primary-blue);
        }

        .notification.success i {
            color: #10b981;
        }

        .notification.error i {
            color: #ef4444;
        }

        .notification.info i {
            color: #06b6d4;
        }

        .action-btn.small {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 6px;
            margin: 0.25rem;
        }

        .edit-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .delete-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, var(--white) 0%, var(--blue-50) 100%);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            width: 80%;
            max-width: 700px;
            animation: modalFadeIn 0.3s ease;
            border: 1px solid var(--blue-200);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--blue-100);
        }

        .modal-header h3 {
            color: var(--gray-800);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .close {
            color: var(--gray-500);
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .close:hover {
            color: var(--gray-700);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--blue-200);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--blue-100);
        }

        .cancel-btn {
            background: linear-gradient(135deg, var(--gray-200) 0%, var(--gray-300) 100%);
            color: var(--gray-700);
        }

        .save-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .delete-confirm-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .user-name {
            color: var(--white);
            font-weight: 500;
            margin-right: 0.5rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
		
		.profile-image-container {
			width: 80px;
			height: 80px;
			border-radius: 50%;
			overflow: hidden;
			border: 3px solid var(--blue-200);
			margin: 0 auto;
		}

		.profile-image-container img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.profile-image-placeholder {
			width: 100%;
			height: 100%;
			background: var(--blue-50);
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--blue-300);
		}
		
		input[readonly], select[readonly] {
			background-color: #f5f5f5;
			cursor: not-allowed;
			border-color: #e0e0e0;
		}

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
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
        
        .profile-container {
            animation: fadeInUp 0.6s ease;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .main-content {
                padding: 1rem;
            }

            .table-container {
                padding: 1rem;
                overflow-x: auto;
            }

            .table-actions {
                flex-direction: column;
                width: 100%;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }

            table {
                min-width: 800px;
            }

            .user-info {
                flex-direction: column;
                align-items: center;
            }

            .profile-header {
                padding: 2rem 1rem 1.5rem;
            }

            .profile-body {
                padding: 2rem 1rem;
            }

            .profile-name {
                font-size: 1.8rem;
            }

            .profile-image {
                width: 120px;
                height: 120px;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>
            <i class="fas fa-<?= $_SESSION['status'] == '3' ? 'user-circle' : 'tachometer-alt' ?>"></i> 
            <?= $_SESSION['status'] == '3' ? 'โปรไฟล์ของฉัน' : 'ระบบบริหารจัดการพนักงาน' ?>
        </h1>
        <div class="user-info">
            <div class="user-avatar">
				<?php if (!empty($_SESSION['profile_image'])): ?>
					<img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['profile_image']) ?>" 
						 alt="รูปโปรไฟล์" 
						 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
				<?php else: ?>
					<?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
				<?php endif; ?>
			</div>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['name'] . ' ' . $_SESSION['surname']); ?></span>
            <span class="role-badge">
                <?php
                switch ($_SESSION['status']) {
                    case '1':
                        echo '<i class="fas fa-crown"></i> แอดมิน';
                        break;
                    case '2':
                        echo '<i class="fas fa-user-tie"></i> หัวหน้าแผนก';
                        break;
                    case '3':
                        echo '<i class="fas fa-user"></i> พนักงาน';
                        break;
                }
                ?>
            </span>
            <?php if (isset($_SESSION['login_count']) && $_SESSION['login_count'] <= 1): ?>
                <span class="new-badge">พนักงานใหม่</span>
            <?php endif; ?>
            <form action="logout.php" method="post">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <?php if ($_SESSION['status'] == '3'): ?>
            <!-- Profile View for Regular Employees -->
            <?php if ($result->num_rows > 0): ?>
                <?php $row = $result->fetch_assoc(); ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-image">
                            <?php if (!empty($row['profile_image'])): ?>
                                <img src="uploads/profile_images/<?= htmlspecialchars($row['profile_image']) ?>" 
                                     alt="รูปโปรไฟล์">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-name">
                            <?= htmlspecialchars($row['title_name'] . $row['name'] . ' ' . $row['surname']) ?>
                        </div>
                        <div class="profile-role">
                            <i class="fas fa-user"></i>
                            <?= getStatusText($row['status']) ?>
                            <?php if (isset($row['login_count']) && $row['login_count'] <= 1): ?>
                                <span style="margin-left: 0.5rem; background: rgba(245, 158, 11, 0.3); padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem;">
                                    <i class="fas fa-star"></i> พนักงานใหม่
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profile-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-id-card"></i>
                                    รหัสพนักงาน
                                </div>
                                <div class="info-value"><?= htmlspecialchars($row['id']) ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-birthday-cake"></i>
                                    อายุ
                                </div>
                                <div class="info-value"><?= htmlspecialchars($row['age']) ?> ปี</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-phone"></i>
                                    เบอร์โทรศัพท์
                                </div>
                                <div class="info-value"><?= htmlspecialchars($row['phone']) ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-envelope"></i>
                                    อีเมล
                                </div>
                                <div class="info-value"><?= htmlspecialchars($row['mail']) ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-building"></i>
                                    แผนก
                                </div>
                                <div class="info-value"><?= htmlspecialchars($row['department']) ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-user-clock"></i>
                                    สถานะพนักงาน
                                </div>
                                <div class="info-value">
                                    <?php
                                    $isNewEmployee = (isset($row['login_count']) && $row['login_count'] <= 1);
                                    $employeeStatusText = $isNewEmployee ? 'พนักงานใหม่' : 'พนักงานเก่า';
                                    $employeeStatusClass = $isNewEmployee ? 'employee-new' : 'employee-old';
                                    ?>
                                    <span class="employee-status <?= $employeeStatusClass ?>">
                                        <?php if ($isNewEmployee): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="fas fa-medal"></i>
                                        <?php endif; ?>
                                        <?= $employeeStatusText ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 2rem; grid-column: 1 / -1;">
                            <div class="info-label">
                                <i class="fas fa-map-marker-alt"></i>
                                ที่อยู่
                            </div>
                            <div class="info-value"><?= nl2br(htmlspecialchars($row['address'])) ?></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="profile-container">
                    <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                        <i class="fas fa-user-slash" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <h3>ไม่พบข้อมูลโปรไฟล์</h3>
                        <p>ไม่สามารถดึงข้อมูลโปรไฟล์ของคุณได้</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Table View for Admin and Manager -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">
                        <i class="fas fa-users"></i> รายชื่อสมาชิก
                    </h2>

                    <div class="table-actions">
                        <?php if ($_SESSION['status'] == '1'): ?>
                            <a href="add_member.php" class="action-btn green">
                                <i class="fas fa-user-plus"></i> เพิ่มสมาชิกใหม่
                            </a>
                            <a href="department_graph.php" class="action-btn cyan">
                                <i class="fas fa-chart-bar"></i> กราฟแผนก
                            </a>
                            <div class="download-section">
                                <button class="action-btn" onclick="toggleDropdown()">
                                    <i class="fas fa-download"></i> ดาวน์โหลดข้อมูล
                                    <i class="fas fa-chevron-down" style="font-size: 0.7em; margin-left: 0.25rem;"></i>
                                </button>
                                <div class="download-dropdown" id="downloadDropdown">
                                    <button class="download-option" onclick="downloadFile('xls')">
                                        <i class="fas fa-file-excel" style="color: #10b981;"></i>
                                        <span>Excel Format</span>
                                    </button>
                                    <button class="download-option" onclick="downloadFile('csv')">
                                        <i class="fas fa-file-csv" style="color: #3b82f6;"></i>
                                        <span>CSV Format</span>
                                    </button>
                                    <button class="download-option" onclick="downloadFile('pdf')">
                                        <i class="fas fa-file-pdf" style="color: #ef4444;"></i>
                                        <span>PDF Format</span>
                                    </button>
                                </div>
                            </div>
                        <?php elseif ($_SESSION['status'] == '2'): ?>
                            <a href="department_graph.php" class="action-btn cyan">
                                <i class="fas fa-chart-bar"></i> กราฟแผนก
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-image"></i> รูปโปรไฟล์</th>
                                <th><i class="fas fa-hashtag"></i> รหัส</th>
                                <th><i class="fas fa-user-tag"></i> คำนำหน้า</th>
                                <th><i class="fas fa-user"></i> ชื่อ</th>
                                <th><i class="fas fa-user"></i> นามสกุล</th>
                                <th><i class="fas fa-birthday-cake"></i> อายุ</th>
                                <th><i class="fas fa-map-marker-alt"></i> ที่อยู่</th>
                                <th><i class="fas fa-phone"></i> โทรศัพท์</th>
                                <th><i class="fas fa-envelope"></i> อีเมล</th>
                                <th><i class="fas fa-shield-alt"></i> สถานะ</th>
                                <th><i class="fas fa-building"></i> แผนก</th>
                                <th><i class="fas fa-user-clock"></i> สถานะพนักงาน</th>
                                <?php if ($_SESSION['status'] == '1'): ?>
                                    <th><i class="fas fa-cog"></i> การจัดการ</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php
                                    $statusText = getStatusText($row['status']);
                                    $statusClass = "status-" . $row['status'];
                                    $isNewEmployee = (isset($row['login_count']) && $row['login_count'] <= 1);
                                    $employeeStatusText = $isNewEmployee ? 'พนักงานใหม่' : 'พนักงานเก่า';
                                    $employeeStatusClass = $isNewEmployee ? 'employee-new' : 'employee-old';
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($row['profile_image'])): ?>
                                                <img src="uploads/profile_images/<?= htmlspecialchars($row['profile_image']) ?>" 
                                                     alt="รูปโปรไฟล์" 
                                                     style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-user" style="color: #999;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= htmlspecialchars($row['id']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['title_name']) ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['surname']) ?></td>
                                        <td><?= htmlspecialchars($row['age']) ?> ปี</td>
                                        <td><?= htmlspecialchars($row['address']) ?></td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= htmlspecialchars($row['mail']) ?></td>
                                        <td>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?php if ($row['status'] == '1'): ?>
                                                    <i class="fas fa-crown"></i>
                                                <?php elseif ($row['status'] == '2'): ?>
                                                    <i class="fas fa-user-tie"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user"></i>
                                                <?php endif; ?>
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['department']) ?></td>
                                        <td>
                                            <span class="employee-status <?= $employeeStatusClass ?>">
                                                <?php if ($isNewEmployee): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-medal"></i>
                                                <?php endif; ?>
                                                <?= $employeeStatusText ?>
                                            </span>
                                        </td>
                                        <?php if ($_SESSION['status'] == '1'): ?>
                                            <td>
                                                <button class="action-btn small edit-btn" onclick="openEditModal(<?= $row['id'] ?>)">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>
                                                <button class="action-btn small delete-btn"
                                                    onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'] . ' ' . $row['surname']) ?>')">
                                                    <i class="fas fa-trash-alt"></i> ลบ
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $_SESSION['status'] == '1' ? '13' : '12' ?>"
                                        style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                        <i class="fas fa-users"
                                            style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                                        <strong>ไม่มีข้อมูลสมาชิก</strong><br>
                                        <small>ยังไม่มีข้อมูลสมาชิกในระบบ</small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Modal (Only for Admin) -->
    <?php if ($_SESSION['status'] == '1'): ?>
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> แก้ไขข้อมูลพนักงาน</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editForm" method="post" action="update_member.php">
                    <input type="hidden" name="id" id="editId">
                    <div class="form-group">
                        <label for="editMemberId"><i class="fas fa-id-card"></i> รหัสสมาชิก</label>
                        <input type="text" id="editMemberId" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="editUsername"><i class="fas fa-user"></i> ชื่อผู้ใช้</label>
                        <input type="text" id="editUsername" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="editPassword"><i class="fas fa-lock"></i> รหัสผ่าน</label>
                        <input type="password" id="editPassword" class="form-control" value="********" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editProfileImage"><i class="fas fa-camera"></i> รูปโปรไฟล์</label>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <img id="profileImagePreview" src="" alt="รูปโปรไฟล์" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd; display: none;">
                            <div>
                                <input type="file" name="profile_image" id="editProfileImage" class="form-control" accept="image/*">
                                <small class="text-muted">ขนาดแนะนำ: 200x200 พิกเซล, รูปแบบ: JPG, PNG, GIF</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editTitle"><i class="fas fa-user-tag"></i> คำนำหน้า</label>
                        <select name="title_name" id="editTitle" class="form-control">
                            <option value="นาย">นาย</option>
                            <option value="นาง">นาง</option>
                            <option value="นางสาว">นางสาว</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editName"><i class="fas fa-user"></i> ชื่อ</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="editSurname"><i class="fas fa-user"></i> นามสกุล</label>
                        <input type="text" name="surname" id="editSurname" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="editAge"><i class="fas fa-birthday-cake"></i> อายุ</label>
                        <input type="number" name="age" id="editAge" class="form-control" min="18" max="60" required>
                    </div>

                    <div class="form-group">
                        <label for="editAddress"><i class="fas fa-map-marker-alt"></i> ที่อยู่</label>
                        <textarea name="address" id="editAddress" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editPhone"><i class="fas fa-phone"></i> โทรศัพท์</label>
                        <input type="tel" name="phone" id="editPhone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="editMail"><i class="fas fa-envelope"></i> อีเมล</label>
                        <input type="email" name="mail" id="editMail" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="editStatus"><i class="fas fa-shield-alt"></i> สถานะ</label>
                        <select name="status" id="editStatus" class="form-control">
                            <option value="1">แอดมิน</option>
                            <option value="2">หัวหน้าแผนก</option>
                            <option value="3">พนักงาน</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editDepartment"><i class="fas fa-building"></i> แผนก</label>
                        <select name="department" id="editDepartment" class="form-control">
                            <option value="แผนกบัญชี">แผนกบัญชี</option>
                            <option value="แผนกบริการ">แผนกบริการ</option>
                            <option value="แผนกIT">แผนกIT</option>
                            <option value="แผนกบุคคล">แผนกบุคคล</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="action-btn cancel-btn" onclick="closeEditModal()">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                        <button type="submit" class="action-btn save-btn">
                            <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> ยืนยันการลบ</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">คุณแน่ใจว่าต้องการลบข้อมูลของ <strong id="deleteName"></strong> หรือไม่?</p>
                <p>การดำเนินการนี้ไม่สามารถยกเลิกได้</p>

                <form id="deleteForm" method="post" action="delete_member.php">
                    <input type="hidden" name="id" id="deleteId">

                    <div class="form-actions">
                        <button type="button" class="action-btn cancel-btn" onclick="closeDeleteModal()">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                        <button type="submit" class="action-btn delete-confirm-btn">
                            <i class="fas fa-trash-alt"></i> ยืนยันการลบ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notification -->
    <div id="notification" class="notification">
        <div id="notificationContent"></div>
    </div>

    <script>
        <?php if ($_SESSION['status'] == '1'): ?>
        // Modal Functions (Only for Admin)
        function openEditModal(id) {
			fetch(`get_member.php?id=${id}`)
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// เติมข้อมูลลงในฟอร์ม
						document.getElementById('editId').value = data.member.id;
						document.getElementById('editMemberId').value = data.member.id;
						document.getElementById('editUsername').value = data.member.username;
						document.getElementById('editTitle').value = data.member.title_name;
						document.getElementById('editName').value = data.member.name;
						document.getElementById('editSurname').value = data.member.surname;
						document.getElementById('editAge').value = data.member.age;
						document.getElementById('editAddress').value = data.member.address;
						document.getElementById('editPhone').value = data.member.phone;
						document.getElementById('editMail').value = data.member.mail;
						document.getElementById('editStatus').value = data.member.status;
						document.getElementById('editDepartment').value = data.member.department;
						
						// แสดงรูปโปรไฟล์ถ้ามี
						const profileImg = document.getElementById('profileImagePreview');
						if (data.member.profile_image) {
							profileImg.src = 'uploads/profile_images/' + data.member.profile_image;
							profileImg.style.display = 'block';
						} else {
							profileImg.style.display = 'none';
						}

						// แสดง Modal
						document.getElementById('editModal').style.display = 'block';
					} else {
						showNotification('ไม่สามารถดึงข้อมูลสมาชิกได้', 'error');
					}
				})
				.catch(error => {
					console.error('Error:', error);
					showNotification('เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
				});
		}

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function confirmDelete(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = name;
            document.getElementById('deleteMessage').innerHTML = `คุณแน่ใจว่าต้องการลบข้อมูลของ <strong>${name}</strong> หรือไม่?`;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // ปิด Modal เมื่อคลิกนอกพื้นที่
        window.onclick = function (event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        }
		
		// จัดการการแสดงตัวอย่างรูปภาพก่อนอัพโหลด
		document.getElementById('editProfileImage').addEventListener('change', function(e) {
			const file = e.target.files[0];
			if (file) {
				const reader = new FileReader();
				reader.onload = function(event) {
					const preview = document.getElementById('profileImagePreview');
					preview.src = event.target.result;
					preview.style.display = 'block';
				};
				reader.readAsDataURL(file);
			}
		});

        // จัดการการส่งฟอร์มแก้ไขด้วย AJAX
        document.getElementById('editForm').addEventListener('submit', function (e) {
			e.preventDefault();

			const formData = new FormData(this);
			
			fetch('update_member.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					showNotification(data.message, 'success');
					closeEditModal();
					// รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่
					setTimeout(() => location.reload(), 1500);
				} else {
					showNotification(data.message, 'error');
				}
			})
			.catch(error => {
				console.error('Error:', error);
				showNotification('เกิดข้อผิดพลาดในการอัปเดตข้อมูล', 'error');
			});
		});

        // จัดการการส่งฟอร์มลบด้วย AJAX
        document.getElementById('deleteForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const deleteBtn = this.querySelector('.delete-confirm-btn');
            const originalText = deleteBtn.innerHTML;

            // แสดงสถานะกำลังโหลด
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังลบ...';
            deleteBtn.disabled = true;

            fetch('delete_member.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        closeDeleteModal();
                        // รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                        deleteBtn.innerHTML = originalText;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                });
        });

        function toggleDropdown() {
            const dropdown = document.getElementById('downloadDropdown');
            dropdown.classList.toggle('show');
        }

        function downloadFile(type) {
            const typeNames = {
                'xls': 'Excel',
                'csv': 'CSV',
                'pdf': 'PDF'
            };

            if (confirm(`ต้องการดาวน์โหลดข้อมูลเป็นไฟล์ ${typeNames[type]} หรือไม่?`)) {
                // Show loading notification
                showNotification(`กำลังเตรียมไฟล์ ${typeNames[type]}...`, 'info');

                if (type === 'pdf') {
                    // เปิดในหน้าต่างใหม่สำหรับ PDF
                    const newWindow = window.open(`?download=1&type=${type}`, '_blank');
                    if (!newWindow) {
                        showNotification('กรุณาอนุญาตให้เปิดหน้าต่างใหม่', 'error');
                    } else {
                        setTimeout(() => {
                            showNotification(`เตรียมไฟล์ ${typeNames[type]} เรียบร้อย`, 'success');
                        }, 2000);
                    }
                } else {
                    // ดาวน์โหลดโดยตรงสำหรับ Excel และ CSV
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = `?download=1&type=${type}`;
                    document.body.appendChild(iframe);

                    setTimeout(() => {
                        showNotification(`ดาวน์โหลดไฟล์ ${typeNames[type]} เรียบร้อย`, 'success');
                        document.body.removeChild(iframe);
                    }, 2000);
                }
            }

            toggleDropdown(); // Close dropdown
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.download-section')) {
                const dropdown = document.getElementById('downloadDropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });
        <?php endif; ?>

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const content = document.getElementById('notificationContent');

            const icon = type === 'error' ? 'exclamation-circle' :
                type === 'info' ? 'info-circle' : 'check-circle';

            content.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-${icon}"></i>
                    <div>
                        <strong>${type === 'error' ? 'เกิดข้อผิดพลาด!' :
                    type === 'info' ? 'ข้อมูล!' : 'สำเร็จ!'}</strong><br>
                        ${message}
                    </div>
                    <i class="fas fa-times" onclick="hideNotification()" 
                       style="cursor: pointer; opacity: 0.7; margin-left: auto;"></i>
                </div>
            `;

            notification.className = `notification ${type} show`;

            // Auto hide after 5 seconds
            setTimeout(() => {
                hideNotification();
            }, 5000);
        }

        function hideNotification() {
            const notification = document.getElementById('notification');
            notification.classList.remove('show');
        }

        // Add loading animation for content
        document.addEventListener('DOMContentLoaded', function () {
            const profileContainer = document.querySelector('.profile-container');
            const tableRows = document.querySelectorAll('tbody tr');
            
            // Profile animation
            if (profileContainer) {
                profileContainer.style.opacity = '0';
                profileContainer.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    profileContainer.style.transition = 'all 0.6s ease';
                    profileContainer.style.opacity = '1';
                    profileContainer.style.transform = 'translateY(0)';
                }, 200);
            }
            
            // Table rows animation
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });

        // แสดงข้อความต้อนรับและข้อความจาก session
        <?php if (isset($_SESSION['success_message'])): ?>
            showNotification('<?php echo addslashes($_SESSION['success_message']); ?>', 'success');
            <?php if (isset($_SESSION['login_count']) && $_SESSION['login_count'] <= 1): ?>
                setTimeout(() => {
                    showNotification('ยินดีต้อนรับสู่ระบบ! คุณเป็นพนักงานใหม่', 'info');
                }, 2000);
            <?php endif; ?>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showNotification('<?php echo addslashes($_SESSION['error_message']); ?>', 'error');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>
