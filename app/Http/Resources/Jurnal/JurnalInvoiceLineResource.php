<?php

namespace App\Http\Resources\Jurnal;

use Illuminate\Http\Resources\Json\JsonResource;

class JurnalInvoiceLineResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->jurnal_id,
            'description' => $this->description,
            'amount' => (string) $this->amount,
            'rate' => (string) $this->rate,
            'quantity' => (float) $this->quantity,
            'line_tax' => [
                'name' => $this->tax_name,
                'rate' => (string) $this->tax_rate,
            ],
            'product' => [
                'id' => $this->product_jurnal_id,
                'name' => $this->product_name,
            ],
            'unit' => [
                'name' => $this->unit_name,
            ],
        ];
    }
}
