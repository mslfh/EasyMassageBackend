<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoucherHistoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'voucher_id' => 'required|integer|exists:vouchers,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'phone' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'service' => 'nullable|string|max:255',
            'action' => 'required|string|in:init,consume,edit,refund',
            'description' => 'nullable|string',
            'pre_amount' => 'required|numeric|min:0',
            'after_amount' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'voucher_id.required' => 'Voucher ID is required.',
            'voucher_id.exists' => 'The selected voucher does not exist.',
            'user_id.exists' => 'The selected user does not exist.',
            'appointment_id.exists' => 'The selected appointment does not exist.',
            'action.required' => 'Action is required.',
            'action.in' => 'Action must be one of: init, consume, edit, refund.',
            'pre_amount.required' => 'Pre amount is required.',
            'pre_amount.numeric' => 'Pre amount must be a number.',
            'pre_amount.min' => 'Pre amount must be at least 0.',
            'after_amount.required' => 'After amount is required.',
            'after_amount.numeric' => 'After amount must be a number.',
            'after_amount.min' => 'After amount must be at least 0.',
        ];
    }
}
