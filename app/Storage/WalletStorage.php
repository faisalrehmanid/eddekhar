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
        $data['WALLET_OWNER_NAME'] = $owner_name;
        $data['WALLET_CURRENCY'] = $currency;
        $data['WALLET_BALANCE'] = 0;
        $data['WALLET_CREATED_AT'] = $exp->setDate(date('Y-m-d H:i:s'));
        $data['WALLET_UPDATED_AT'] = $exp->setDate(date('Y-m-d H:i:s'));
        $wallet_id = $this->DB->insert(DBSchema::WALLETS, $data);

        return $wallet_id;
    }
}
