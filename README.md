# CampusFlow Academic Portal

A comprehensive academic management system built for TVET-style institutions. It covers admissions, staff management, course delivery, online examinations with proctoring, assignment submission, library resources, and live online classes.

The system is designed to run on any standard PHP 7.4+ / MySQL 5.7+ stack (XAMPP, WAMP, MAMP, or shared hosting such as InfinityFree).

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [User Roles](#user-roles)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Default Accounts](#default-accounts)
- [Folder Structure](#folder-structure)
- [Deployment](#deployment)
- [Testing Checklist](#testing-checklist)
- [Security Notes](#security-notes)
- [License](#license)

---

## Overview

CampusFlow is a full-featured academic portal built around four roles: Administrator, Principal, Lecturer, and Student. It handles the entire academic lifecycle from a public-facing website and online applications through to admissions, unit enrollment, teaching materials, proctored exams, grading, and transcripts.

The public website serves as the college landing site, while authenticated users access role-specific dashboards that connect to a shared database.

---

## Features

### Public Website
- Homepage with institutional overview and quick links
- About page with history, mission, campuses, and accreditation
- Course catalogue fetched from the database (add or remove a course and the public page updates immediately)
- How to Apply with step-by-step guidance
- Online application form that saves to the database and feeds the admin review queue
- Online Campus page explaining the eLearning platform
- eLibrary overview page
- Past Papers overview page
- Photo Gallery with graceful fallback images
- Blog with real articles
- Contact page with Google Maps embed and opening hours
- Floating WhatsApp button with pre-filled message options

### Administrator
- Review online applications, admit students, and assign admission numbers
- Create and manage all user accounts (admin, principal, lecturer, student)
- Manage departments, courses, and units
- Monitor all exam results across every course and unit
- View the full audit log of every action taken by every user
- Upload and manage eLibrary resources
- Reset profile information and password

### Principal
- Institution dashboard with high-level statistics
- Assign lecturers to specific units
- Manage unit notes across all departments
- Manage course tutorials (video links)
- View institutional results across all students
- Generate department and performance reports

### Lecturer
- Personal dashboard with assigned units and student counts
- Enroll students into their own units (per unit)
- Create and manage multiple-choice exams with configurable duration, marks, and pass mark
- Full CRUD for exam questions
- Upload unit notes, assignments, and past papers (file upload or link)
- View student assignment submissions, grade them, and provide feedback
- Schedule online sessions (Zoom, Google Meet, Microsoft Teams, Jitsi)
- View automatic attendance per session
- Enter CAT 1, CAT 2, and Final exam scores for physical exams
- View and authorize retakes for their own exam results
- Upload library resources for their own units
- Monitor all results for their units

### Student
- Personal dashboard showing enrolled units, available exams, notes, assignments, tutorials, and online classes
- Take proctored exams with a countdown timer, webcam snapshots every 30 seconds, tab-switch detection, copy/paste blocking, and fullscreen enforcement
- Auto-graded exams with retake control enforced by the lecturer
- Full answer review after submission: shows selected answer, correct answer, and skipped questions
- Submit assignments with file upload, one revision allowed until graded
- Download unit notes and past papers for enrolled units
- Join online classes with automatic attendance
- View tutorials filtered to their own course
- Access eLibrary resources for enrolled units
- Generate a full printable academic transcript with CAT and Final exam breakdown

---

## User Roles

| Role | Landing Page | Key Responsibilities |
|------|-------------|----------------------|
| Administrator | `/admin/dashboard.php` | Admissions, user management, infrastructure, audit |
| Principal | `/principal/dashboard.php` | Oversight, lecturer assignment, reports |
| Lecturer | `/lecturer/dashboard.php` | Teaching, exams, grading, materials |
| Student | `/student/dashboard.php` | Learning, exams, assignments, transcript |

---

## Technology Stack

- PHP 7.4 or newer (procedural with prepared statements)
- MySQL 5.7 / MariaDB 10.3 or newer
- HTML5, CSS3 (custom, no framework)
- Vanilla JavaScript (no build step required)
- WebRTC `getUserMedia` for webcam proctoring
- Fonts: system UI stack with Georgia for display headings

---

## Installation

### Requirements

- PHP 7.4+ with the `mysqli` extension enabled
- MySQL 5.7+ or MariaDB 10.3+
- A local server such as XAMPP, WAMP, MAMP, or Laragon
- A modern browser with webcam support (Chrome, Edge, Firefox, Safari)

### Steps

1. Clone or download the repository into your web root.
git clone https://github.com/your-username/campusflow-exam-system.git

text
If using XAMPP on Windows, the folder should be placed at:
C:\xampp\htdocs\campusflow-exam-system\

text

2. Start Apache and MySQL from your XAMPP (or equivalent) control panel.

3. Create the `uploads` and `snapshots` folders if they do not exist, and make sure they are writable. On Windows defaults are already fine; on Linux or macOS:
chmod 755 uploads snapshots

text

4. Import the database (see the next section).

5. Open the site in a browser:
http://localhost/campusflow-exam-system/

text
You will be redirected to the public landing page.

6. Log in as administrator using the credentials under [Default Accounts](#default-accounts).

---

## Database Setup

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a database named `campusflow` (or any name — you will update the config file to match).
3. Select that database, open the SQL tab, and paste the entire contents of `database.sql`.
4. Click Go. This creates all tables and seeds sample departments, courses, exams, and questions.

The system will automatically seed the first admin and principal accounts on the first request if they do not exist.

---

## Configuration

Open `config/db.php` and adjust the constants for your environment.

### Local development (XAMPP default)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campusflow');

Default Accounts
On first load, the system creates:

Role	Email	Password
Administrator	admin@campusflow.local	Admin@2026
Principal	principal@campusflow.local	Principal@2026
You should change these immediately after first login.

LOGIN CREDENTIALS
Email: admin@campusflow.local

Password: Admin@2026



Email: principal@campusflow.local              

Password: Principal@2026