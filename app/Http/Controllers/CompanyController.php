<?php

namespace App\Http\Controllers;

use App\Models\QontakCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = QontakCompany::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $searchFields = $request->has('search_fields')
                ? explode(',', $request->search_fields)
                : ['name', 'email'];
            $query->where(function ($q) use ($searchFields, $search) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }
        $sortBy = $request->input('sortBy', 'created_at');
        $sortType = $request->input('sortType', 'desc');
        if (in_array($sortBy, ['id', 'name', 'email', 'telephone', 'website', 'created_at'])) {
            $query->orderBy($sortBy, $sortType);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $perPage = $request->input('rowsPerPage', 10);
        $data = $query->paginate($perPage);
        return $this->successResponse($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'telephone' => 'nullable|string',
        ]);
        $qontakPayload = [
            "name" => $request->name,
            "website" => $request->website ?? "",
            "creator_id" => $request->creator_id ?? 1, // Default ID user CRM Qontak (bukan user lokal)
            "creator_name" => $request->creator_name ?? "CRM User",
            "telephone" => $request->telephone ?? "",
            "address" => $request->address ?? "",
            "country" => $request->country ?? "",
            "province" => $request->province ?? "",
            "city" => $request->city ?? "",
            "zipcode" => $request->zipcode ?? "",
            "industry_id" => null,
            "business_reg_number" => $request->business_reg_number ?? "",
            "industry_name" => null,
            "crm_type_id" => 1009282,
            "crm_type_name" => null,
            "number_of_employees" => null,
            "crm_source_id" => null,
            "crm_source_name" => null,
            "annual_revenue" => null,
            "crm_deal_ids" => [],
            "crm_deal_name" => [],
            "crm_person_ids" => [],
            "crm_person_name" => [],
            "ancestry" => null,
            "additional_fields" => []
        ];

        DB::beginTransaction(); // Mulai transaksi DB Lokal

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('QONTAK_TOKEN'),
            ])->post('https://app.qontak.com/api/v3.1/companies', $qontakPayload);
            if ($response->failed()) {
                return response()->json([
                    'message' => 'Gagal mengirim data ke Qontak API',
                    'error' => $response->json()
                ], $response->status());
            }
            $apiResult = $response->json();
            $qontakData = $apiResult['data'] ?? [];
            $crmCompanyId = $qontakData['id'] ?? null;
            $localCompany = QontakCompany::create([
                'crm_company_id' => $crmCompanyId, // ID dari Qontak
                'name' => $request->name,
                'email' => $request->email, // Email mungkin tidak masuk ke payload Qontak tapi disimpan di lokal
                'telephone' => $request->telephone,
                'website' => $request->website,
                'address' => $request->address,
                'crm_source_id' => $request->crm_source_id,
                'crm_source_name' => $request->crm_source_name,
                'created_by' => Auth::id() ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Berhasil menyimpan data ke Qontak dan Lokal',
                'data' => $localCompany,
                'qontak_response' => $qontakData
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan internal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
