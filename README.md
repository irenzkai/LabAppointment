# 🧪 Laboratory Appointment System

A full-stack web application built with **Laravel 11** and **Bootstrap 5** to manage laboratory test bookings. This system features a multi-role architecture (User, Staff, Admin) and is deployed using **Docker** on **Render** with an **Aiven MySQL** cloud database.

## 🚀 Tech Stack
*   **Framework:** Laravel 11 (PHP 8.2+)
*   **Frontend:** Bootstrap 5, JavaScript (Vanilla), Blade Templates
*   **Database:** MySQL (Local: XAMPP | Production: Aiven SQL)
*   **Deployment:** Docker, Render (Web Service)
*   **Auth:** Laravel Breeze (Customized)

---

## 🛠️ Getting Started (For Collaborators)

### 1. Repository Setup
Open VS Code in an empty folder and run the following:
```bash
# Clone the repository
git clone https://github.com/irenzkai/LabAppointment.git

# Enter the project directory
cd LabAppointment

# Configure your Git identity (if not already done)
git config user.name "Your GitHub Username"
git config user.email "your-email@example.com"
```

### 2. Local Environment Setup (XAMPP)
Ensure **Apache** and **MySQL** are running in your XAMPP Control Panel.

```bash
# 1. Install PHP dependencies
composer install

# 2. Setup the Environment file
cp .env.example .env

# 3. Generate Security Key
php artisan key:generate
```

### 3. Local Database Configuration
1.  Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2.  Create a new database named: `labappointment`.
3.  Open the `.env` file in VS Code and update these lines:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=labappointment
    DB_USERNAME=root
    DB_PASSWORD=
    ```

### 4. Initialize Local Data
Run migrations and seed the default admin account:
```bash
# Create tables
php artisan migrate

# Create default admin 
php artisan db:seed --class=AdminSeeder
```

### 5. Run the Project
```bash
php artisan serve
```
Visit the site at: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## 🔄 Daily Workflow
To keep the live site updated and avoid code conflicts, follow this order:

1.  **Pull latest changes:** `git pull origin main`
2.  **Make edits** in VS Code.
3.  **Test locally** on `127.0.0.1:8000`.
4.  **Submit changes:**
    ```bash
    git add .
    git commit -m "Brief description of changes"
    git push origin main
    ```
    *Note: Pushing to the main branch triggers an automatic Docker rebuild and deployment on Render.*

---

## 🗄️ Remote Database Access (Aiven SQL)
To view live production data, connect using **DBeaver** or **MySQL Workbench**.

| Setting | Value |
| :--- | :--- |
| **Host** | `mysql-1c9f6347-online-7a36.k.aivencloud.com` |
| **Port** | `19906` |
| **User** | `avnadmin` |
| **Password** | *To Request From Owner* |
| **SSL CA Cert** | `storage/certs/ca.pem` |

**Security Note:** The production environment requires SSL. Ensure your database client is configured to use the provided `ca.pem` certificate.

---

## ⚠️ Important Deployment Notes
*   **HTTPS:** The system is configured to force HTTPS in production to prevent CSRF "Page Expired" errors.
*   **Migrations:** Database migrations are automated via the Docker `CMD` on Render.
*   **Cold Starts:** On the Render Free Tier, the site may take ~30 seconds to "wake up" after inactivity.