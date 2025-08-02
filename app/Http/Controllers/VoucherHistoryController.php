<?php

namespace App\Http\Controllers;

use App\Services\VoucherHistoryService;
use Illuminate\Http\Request;

class VoucherHistoryController extends Controller
{
    protected $voucherHistoryService;

    public function __construct(VoucherHistoryService $voucherHistoryService)
    {
        $this->voucherHistoryService = $voucherHistoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $start = $request->query('start', 0);
        $count = $request->query('count', 10);
        $filter = $request->query('filter', null);
        $sortBy = $request->query('sortBy', 'id');
        $descending = $request->query('descending', false);

        $voucherHistories = $this->voucherHistoryService->getPaginatedVoucherHistories($start, $count, $filter, $sortBy, $descending);

        return response()->json([
            'rows' => $voucherHistories['data'],
            'total' => $voucherHistories['total'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $voucherHistory = $this->voucherHistoryService->createVoucherHistory($data);
        return response()->json($voucherHistory, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json($this->voucherHistoryService->getVoucherHistoryById($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $voucherHistory = $this->voucherHistoryService->updateVoucherHistory($id, $data);
        return response()->json($voucherHistory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->voucherHistoryService->deleteVoucherHistory($id);
        return response()->json(['message' => 'Voucher history deleted successfully']);
    }

    /**
     * Get voucher histories by voucher ID
     */
    public function getByVoucherId($voucherId)
    {
        $histories = $this->voucherHistoryService->getVoucherHistoriesByVoucherId($voucherId);
        return response()->json($histories);
    }

    /**
     * Get voucher histories by user ID
     */
    public function getByUserId($userId)
    {
        $histories = $this->voucherHistoryService->getVoucherHistoriesByUserId($userId);
        return response()->json($histories);
    }

    /**
     * Get voucher histories by action type
     */
    public function getByAction($action)
    {
        $histories = $this->voucherHistoryService->getVoucherHistoriesByAction($action);
        return response()->json($histories);
    }

    /**
     * Record voucher consumption
     */
    public function recordConsumption(Request $request)
    {
        $voucherId = $request->input('voucher_id');
        $consumeAmount = $request->input('consume_amount');
        $additionalData = $request->except(['voucher_id', 'consume_amount']);

        $history = $this->voucherHistoryService->recordVoucherConsumption($voucherId, $consumeAmount, $additionalData);
        return response()->json($history, 201);
    }

    /**
     * Record voucher initialization
     */
    public function recordInit(Request $request)
    {
        $voucherId = $request->input('voucher_id');
        $initialAmount = $request->input('initial_amount');
        $additionalData = $request->except(['voucher_id', 'initial_amount']);

        $history = $this->voucherHistoryService->recordVoucherInit($voucherId, $initialAmount, $additionalData);
        return response()->json($history, 201);
    }

    /**
     * Record voucher modification
     */
    public function recordEdit(Request $request)
    {
        $voucherId = $request->input('voucher_id');
        $oldAmount = $request->input('old_amount');
        $newAmount = $request->input('new_amount');
        $additionalData = $request->except(['voucher_id', 'old_amount', 'new_amount']);

        $history = $this->voucherHistoryService->recordVoucherEdit($voucherId, $oldAmount, $newAmount, $additionalData);
        return response()->json($history, 201);
    }

    /**
     * Record voucher refund
     */
    public function recordRefund(Request $request)
    {
        $voucherId = $request->input('voucher_id');
        $refundAmount = $request->input('refund_amount');
        $additionalData = $request->except(['voucher_id', 'refund_amount']);

        $history = $this->voucherHistoryService->recordVoucherRefund($voucherId, $refundAmount, $additionalData);
        return response()->json($history, 201);
    }
}
