# Final Project Web Information System - Connectify

A modern contact management system with responsive design and user-friendly interface.

## Project Structure
```
WIS_FINAL_PROJECT/
├── assets/
│   ├── css/
│   │   ├── styles.css        # Main styles
│   │   ├── dashboard.css     # Dashboard specific styles
│   │   └── notifications.css # Notification styles
│   ├── js/
│   │   └── dashboard.js      # Dashboard functionality
│   └── img/                  # Image assets
├── includes/
│   ├── config.php           # Database configuration
│   ├── user_operations.php  # User-related operations
│   └── contact_operations.php # Contact-related operations
├── uploads/                 # User uploads (avatars)
├── index.php               # Entry point
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # Main dashboard
└── README.md             # Project documentation
```

## Features

### Core Functionality
- User authentication (login/register)
- Contact management (add, edit, delete contacts)
- Profile management with avatar support
- Dashboard overview

### UI/UX Improvements
- Responsive navigation system
  - Desktop: Full navigation bar
  - Mobile: Hamburger menu with collapsible navigation
  - Touch-friendly interface
- Modern and clean design
- Intuitive user interface
- Real-time notifications
- Font Awesome icons integration

## Technical Details

### Mobile Responsiveness
The application is fully responsive and works seamlessly on both desktop and mobile devices:
- Breakpoint at 768px for mobile devices
- Collapsible navigation menu with hamburger button
- Touch-optimized interface
- Fluid layouts and responsive grids

### Technologies Used
- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- Font Awesome for icons

## Setup Instructions
1. Place the project in your web server directory
2. Configure database settings in `config.php`
3. Import the database schema
4. Access the application through your web browser

## Browser Support
- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers
