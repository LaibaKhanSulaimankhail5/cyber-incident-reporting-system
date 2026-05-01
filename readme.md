# CyberIncidentSystem

AI-Assisted Cyber Incident Reporting & Threat Alert System

---

## Overview

CyberIncidentSystem is a role-based web application for reporting, managing, and analyzing cybersecurity incidents.  
It enables employees to submit incidents and allows administrators to monitor, investigate, and respond in real time.

---

## Features

- Incident reporting with predefined categories
- AI-based risk scoring and classification
- Role-based dashboards (Employee / Admin)
- Real-time updates using AJAX
- Incident tracking and filtering
- Admin controls for user and incident management
- Secure authentication and session handling

---

## Tech Stack

- **Backend:** PHP (Core)
- **Database:** MySQL (PDO)
- **Frontend:** JavaScript (ES6), HTML, CSS
- **Communication:** AJAX (Fetch API)

---

## Project Structure

cyberguard/
├── config/
├── ajax/
├── css/
├── js/
├── index.php
├── register.php
├── dashboard.php
├── admin.php
├── report.php
├── incident.php
├── logout.php
└── setup.sql

---

## Setup

```bash
git clone https://github.com/yourusername/cyberguard.git
cd cyberguard

Import database:

mysql -u root -p < setup.sql

Update database credentials in:

config/db.php

Run project:

http://localhost/cyberguard/
Demo Credentials
Role	Email	Password
Admin	admin@company.com
	admin123
Employee	john@company.com
	pass123
Security
Prepared statements (SQL injection protection)
Password hashing (bcrypt)
CSRF protection
XSS prevention
Role-based access control
```
