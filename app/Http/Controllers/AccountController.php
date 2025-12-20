<?php

namespace App\Http\Controllers; // [FIX] Pastikan namespace benar

use App\Exports\BudgetsExport;
use App\Imports\BudgetsImport;
use App\Models\JurnalAccount;
use App\Models\AccountBudget;
use App\Models\AccountGrouping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller ini menangani CRUD untuk data Budget Akun.
 */
class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2020|max:2050'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $year = $request->input('year');

            // [FIX] Eager load relasi grouping yang baru
            $accounts = JurnalAccount::orderBy('number', 'asc')
                ->with([
                    'budgets' => fn($query) => $query->where('year', $year),
                    'accountGrouping', // <-- Muat relasi objek grouping akun
                    'budgetGrouping'   // <-- Muat relasi objek grouping budget
                ])
                ->get();

            // 3. Format data agar rapi
            $formattedAccounts = $accounts->map(function ($account) use ($year) {
                $budgetData = $account->budgets->first();
                unset($account->budgets);

                $account->budget_data = $budgetData ?? [
                    'id' => null,
                    'jurnal_account_id' => $account->id,
                    'year' => (int) $year,
                    'budget_jan' => "0.00",
                    'budget_feb' => "0.00",
                    'budget_mar' => "0.00",
                    'budget_apr' => "0.00",
                    'budget_mei' => "0.00",
                    'budget_jun' => "0.00",
                    'budget_jul' => "0.00",
                    'budget_ags' => "0.00",
                    'budget_sep' => "0.00",
                    'budget_okt' => "0.00",
                    'budget_nov' => "0.00",
                    'budget_des' => "0.00",
                ];

                // Data relasi 'accountGrouping' dan 'budgetGrouping'
                // akan otomatis menempel di $account
                return $account;
            });

            return $this->successResponse($formattedAccounts, 'Data COA dan Budget berhasil diambil');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data budget: ' . $e->getMessage(), 500);
        }
    }
    public function getallccoa(Request $request)
    {
        $query = JurnalAccount::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $searchFields = $request->has('search_fields')
                ? explode(',', $request->search_fields)
                : ['name', 'category'];
            $query->where(function ($q) use ($searchFields, $search) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }
        $sortBy = $request->input('sortBy', 'created_at');
        $sortType = $request->input('sortType', 'desc');
        if (in_array($sortBy, ['id', 'name', 'category', 'balance_amount'])) {
            $query->orderBy($sortBy, $sortType);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $perPage = $request->input('rowsPerPage', 10);
        $data = $query->paginate($perPage);
        return $this->successResponse($data);
    }
    public function save(Request $request): JsonResponse
    {
        $monthKeys = [
            'budget_jan',
            'budget_feb',
            'budget_mar',
            'budget_apr',
            'budget_mei',
            'budget_jun',
            'budget_jul',
            'budget_ags',
            'budget_sep',
            'budget_okt',
            'budget_nov',
            'budget_des',
        ];

        // 1. Siapkan aturan validasi dasar
        $rules = [
            'jurnal_account_id' => 'required|integer|exists:jurnal_accounts,id',
            'year' => 'required|integer|min:2020|max:2050',
        ];

        // 2. Tambahkan aturan validasi untuk setiap bulan
        foreach ($monthKeys as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        try {
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();

            // 3. Pisahkan data 'unique key' dan data 'values'
            $uniqueKeys = [
                'jurnal_account_id' => $validatedData['jurnal_account_id'],
                'year' => $validatedData['year'],
            ];

            // Ambil hanya data 12 bulan
            $budgetValues = array_intersect_key($validatedData, array_flip($monthKeys));

            // 4. Lakukan Update atau Create (Upsert)
            $budget = AccountBudget::updateOrCreate($uniqueKeys, $budgetValues);

            return $this->successResponse($budget, 'Budget berhasil disimpan');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan budget: ' . $e->getMessage(), 500);
        }
    }

    public function updateGrouping(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'jurnal_account_id' => 'required|integer|exists:jurnal_accounts,id',
                'grouping_akun' => 'nullable|integer|exists:account_groupings,id',
                'grouping_budget' => 'nullable|integer|exists:account_groupings,id',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            $account = JurnalAccount::find($validated['jurnal_account_id']);

            $account->account_grouping_id = $validated['grouping_akun'] ?? null;
            $account->budget_grouping_id = $validated['grouping_budget'] ?? null;
            $account->save();

            $account->load(['accountGrouping', 'budgetGrouping']);

            // samakan dengan UI
            $account->grouping_akun = $account->accountGrouping;
            $account->grouping_budget = $account->budgetGrouping;

            return $this->successResponse($account, 'Grouping akun berhasil diperbarui');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui grouping: ' . $e->getMessage(), 500);
        }
    }


    public function getGroupingOptions(): JsonResponse
    {
        try {
            // [FIX] Ambil dari tabel 'groupings' berdasarkan 'type'
            $groupingAkunOptions = AccountGrouping::where('type', 'akun')->get(['id', 'name']);
            $groupingBudgetOptions = AccountGrouping::where('type', 'budget')->get(['id', 'name']);

            $data = [
                'grouping_akun' => $groupingAkunOptions,
                'grouping_budget' => $groupingBudgetOptions,
            ];

            return $this->successResponse($data, 'Opsi grouping berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil opsi grouping: ' . $e->getMessage(), 500);
        }
    }
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
            'year' => 'required|integer'
        ]);

        $year = $request->year;

        // Baca Excel
        $rows = Excel::toArray(new BudgetsImport, $request->file('file'))[0];

        // Nama bulan di Excel
        $monthKeys = [
            'jan',
            'feb',
            'mar',
            'apr',
            'mei',
            'jun',
            'jul',
            'ags',
            'sep',
            'okt',
            'nov',
            'des'
        ];

        // Mapping: Excel → Database
        $dbMonthKeys = [
            'jan' => 'budget_jan',
            'feb' => 'budget_feb',
            'mar' => 'budget_mar',
            'apr' => 'budget_apr',
            'mei' => 'budget_mei',
            'jun' => 'budget_jun',
            'jul' => 'budget_jul',
            'ags' => 'budget_ags',
            'sep' => 'budget_sep',
            'okt' => 'budget_okt',
            'nov' => 'budget_nov',
            'des' => 'budget_des',
        ];

        $preview = [];

        foreach ($rows as $row) {

            if (!isset($row['account_code'])) continue;

            // Bersihkan account code
            $accountCode = trim($row['account_code'], " '");
            $accountCode = preg_replace('/[^0-9A-Za-z\-\.\_]/', '', $accountCode);

            // Ambil COA
            $account = JurnalAccount::with(['accountGrouping', 'budgetGrouping'])
                ->where('number', $accountCode)
                ->first();

            if (!$account) {
                $preview[] = [
                    'status' => 'error',
                    'account_code' => $accountCode,
                    'message' => "Account tidak ditemukan di COA",
                ];
                continue;
            }
            $cekGrouping = AccountGrouping::where('type', 'akun')->where('name', strtoupper($row['account_group']))->first();
            if (!$cekGrouping && strtoupper($row['account_group']) != "") {
                $cekGrouping = AccountGrouping::create([
                    "name" => strtoupper($row['account_group']),
                    "type" => "akun"
                ]);
            }
            $cekbudgetGrouping = AccountGrouping::where('type', 'budget')->where('name', strtoupper($row['budget_group']))->first();
            if (!$cekbudgetGrouping && strtoupper($row['budget_group']) != "") {
                $cekbudgetGrouping = AccountGrouping::create([
                    "name" => strtoupper($row['budget_group']),
                    "type" => "budget"
                ]);
            }
            // Existing data
            $existing = AccountBudget::where('jurnal_account_id', $account->id)
                ->where('year', $year)
                ->first();

            // NEW BUDGET (Excel)
            $newBudget = [];
            foreach ($monthKeys as $key) {
                $newBudget[$key] = isset($row[$key])
                    ? (float) str_replace(',', '', $row[$key])
                    : 0;
            }

            // Jika data NEW
            if (!$existing) {
                $preview[] = [
                    'is_new' => true,
                    'status' => 'new',
                    'number' => $account->number,
                    'name' => $account->name,
                    'account_grouping' => $account->accountGrouping,
                    'budget_grouping' => $account->budgetGrouping,
                    'account_grouping_new' => $cekGrouping ?? $account->accountGrouping,
                    'budget_grouping_new' => $cekbudgetGrouping ?? $account->budgetGrouping,
                    'budget_original' => array_fill_keys($monthKeys, 0),
                    'budget_new' => $newBudget,
                ];
                continue;
            }

            // EXISTING → cek perubahan
            $original = [];
            $isChanged = false;

            foreach ($monthKeys as $key) {

                $dbKey = $dbMonthKeys[$key];  // contoh: jan → budget_jan
                $oldVal = (float) ($existing->$dbKey ?? 0);

                $original[$key] = $oldVal;

                if ($newBudget[$key] != $oldVal) {
                    $isChanged = true;
                }
            }

            $preview[] = [
                'is_new' => false,
                'status' => $isChanged ? 'updated' : 'same',
                'number' => $account->number,
                'name' => $account->name,
                'account_grouping' => $account->accountGrouping,
                'budget_grouping' => $account->budgetGrouping,
                'account_grouping_new' => $cekGrouping ?? $account->accountGrouping,
                'budget_grouping_new' => $cekbudgetGrouping ?? $account->budgetGrouping,
                'budget_original' => $original,
                'budget_new' => $newBudget,
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $preview
        ]);
    }

    public function importSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer',
            'preview' => 'required|array'
        ]);

        if ($validator->failed()) {
            return $this->errorResponse("Validation Error", 422, $validator->errors()->all());
        }

        $year = $request->year;
        $preview = $request->preview;

        DB::beginTransaction();

        try {

            foreach ($preview as $item) {

                if (!isset($item['number'])) {
                    continue;
                }

                // Cari akun jurnal
                $account = JurnalAccount::where('number', $item['number'])->first();
                if (!$account) continue;

                // --- UPDATE GROUPING JIKA ADA PERUBAHAN ---
                if (isset($item['account_grouping_new'])) {
                    $account->account_grouping_id = $item['account_grouping_new']['id'] ?? null;
                }

                if (isset($item['budget_grouping_new'])) {
                    $account->budget_grouping_id = $item['budget_grouping_new']['id'] ?? null;
                }

                $account->save();


                // --- HANDLE BUDGET ---
                $newBudget = $item['budget_new'] ?? [];
                if (!$newBudget) continue;

                // mapping database columns
                $dbMap = [
                    'jan' => 'budget_jan',
                    'feb' => 'budget_feb',
                    'mar' => 'budget_mar',
                    'apr' => 'budget_apr',
                    'mei' => 'budget_mei',
                    'jun' => 'budget_jun',
                    'jul' => 'budget_jul',
                    'ags' => 'budget_ags',
                    'sep' => 'budget_sep',
                    'okt' => 'budget_okt',
                    'nov' => 'budget_nov',
                    'des' => 'budget_des',
                ];

                $values = [];

                foreach ($dbMap as $key => $column) {
                    $values[$column] = (float) ($newBudget[$key] ?? 0);
                }

                // CEK APAKAH SUDAH ADA
                $existing = AccountBudget::where('jurnal_account_id', $account->id)
                    ->where('year', $year)
                    ->first();

                if ($existing) {

                    // 🔵 UPDATE
                    $existing->update($values);
                } else {

                    // 🟢 INSERT
                    AccountBudget::create(array_merge($values, [
                        'jurnal_account_id' => $account->id,
                        'year' => $year
                    ]));
                }
            }

            DB::commit();

            return $this->successResponse([
                'success' => true,
                'message' => 'Data import berhasil disimpan!'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();
            return $this->errorResponse("Error", 500, [
                'success' => false,
                'message' => 'Gagal menyimpan import: ' . $e->getMessage()
            ]);
        }
    }





    public function importexcel(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls',
                'year' => 'required|integer|min:2020|max:2050',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $file = $request->file('file');
            $year = $request->input('year');

            // Gunakan class import kita, teruskan tahun
            Excel::import(new BudgetsImport($year), $file);

            return $this->successResponse(null, 'Data budget berhasil diimpor.');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengimpor file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mengekspor data budget ke file Excel.
     */
    public function exportexcel(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2020|max:2050',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse('validation error', 422, $validator->errors()->all());
            }
            $year = $request->input('year', date('Y'));
            $fileName = "budget_{$year}_export_" . date('YmdHis') . ".xlsx";

            // Gunakan class export kita, teruskan tahun
            return Excel::download(new BudgetsExport($year), $fileName);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mengunduh template import (yang berisi data saat ini).
     */
    public function downloadtemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2020|max:2050',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('validation error', 422, $validator->errors()->all());
            }

            $year = $request->input('year', date('Y'));
            $fileName = "budget_template_{$year}.xlsx";

            // FIX PALING PENTING
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            return Excel::download(new BudgetsExport($year), $fileName);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengunduh template: ' . $e->getMessage(), 500);
        }
    }
}
