<?php

namespace App\Storage;

use App\Library\Db\DB;
use App\Library\Db\DBSchema;

class IdempotencyKeyStorage
{
    private DB $DB;

    // Idempotency keys expire after 24 hours
    private const EXPIRY_HOURS = 24;

    public function __construct(
        DB $DB
    ) {
        $this->DB = $DB;
    }

    public function checkIdempotencyKey(
        $idempotency_key,
        $idempotency_endpoint,
        array $request
    ) {
        // Clean up expired keys first
        // $this->cleanupExpiredIdempotencyKeys();

        // Calculate request hash
        $request_hash = $this->requestHash($request);

        $exp = $this->DB->getExpression();

        $query = ' SELECT
                        IDEMPOTENCY_KEY_ID,
                        IDEMPOTENCY_KEY,
                        IDEMPOTENCY_ENDPOINT,
                        REQUEST_HASH,
                        RESPONSE_CODE,
                        RESPONSE_BODY,
                        '.$exp->getDate('IDEMPOTENCY_KEY_CREATED_AT').' IDEMPOTENCY_KEY_CREATED_AT
                    FROM '.DBSchema::IDEMPOTENCY_KEYS.'
                        WHERE
                             IDEMPOTENCY_KEY = :IDEMPOTENCY_KEY
                        AND IDEMPOTENCY_ENDPOINT = :IDEMPOTENCY_ENDPOINT
                        AND '.$exp->getDate('IDEMPOTENCY_KEY_EXPIRED_AT').' > :CURRENT_DATE
                    LIMIT 1';
        $values = [
            ':IDEMPOTENCY_KEY' => $idempotency_key,
            ':IDEMPOTENCY_ENDPOINT' => $idempotency_endpoint,
            ':CURRENT_DATE' => date('Y-m-d H:i:s'),
        ];
        $row = $this->DB->fetchRow($query, $values);

        if (! empty($row) && $request_hash !== $row['request_hash']) {
            return [];
        }

        return $row;
    }

    public function insertIdempotencyKey(
        $idempotency_key,
        $idempotency_endpoint,
        array $request_body,
        $response_code,
        array $response_body,
    ) {
        $exp = $this->DB->getExpression();
        $idempotency_key_expired_at = (new \DateTime)->modify('+'.self::EXPIRY_HOURS.' hours')->format('Y-m-d H:i:s');

        $data = [];
        $data['IDEMPOTENCY_KEY'] = $idempotency_key;
        $data['IDEMPOTENCY_ENDPOINT'] = $idempotency_endpoint;
        $data['REQUEST_HASH'] = $this->requestHash($request_body);
        $data['REQUEST_BODY'] = json_encode($request_body);
        $data['RESPONSE_CODE'] = $response_code;
        $data['RESPONSE_BODY'] = json_encode($response_body);
        $data['IDEMPOTENCY_KEY_CREATED_AT'] = $exp->setDate(date('Y-m-d H:i:s'));
        $data['IDEMPOTENCY_KEY_EXPIRED_AT'] = $idempotency_key_expired_at;
        $idempotency_key_id = $this->DB->insert(DBSchema::IDEMPOTENCY_KEYS, $data);

        return $idempotency_key_id;
    }

    private function requestHash(array $data): string
    {
        return hash('sha256', json_encode($data));
    }
}
