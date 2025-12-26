## Technology Stack

- **Framework**: Laravel 12
- **Database**: MySQL 8.0+
- **PHP**: 8.4+
- **Architecture**: Service Layer Pattern

## How to Setup

1. Download source code from https://github.com/faisalrehmanid/eddekhar
2. Create database in MSQYL named: eddekhar_wallet_service
3. Import database dump file given in source code: eddekhar_wallet_service.sql
4. Import Postman collection Wallet Service API.postman_collection.json using version 2.1
5. Run command on VS code: `php artisan serve`
6. Set Postman collection `{{base_url}}`: http://127.0.0.1:8000/api
