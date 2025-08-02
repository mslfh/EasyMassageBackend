<?php

namespace App\Repositories;

use App\Models\VoucherHistory;
use App\Contracts\VoucherHistoryContract;

class VoucherHistoryRepository implements VoucherHistoryContract
{
    public function getAllVoucherHistories()
    {
        return VoucherHistory::with(['voucher', 'user', 'appointment'])->get();
    }

    public function getPaginatedVoucherHistories($start = 0, $count = 10, $filter = null, $sortBy = 'id', $descending = false)
    {
        $query = VoucherHistory::with(['voucher', 'user', 'appointment']);

        if ($filter) {
            $query->where(function($q) use ($filter) {
                $q->where('action', 'like', '%' . $filter . '%')
                  ->orWhere('description', 'like', '%' . $filter . '%')
                  ->orWhere('name', 'like', '%' . $filter . '%')
                  ->orWhere('phone', 'like', '%' . $filter . '%')
                  ->orWhere('service', 'like', '%' . $filter . '%');
            });
        }

        if ($descending) {
            $query->orderByDesc($sortBy);
        } else {
            $query->orderBy($sortBy);
        }

        $total = $query->count();
        $data = $query->skip($start)->take($count)->get();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    public function getVoucherHistoryById($id)
    {
        return VoucherHistory::with(['voucher', 'user', 'appointment'])->findOrFail($id);
    }

    public function createVoucherHistory(array $data)
    {
        return VoucherHistory::create($data);
    }

    public function updateVoucherHistory($id, array $data)
    {
        $voucherHistory = VoucherHistory::findOrFail($id);
        $voucherHistory->update($data);
        return $voucherHistory;
    }

    public function deleteVoucherHistory($id)
    {
        $voucherHistory = VoucherHistory::findOrFail($id);
        return $voucherHistory->delete();
    }

    public function getVoucherHistoriesByUserId($userId)
    {
        return VoucherHistory::with(['voucher', 'user', 'appointment'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getVoucherHistoriesByVoucherId($voucherId)
    {
        return VoucherHistory::with(['voucher', 'appointment'])
            ->where('voucher_id', $voucherId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getVoucherHistoriesByAction($action)
    {
        return VoucherHistory::with(['voucher', 'user', 'appointment'])
            ->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
