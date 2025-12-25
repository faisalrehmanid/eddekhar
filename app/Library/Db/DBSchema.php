<?php

namespace App\Library\Db;

/**
 * List of all tables, stored procedures and database objects
 */
class DBSchema
{
    // List of all tables
    public const IDEMPOTENCY_KEYS = '`wallet_service`.`idempotency_keys`';

    public const TRANSACTIONS = '`wallet_service`.`transactions`';

    public const WALLETS = '`wallet_service`.`wallets`';
}
