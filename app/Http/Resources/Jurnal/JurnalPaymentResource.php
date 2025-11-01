<?php

namespace App\Http\Resources\Jurnal;

use Illuminate\Http\Resources\Json\JsonResource;

class JurnalPaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->jurnal_id,
            'transaction_no' => $this->transaction_no,
            'transaction_date' => $this->transaction_date->format('d/m/Y'),
            'amount' => (float) $this->amount,
            'payment_method_name' => $this->payment_method_name,
        ];
    }
}
