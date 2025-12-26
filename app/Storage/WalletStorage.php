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

    public function getWalletById($wallet_id)
    {
        if (empty($wallet_id)) {
            return [];
        }

        $exp = $this->DB->getExpression();

        $query = ' SELECT
                        WALLET_ID,
                        WALLET_OWNER_NAME,
                        WALLET_CURRENCY,
                        WALLET_BALANCE,
                        '.$exp->getDate('WALLET_CREATED_AT').' WALLET_CREATED_AT
                    FROM '.DBSchema::WALLETS.' A
                    WHERE WALLET_ID = :WALLET_ID LIMIT 1';
        $values = [':WALLET_ID' => $wallet_id];
        $row = $this->DB->fetchRow($query, $values);

        return $row;
    }

    public function insertWallet($wallet_owner_name, $wallet_currency)
    {
        $exp = $this->DB->getExpression();

        $data = [];
        $data['WALLET_OWNER_NAME'] = $wallet_owner_name;
        $data['WALLET_CURRENCY'] = $wallet_currency;
        $data['WALLET_BALANCE'] = 0;
        $data['WALLET_CREATED_AT'] = $exp->setDate(date('Y-m-d H:i:s'));
        $data['WALLET_UPDATED_AT'] = $exp->setDate(date('Y-m-d H:i:s'));
        $wallet_id = $this->DB->insert(DBSchema::WALLETS, $data);

        return $wallet_id;
    }
}
