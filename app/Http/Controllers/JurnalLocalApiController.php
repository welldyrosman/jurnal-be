<?php

namespace App\Http\Controllers;

use App\Http\Resources\Jurnal\JurnalInvoiceResource;
use App\Models\JurnalInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class JurnalLocalApiController extends Controller
{
   
     public function getSalesInvoices(Request $request)
    {
        // Menambahkan opsi sorting baru pada validasi
        $request->validate([
            'page' => 'integer|min:1',
            'page_size' => 'integer|min:1|max:100',
            'transaction_date_from' => 'date_format:Y-m-d',
            'transaction_date_to' => 'date_format:Y-m-d',
            'due_date_from' => 'date_format:Y-m-d',
            'due_date_to' => 'date_format:Y-m-d',
            'status' => 'string|in:open,partial,paid,overdue,approved',
            'person_id' => 'integer',
            'search_column' => 'string|in:transaction_no,person_name',
            'search_term' => 'string|min:1',
            'sort' => 'string|in:due_date,transaction_date,transaction_no,person_name,status_name,remaining,total_amount,created_at', // Validasi kolom sort
            'order' => 'string|in:asc,desc', 
        ]);

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $sortBy = $request->input('sort', 'transaction_date'); // Default sort by transaction_date
        $orderBy = $request->input('order', 'desc'); // Default order is descending

        // Memulai query builder
        $query = JurnalInvoice::with(['person', 'lines', 'payments']);

        // Menerapkan filter secara dinamis
        $this->applyFilters($query, $request);

        // --- LOGIKA SORTING BARU ---
        // Mapping dari parameter sort ke kolom database aktual
        $sortColumnMap = [
            'transaction_date' => 'jurnal_invoices.transaction_date',
            'due_date'         => 'jurnal_invoices.due_date',
            'transaction_no'   => 'jurnal_invoices.transaction_no',
            'status_name'      => 'jurnal_invoices.transaction_status_name',
            'remaining'        => 'jurnal_invoices.remaining',
            'total_amount'     => 'jurnal_invoices.total_amount', // 'original_amount' di API Jurnal
            'created_at'       => 'jurnal_invoices.created_at_jurnal', // Menggunakan created_at dari Jurnal
            'person_name'      => 'jurnal_persons.display_name', // Kolom dari tabel relasi
        ];

        $sortColumn = $sortColumnMap[$sortBy] ?? 'jurnal_invoices.transaction_date';

        // Menambahkan join jika sorting dilakukan pada tabel relasi (jurnal_persons)
        if ($sortBy === 'person_name') {
            $query->join('jurnal_persons', 'jurnal_invoices.person_id', '=', 'jurnal_persons.id')
                  ->select('jurnal_invoices.*'); // Mencegah konflik nama kolom
        }
        
        // Menerapkan sorting
        $query->orderBy($sortColumn, $orderBy);
        
        // Ganti secondary sort ke 'jurnal_id' untuk meniru perilaku API Jurnal secara akurat
        $query->orderBy('jurnal_invoices.jurnal_id', 'desc');
        // --- AKHIR LOGIKA SORTING BARU ---

        // Ambil data dari database dengan paginasi
        $invoicesPaginator = $query->paginate($pageSize, ['*'], 'page', $page);

        // Gunakan API Resource untuk transformasi data
        $transformedInvoices = JurnalInvoiceResource::collection($invoicesPaginator);

        // Buat struktur respons yang sama persis dengan Jurnal API
        return response()->json([
            'sales_invoices' => $transformedInvoices,
            'total_count'    => $invoicesPaginator->total(),
            'current_page'   => $invoicesPaginator->currentPage(),
            'total_pages'    => $invoicesPaginator->lastPage(),
            'links' => [
                'next_link' => $invoicesPaginator->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Menerapkan filter yang diterima dari request ke query Eloquent.
     *
     * @param Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters(Builder &$query, Request $request): void
    {
        // Filter berdasarkan rentang tanggal transaksi
        $query->when($request->filled('transaction_date_from'), function ($q) use ($request) {
            $q->whereDate('transaction_date', '>=', $request->transaction_date_from);
        });
        $query->when($request->filled('transaction_date_to'), function ($q) use ($request) {
            $q->whereDate('transaction_date', '<=', $request->transaction_date_to);
        });

        // Filter berdasarkan rentang tanggal jatuh tempo
        $query->when($request->filled('due_date_from'), function ($q) use ($request) {
            $q->whereDate('due_date', '>=', $request->due_date_from);
        });
        $query->when($request->filled('due_date_to'), function ($q) use ($request) {
            $q->whereDate('due_date', '<=', $request->due_date_to);
        });

        // Filter berdasarkan status invoice
        $query->when($request->filled('status'), function ($q) use ($request) {
            // Mapping status Jurnal ke status di database Anda
            $statusMap = [
                'open' => ['approved'],
                'partial' => ['approved'], // Anda mungkin perlu logika 'remaining' > 0
                'paid' => ['paid'],
                'overdue' => ['approved'], // Anda mungkin perlu logika due_date < now()
                'approved' => ['approved'],
            ];
            $dbStatus = $statusMap[$request->status] ?? [$request->status];
            $q->whereIn('status', $dbStatus);

            if ($request->status === 'overdue') {
                $q->where('due_date', '<', now());
            }
        });

        // Filter berdasarkan ID Person (pelanggan)
        $query->when($request->filled('person_id'), function ($q) use ($request) {
            $q->whereHas('person', function ($subQuery) use ($request) {
                $subQuery->where('jurnal_id', $request->person_id);
            });
        });
        
        // Filter pencarian
        $query->when($request->filled('search_term') && $request->filled('search_column'), function ($q) use ($request) {
            $term = '%' . $request->search_term . '%';
            if ($request->search_column === 'transaction_no') {
                $q->where('transaction_no', 'like', $term);
            } elseif ($request->search_column === 'person_name') {
                $q->whereHas('person', function ($subQuery) use ($term) {
                    $subQuery->where('display_name', 'like', $term);
                });
            }
        });
    }
}
