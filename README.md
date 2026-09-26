# Invoice Manager SaaS

A multi-tenant invoice management system built with Laravel for freelancers and businesses.

## Features

- **Multi-Tenant Architecture**: Each company has isolated data with secure tenant-based access
- **User Management**: Role-based access control (Owner, Admin, Member)
- **Client Management**: Store and manage client information
- **Product Catalog**: Maintain products and services with pricing
- **Invoice Generation**: Create professional invoices with line items
- **PDF Export**: Download invoices as PDF documents
- **Public Invoice Sharing**: Share invoices with clients via unique links
- **Dashboard Analytics**: Revenue tracking and invoice status overview
- **Company Settings**: Customize branding, invoice templates, and preferences

## Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js & NPM (optional, for asset compilation)

## Installation

1. **Clone or download the project**

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   
   Edit `.env` file with your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=invoice_manager
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Create storage link**
   ```bash
   php artisan storage:link
   ```

7. **Seed demo data (optional)**
   ```bash
   php artisan db:seed
   ```
   
   Demo credentials:
   - Email: `demo@example.com`
   - Password: `password`

8. **Start the development server**
   ```bash
   php artisan serve
   ```

   Visit `http://localhost:8000`

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/           # Authentication controllers
│   │   ├── ClientController.php
│   │   ├── CompanyController.php
│   │   ├── DashboardController.php
│   │   ├── InvoiceController.php
│   │   ├── ProductController.php
│   │   ├── SettingsController.php
│   │   └── UserController.php
│   └── Middleware/
│       ├── CheckRole.php   # Role-based access
│       └── EnsureTenant.php # Multi-tenant isolation
├── Models/
│   ├── Client.php
│   ├── Company.php
│   ├── CompanySetting.php
│   ├── Invoice.php
│   ├── InvoiceItem.php
│   ├── Product.php
│   └── User.php
└── Providers/

database/
├── migrations/             # Database schema
└── seeders/               # Demo data

resources/views/
├── auth/                  # Login & registration
├── clients/               # Client CRUD views
├── company/               # Company setup
├── invoices/              # Invoice management
├── layouts/               # Base templates
├── products/              # Product catalog
├── settings/              # Company settings
└── users/                 # Team management
```

## User Roles

| Role   | Permissions                                           |
|--------|-------------------------------------------------------|
| Owner  | Full access, cannot be deleted                        |
| Admin  | Manage team, settings, and all business operations    |
| Member | Create/manage invoices, clients, products             |

## Multi-Tenancy

The application uses a simple tenant-per-user model where:
- Each user belongs to a company
- Global scopes automatically filter data by company
- Middleware ensures users have an active company

## Invoice Workflow

1. **Draft** → Invoice created, can be edited
2. **Sent** → Marked as sent to client
3. **Paid** → Payment received
4. **Overdue** → Past due date (auto-updated via scheduler)
5. **Cancelled** → Invoice cancelled

## Customization

### Invoice Settings
- Custom invoice prefix and numbering
- Default payment terms
- Invoice footer text

### Company Branding
- Company logo
- Theme color
- Custom currency

### Payment Information
- Bank details
- PayPal integration
- Custom payment instructions

## Scheduled Tasks

Add to your server's crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

This will:
- Automatically mark overdue invoices

## License

MIT License

## Support

For issues and feature requests, please create an issue in the repository.
