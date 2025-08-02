<?php

namespace App\Services;

use App\Contracts\VoucherContract;

class VoucherService
{
    protected $voucherRepository;

    protected $voucherHistoryService;

    public function __construct(VoucherContract $voucherRepository, VoucherHistoryService $voucherHistoryService)
    {
        $this->voucherRepository = $voucherRepository;
        $this->voucherHistoryService = $voucherHistoryService;
    }

    public function getAllVouchers()
    {
        return $this->voucherRepository->getAllVouchers();
    }

    public function getPaginatedVouchers($start = 0, $count = 10, $filter = null, $sortBy = 'id', $descending = false)
    {
        return $this->voucherRepository->getPaginatedVouchers($start, $count, $filter, $sortBy, $descending);
    }

    public function getVoucherById($id)
    {
        return $this->voucherRepository->getVoucherById($id);
    }

    public function generateVoucherCode($length = 8)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, 25)];
        }
        if ($this->voucherRepository->findByCode($code)) {
            return $this->generateVoucherCode($length);
        } else {
            return $code;
        }
    }

    public function createVoucher(array $data)
    {
        if (empty($data['code'])) {
            $data['code'] = $this->generateVoucherCode();
        }
        if (empty($data['remaining_amount'])) {
            $data['remaining_amount'] = $data['amount'];
        }
        $vourcher = $this->voucherRepository->createVoucher($data);
        // Log voucher creation history
        $this->voucherHistoryService->recordVoucherInit(
            $vourcher->id,
            $data['remaining_amount'],
            ['description' => 'Voucher created']
        );
        return $vourcher;
    }

    public function bulkCreateVoucher(array $data)
    {
        $codes = [];
        if (empty($data['codes'])) {
            for ($i = 0; $i < $data['count']; $i++) {
                $codes[] = $this->generateVoucherCode();
            }
        } else {
            $codes = explode(',', $data['codes']);
        }
        unset($data['codes']);
        for ($i = 0; $i < $data['count']; $i++) {
            $data['code'] = $codes[$i];
            if (empty($data['remaining_amount'])) {
                $data['remaining_amount'] = $data['amount'];
            }
            $this->voucherRepository->createVoucher($data);
        }
        return [
            'status' => 'success',
            'message' => 'Vouchers created successfully',
            'codes' => $codes
        ];
    }

    public function updateVoucher($id, array $data)
    {
        $voucher = $this->voucherRepository->getVoucherById($id);
        //log voucher update history
        $this->voucherHistoryService->recordVoucherEdit(
            $id,
            $voucher->remaining_amount,
            $data['remaining_amount'] ?? $voucher->remaining_amount,
            [
                'description' => 'Voucher updated',
            ]
        );
        return $this->voucherRepository->updateVoucher($id, $data);
    }

    public function verifyVoucher($code)
    {
        $voucher = $this->voucherRepository->findByCode($code);
        if (!$voucher || $voucher->is_active !== 1) {
            return [
                'status' => 'error',
                'message' => "Voucher code {$code} is invalid."

            ];
        } else {
            return [
                'status' => 'success',
                'message' => 'Voucher is valid',
                'data' => $voucher
            ];
        }
    }

    public function consumeVoucher(
        $code,
        $amount,
        $userId = null,
        $appointmentId = null,
        $phone = null,
        $name = null,
        $service = null
    ) {
        $voucher = $this->voucherRepository->findByCode($code);
        if (!$voucher || $voucher->is_active !== 1) {
            return [
                'status' => 'error',
                'message' => "Voucher code {$code} is invalid."
            ];
        }

        if ($voucher->remaining_amount < $amount) {
            return [
                'status' => 'error',
                'message' => "Insufficient voucher balance."
            ];
        }

        // Deduct the amount from the voucher
        $voucher->remaining_amount -= $amount;

        $voucher->save();

        // Log the voucher consumption
        $this->voucherHistoryService->recordVoucherConsumption(
            $voucher->id,
            $amount,
            [
                'description' => 'Voucher consumed',
                'user_id' => $userId,
                'appointment_id' => $appointmentId,
                'phone' => $phone,
                'name' => $name,
                'service' => $service
            ]
        );

        return [
            'status' => 'success',
            'message' => 'Voucher consumed successfully',
            'data' => $voucher
        ];
    }

    public function verifyValidCode($codes)
    {
        foreach ($codes as $code) {
            $voucher = $this->voucherRepository->findByCode($code);
            if ($voucher) {
                return [
                    'status' => 'error',
                    'message' => "Voucher code {$code} is invalid."
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => 'All vouchers are valid',
            'data' => $codes
        ];
    }

    public function deleteVoucher($id)
    {
        return $this->voucherRepository->deleteVoucher($id);
    }

    public function findByCode($code)
    {
        return $this->voucherRepository->findByCode($code);
    }

}
