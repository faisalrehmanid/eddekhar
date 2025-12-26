<?php

namespace App\Library\Pagination;

class PaginationUtil
{
    /**
     * Validate pagination parameters
     *
     * @param  array  $params  e.g. [
     *                         'page_number' => 1,
     *                         'records_per_page' => 25,
     *                         'order_by' => 'created_at_asc'
     *                         ]
     * @param  array  $options  e.g. [
     *                          'records_per_page_options' => ['25', '50', '75', '100'],
     *                          'order_by_options' => [
     *                          'created_at_desc' => 'Created At Desc',
     *                          'created_at_asc' => 'Created At Asc'
     *                          ]]
     * @return array e.g. [
     *               'page_number' => 1,
     *               'records_per_page' => 25,
     *               'records_per_page_options' => ['25', '50', '75', '100'],
     *               'order_by' => 'created_at_asc',
     *               'order_by_options' => [
     *               'created_at_desc' => 'Created At Desc',
     *               'created_at_asc' => 'Created At Asc'
     *               ]]
     */
    public function validatePaginationParams(array $params, array $options = []): array
    {
        $page_number = $this->validatePageNumber($params['page_number'] ?? 1);

        $records = $this->validateRecordsPerPage($params['records_per_page'] ?? null, $options['records_per_page_options'] ?? []);

        $pagination = [
            'page_number' => $page_number,
            'records_per_page' => $records['records_per_page'],
            'records_per_page_options' => $records['records_per_page_options'],
            'order_by' => '',
            'order_by_options' => [],
            'filter_logic' => $this->validateFilterLogic($params['filter_logic'] ?? null),
            'exact_match' => $this->validateExactMatch($params['exact_match'] ?? null),
        ];

        if (! empty($options['order_by_options'])) {
            $order_by = $this->validateOrderBy($params['order_by'] ?? null, $options['order_by_options']);

            $pagination['order_by'] = $order_by;
            $pagination['order_by_options'] = $options['order_by_options'];
        }

        return $pagination;
    }

    /**
     * Build standard pagination response
     *
     * @param  string  $page_number
     * @param  string  $records_per_page
     * @param  array  $records_per_page_options
     * @param  string  $order_by
     * @param  array  $order_by_options
     * @param  string  $total_records
     * @param  string  $total_records_found
     * @param  array  $chunk
     */
    public function buildPaginationResponse(
        $page_number,
        $records_per_page,
        $records_per_page_options,
        $order_by,
        $order_by_options,
        $total_records,
        $total_records_found,
        $chunk
    ): array {
        $page_num = (int) $page_number;
        $per_page = (int) $records_per_page;
        $total_pages = $per_page > 0 ? (int) ceil($total_records_found / $per_page) : 0;
        $count = count($chunk);

        return [
            'meta' => [
                'page_number' => (int) $page_number,
                'records_per_page' => (int) $records_per_page,
                'order_by' => (string) $order_by,
                'count' => (int) $count,
                'total_records' => (int) $total_records,
                'total_records_found' => (int) $total_records_found,
                'total_pages' => (int) $total_pages,
                'has_next' => ($page_num * $per_page) < $total_records_found,
                'has_previous' => $page_num > 1,
                'next_page' => ($page_num * $per_page) < $total_records_found ? (int) ($page_num + 1) : null,
                'previous_page' => $page_num > 1 ? (int) ($page_num - 1) : null,
            ],
            'options' => [
                'records_per_page_options' => $records_per_page_options,
                'order_by_options' => (! empty($order_by_options)) ? $order_by_options : [],
            ],
            'chunk' => $chunk,
        ];
    }

    /**
     * Validate page_number
     * For invalid page_number, 1 will be return as page_number
     *
     * @param  string  $page_number
     * @return int $page_number Numeric page number
     */
    private function validatePageNumber($page_number): int
    {
        return (is_numeric($page_number) && $page_number > 0) ? (int) $page_number : 1;
    }

    /**
     * Validate records_per_page from given options
     * if value is invalid return first option from list
     *
     * @param  string  $records_per_page
     * @param  array  $options  e.g. ['25', '50', '75', '100']
     * @return array ['records_per_page' => 25, 'records_per_page_options' => ['25', '50', '75', '100']]
     */
    private function validateRecordsPerPage($records_per_page, array $options = []): array
    {
        if (empty($options)) {
            $options = ['25', '50', '75', '100'];
        }

        $records_per_page = in_array((string) $records_per_page, $options, true)
            ? (string) $records_per_page
            : $options[0];

        return [
            'records_per_page' => $records_per_page,
            'records_per_page_options' => $options,
        ];
    }

    /**
     * Validate order_by option from given options
     * if not found return first option from list
     *
     * @param  string  $order_by
     * @param  array  $options  e.g. ['created_at_desc' => 'Created At Desc', 'created_at_asc' => 'Created At Asc']
     * @return string $order_by  Valid order by option
     */
    private function validateOrderBy($order_by, array $options): string
    {
        // if user passes the label instead of the key
        if (isset($options[$order_by])) {
            return $order_by;
        }

        // fallback: first available key
        return array_key_first($options);
    }

    /**
     * Validate filter logic value, Logical operator used to build query
     * if invalid value given, Default 'AND' will return
     *
     * @param  string  $filter_logic  Filter logic value
     * @return string 'AND' | 'OR', Default 'AND' will return
     */
    private function validateFilterLogic($filter_logic): string
    {
        $filter_logic = strtoupper(trim($filter_logic));
        if (in_array($filter_logic, ['AND', 'OR'], true)) {
            return $filter_logic;
        }

        return 'AND'; // Default value
    }

    /**
     * Validate exact match value, Used to match exact string in query
     * if invalid value given, Default '0' will return
     *
     * @param  string  $exact_match
     * @return string '0' | '1' Default '0' will return
     */
    private function validateExactMatch($exact_match): string
    {
        $exact_match = trim($exact_match);
        if (in_array($exact_match, ['0', '1'], true)) {
            return $exact_match;
        }

        return '0'; // Default value
    }
}
