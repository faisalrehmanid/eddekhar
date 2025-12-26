<?php

namespace App\Storage;

use App\Library\Db\DB;
use App\Library\Db\DBSchema;

class IdempotencyKeyStorage
{
    private DB $DB;

    private const EXPIRY_HOURS = 24; // Idempotency keys expire after 24 hours

    public function __construct(
        DB $DB
    ) {
        $this->DB = $DB;
    }

    public function hashRequest(array $data): string
    {
        return hash('sha256', json_encode($data));
    }

    public function insertIdempotencyKey(
        $idempotency_key,
        $endpoint,
        array $request,
        $response_code,
        array $response_body,
    ) {
        $exp = $this->DB->getExpression();
        $idempotency_key_expires_at = (new \DateTime)->modify('+'.self::EXPIRY_HOURS.' hours')->format('Y-m-d H:i:s');

        $data = [];
        $data['IDEMPOTENCY_KEY'] = $idempotency_key;
        $data['ENDPOINT'] = $endpoint;
        $data['REQUEST_HASH'] = $this->hashRequest($request);
        $data['RESPONSE_CODE'] = $response_code;
        $data['RESPONSE_BODY'] = json_encode($response_body);
        $data['IDEMPOTENCY_KEY_CREATED_AT'] = $exp->setDate(date('Y-m-d H:i:s'));
        $data['IDEMPOTENCY_KEY_EXPIRES_AT'] = $idempotency_key_expires_at;
        $idempotency_key_id = $this->DB->insert(DBSchema::IDEMPOTENCY_KEYS, $data);

        return $idempotency_key_id;
    }
}
