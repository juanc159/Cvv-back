<?php

namespace App\Http\Controllers;

use App\Http\Resources\Grade\GradeSelectInifiniteResource;
use App\Http\Resources\Settings\Country\CountrySelectResource;
use App\Repositories\GradeRepository;
use App\Repositories\Querys\CityRepository;
use App\Repositories\Querys\CountryRepository;
use App\Repositories\Querys\DepartmentRepository;
use App\Repositories\SectionRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    private $countryRepository;

    private $departmentRepository;

    private $cityRepository;

    private $userRepository;
    private $gradeRepository;
    private $sectionRepository;

    public function __construct(
        CountryRepository $countryRepository,
        DepartmentRepository $departmentRepository,
        CityRepository $cityRepository,
        UserRepository $userRepository,
        GradeRepository $gradeRepository,
        SectionRepository $sectionRepository,
    ) {
        $this->countryRepository = $countryRepository;
        $this->departmentRepository = $departmentRepository;
        $this->cityRepository = $cityRepository;
        $this->userRepository = $userRepository;
        $this->gradeRepository = $gradeRepository;
        $this->sectionRepository = $sectionRepository;
    }

    public function selectCountries(Request $request)
    {
        $countries = $this->countryRepository->list($request->all());

        $dataCountries = CountrySelectResource::collection($countries);

        return response()->json([
            'code' => 200,
            'message' => 'Datos Encontrados',
            'countries_arrayInfo' => $dataCountries,
            'countries_countLinks' => $countries->lastPage(),
        ]);
    }

    public function selectDepartments($country_id)
    {
        $departments = $this->departmentRepository->selectList($country_id);

        return response()->json([
            'code' => 200,
            'message' => 'Datos Encontrados',
            'departments' => $departments,
        ]);
    }

    public function selectCities($department_id)
    {
        $cities = $this->cityRepository->selectList($department_id);

        return response()->json([
            'code' => 200,
            'message' => 'Datos Encontrados',
            'cities' => $cities,
        ]);
    }

    public function selectInfiniteGrade(Request $request)
    {
        $grade = $this->gradeRepository->list($request->all());
        $dataGrade = GradeSelectInifiniteResource::collection($grade);

        return [
            'code' => 200,
            'grade_arrayInfo' => $dataGrade,
            'grade_countLinks' => $grade->lastPage(),
        ];
    }
    public function selectInfiniteSection(Request $request)
    {
        $section = $this->sectionRepository->list($request->all());
        $dataSection = GradeSelectInifiniteResource::collection($section);

        return [
            'code' => 200,
            'section_arrayInfo' => $dataSection,
            'section_countLinks' => $section->lastPage(),
        ];
    }
}
