## **Ramisi Hospital Management System (HMS)**

Welcome to the **Ramisi HMS** project! This system uses **PHP** and **MySQL** to manage a hospital’s operations efficiently. It includes user roles for **Admin, Doctor, Nurse, Receptionist, and Patient**. Each role has a separate dashboard and functionality tailored to their daily tasks.

---

### **Table of Contents**
1. [Project Structure](#project-structure)
2. [Installation & Setup](#installation--setup)
3. [Database Schema](#database-schema)
4. [User Roles & Dashboards](#user-roles--dashboards)
5. [Key Features](#key-features)
6. [Security & Best Practices](#security--best-practices)
7. [Contributing](#contributing)

---

### **Project Structure**
```
Ramisi-HMS/
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── manage_staff.php
│   ├── manage_schedule.php
│   └── generate_reports.php
├── doctor/
│   ├── login.php
│   ├── dashboard.php
│   ├── diagnose_prescribe.php
│   ├── update_medical_records.php
│   └── view_patient_details.php
├── nurse/
│   └── login.php
├── receptionist/
│   └── login.php
├── patient/
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   └── appointments.php
├── config/
│   └── database.php
├── assets/
│   ├── css/
│   └── images/
├── index.php
└── README.md
```

---

### **Installation & Setup**

1. **Clone the Repository**  
   ```bash
   git clone https://github.com/frashid17/ramisi-hms.git
   ```
2. **Create Database**  
   - Create a MySQL database named `Ramisi_HMS` (or your preferred name).
   - Import the SQL schema `ramisi_hms.sql` into your database.
3. **Update Database Config**  
   - In `config/database.php`, adjust `$host`, `$db`, `$user`, and `$pass` for your environment.
4. **Folder Permissions**  
   - Ensure web server can read (and write if needed) the `assets` folder (for images, etc.).
5. **Launch the Project**  
   - Navigate to `http://localhost/Ramisi-HMS/` in your browser.

---

### **Database Schema**
Common tables include:
- **users** (shared by all roles)
- **doctors**, **nurses**, **receptionists**, **patients** (role-specific details)
- **appointments** (links patients and doctors)
- **medical_records** (diagnosis, prescription, notes)
- **payments** (tracks patient bills or payments)
  
Ensure you run the appropriate SQL commands to create these tables before using the system.

---

### **User Roles & Dashboards**

1. **Admin**  
   - **Login**: `admin/login.php` (hardcoded credentials or from `users` table)
   - **Dashboard**: `admin/dashboard.php`
   - **Functions**:
     - View all appointments
     - Manage payments
     - Add/manage staff (doctor, nurse, receptionist)
     - Manage doctor schedules
     - Generate reports

2. **Doctor**  
   - **Login**: `doctor/login.php`
   - **Dashboard**: `doctor/dashboard.php`
   - **Functions**:
     - Diagnose & prescribe
     - Update medical records
     - View appointments & patient details

3. **Nurse**  
   - **Login**: `nurse/login.php`
   - **Functions** (planned):
     - Assist in patient care
     - Update patient statuses

4. **Receptionist**  
   - **Login**: `receptionist/login.php`
   - **Functions** (planned):
     - Manage patient appointments
     - Check payments

5. **Patient**  
   - **Login**: `patient/login.php`
   - **Register**: `patient/register.php`
   - **Dashboard**: `patient/dashboard.php`
   - **Functions**:
     - View appointments & book new ones
     - View payments
     - Change password

---

### **Key Features**

- **Role-Based Authentication**:  
  Each role accesses the system via their respective login pages. The code checks `$_SESSION['role']` to restrict page access.

- **Appointments Management**:  
  Patients can book appointments with available doctors. Admin/Receptionists can track or update them. Doctors see only their own appointments.

- **Medical Records**:  
  Doctors can create or update records with diagnosis, prescription, and notes. Patients see their records (in progress if you add a patient view).

- **Payments**:  
  Tracks each payment’s amount, status (paid, pending), and date. Admin sees all transactions.

- **Reporting**:  
  Admin can generate a CSV report of payments and appointments.

---

### **Security & Best Practices**

- Use **`password_hash()`** and **`password_verify()`** for storing and validating user passwords.
- Validate all form inputs (server-side) to prevent SQL injection or XSS attacks.
- Use **parameterized queries** (`PDO::prepare`) when interacting with the database.
- Restrict file/folder permissions on the server so that config files (e.g., `database.php`) aren’t publicly accessible.

---

### **Contributing**

1. **Fork the Repository**  
2. **Create a Feature Branch**: `git checkout -b feature/my-new-feature`
3. **Commit Changes**: `git commit -m 'Add my new feature'`
4. **Push to Branch**: `git push origin feature/my-new-feature`
5. **Open a Pull Request**: Submit a PR describing your changes.

---

**Thank you** for using **Ramisi HMS**. 