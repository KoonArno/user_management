<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการล็อกอิน และบทบาท (เฉพาะแอดมินที่เข้าถึงได้)
if (!isset($_SESSION['user_id']) || ($_SESSION['status'] != '1' && $_SESSION['status'] != '2')){
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

// ดึงข้อมูลจำนวนพนักงานแต่ละแผนก
$sql = "SELECT department, COUNT(id) AS employee_count FROM members GROUP BY department ORDER BY department";
$result = $conn->query($sql);

$department_data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $department_data[] = $row;
    }
}
$conn->close();

$labels = [];
$data = [];
$total_employees = 0;
foreach ($department_data as $item) {
    $labels[] = $item['department'];
    $data[] = (int)$item['employee_count'];
    $total_employees += (int)$item['employee_count'];
}

// แปลงข้อมูลเป็น JSON เพื่อส่งไปยัง JavaScript
$json_labels = json_encode($labels);
$json_data = json_encode($data);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>กราฟแท่งจำนวนพนักงานแต่ละแผนก</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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

        /* Header Styles - เหมือน dashboard.php */
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.05), transparent);
            transition: left 0.5s;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(37, 99, 235, 0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
            display: block;
            background: var(--blue-100);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            display: block;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Chart Container */
        .chart-container {
            background: var(--white);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.1);
            position: relative;
            overflow: hidden;
        }

        .page-title {
            color: var(--gray-800);
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary-blue);
            background: var(--blue-100);
            padding: 0.5rem;
            border-radius: 8px;
        }

        .chart-wrapper {
            position: relative;
            height: 450px;
            margin-bottom: 2rem;
        }

        canvas {
            border-radius: 12px;
        }

        /* Department List */
        .department-list {
            background: var(--blue-50);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            border: 1px solid var(--blue-200);
        }

        .department-list h3 {
            color: var(--gray-800);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .department-list h3 i {
            color: var(--primary-blue);
        }

        .department-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--blue-200);
            transition: all 0.3s ease;
        }

        .department-item:last-child {
            border-bottom: none;
        }

        .department-item:hover {
            background: var(--blue-100);
            padding-left: 1rem;
            border-radius: 8px;
            margin: 0 -0.5rem;
            padding-right: 1rem;
        }

        .department-name {
            font-weight: 500;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .department-name i {
            color: var(--primary-blue);
        }

        .department-count {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--light-blue) 100%);
            color: var(--white);
            padding: 0.4rem 0.875rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        /* Action Buttons */
        .actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--light-blue) 100%);
            color: var(--white);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-family: "Sarabun", sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }

        .btn-secondary:hover {
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        /* Loading */
        .loading {
            display: none;
            text-align: center;
            padding: 3rem;
            color: var(--gray-600);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--blue-200);
            border-top: 4px solid var(--primary-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Fade In Animation */
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .chart-container {
                padding: 1.5rem;
            }
            
            .chart-wrapper {
                height: 350px;
            }
            
            .header h1 {
                font-size: 1.25rem;
            }
            
            .page-title {
                font-size: 1.25rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 200px;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }

            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <i class="fas fa-chart-bar"></i>
            ระบบบริหารจัดการพนักงาน
        </h1>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="stat-number"><?php echo $total_employees; ?></span>
                <span class="stat-label">พนักงานทั้งหมด</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <span class="stat-number"><?php echo count($department_data); ?></span>
                <span class="stat-label">จำนวนแผนก</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="stat-number"><?php echo $total_employees > 0 ? round($total_employees / count($department_data), 1) : 0; ?></span>
                <span class="stat-label">เฉลี่ยต่อแผนก</span>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="chart-container fade-in">
            <h2 class="page-title">
                <i class="fas fa-chart-bar"></i>
                กราฟแสดงจำนวนพนักงานแต่ละแผนก
            </h2>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>กำลังโหลดข้อมูล...</p>
            </div>
            
            <div class="chart-wrapper">
                <canvas id="departmentChart"></canvas>
            </div>

            <!-- Department List -->
            <div class="department-list">
                <h3>
                    <i class="fas fa-list"></i>
                    รายละเอียดแผนก
                </h3>
                <?php foreach ($department_data as $dept): ?>
                <div class="department-item">
                    <span class="department-name">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($dept['department']); ?>
                    </span>
                    <span class="department-count"><?php echo $dept['employee_count']; ?> คน</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="actions fade-in">
            <a href="dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i>
                กลับสู่แดชบอร์ด
            </a>
            <button onclick="downloadChart()" class="btn btn-secondary">
                <i class="fas fa-download"></i>
                ดาวน์โหลดกราฟ
            </button>
        </div>
    </div>

    <script>
        // Chart Data
        const labels = <?php echo $json_labels; ?>;
        const data = <?php echo $json_data; ?>;

        // Blue theme color palette
        const colors = [
            'rgba(37, 99, 235, 0.8)',   // primary-blue
            'rgba(59, 130, 246, 0.8)',  // light-blue
            'rgba(14, 165, 233, 0.8)',  // sky-blue
            'rgba(6, 182, 212, 0.8)',   // cyan-blue
            'rgba(99, 102, 241, 0.8)',  // indigo
            'rgba(139, 92, 246, 0.8)',  // violet
            'rgba(168, 85, 247, 0.8)',  // purple
            'rgba(236, 72, 153, 0.8)'   // pink
        ];

        const borderColors = [
            'rgba(37, 99, 235, 1)',
            'rgba(59, 130, 246, 1)',
            'rgba(14, 165, 233, 1)',
            'rgba(6, 182, 212, 1)',
            'rgba(99, 102, 241, 1)',
            'rgba(139, 92, 246, 1)',
            'rgba(168, 85, 247, 1)',
            'rgba(236, 72, 153, 1)'
        ];

        // Show loading
        document.getElementById('loading').style.display = 'block';

        // Initialize chart after a short delay for smooth loading effect
        setTimeout(() => {
            const ctx = document.getElementById('departmentChart').getContext('2d');
            
            const departmentChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'จำนวนพนักงาน',
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderColor: borderColors.slice(0, data.length),
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value % 1 === 0) {
                                        return value + ' คน';
                                    }
                                },
                                font: {
                                    family: 'Sarabun',
                                    size: 12
                                },
                                color: '#6b7280'
                            },
                            title: {
                                display: true,
                                text: 'จำนวนพนักงาน',
                                font: {
                                    family: 'Sarabun',
                                    size: 14,
                                    weight: 'bold'
                                },
                                color: '#374151'
                            },
                            grid: {
                                color: 'rgba(37, 99, 235, 0.1)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: 'Sarabun',
                                    size: 12
                                },
                                color: '#6b7280',
                                maxRotation: 45
                            },
                            title: {
                                display: true,
                                text: 'แผนก',
                                font: {
                                    family: 'Sarabun',
                                    size: 14,
                                    weight: 'bold'
                                },
                                color: '#374151'
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(37, 99, 235, 0.9)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(37, 99, 235, 1)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' คน';
                                }
                            }
                        }
                    },
                    onHover: (event, activeElements) => {
                        event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                    }
                }
            });

            // Hide loading
            document.getElementById('loading').style.display = 'none';
            
            // Store chart reference for download function
            window.departmentChart = departmentChart;
        }, 1000);

        // Download chart function
        function downloadChart() {
            const canvas = document.getElementById('departmentChart');
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = 'department-chart-' + new Date().toISOString().split('T')[0] + '.png';
            link.href = url;
            link.click();
            
            // Show notification (if you have notification system)
            if (typeof showNotification === 'function') {
                showNotification('ดาวน์โหลดกราฟเรียบร้อยแล้ว', 'success');
            }
        }

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading animation for elements
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    el.style.transition = 'all 0.6s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>