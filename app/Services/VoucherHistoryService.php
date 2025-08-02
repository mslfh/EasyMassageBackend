<?php

namespace App\Services;

use App\Contracts\VoucherHistoryContract;
use App\Contracts\VoucherContract;

class VoucherHistoryService
{
    protected $voucherHistoryRepository;
    protected $voucherRepository;

    public function __construct(VoucherHistoryContract $voucherHistoryRepository, VoucherContract $voucherRepository)
    {
        $this->voucherHistoryRepository = $voucherHistoryRepository;
        $this->voucherRepository = $voucherRepository;
    }

    public function getAllVoucherHistories()
    {
        return $this->voucherHistoryRepository->getAllVoucherHistories();
    }

    public function getPaginatedVoucherHistories($start = 0, $count = 10, $filter = null, $sortBy = 'id', $descending = false)
    {
        return $this->voucherHistoryRepository->getPaginatedVoucherHistories($start, $count, $filter, $sortBy, $descending);
    }

    public function getVoucherHistoryById($id)
    {
        return $this->voucherHistoryRepository->getVoucherHistoryById($id);
    }

    public function createVoucherHistory(array $data)
    {
        return $this->voucherHistoryRepository->createVoucherHistory($data);
    }

    public function updateVoucherHistory($id, array $data)
    {
        return $this->voucherHistoryRepository->updateVoucherHistory($id, $data);
    }

    public function deleteVoucherHistory($id)
    {
        return $this->voucherHistoryRepository->deleteVoucherHistory($id);
    }

    public function getVoucherHistoriesByVoucherId($voucherId)
    {
        return $this->voucherHistoryRepository->getVoucherHistoriesByVoucherId($voucherId);
    }

    public function getVoucherHistoriesByUserId($userId)
    {
        return $this->voucherHistoryRepository->getVoucherHistoriesByUserId($userId);
    }

    public function getVoucherHistoriesByAction($action)
    {
        return $this->voucherHistoryRepository->getVoucherHistoriesByAction($action);
    }

    /**
     * Record voucher consumption
     */
    public function recordVoucherConsumption($voucherId, $consumeAmount, $additionalData = [])
    {
        $voucher = $this->voucherRepository->getVoucherById($voucherId);

        $historyData = [
            'voucher_id' => $voucherId,
            'action' => 'consume',
            'pre_amount' => $voucher->remaining_amount + $consumeAmount,
            'after_amount' => $voucher->remaining_amount,
            'description' => $additionalData['description'] ?? 'Voucher consumption',
        ];

        // Add optional fields if provided
        if (isset($additionalData['user_id'])) {
            $historyData['user_id'] = $additionalData['user_id'];
        }
        if (isset($additionalData['appointment_id'])) {
            $historyData['appointment_id'] = $additionalData['appointment_id'];
        }
        if (isset($additionalData['phone'])) {
            $historyData['phone'] = $additionalData['phone'];
        }
        if (isset($additionalData['name'])) {
            $historyData['name'] = $additionalData['name'];
        }
        if (isset($additionalData['service'])) {
            $historyData['service'] = $additionalData['service'];
        }

        return $this->createVoucherHistory($historyData);
    }

    /**
     * Record voucher creation/initialization
     */
    public function recordVoucherInit($voucherId, $initialAmount, $additionalData = [])
    {
        $historyData = [
            'voucher_id' => $voucherId,
            'action' => 'init',
            'pre_amount' => $initialAmount,
            'after_amount' => $initialAmount,
            'description' => $additionalData['description'] ?? 'Voucher initialized',
        ];

        // Add optional fields if provided
        if (isset($additionalData['user_id'])) {
            $historyData['user_id'] = $additionalData['user_id'];
        }
        if (isset($additionalData['phone'])) {
            $historyData['phone'] = $additionalData['phone'];
        }
        if (isset($additionalData['name'])) {
            $historyData['name'] = $additionalData['name'];
        }

        return $this->createVoucherHistory($historyData);
    }

    /**
     * Record voucher modification
     */
    public function recordVoucherEdit($voucherId, $oldAmount, $newAmount, $additionalData = [])
    {
        $historyData = [
            'voucher_id' => $voucherId,
            'action' => 'edit',
            'pre_amount' => $oldAmount,
            'after_amount' => $newAmount,
            'description' => $additionalData['description'] ?? 'Voucher amount modified',
        ];

        // Add optional fields if provided
        if (isset($additionalData['user_id'])) {
            $historyData['user_id'] = $additionalData['user_id'];
        }

        return $this->createVoucherHistory($historyData);
    }

    /**
     * Record voucher refund
     */
    public function recordVoucherRefund($voucherId, $refundAmount, $additionalData = [])
    {
        $voucher = $this->voucherRepository->getVoucherById($voucherId);

        $historyData = [
            'voucher_id' => $voucherId,
            'action' => 'refund',
            'pre_amount' => $voucher->remaining_amount,
            'after_amount' => $voucher->remaining_amount + $refundAmount,
            'description' => $additionalData['description'] ?? 'Voucher refund',
        ];

        // Add optional fields if provided
        if (isset($additionalData['user_id'])) {
            $historyData['user_id'] = $additionalData['user_id'];
        }
        if (isset($additionalData['appointment_id'])) {
            $historyData['appointment_id'] = $additionalData['appointment_id'];
        }
        if (isset($additionalData['phone'])) {
            $historyData['phone'] = $additionalData['phone'];
        }
        if (isset($additionalData['name'])) {
            $historyData['name'] = $additionalData['name'];
        }
        if (isset($additionalData['service'])) {
            $historyData['service'] = $additionalData['service'];
        }

        // Update voucher remaining amount
        $this->voucherRepository->updateVoucher($voucherId, [
            'remaining_amount' => $voucher->remaining_amount + $refundAmount
        ]);

        return $this->createVoucherHistory($historyData);
    }

}
