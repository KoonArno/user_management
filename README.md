# User Management System

ระบบจัดการผู้ใช้งาน (User Management System) สำหรับการบริหารจัดการข้อมูลผู้ใช้

## 📋 รายละเอียดโปรเจค

ระบบนี้พัฒนาขึ้นเพื่อจัดการข้อมูลผู้ใช้งาน รองรับการสร้าง แก้ไข ลบ และค้นหาข้อมูลผู้ใช้

## 🌐 ทดลองใช้งาน

🔗 **Demo URL**: [https://67040460.byethost12.com/login.php](https://67040460.byethost12.com/login.php)

### บัญชีทดสอบ

#### 👔 หัวหน้าแผนก
- **Username**: `Head`
- **Password**: `123456`
- มีสิทธิ์เข้าถึงและจัดการข้อมูลผู้ใช้ทั้งหมด

#### 👤 พนักงาน
- **Username**: `test`
- **Password**: `123456`
- มีสิทธิ์เข้าถึงข้อมูลพื้นฐาน

> ⚠️ **หมายเหตุ**: นี่เป็นระบบทดสอบ กรุณาอย่าใส่ข้อมูลส่วนตัวจริงหรือข้อมูลที่สำคัญ

## ✨ ฟีเจอร์หลัก

- 👤 การจัดการข้อมูลผู้ใช้ (CRUD Operations)
- 🔐 ระบบ Authentication และ Authorization
- 🔍 ค้นหาและกรองข้อมูลผู้ใช้
- 📊 แสดงรายการผู้ใช้แบบ Pagination
- 👥 จัดการ Role และ Permission
- 📧 ส่ง Email ยืนยันตัวตน
- 🔒 เข้ารหัสรหัสผ่านอย่างปลอดภัย

## 🛠️ เทคโนโลยีที่ใช้

- **Backend**: Node.js / Python / PHP (ระบุตามที่ใช้จริง)
- **Database**: MongoDB / MySQL / PostgreSQL (ระบุตามที่ใช้จริง)
- **Frontend**: React / Vue / Angular (ระบุตามที่ใช้จริง)
- **Authentication**: JWT / Session / OAuth

## 📦 การติดตั้ง

### ความต้องการของระบบ

- Node.js v14+ (หรือระบุ version ที่ใช้)
- npm หรือ yarn
- Database (MongoDB/MySQL/PostgreSQL)

### ขั้นตอนการติดตั้ง

1. Clone repository

```bash
git clone https://github.com/KoonArno/user_management.git
cd user_management
```

2. ติดตั้ง dependencies

```bash
npm install
# หรือ
yarn install
```

3. ตั้งค่า Environment Variables

สร้างไฟล์ `.env` และกำหนดค่าต่อไปนี้:

```env
PORT=3000
DATABASE_URL=your_database_url
JWT_SECRET=your_jwt_secret
EMAIL_SERVICE=your_email_service
```

4. รัน Database Migration (ถ้ามี)

```bash
npm run migrate
```

5. เริ่มต้นการทำงาน

```bash
# Development mode
npm run dev

# Production mode
npm start
```

## 🚀 การใช้งาน

### API Endpoints

#### Authentication

- `POST /api/auth/register` - ลงทะเบียนผู้ใช้ใหม่
- `POST /api/auth/login` - เข้าสู่ระบบ
- `POST /api/auth/logout` - ออกจากระบบ
- `POST /api/auth/refresh` - Refresh token

#### User Management

- `GET /api/users` - ดึงรายการผู้ใช้ทั้งหมด
- `GET /api/users/:id` - ดึงข้อมูลผู้ใช้ตาม ID
- `POST /api/users` - สร้างผู้ใช้ใหม่
- `PUT /api/users/:id` - แก้ไขข้อมูลผู้ใช้
- `DELETE /api/users/:id` - ลบผู้ใช้

### ตัวอย่างการใช้งาน

#### ลงทะเบียนผู้ใช้ใหม่

```javascript
fetch('http://localhost:3000/api/auth/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    username: 'john_doe',
    email: 'john@example.com',
    password: 'securePassword123'
  })
})
```

#### ดึงรายการผู้ใช้

```javascript
fetch('http://localhost:3000/api/users', {
  headers: {
    'Authorization': 'Bearer YOUR_JWT_TOKEN'
  }
})
```

## 📁 โครงสร้างโปรเจค

```
user_management/
├── src/
│   ├── controllers/     # Controllers
│   ├── models/         # Database models
│   ├── routes/         # API routes
│   ├── middleware/     # Middleware functions
│   ├── utils/          # Utility functions
│   └── config/         # Configuration files
├── tests/              # Test files
├── public/             # Static files
├── .env.example        # ตัวอย่าง environment variables
├── package.json
└── README.md
```