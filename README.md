# PUP Biñan Website (Public Site + Admin CMS + Analytics)

A PHP + MySQL website for **Polytechnic University of the Philippines – Biñan Campus**, with a public-facing site, an admin panel (CMS), and basic analytics tracking.

## Project Structure

- `public_html/`  
  Main web app that you deploy to your hosting web root. :contentReference[oaicite:0]{index=0}
- `DO_NOT_UPLOAD_HERE/`  
  Non-deployment folder in the repo root. :contentReference[oaicite:1]{index=1}

Key folders inside `public_html/`:
- `pages/` public pages like `about.php`, `programs.php`, `news.php`, `services.php`, `student_handbook.php`, `citizen_charter.php`, etc. :contentReference[oaicite:2]{index=2}
- `admin/` admin panel (login, dashboard, content management, password reset flow). :contentReference[oaicite:3]{index=3}
- `DATAANALYTICS/` analytics utilities + database schema. :contentReference[oaicite:4]{index=4}
- `database/` SQL tables for events, page views, CSV uploads, campus offices, and more. :contentReference[oaicite:5]{index=5}
- `vendor/` Composer dependencies (includes PHPMailer). :contentReference[oaicite:6]{index=6}

## Features

### Public Website
- Multiple campus pages (About, Admissions Guide, Programs, News, Services, Campus Offices, Forms, FAQ, History, Citizen Charter, Student Handbook, etc.). :contentReference[oaicite:7]{index=7}
- SEO essentials included: `robots.txt` and `sitemap.xml`. :contentReference[oaicite:8]{index=8}

### Events Calendar
- Events are stored in an `events` table and can be shown on homepage and/or announcements. :contentReference[oaicite:9]{index=9}
- Calendar API endpoint logic exists in `api/calendar.php`. :contentReference[oaicite:10]{index=10}

### Admin CMS (Administration Panel)
- Admin login at `admin/login.php`. :contentReference[oaicite:11]{index=11}
- Site settings + content tables exist (example: announcements, news, media library, social links). :contentReference[oaicite:12]{index=12}
- Password reset guide included (`admin/PASSWORD_RESET_SETUP.md`). :contentReference[oaicite:13]{index=13}

### Analytics
- Page view tracking table `page_views`. :contentReference[oaicite:14]{index=14}
- Visitor logging and related tables exist in the main schema. :contentReference[oaicite:15]{index=15}
- CSV uploads tracking table `csv_uploads` for analytics workflows. :contentReference[oaicite:16]{index=16}

## Tech Stack
- PHP (server-side)
- MySQL / MariaDB (database)
- HTML, CSS, JavaScript (frontend)
- Composer dependencies (PHPMailer included) :contentReference[oaicite:17]{index=17}

## Getting Started (Local Setup with XAMPP)

### 1) Put the project in your web root
Example:
- Copy `public_html/` into `C:\xampp\htdocs\pup-bi-an-website\`
- Then your entry point is:
  - `http://localhost/pup-bi-an-website/`

### 2) Create and import the database
There are SQL files provided:
- Main schema (creates tables like admins, site_settings, announcements, news, media_library, visitors, social_links, etc.):  
  `public_html/DATAANALYTICS/pupbcadmin_schema.sql` :contentReference[oaicite:18]{index=18}
- Additional tables (events, page views, CSV uploads, campus offices, etc.) are in:  
  `public_html/database/` :contentReference[oaicite:19]{index=19}

Import using phpMyAdmin:
1. Create a database (example: `pupbcadmin`)
2. Import `pupbcadmin_schema.sql`
3. Import the needed files from `public_html/database/` (events/page views/csv uploads/etc.)

### 3) Configure DB connection
Update your DB credentials in:
- `public_html/DATAANALYTICS/db.php` (and any other DB config files used by admin/pages)

### 4) Run
Start Apache + MySQL in XAMPP, then open:
- `http://localhost/pup-bi-an-website/`

## Admin Panel

Open:
- `/admin/login.php` :contentReference[oaicite:20]{index=20}

Notes:
- The DB schema seeds an `admin` account row (hashed password). :contentReference[oaicite:21]{index=21}  
  If you do not know the password, set a new one by updating `admins.password_hash` using `password_hash()` in PHP.

## Email Password Reset (Important)

A setup guide exists here:
- `public_html/admin/PASSWORD_RESET_SETUP.md` :contentReference[oaicite:22]{index=22}

Security warning:
- **Do not commit SMTP passwords or secrets.** Your repo currently contains SMTP config placeholders and may include real credentials. Rotate any exposed passwords immediately and move secrets to environment variables (or a config file excluded by `.gitignore`). :contentReference[oaicite:23]{index=23}

## Deployment Notes (Shared Hosting like Hostinger)

Typical approach:
1. Upload the **contents of `public_html/`** into your hosting `public_html/` directory.
2. Create/import the MySQL database (same SQL files as local).
3. Update DB credentials in your PHP config files.
4. Ensure file permissions allow uploads where needed (images, media, CSV uploads).

## Contributing
This is a school/campus project. If you want contributions:
- Fork the repo
- Create a feature branch
- Open a pull request

## License
No license is specified yet. If you plan to make this public/open-source, add a `LICENSE` file (MIT is a common choice).
