## About

A complete church website and content platform built from the ground up in
plain PHP and MySQL, no frameworks. What started as a marketing site grew
into a small multi-feature platform: a live-editable storefront with cart
and checkout, a real-time chat widget, an events system with a calendar and
year planner, a bulk-upload photo gallery with a lightbox, and a live
streaming page with a Twitch-style comment wall that can also overlay
directly onto an OBS broadcast.

### Technical highlights

- **Vanilla PHP 8 + PDO (MySQL)**, no framework, built on the fundamentals
- **Security-first**: CSRF tokens on every mutating request, bcrypt password
  hashing, per-IP login rate limiting, prepared statements throughout,
  consistent output escaping, hardened sessions
- **Role-based admin system**: multiple staff accounts, plus a hidden
  developer view that unlocks automatically for a matching account
- **Self-hosted analytics**: page views and real load-time tracking with
  zero third-party services
- **Real-time features without a framework**: polling-based live chat, a
  live Twitch-style comment wall, and an OBS browser-source overlay
- **File upload pipeline** with MIME validation, randomized filenames, and
  path-traversal protection
- **Hand-written, fully responsive CSS** (no Bootstrap/Tailwind), custom
  design system with a glassmorphism header
- **E-commerce flow**: server-side cart in PHP sessions, checkout with two
  payment paths, full admin order log

**Stack:** PHP 8.3 · MySQL/MariaDB (PDO) · vanilla JavaScript · HTML5/CSS3
