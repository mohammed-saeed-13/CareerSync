# CareerSync – Smart Campus Placement Ecosystem
## Setup Instructions for XAMPP

---

## 📁 Folder Structure

```
careersync/
├── admin/
│   ├── analytics.php
│   ├── applications.php
│   ├── criteria.php
│   ├── dashboard.php
│   ├── drives.php
│   ├── interviews.php
│   └── students.php
├── alumni/
│   ├── dashboard.php
│   ├── mentorship.php
│   ├── profile.php
│   └── referrals.php
├── api/
│   ├── chat.php
│   ├── notifications.php
│   └── notify.php
├── assets/
│   ├── css/main.css
│   └── js/main.js
├── includes/
│   ├── auth.php
│   ├── db.php
│   ├── footer.php
│   ├── gemini.php
│   ├── header.php
│   └── sidebar.php
├── student/
│   ├── alumni.php
│   ├── applications.php
│   ├── dashboard.php
│   ├── drives.php
│   ├── profile.php
│   ├── resume.php
│   └── skill-gap.php
├── config.php
├── index.php
├── login.php
├── logout.php
├── notifications.php
├── register.php
├── schema.sql
└── unauthorized.php
```

---

## ⚡ Quick Setup Steps

### Step 1 – Install XAMPP
Download from https://www.apachefriends.org and install.
Start **Apache** and **MySQL** from XAMPP Control Panel.

### Step 2 – Copy Project Files
Place the `careersync` folder inside:
```
C:\xampp\htdocs\careersync\       (Windows)
/Applications/XAMPP/htdocs/careersync/  (macOS)
```

### Step 3 – Create Database
1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Click **New** → name it `careersync` → Click **Create**
3. Click **Import** tab → Choose `schema.sql` → Click **Go**

### Step 4 – Configure Connection
Open `config.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'careersync');
define('DB_USER', 'root');
define('DB_PASS', '');  // your MySQL password if set
```

### Step 5 – Add Gemini API Key
In `config.php`:
```php
define('GEMINI_API_KEY', 'your_actual_gemini_api_key_here');
```
Get your key from: https://aistudio.google.com/app/apikey

### Step 6 – Access the App
Open browser: http://localhost/careersync

---

## 🔐 Demo Login Credentials

| Role    | Email                  | Password   |
|---------|------------------------|------------|
| Admin   | admin@careersync.edu   | password   |
| Student | rahul@student.edu      | password   |
| Student | priya@student.edu      | password   |
| Alumni  | amit@alumni.edu        | password   |

---

## ✨ Key Features

| Module                    | Description                                              |
|---------------------------|----------------------------------------------------------|
| Smart Eligibility Engine  | Auto-queries eligible students by CGPA, branch, backlogs |
| AI Resume Analyzer        | Gemini-powered resume score, ATS check, suggestions      |
| Skill Gap Prediction      | Compares your skills vs placed students                  |
| Career AI Chatbot         | Context-aware bot using your profile + live drive data   |
| Interview Scheduler       | Assigns time slots with overlap prevention               |
| Alumni Connect            | Mentorship booking + Job referral board                  |
| Analytics Dashboard       | Charts for placement trends, skills, branches            |
| Dark/Light Theme          | Full system theme with localStorage persistence          |
| Role-based Access Control | Admin / Student / Alumni with middleware protection      |

---

## 🔧 PHP Requirements
- PHP 7.4+ (8.x recommended)
- PDO + PDO_MySQL extension enabled
- cURL extension enabled (for Gemini API)
- MySQL 5.7+ or MariaDB 10.3+

---

## 🚀 Hackathon Notes
- All data is 100% database-driven (no hardcoded arrays)
- CSRF protection on all POST forms
- SQL injection prevented via prepared statements everywhere
- XSS prevented via htmlspecialchars() on all output
- Passwords hashed with bcrypt (cost 12)
- Session regeneration on login
- Responsive design works on mobile

---

## 📞 Support
For any issues during hackathon setup, check:
1. XAMPP Apache + MySQL are running
2. Database `careersync` exists with schema imported
3. PHP cURL is enabled for Gemini API calls
4. File permissions are correct on Linux/macOS
