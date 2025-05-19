<?php

namespace App\Http\Controllers;

use App\Http\Resources\Country\CountrySelectResource;
use App\Http\Resources\Grade\GradeSelectInifiniteResource;
use App\Http\Resources\Student\StudentSelectInifiniteResource;
use App\Http\Resources\TypeEducation\TypeEducationSelectResource;
use App\Repositories\CityRepository;
use App\Repositories\CountryRepository;
use App\Repositories\GradeRepository;
use App\Repositories\SectionRepository;
use App\Repositories\StateRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TypeEducationRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function __construct(
        protected CountryRepository $countryRepository,
        protected StateRepository $stateRepository,
        protected CityRepository $cityRepository,
        protected UserRepository $userRepository,
        protected TypeEducationRepository $typeEducationRepository,
        protected GradeRepository $gradeRepository,
        protected SectionRepository $sectionRepository,
        protected StudentRepository $studentRepository,
    ) {}

    public function selectInfiniteCountries(Request $request)
    {
        $countries = $this->countryRepository->list($request->all());

        $dataCountries = CountrySelectResource::collection($countries);

        return [
            'code' => 200,
            'countries_arrayInfo' => $dataCountries,
            'countries_countLinks' => $countries->lastPage(),
        ];
    }

    public function selectStates($country_id)
    {
        $states = $this->stateRepository->selectList($country_id);

        return [
            'code' => 200,
            'states' => $states,
        ];
    }

    public function selectCities($state_id)
    {
        $cities = $this->cityRepository->selectList($state_id);

        return [
            'code' => 200,
            'cities' => $cities,
        ];
    }

    public function selectCitiesCountry($country_id)
    {
        $country = $this->countryRepository->find($country_id, ['cities']);

        return response()->json([
            'code' => 200,
            'message' => 'Datos Encontrados',
            'cities' => $country['cities']->map(function ($item) {
                return [
                    'value' => $item->id,
                    'title' => $item->name,
                ];
            }),
        ]);
    }

    public function selectInifiniteTypeEducation(Request $request)
    {
        $request['status'] = 1;
        $typeEducation = $this->typeEducationRepository->list($request->all());
        $dataTypeEducation = TypeEducationSelectResource::collection($typeEducation);

        return [
            'code' => 200,
            'typeEducation_arrayInfo' => $dataTypeEducation,
            'typeEducation_countLinks' => $typeEducation->lastPage(),
        ];
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
    public function selectInfiniteStudent(Request $request)
    {
        $student = $this->studentRepository->list($request->all());
        $dataStudent = StudentSelectInifiniteResource::collection($student);

        return [
            'code' => 200,
            'student_arrayInfo' => $dataStudent,
            'student_countLinks' => $student->lastPage(),
        ];
    }

    public function autoCompleteDataStudents(Request $request)
    {
        $data = $this->studentRepository->selectList($request->all(), fieldTitle: 'full_name', limit: 10);

        return [
            'code' => 200,
            'data' => $data,
        ];
    }
}
