<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\Company\CreateCompanyRequest;
use App\Http\Requests\Project\Company\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    /** Display a listing of the resource. */
    public function getInnerCompanies(): \Illuminate\Http\JsonResponse
    {
        return $this->onSuccess(Company::getInnerCompanies());
    }

    /** Display a listing of the resource. */
    public function getClients(): \Illuminate\Http\JsonResponse
    {
        return $this->onSuccess(Company::getClients());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateCompanyRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateCompanyRequest $request): \Illuminate\Http\JsonResponse
    {
        $company = Company::create($request->all());

        return $this->onSuccess($company, __('messages.company_has_been_created'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Company $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Company $company): \Illuminate\Http\JsonResponse
    {
        return $this->onSuccess($company);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCompanyRequest $request
     * @param Company $company
     * @return JsonResponse
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->fill($request->all());
        $company->save();

        return $this->onSuccess(
            $company,
            __('messages.project_has_been_updated')
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return $this->onSuccess(null, __('messages.project_has_been_removed'), 204);
    }
}
