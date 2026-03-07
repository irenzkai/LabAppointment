🚀 Getting Started (For Collaborators)
1. Repository Setup

Open VS Code in an empty folder and run the following commands:

code
Bash
download
content_copy
expand_less
# Clone the repository
git clone https://github.com/irenzkai/LabAppointment.git

# Enter the project directory
cd LabAppointment

# Configure your Git identity
git config user.name "Your GitHub Username"
git config user.email "your-email@example.com"
2. Local Environment Setup (XAMPP)

Ensure Apache and MySQL are running in your XAMPP Control Panel.

code
Bash
download
content_copy
expand_less
# 1. Install PHP dependencies
composer install

# 2. Install & Build Frontend assets
npm install
npm run build

# 3. Setup the Environment file
cp .env.example .env

# 4. Generate Security Key
php artisan key:generate
3. Database Configuration

Open http://localhost/phpmyadmin.

Create a new database named: labappointment.

Open the .env file in VS Code and update these lines:

code
Env
download
content_copy
expand_less
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=labappointment
DB_USERNAME=root
DB_PASSWORD=
4. Initialize Data

Run the migrations and seed the default admin account:

code
Bash
download
content_copy
expand_less
# Create tables
php artisan migrate

# Create default admin (admin@lab.com | password123)
php artisan db:seed --class=AdminSeeder
5. Run the Project
code
Bash
download
content_copy
expand_less
php artisan serve

Visit the site at: http://127.0.0.1:8000

🛠 Daily Workflow

To avoid code conflicts, always follow this order when working:

Update your local code (Do this before starting work):

code
Bash
download
content_copy
expand_less
git pull origin main

Make your edits in VS Code.

Submit your changes:

code
Bash
download
content_copy
expand_less
git add .
git commit -m "Briefly describe what you changed"
git push origin main

Note: Once pushed, Render will automatically start a new deployment for the live site.

🗄 Remote Database Access

To view the live data on Aiven SQL, use MySQL Workbench or DBeaver.

Setting	Value
Host	mysql-1c9f6347-online-7a36.k.aivencloud.com
Port	19906
User	avnadmin
Password	[Ask Project Owner for Password]
SSL CA Cert	Found in storage/certs/ca.pem