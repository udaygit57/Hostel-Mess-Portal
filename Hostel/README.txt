HOSTEL MESS PORTAL  (HTML + CSS + JavaScript + PHP + MySQL)

SETUP (XAMPP)
1. Copy this whole "Hostel" folder to  C:\xampp\htdocs\Hostel
2. Open the XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin  ->  Import  ->  choose database.sql  ->  Go.
   (Or open database.sql in MySQL Workbench and run it.)  This creates the database "hostel_db".
4. Open http://localhost/Hostel/

LOGINS
- Warden panel: http://localhost/Hostel/admin/login.php
  username: warden   password: admin123   (change it in Settings after your first login)
- Student registration (one-time activation code):
    1. Warden: Students page -> add the student. The system shows a random activation code ONCE.
    2. Warden gives the student their roll number + code privately (in person / private message).
    3. Student: login page -> "Create an account" -> roll number + activation code + a new password.
    4. The code is deleted as soon as registration succeeds, so it cannot be used again.
  A code expires after 7 days and locks after 5 wrong tries. Lost or expired? Press "New code".
  Forgot password? Warden presses "Reset password" and gives a new code.
  Sample student: roll no 78626264, demo code DEMO-2026 (valid 30 days after import).

MONTHLY WORK FOR THE WARDEN
1. Dues page -> pick the month -> "Create bills".
2. Holiday / vacation (Holi, Diwali, semester break): Dues page -> "Deduct fee for everyone",
   enter the amount you decide and a reason. Or use Edit on one bill for a single student.
3. When a student pays by UPI, find the bill and press "Mark paid".

IF MySQL HAS A PASSWORD: edit DB_USER / DB_PASS at the top of config.php.

FOLDERS
  config.php            database settings + shared helper functions
  database.sql          tables and sample data (menu from the 2026-27 chart)
  add_activation_codes.sql  only if you imported an older database.sql: adds the code columns
  user_login/           login.php, register.php
  user_interface/       userinterface.php (dues), pay.php (QR), menu.php, logout.php
  admin/                warden panel
  assets/qrcode.js      QR code generator (works offline)
