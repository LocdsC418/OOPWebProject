# EQ Lend: Equipment Lending System

A web app for borrowing and returning equipment. Written in PHP with a MySQL database.

## Features

- Browse equipment, search, and filter by category
- Return items, with a fine of 20 baht per day when overdue
- Add equipment and register members
- Borrowing history, most popular equipment, and total fines

## Installation (XAMPP)

1. Install XAMPP, then start Apache and MySQL
2. Open http://localhost/phpmyadmin, go to the Import tab, and import [`equipment/database.sql`](equipment/database.sql)
3. If your MySQL has a password, edit `USER` / `PASS` in [`equipment/src/Database.php`](equipment/src/Database.php)
4. Copy the `equipment` folder to `C:\xampp\htdocs\`
5. Open http://localhost/equipment/

## File structure

- [equipment/](equipment/): Website source code
  - [src/](equipment/src/): All OOP classes (`Database`, `Model`, `Category`, `Equipment`, `Member`, `Loan`, etc.)
  - [partials/](equipment/partials/): Shared header / footer used on every page
  - `index.php`, `members.php`, `history.php`: The site's pages
  - [style.css](equipment/style.css): The site's stylesheet
  - [database.sql](equipment/database.sql): Table schema and sample data
