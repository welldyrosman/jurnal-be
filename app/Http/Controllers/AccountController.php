<?php

namespace App\Http\Controllers; // [FIX] Pastikan namespace benar

use App\Models\JurnalAccount;
use App\Models\AccountBudget;
use App\Models\AccountGrouping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

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
                    'budgets' => fn ($query) => $query->where('year', $year),
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
                    'budget_jan' => "0.00", 'budget_feb' => "0.00", 'budget_mar' => "0.00",
                    'budget_apr' => "0.00", 'budget_mei' => "0.00", 'budget_jun' => "0.00",
                    'budget_jul' => "0.00", 'budget_ags' => "0.00", 'budget_sep' => "0.00",
                    'budget_okt' => "0.00", 'budget_nov' => "0.00", 'budget_des' => "0.00",
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

    public function save(Request $request): JsonResponse
    {
        $monthKeys = [
            'budget_jan', 'budget_feb', 'budget_mar',
            'budget_apr', 'budget_mei', 'budget_jun',
            'budget_jul', 'budget_ags', 'budget_sep',
            'budget_okt', 'budget_nov', 'budget_des',
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
            // [FIX] Validasi input (ganti ke _id dan cek tabel groupings)
            $validator = Validator::make($request->all(), [
                'jurnal_account_id' => 'required|integer|exists:jurnal_accounts,id',
                'account_grouping_id' => 'nullable|integer|exists:groupings,id',
                'budget_grouping_id'  => 'nullable|integer|exists:groupings,id',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();

            // 2. Cari akun di database lokal
            $account = JurnalAccount::find($validatedData['jurnal_account_id']);

            if (!$account) {
                return $this->errorResponse('Akun tidak ditemukan di database lokal.', 404);
            }

            // [FIX] Update dan simpan data grouping (FK)
            $account->account_grouping_id = $validatedData['account_grouping_id'] ?? null;
            $account->budget_grouping_id = $validatedData['budget_grouping_id'] ?? null;
            $account->save();

            // Muat relasi yang baru disimpan untuk dikembalikan ke frontend
            $account->load(['accountGrouping', 'budgetGrouping']);

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
}