<?php

namespace App\Library\Db;

/**
 * List of all tables, stored procedures and database objects
 */
class DBSchema
{
    // List of all tables
    public const IDEMPOTENCY_KEYS = '`EDDEKHAR_WALLET_SERVICE`.`IDEMPOTENCY_KEYS`';

    public const TRANSACTIONS = '`EDDEKHAR_WALLET_SERVICE`.`TRANSACTIONS`';

    public const WALLETS = '`EDDEKHAR_WALLET_SERVICE`.`WALLETS`';
}
