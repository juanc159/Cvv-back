<?php

namespace App\Http\Controllers;

use App\Http\Resources\Settings\Country\CountrySelectResource;
use App\Repositories\Querys\CityRepository;
use App\Repositories\Querys\CountryRepository;
use App\Repositories\Querys\DepartmentRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    private $countryRepository;

    private $departmentRepository;

    private $cityRepository;

    private $userRepository;

    public function __construct(
        CountryRepository $countryRepository,
        DepartmentRepository $departmentRepository,
        CityRepository $cityRepository,
        UserRepository $userRepository,
    ) {
        $this->countryRepository = $countryRepository;
        $this->departmentRepository = $departmentRepository;
        $this->cityRepository = $cityRepository;
        $this->userRepository = $userRepository;
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
}
