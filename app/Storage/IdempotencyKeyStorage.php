<?php

namespace App\Storage;

use App\Exceptions\ApiException;
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
        $this->cleanupExpiredIdempotencyKeys();

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
                        '.$exp->getDate('IDEMPOTENCY_KEY_CREATED_AT').' IDEMPOTENCY_KEY_CREATED_AT,
                        '.$exp->getDate('IDEMPOTENCY_KEY_EXPIRED_AT').' IDEMPOTENCY_KEY_EXPIRED_AT
                    FROM '.DBSchema::IDEMPOTENCY_KEYS.'
                        WHERE
                             LOWER(IDEMPOTENCY_KEY) = LOWER(:IDEMPOTENCY_KEY)
                    LIMIT 1';
        $values = [
            ':IDEMPOTENCY_KEY' => $idempotency_key,
        ];
        $row = $this->DB->fetchRow($query, $values);

        if (! empty($row)) {
            // If key exists but expired
            if (strtotime($row['idempotency_key_expired_at']) < time()) {
                throw new ApiException(400, 'Idempotency key has expired');
            }

            // If key exists but with different endpoint
            if ($idempotency_endpoint !== $row['idempotency_endpoint']) {
                throw new ApiException(400, 'Idempotency key already used with different endpoint operation');
            }

            // If key exists but with different request body
            if ($request_hash !== $row['request_hash']) {
                throw new ApiException(400, 'Idempotency key already used with different request body');
            }

            // Send cached response
            $response = json_decode($row['response_body'], true);

            return $response;
        }
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

    private function cleanupExpiredIdempotencyKeys()
    {
        $exp = $this->DB->getExpression();

        $query = ' DELETE FROM '.DBSchema::IDEMPOTENCY_KEYS.'
                    WHERE '.$exp->getDate('IDEMPOTENCY_KEY_EXPIRED_AT').' < :CURRENT_DATE ';
        // Remove all keys that are 2 more than days older
        $values = [
            ':CURRENT_DATE' => date('Y-m-d H:i:s', strtotime('-2 day')),
        ];
        $this->DB->delete($query, $values);
    }
}
