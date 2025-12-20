<?php

namespace App\Http\Controllers;

use App\Services\HmacAuthService;
use Illuminate\Http\Request;

class QontakDealController extends Controller
{
    private $hmacService;

    public function __construct(HmacAuthService $hmacService)
    {
        $this->hmacService = $hmacService;
    }
    public function getDeals()
    {
        $response = $this->hmacService->get('deals');

        return response()->json($response->json());
    }
    public function createDeal(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'value' => 'required|numeric',
            'stage' => 'required|string',
        ]);

        $response = $this->hmacService->post('deals', $data);

        if ($response->failed()) {
            return response()->json($response->json(), $response->status());
        }

        return response()->json($response->json(), 201);
    }
    public function getDeal($id)
    {
        $response = $this->hmacService->get('deals/' . $id);

        return response()->json($response->json());
    }
    public function updateDeal(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'string',
            'value' => 'numeric',
            'stage' => 'string',
        ]);

        $response = $this->hmacService->put('deals/' . $id, $data);

        return response()->json($response->json());
    }
    public function deleteDeal($id)
    {
        $response = $this->hmacService->delete('deals/' . $id);

        return response()->json($response->json());
    }
}
