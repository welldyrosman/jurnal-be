<?php

namespace App\Http\Controllers;

use App\Models\QontakDeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QontakDealReportController extends Controller
{

    public function index(Request $request)
    {
        $perPage  = $request->get('rowsPerPage', 25);
        $search   = $request->get('search');
        $sortBy   = $request->get('sortBy', 'nilai');
        $sortType = $request->get('sortType', 'desc');

        $query = DB::table('qontak_deals as d')
            ->leftJoin('qontak_companies as c', 'c.id', '=', 'd.qontak_company_id')
            ->leftJoin('qontak_sources as s', 's.id', '=', 'd.qontak_source_id')
            ->leftJoin(
                'qontak_deal_product_associations as da',
                'da.crm_deal_id',
                '=',
                'd.deal_id'
            )
            ->select([
                'd.id',
                'd.name as lead_name',
                'd.creator_name as creator',
                'c.name as leads',
                'c.telephone as contact',
                DB::raw("GROUP_CONCAT(DISTINCT da.product_name SEPARATOR ', ') as layanan_jasa"),
                's.crm_source_name as sumber',
                'd.amount as nilai',
                'd.crm_stage_name as keterangan',
            ])
            ->groupBy(
                'd.id',
                'd.name',
                'd.creator_name',
                'c.name',
                'c.telephone',
                's.crm_source_name',
                'd.amount',
                'd.crm_stage_name'
            );

        // 🔍 SEARCH
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('d.name', 'like', "%{$search}%")
                    ->orWhere('d.creator_name', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%")
                    ->orWhere('c.telephone', 'like', "%{$search}%")
                    ->orWhere('da.product_name', 'like', "%{$search}%")
                    ->orWhere('s.crm_source_name', 'like', "%{$search}%")
                    ->orWhere('d.crm_stage_name', 'like', "%{$search}%");
            });
        }

        // 🔃 SORTING
        $sortableMap = [
            'lead_name'     => 'd.name',
            'creator'       => 'd.creator_name',
            'leads'         => 'c.name',
            'contact'       => 'c.telephone',
            'sumber'        => 's.crm_source_name',
            'nilai'         => 'd.amount',
            'keterangan'    => 'd.crm_stage_name',
            'layanan_jasa'  => DB::raw("GROUP_CONCAT(DISTINCT da.product_name SEPARATOR ', ')"),
        ];

        if (isset($sortableMap[$sortBy])) {
            $query->orderBy($sortableMap[$sortBy], $sortType);
        } else {
            $query->orderByDesc('d.amount');
        }

        return $this->successResponse(
            $query->paginate($perPage)
        );
    }



    public function byStage()
    {
        $data = QontakDeal::select('crm_stage_name', DB::raw('COUNT(*) as total'))
            ->groupBy('crm_stage_name')
            ->orderBy('total', 'DESC')
            ->get();

        return response()->json($data);
    }

    public function amountByStage()
    {
        $data = QontakDeal::select('crm_stage_name', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('crm_stage_name')
            ->get();

        return response()->json($data);
    }

    public function perMonth()
    {
        $data = QontakDeal::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw("COUNT(*) as total")
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }
}
