<?php

namespace App\Storage;

use App\Library\Db\DB;
use App\Library\Db\DBSchema;

class WalletStorage
{
    private $DB;

    public function __construct(DB $DB)
    {
        $this->DB = $DB;
    }

    public function insertWallet($owner_name, $currency)
    {
        $exp = $this->DB->getExpression();

        $data = [];
        $data['OWNER_NAME'] = $owner_name;
        $data['CURRENCY'] = $currency;
        $data['BALANCE'] = 0;
        $wallet_id = $this->DB->insert(DBSchema::WALLETS, $data);

        return $wallet_id;
    }
}
