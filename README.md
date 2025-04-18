# Peer Review System

A modern web-based peer review system for online learning, allowing students to submit their work and receive feedback from peers.

## Features

- User authentication (login/register)
- Profile management with customizable information
- Work submission with file attachments
- Anonymous peer reviews
- Star rating system
- Modern UI with animations and transitions
- Responsive design for all devices

## Tech Stack

- PHP 7.4+
- MySQL 5.7+
- HTML5
- Tailwind CSS
- JavaScript
- Alpine.js
- Animate.css

## Setup Instructions

1. Clone the repository to your web server directory
2. Create a MySQL database and import the `database.sql` file
3. Configure the database connection in `config/database.php`
4. Create the following directories with write permissions:
   - `uploads/profile_pictures/`
   - `uploads/submissions/`
5. Make sure your web server has PHP with PDO and file upload extensions enabled

## Directory Structure

```
├── config/
│   └── database.php
├── uploads/
│   ├── profile_pictures/
│   └── submissions/
├── index.php
├── login.php
├── register.php
├── profile.php
├── submissions.php
├── review.php
├── logout.php
└── database.sql
```

## Security Features

- Password hashing
- Prepared statements for database queries
- Input sanitization
- Session management
- File upload validation

## Usage

1. Register a new account or login
2. Complete your profile
3. Submit your work for review
4. Review other students' submissions
5. Receive and view feedback on your submissions

## Contributing

Feel free to submit issues and enhancement requests! 