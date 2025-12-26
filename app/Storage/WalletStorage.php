<?php

namespace App\Storage;

use App\Library\Db\DB;
use App\Library\Db\DBSchema;
use App\Library\Pagination\PaginationUtil;

class WalletStorage
{
    private DB $DB;

    private PaginationUtil $PaginationUtil;

    public function __construct(
        DB $DB,
        PaginationUtil $PaginationUtil
    ) {
        $this->DB = $DB;
        $this->PaginationUtil = $PaginationUtil;
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

    public function listWallets(array $params)
    {
        $options = [
            'order_by_options' => [
                'created_at_desc' => 'Created At Desc',
                'created_at_asc' => 'Created At Asc',
            ],
            'records_per_page_options' => [10, 20, 50, 100],
        ];
        $pagination = $this->PaginationUtil->validatePaginationParams($params, $options);
        extract($pagination);

        $wallet_owner_name = trim($params['wallet_owner_name'] ?? '');
        $wallet_currency = trim($params['wallet_currency'] ?? '');

        $exp = $this->DB->getExpression();

        $where = '';
        $values = [];
        $core_query = ' FROM '.DBSchema::WALLETS.' A';

        // Get total records
        $query = ' SELECT
                        COUNT(A.WALLET_ID) TOTAL_RECORDS
                    '.$core_query;
        $total_records = $this->DB->fetchKey('total_records', $query, $values);

        // Filters
        $filters = [
            $filter_logic => [
                [
                    'field' => 'A.WALLET_OWNER_NAME',
                    'operator' => (empty($exact_match)) ? '%LIKE%' : '=',
                    'value' => $wallet_owner_name,
                    'transform' => 'lower',
                ],
                [
                    'field' => 'A.WALLET_CURRENCY',
                    'operator' => (empty($exact_match)) ? '%LIKE%' : '=',
                    'value' => $wallet_currency,
                    'transform' => 'lower',
                ],
            ],
        ];
        $where_clause = $this->DB->buildWhereClause($filters);
        // pr($where_clause);
        if (! empty($where_clause[0])) {
            $where .= 'WHERE '.$where_clause[0];
            $values = array_merge($values, $where_clause[1]);
        }

        // Get total records found
        $query = ' SELECT
                        COUNT(A.WALLET_ID) TOTAL_RECORDS_FOUND
                        '.$core_query.' '.$where;
        $total_records_found = $this->DB->fetchKey('total_records_found', $query, $values);

        // Query
        $query = ' SELECT
                        A.WALLET_ID,
                        A.WALLET_OWNER_NAME,
                        A.WALLET_CURRENCY,
                        A.WALLET_BALANCE,
                        '.$exp->getDate('WALLET_CREATED_AT').' WALLET_CREATED_AT
                        '.$core_query.' '.$where;

        $order_by_clause = match ($order_by) {
            'created_at_asc' => ' ORDER BY A.WALLET_CREATED_AT ASC ',
            'created_at_desc' => ' ORDER BY A.WALLET_CREATED_AT DESC ',
        };
        $query .= $order_by_clause;

        // echo $query;
        // pr($values);
        $chunk = $this->DB->fetchChunk($query, $values, $page_number, $records_per_page);

        // pagination response
        return $this->PaginationUtil->buildPaginationResponse(
            $page_number,
            $records_per_page,
            $records_per_page_options,
            $order_by,
            $order_by_options,
            $total_records,
            $total_records_found,
            $chunk
        );
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

    public function updateBalance($wallet_id, $amount, $operation = 'plus')
    {
        if (! in_array($operation, ['plus', 'minus'])) {
            throw new \Exception('Invalid operation');
        }

        // Use FOR UPDATE to lock the row
        $query = ' SELECT
                        WALLET_BALANCE
                    FROM '.DBSchema::WALLETS.'
                    WHERE WALLET_ID = :WALLET_ID FOR UPDATE';
        $values = [':WALLET_ID' => $wallet_id];
        $wallet = $this->DB->fetchRow($query, $values);

        if (empty($wallet)) {
            throw new \Exception('Wallet not found');
        }

        if ($operation == 'plus') {
            $new_balance = (int) $wallet['wallet_balance'] + (int) $amount;
        }

        if ($operation == 'minus') {
            $new_balance = (int) $wallet['wallet_balance'] - (int) $amount;
        }

        if ($new_balance < 0) {
            throw new \Exception('Insufficient funds');
        }

        $exp = $this->DB->getExpression();
        $date = $exp->setDate(date('Y-m-d H:i:s'));

        $query = ' UPDATE '.DBSchema::WALLETS.' SET
                        WALLET_BALANCE = :WALLET_BALANCE,
                        WALLET_UPDATED_AT = '.$date->getFragment().'
                    WHERE WALLET_ID = :WALLET_ID ';
        $values = [
            ':WALLET_ID' => $wallet_id,
            ':WALLET_BALANCE' => $new_balance,
        ];
        $values = array_merge($values, $date->getValues());
        $this->DB->update($query, $values);

        return true;
    }
}
