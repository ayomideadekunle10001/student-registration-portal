CREATE DATABASE IF NOT EXISTS library_borrowing_db;
USE library_borrowing_db;

CREATE TABLE IF NOT EXISTS borrow_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    matric VARCHAR(100) NOT NULL,
    department VARCHAR(255) NOT NULL,
    level VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    bookTitle VARCHAR(255) NOT NULL,
    bookCode VARCHAR(100) NOT NULL,
    bookCategory VARCHAR(100) NOT NULL,
    borrowDuration INT NOT NULL,
    pickupMode VARCHAR(100) NOT NULL,
    services TEXT NOT NULL,
    borrowID VARCHAR(100) UNIQUE NOT NULL,
    status VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);