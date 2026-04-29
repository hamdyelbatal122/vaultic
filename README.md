# Login System — PHP & MySQL

A secure **user authentication system** built with PHP and MySQL. Features user registration, login, session management, and password hashing.

## ✨ Features

- 🔐 User registration with input validation
- 🔑 Secure login with session management
- 🛡️ Password hashing (MD5 / bcrypt)
- 🚪 Logout functionality
- 💾 MySQL database integration
- 📱 Responsive design

## 🛠️ Tech Stack

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)

## ⚙️ Requirements

- PHP >= 5.6
- MySQL >= 5.6
- Apache (XAMPP / WAMP recommended)

## 🚀 Getting Started

1. **Start Apache & MySQL** in XAMPP

2. **Clone the repository** into your web root
   ```bash
   git clone https://github.com/hamdyelbatal122/login_system.git /path/to/xampp/htdocs/login_system
   ```

3. **Import the database**
   - Open `http://localhost/phpmyadmin/`
   - Create a new database (e.g., `login_db`)
   - Import the SQL file from the project folder

4. **Configure your database credentials**

   Open `config.php` and update:
   ```php
   $host     = 'localhost';
   $db_user  = 'root';
   $db_pass  = '';
   $db_name  = 'login_db';
   ```

5. **Access the app**
   ```
   http://localhost/login_system/
   ```

## 📁 Project Structure

```
login_system/
├── config.php       # Database configuration
├── index.php        # Login page
├── register.php     # Registration page
├── dashboard.php    # Protected page
├── logout.php       # Session destroy
├── database/        # SQL dump
└── README.md
```

## 📄 License

This project is open source and available under the [MIT License](LICENSE).
