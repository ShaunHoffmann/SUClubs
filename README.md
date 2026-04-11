# SU Club Organizations

A web-based database application for managing Salisbury University's student clubs, members, events, and finances.

## What It Does

Salisbury University clubs currently run on scattered spreadsheets, paper forms, and social media — leading to disorganized events, financial discrepancies, and outdated contact info. This app replaces all of that with a single source of truth.

The app has a signup/login system with two access tiers:

- **Public view** (any logged-in student): browse clubs, members, officers, and events
- **Admin view** (club E-board members): full access to every relation for their club — rosters, fees, payments, communication channels, and reports

SGA admins sit above both and can verify registrations, update org records, and pull reports across all clubs.

## Tech Stack

- **Backend:** PHP, MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Server:** Apache (LAMP stack)

## Database Schema

The database is organized around the following relations:

| Table | Purpose |
|---|---|
| `Club` | Club name, description, contact info, type, foundation date |
| `Members` | Student records keyed by `suID` |
| `Membership` | Many-to-many link between members and clubs, with `dateJoined` |
| `Officers` | Weak entity tracking which members hold officer positions in which clubs |
| `Events` | Club events with date, time, type, and attendance |
| `Location` | Building, room number, and address for events |
| `Communication` | Primary app and join link for each club |
| `Fees` | Membership and event fees with due dates |
| `Member_Payments` | Payment records linking members to fees |

Foreign keys enforce referential integrity across all relations.

## Project Structure

```
/
├── sql/          # Schema, seed data, and migration scripts
├── public/       # Entry point and publicly served files
├── src/          # PHP application code
├── assets/       # CSS, JS, images
└── docs/         # Proposal, ER diagram, mockups
```

## Getting Started

### Prerequisites

- PHP 8.x
- MySQL 8.x
- Apache (or any LAMP/WAMP/XAMPP setup)

### Setup

1. Clone the repo
   ```bash
   git clone <repo-url>
   cd <repo-name>
   ```

2. Create the database and load the schema
   ```bash
   mysql -u root -p < sql/schema.sql
   mysql -u root -p < sql/seed.sql
   ```

3. Configure database credentials in `src/config.php`

4. Point your local web server's document root to the `public/` directory

5. Open `http://localhost` in your browser

## Features

- **Signup / Login:** Account creation and authentication for all users
- **Public view:** Browse clubs, members, officers, and events. View "My Clubs," check personal fee status, and RSVP
- **E-board admin view:** Club officers get full access to their club's relations — manage rosters, post events, track dues, and view payment history
- **SGA admin panel:** Verify clubs, manage fees across orgs, and pull attendance reports

## Team

- Shaun Hoffmann
- Rebecca Broomfield
- Dean Bullock
- Gilberto Rodriguez Hernandez
- Daniel Lugasi

**Course:** COSC 386-001 — Database Management Systems
**Instructor:** Dr. Jing

## Development Workflow

- Weekly team meetings every Tuesday at 1:00 PM
- Rotating task assignment to balance workload
- Frontend (Shaun, Rebecca) and backend (Dean, Gilberto, Daniel) work in parallel
- Shared documentation and Git for coordination

## Status

In active development — Spring 2026 semester project.
