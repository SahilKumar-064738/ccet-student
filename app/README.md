# CCET Student Vault - Advanced Multi-Admin System

## Quick Start

### 1. Start the application
```bash
docker-compose up -d
```

### 2. Access the application
- Public: http://localhost:8080
- Admin: http://localhost:8080/admin

### 3. Super Admin Login
Use email: superadmin@ccet.ac.in
The system will send an OTP to log in.

### 4. Configure SMTP
Edit `app/config/config.php` and update SMTP settings for OTP emails.

## Features
- Public browsing without login
- OTP-based admin authentication
- Role-based access (Super Admin + Branch-Year Admin)
- PDF-only uploads with MIME validation
- Teacher name filtering
- Secure file downloads
- Audit logging

## Directory Structure
- `/public` - Public-facing pages
- `/admin` - Admin dashboard and management
- `/api` - REST API endpoints
- `/lib` - Core library classes
- `/config` - Configuration files
- `/uploads` - File storage (restricted access)

## Security Features
- Rate limiting on OTP requests
- CSRF protection
- Secure file downloads
- HttpOnly cookies
- SQL injection prevention
- XSS protection

## Support
For issues, check logs in Docker containers:
```bash
docker-compose logs -f php
docker-compose logs -f mysql
```
