<?php

namespace App\Http\Controllers;

use App\Http\Resources\Company\CompanyPwSchoolResource;
use App\Repositories\BannerRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\TypeEducationRepository;

class PwController extends Controller
{
    private $companyRepository;
    private $bannerRepository;
    private $typeEducationRepository;

    public function __construct(CompanyRepository $companyRepository, BannerRepository $bannerRepository,TypeEducationRepository $typeEducationRepository)
    {
        $this->companyRepository = $companyRepository;
        $this->bannerRepository = $bannerRepository;
        $this->typeEducationRepository = $typeEducationRepository;
    }

    public function dataPrincipal()
    {
        $banners = $this->bannerRepository->list(["typeData" => "all", "company_id" => null], select: ["id", "path"]);
        $companies = $this->companyRepository->list(["typeData" => "all"], select: ["id", "name","image_principal"]);

        return response()->json([
            "banners" => $banners,
            "companies" => $companies,
        ]);
    }

    public function dataSchool($id)
    {
        $company = $this->companyRepository->find($id);
        $typeEducations = $this->typeEducationRepository->selectList();

        $company = new CompanyPwSchoolResource($company);
        return response()->json([
            "company" => $company,
            "typeEducations" => $typeEducations,
        ]);
    }
}
