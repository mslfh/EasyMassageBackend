<?php

namespace App\Contracts;

interface VoucherHistoryContract
{
    public function getAllVoucherHistories();
    public function getPaginatedVoucherHistories($start = 0, $count = 10, $filter = null, $sortBy = 'id', $descending = false);
    public function getVoucherHistoryById($id);

    public function createVoucherHistory(array $data);
    public function updateVoucherHistory($id, array $data);
    public function deleteVoucherHistory($id);
    public function getVoucherHistoriesByVoucherId($voucherId);
    public function getVoucherHistoriesByUserId($userId);
    public function getVoucherHistoriesByAction($action);
}
