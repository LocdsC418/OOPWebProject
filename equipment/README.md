# EQ Lend: Equipment Lending System

Project for the Object-Oriented Programming course

## Installation (XAMPP)

1. Install XAMPP, then start Apache and MySQL
2. Open http://localhost/phpmyadmin, go to the Import tab, and import `database.sql`
3. If your MySQL has a password, edit `USER` / `PASS` in `src/Database.php`
4. Copy the `equipment` folder to `C:\xampp\htdocs\`
5. Open http://localhost/equipment/

## Features

- Browse equipment, search, and filter by category
- Return items, with a fine of 20 baht per day when overdue
- Add equipment and register members
- Borrowing history, most popular equipment, and total fines

## Class structure

- `Database`: Singleton PDO connection
- `Model`: Abstract base class (`find()`, `count()`)
- `Category`, `Equipment`, `Member`, `Loan`: Extend `Model`
- `LoanException`: The system's exception
- `Flash`, `Csrf`: Flash messages and CSRF protection
