<?php

namespace App\Http\Controllers;

use App\Services\Qontak\QontakDashboardV2Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QontakDashboardV2Controller extends Controller
{
    public function __construct(
        private readonly QontakDashboardV2Service $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => ['nullable', 'string'],
                'end_date' => ['nullable', 'string'],
                'metric' => ['nullable', Rule::in(['qty', 'amount'])],
                'selected_period' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
                'pipeline_id' => ['nullable', 'string', 'max:100'],
                'pipeline_name' => ['nullable', 'string', 'max:255'],
                'stage_name' => ['nullable', 'string', 'max:255'],
                'team_name' => ['nullable', 'string', 'max:255'],
                'source_entity' => ['nullable', Rule::in(['all', 'contacts', 'companies', 'deals'])],
                'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
                'task_status_id' => ['nullable', 'string', 'max:50'],
                'task_priority_id' => ['nullable', 'string', 'max:50'],
                'page' => ['nullable', 'integer', 'min:1'],
                'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
                'sortBy' => ['nullable', 'string', 'max:100'],
                'sortType' => ['nullable', Rule::in(['asc', 'desc'])],
                'include' => ['nullable', 'array'],
                'include.*' => ['string', Rule::in(QontakDashboardV2Service::CONTENT_CODES)],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $filters = $validator->validated();
            $include = $request->input('include');
            if (!is_array($include)) {
                $include = null;
            }

            $cacheKey = 'dashboard-qontak-2:v2:' . sha1(json_encode([
                'filters' => $filters,
                'include' => $include,
            ]));

            $data = Cache::remember($cacheKey, 120, function () use ($filters, $include) {
                return $this->service->buildDashboard($filters, $include);
            });

            return $this->successResponse($data, 'Data dashboard Qontak 2 berhasil diambil');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal mengambil data dashboard Qontak 2', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function content(string $code, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => ['nullable', 'string'],
                'end_date' => ['nullable', 'string'],
                'metric' => ['nullable', Rule::in(['qty', 'amount'])],
                'selected_period' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
                'pipeline_id' => ['nullable', 'string', 'max:100'],
                'pipeline_name' => ['nullable', 'string', 'max:255'],
                'stage_name' => ['nullable', 'string', 'max:255'],
                'team_name' => ['nullable', 'string', 'max:255'],
                'source_entity' => ['nullable', Rule::in(['all', 'contacts', 'companies', 'deals'])],
                'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
                'task_status_id' => ['nullable', 'string', 'max:50'],
                'task_priority_id' => ['nullable', 'string', 'max:50'],
                'page' => ['nullable', 'integer', 'min:1'],
                'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
                'sortBy' => ['nullable', 'string', 'max:100'],
                'sortType' => ['nullable', Rule::in(['asc', 'desc'])],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $payload = $this->service->buildSingleContent($code, $validator->validated());
            if (!empty($payload['error'])) {
                return $this->errorResponse($payload['error'], 422);
            }

            return $this->successResponse($payload, 'Data konten dashboard Qontak 2 berhasil diambil');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal mengambil data konten dashboard Qontak 2', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function teamOptions(): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->service->teamOptions(),
                'Data team dashboard Qontak 2 berhasil diambil'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal mengambil data team dashboard Qontak 2', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function stageOptions(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'pipeline_name' => ['nullable', 'string', 'max:255'],
                'team_name' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            return $this->successResponse(
                $this->service->stageOptions($validator->validated()),
                'Data stage dashboard Qontak 2 berhasil diambil'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal mengambil data stage dashboard Qontak 2', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }
}
