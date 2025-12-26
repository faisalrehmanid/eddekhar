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

    public function listWalletTransactions($wallet_id, array $params)
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

        $transaction_type = trim($params['transaction_type'] ?? '');
        $to_date = trim($params['to_date'] ?? '');
        $from_date = trim($params['from_date'] ?? '');

        $exp = $this->DB->getExpression();

        $where = '';
        $values = [];
        $core_query = ' FROM '.DBSchema::TRANSACTIONS.' A';

        // Get total records
        $query = ' SELECT
                        COUNT(A.TRANSACTION_ID) TOTAL_RECORDS
                    '.$core_query;
        $total_records = $this->DB->fetchKey('total_records', $query, $values);

        // Filters
        $filters = [
            $filter_logic => [
                [
                    'field' => 'A.TRANSACTION_TYPE',
                    'operator' => (empty($exact_match)) ? '%LIKE%' : '=',
                    'value' => $transaction_type,
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
                        COUNT(A.TRANSACTION_ID) TOTAL_RECORDS_FOUND
                        '.$core_query.' '.$where;
        $total_records_found = $this->DB->fetchKey('total_records_found', $query, $values);

        // Query
        $query = ' SELECT
                        A.TRANSACTION_ID,
                        A.TRANSACTION_TYPE,
                        A.TRANSACTION_AMOUNT,
                        A.RELATED_WALLET_ID,
                        '.$exp->getDate('TRANSACTION_CREATED_AT').' TRANSACTION_CREATED_AT
                        '.$core_query.' '.$where;

        $order_by_clause = match ($order_by) {
            'created_at_asc' => ' ORDER BY A.TRANSACTION_CREATED_AT ASC ',
            'created_at_desc' => ' ORDER BY A.TRANSACTION_CREATED_AT DESC ',
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
