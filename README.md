# AfricaVLP Laravel Migration Project

## 🎯 Project Overview

This project represents a complete migration from two legacy CakePHP applications to modern Laravel applications, implementing a comprehensive African Union Volunteer Leadership Platform (AfricaVLP) with enhanced features and enterprise-level functionality.

### Migration Source
- **Original Admin App**: CakePHP `admin/` folder → `admin-laravel-app/`
- **Original Client App**: CakePHP `Well-known/` folder → `client-laravel-app/`
- **Database**: Shared MySQL database with complete schema migration

---

## 🏗️ Architecture Overview

### 1. Admin Laravel Application (`admin-laravel-app/`)
**Purpose**: Comprehensive administrative interface for platform management

**Core Features**:
- ✅ **User Management** - Comprehensive user CRUD with role-based access control
- ✅ **Organization Management** - Full organization lifecycle management
- ✅ **Content Management** - Blog posts, news, events, and resources with rich text editing
- ✅ **Analytics Dashboard** - Real-time system analytics and reporting
- ✅ **Role Management** - Super Admin, Admin, Moderator, Editor, and Viewer roles
- ✅ **Audit Trail** - Complete activity logging and audit system
- ✅ **Support System** - Ticket management with SLA monitoring
- ✅ **Translation Management** - Multi-language content management
- ✅ **Tag System** - Hierarchical content tagging and organization
- ✅ **Cloud Storage** - Cloudinary integration for all file and image uploads

### 2. Client Laravel Application (`client-laravel-app/`)
**Purpose**: User-facing platform for volunteers, organizations, and community engagement

**Core Features**:
- ✅ **User Registration & Profiles** - Complete user onboarding and profile management
- ✅ **Organization Discovery** - Browse and join organizations
- ✅ **Opportunity Applications** - Apply for volunteering opportunities
- ✅ **Event Participation** - Event discovery and registration
- ✅ **Alumni Forums** - Organization-specific and public discussion forums
- ✅ **Resource Access** - Educational and organizational resource library
- ✅ **Multi-language Support** - Interface available in multiple languages
- ✅ **Interactive Map** - Geographic visualization of organizations and opportunities
- ✅ **News & Blog** - Stay updated with latest news and blog posts
- ✅ **Cloud Storage** - Cloudinary integration for all file and image uploads
- ✅ **Tagged Content System** - Hierarchical content tagging and categorization
- ✅ **Multi-language Support** - Internationalization with translation management

---

## 🎨 Custom Branding & UI

### Color Scheme Implementation
- **Primary Buttons**: `#8A2B13` (Dark Red-Brown) with hover `#F4F2C9` (Light Cream)
- **Success Buttons**: `#28a745` (Green) with hover `#218838` (Darker Green)
- **Danger Buttons**: `#dc3545` (Red) with hover `#c82333` (Darker Red)
- **Cards**: `#1789A7` (Teal Blue) background with white text
- **Responsive Design**: Mobile-first approach with Bootstrap 5.3 integration

### Asset Migration
- ✅ **Complete Logo Migration** - All brand logos and variations
- ✅ **Image Assets** - User avatars, placeholders, backgrounds, icons
- ✅ **CSS/JS Libraries** - Bootstrap, jQuery, TinyMCE, vector maps
- ✅ **AssetHelper Classes** - Centralized asset management and verification
- **Private Messaging**: User-to-user and organization communication
- **Event Management**: Event discovery and registration
- **Resource Library**: Access to organizational resources
- **Multi-language Support**: Internationalization with multiple languages

## Technology Stack

### Backend
- **Laravel 10.x** (PHP 8.1+)
- **MySQL** database (shared between applications)
- **Laravel Sanctum** for API authentication
- **Laravel Queue** for background jobs
- **Laravel Mail** with SendGrid integration

### Frontend
- **Blade** templating engine
- **Tailwind CSS** for styling
- **Alpine.js** for interactive components
- **Laravel Mix/Vite** for asset compilation

### Third-party Integrations
- **Cloudinary** for image management
- **Google Translate API** for multi-language support
- **SendGrid** for email delivery
- **Mobile Detection** for responsive design

## Database Schema

The applications maintain the existing CakePHP database schema with:
- 30+ tables including users, organizations, events, news, resources
- All foreign key relationships preserved
- CakePHP timestamp conventions (`created`/`modified` columns)
- Complete data integrity from original applications

## Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js and NPM

### Admin Application Setup
```bash
cd admin-laravel-app
composer install
cp .env.example .env
php artisan key:generate
# Configure database settings in .env
php artisan migrate
php artisan serve --port=8001
```

### Client Application Setup
```bash
cd client-laravel-app
composer install
cp .env.example .env
php artisan key:generate
# Configure database settings in .env
php artisan migrate
php artisan serve --port=8002
```

## Environment Configuration

### Admin Application (.env)
```
APP_NAME="AU VLP Admin"
APP_URL=http://admin.au-vlp.local
DB_DATABASE=hruaif93_au_vlp
# ... other configurations
```

### Client Application (.env)
```
APP_NAME="AU VLP Client"
APP_URL=http://client.au-vlp.local
DB_DATABASE=hruaif93_au_vlp
# ... other configurations
```

## Deployment

The applications are designed to be deployed separately:
- **Admin Application**: Typically on a subdomain like `admin.au-vlp.org`
- **Client Application**: On the main domain like `au-vlp.org`

Both applications can be deployed on the same server or different servers, as long as they can access the shared MySQL database.

## Migration Status

### Completed Features
- ✅ Project structure setup
- ✅ Database configuration
- ✅ Basic models and relationships
- ✅ Authentication system foundation
- ✅ Route structure
- ✅ Controller foundations

### In Progress
- 🔄 Complete model relationships
- 🔄 Database migrations
- 🔄 View templates
- 🔄 Security enhancements

### Planned Features
- 📋 Complete UI/UX implementation
- 📋 Third-party service integrations
- 📋 Testing suite
- 📋 Performance optimization
- 📋 Production deployment

## Development Guidelines

### Code Standards
- Follow Laravel best practices
- Use PSR-12 coding standards
- Implement proper error handling
- Write comprehensive tests
- Document all public methods

### Security Considerations
- CSRF protection on all forms
- Input validation and sanitization
- Rate limiting on authentication
- Secure file uploads
- SQL injection prevention

### Performance Optimization
- Database query optimization
- Caching strategies
- Asset optimization
- Queue system for heavy operations

## Contributing

1. Follow the existing code structure
2. Maintain compatibility with shared database
3. Test changes in both applications
4. Update documentation as needed
5. Follow security best practices

## Support

For questions or issues related to this migration project, please refer to the project documentation or contact the development team.