# AgriLink Uganda

A market-intelligence and farmer-buyer linkage platform connecting smallholder farmers in Uganda to buyers, with automated matching, produce aggregation, and market price recommendations.

## Tech stack

- Backend: PHP
- Database: MySQL
- Frontend: HTML, CSS, JavaScript

## Structure

- backend/ - PHP API/logic and database connection
- frontend/ - HTML/CSS/JS client
- docs/ - concept note, ERD, workflow diagrams, schema

## Getting started

### Database

1. Create the database: run docs/schema.sql in phpMyAdmin or via CLI:
   mysql -u root -p < docs/schema.sql
2. Copy backend/config/database.example.php to backend/config/database.php and fill in your DB credentials.

### Backend

Requires PHP 8+.
cd backend
php -S localhost:8000

### Frontend

Open frontend/pages/index.html directly, or serve it alongside the backend.

## Documentation

See docs/WORKFLOWS.md for system workflows and ERD, and docs/schema.sql for the database schema.

## Team workflow

See CONTRIBUTING.md.
