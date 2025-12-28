# Eddekhar Wallet Service

A Laravel-based wallet service API that provides wallet management, deposits, withdrawals, and transfers between wallets with idempotency support.

## Technology Stack

- **Framework**: Laravel 12
- **Database**: MySQL 8.0+
- **PHP**: 8.4+
- **Architecture**: Service Layer Pattern

## Prerequisites

Before setting up the application, ensure you have the following installed:

- PHP 8.4 or higher
- Composer (latest version)
- MySQL 8.0 or higher
- Git (for cloning the repository)

## Installation & Setup

Follow these steps to set up the application on your local environment:

### 1. Clone the Repository

```bash
git clone https://github.com/faisalrehmanid/eddekhar.git
cd eddekhar
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the example environment file and configure your environment variables:

```bash
cp .env.example .env
```

Edit the `.env` file and update the database configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eddekhar_wallet_service
DB_USERNAME=root
DB_PASSWORD=your_database_password
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Setup Database

#### Using Database Dump

1. Create a new database in MySQL:
   ```sql
   CREATE DATABASE eddekhar_wallet_service;
   ```

2. Import the provided SQL dump file:
   ```bash
   mysql -u root -p eddekhar_wallet_service < eddekhar_wallet_service.sql
   ```
   
### 6. Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 7. Start the Development Server

```bash
php artisan serve
```

The application will be available at: `http://127.0.0.1:8000`

## API Testing with Postman

### Import Postman Collection

1. Open Postman
2. Click on **Import** button
3. Select the file: `Wallet Service API.postman_collection.json` from the project root
4. The collection will be imported with all available API endpoints

### Configure Postman Environment

1. In Postman, create a new environment or use the collection variables
2. Set the base URL variable:
   ```
   {{base_url}} = http://127.0.0.1:8000/api
   ```

## API Endpoints

The API provides the following main endpoints:

- **Wallets**
  - Create Wallet
  - Get Wallet Details
  - List Wallets
  - Get Wallet Balance
  - List Wallet Transactions

- **Transactions**
  - Deposit to Wallet (requires Idempotency-Key header)
  - Withdraw from Wallet (requires Idempotency-Key header)
  - Transfer Between Wallets (requires Idempotency-Key header)

## Troubleshooting

### Database Connection Issues

If you encounter database connection errors:
1. Verify MySQL is running
2. Check database credentials in `.env` file
3. Ensure the database exists: `CREATE DATABASE eddekhar_wallet_service;`
4. Run `php artisan config:clear` after changing `.env`

### Permission Issues

If you encounter permission errors:
```bash
chmod -R 775 storage bootstrap/cache
```

### Port Already in Use

If port 8000 is already in use, specify a different port:
```bash
php artisan serve --port=8001
```
