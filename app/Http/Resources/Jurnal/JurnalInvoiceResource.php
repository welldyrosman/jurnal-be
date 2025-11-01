<?php

namespace App\Http\Resources\Jurnal;

use Illuminate\Http\Resources\Json\JsonResource;

class JurnalInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Mengembalikan struktur data mentah yang sudah disimpan sebelumnya.
        // Ini adalah cara termudah & paling akurat untuk meniru respons asli.
        if ($this->raw_data) {
            return json_decode($this->raw_data, true);
        }

        // Fallback jika raw_data tidak ada (meskipun seharusnya selalu ada)
        return [
            'id' => $this->jurnal_id,
            'transaction_no' => $this->transaction_no,
            'status' => $this->status,
            'source' => $this->source,
            'address' => $this->address,
            'message' => $this->message,
            'memo' => $this->memo,
            'remaining' => (string) $this->remaining,
            'original_amount' => (string) $this->total_amount, // Mapping nama kolom
            'shipping_price' => (string) $this->shipping_price,
            'shipping_address' => $this->shipping_address,
            'is_shipped' => $this->is_shipped,
            'reference_no' => $this->reference_no,
            'tax_amount' => (string) $this->tax_amount,
            'subtotal' => (string) $this->subtotal,
            'deposit' => (string) $this->deposit,
            'created_at' => $this->created_at_jurnal->toIso8601String(),
            'updated_at' => $this->updated_at_jurnal->toIso8601String(),
            'deleted_at' => $this->deleted_at_jurnal ? $this->deleted_at_jurnal->toIso8601String() : null,
            'transaction_date' => $this->transaction_date->format('d/m/Y'),
            'due_date' => $this->due_date->format('d/m/Y'),
            'shipping_date' => $this->shipping_date ? $this->shipping_date->format('d/m/Y') : null,
            'payment_received_amount' => (string) $this->payment_received,
            'transaction_status' => [
                'name_bahasa' => $this->transaction_status_name,
            ],
            'person' => new JurnalPersonResource($this->whenLoaded('person')),
            'transaction_lines_attributes' => JurnalInvoiceLineResource::collection($this->whenLoaded('lines')),
            'payments' => JurnalPaymentResource::collection($this->whenLoaded('payments')),
            'term' => [
                'name' => $this->term_name,
            ],
            'currency_code' => $this->currency_code,
            // Tambahkan field lain yang diperlukan di sini...
        ];
    }
}
