<?php

namespace App\Http\Resources\Jurnal;

use Illuminate\Http\Resources\Json\JsonResource;

class JurnalPersonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->jurnal_id,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'address' => $this->address,
            'billing_address' => $this->billing_address,
            'phone' => $this->phone,
        ];
    }
}
