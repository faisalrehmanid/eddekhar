<?php

namespace App\Storage;

use App\Library\Db\DB;
use App\Library\Db\DBSchema;
use App\Library\Pagination\PaginationUtil;

class TransactionStorage
{
    private DB $DB;

    private PaginationUtil $PaginationUtil;

    public function __construct(DB $DB, PaginationUtil $PaginationUtil)
    {
        $this->DB = $DB;
        $this->PaginationUtil = $PaginationUtil;
    }

    public function insertTransaction(
        $wallet_id,
        $transaction_type,
        $transaction_amount,
        $transaction_balance_after,
        $related_wallet_id,
        $reference_id,
        $transaction_description
    ) {
        $exp = $this->DB->getExpression();

        if (! in_array($transaction_type, ['deposit', 'withdraw', 'transfer_debit', 'transfer_credit'])) {
            throw new \Exception('Invalid transaction_type');
        }

        $data = [];
        $data['wallet_id'] = $wallet_id;
        $data['transaction_type'] = $transaction_type;
        $data['transaction_amount'] = $transaction_amount;
        $data['transaction_balance_after'] = $transaction_balance_after;
        $data['related_wallet_id'] = $related_wallet_id;
        $data['reference_id'] = $reference_id;
        $data['transaction_description'] = $transaction_description;
        $data['transaction_created_at'] = $exp->setDate(date('Y-m-d H:i:s'));
        $transaction_id = $this->DB->insert(DBSchema::TRANSACTIONS, $data);

        return $transaction_id;
    }
}
