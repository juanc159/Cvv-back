<?php

namespace App\Services;

use App\Helpers\PromotionNormalizer;
use App\Imports\PromotionFileImport;
use App\Models\City;
use App\Models\Country;
use App\Models\Grade;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchRow;
use App\Models\Section;
use App\Models\State;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use App\Models\Term;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Motor de promoción de alumnos de un año escolar al siguiente.
 *
 * Trabaja siempre en dos tiempos:
 *
 *   1. previsualizar(): lee el archivo, decide qué haría con cada fila y lo guarda,
 *      pero NO toca la tabla students. El usuario revisa el resultado en pantalla.
 *   2. aplicar(): escribe únicamente las filas que el usuario aprobó.
 *
 * La razón de separarlo es que este proceso mueve a cientos de alumnos de grado y
 * saca de su sección a los que no vienen en el archivo. Aplicarlo a ciegas, como se
 * hacía antes desde la ruta /actualizar, no deja margen para detectar un archivo
 * equivocado antes de que el daño esté hecho.
 */
class StudentPromotionService
{
    /** Columnas del archivo y las variantes con las que las escribe el colegio. */
    private const COLUMN_ALIASES = [
        'identity_document' => ['CEDULA', 'CEDULA ESCOLAR', 'C I', 'CI', 'DOCUMENTO', 'N CEDULA', 'NRO CEDULA', 'NUMERO DE CEDULA'],
        'full_name' => ['NOMBRE DEL ALUMNO', 'NOMBRE', 'NOMBRES', 'ALUMNO', 'ESTUDIANTE', 'NOMBRES Y APELLIDOS', 'APELLIDOS Y NOMBRES', 'NOMBRE Y APELLIDO'],
        'gender' => ['SEXO', 'GENERO'],
        'birthday' => ['FECHANAC', 'FECHA NAC', 'FECHA DE NACIMIENTO', 'NACIMIENTO', 'F NAC', 'FECHA NACIMIENTO'],
        'state' => ['ESTADO', 'DEPARTAMENTO'],
        'city' => ['MUNICIPIO', 'CIUDAD'],
        'country' => ['PAIS'],
    ];

    /** Índices en memoria de los alumnos del colegio, para no consultar fila por fila. */
    private array $byDocument = [];
    private array $byNameAndBirthday = [];
    private array $byName = [];

    /** Cachés de geografía, para no repetir consultas por cada fila del archivo. */
    private array $countryCache = [];
    private array $stateCache = [];
    private array $cityCache = [];

    /**
     * Analiza el archivo contra la base y deja el resultado guardado como un lote en
     * estado "preview". No modifica ningún alumno.
     *
     * @param  array  $input  company_id, term_id, type_education_id, grade_id, section_id, entry_date, user_id
     */
    public function preview(array $input, UploadedFile $file): PromotionBatch
    {
        $companyId = $input['company_id'];
        $gradeId = $input['grade_id'];
        $sectionId = $input['section_id'];

        $rows = $this->readFile($file);

        if (empty($rows)) {
            throw new \RuntimeException(json_encode([
                'message' => 'No se encontraron filas con datos en el archivo. Verifique que tenga una columna de cédula y otra de nombre.',
            ]));
        }

        $this->buildIndexes($companyId);

        $batch = PromotionBatch::create([
            'company_id' => $companyId,
            'term_id' => $input['term_id'],
            'user_id' => $input['user_id'] ?? null,
            'type_education_id' => $input['type_education_id'],
            'grade_id' => $gradeId,
            'section_id' => $sectionId,
            'file_name' => $file->getClientOriginalName(),
            'entry_date' => $input['entry_date'] ?? null,
            'status' => 'preview',
            'total_rows' => count($rows),
        ]);

        // Cédulas ya vistas en este mismo archivo, para detectar filas repetidas.
        $seenInFile = [];
        // Alumnos que el archivo reclama: los que estén en el destino y no aparezcan
        // aquí son los que salen de la sección.
        $claimedStudentIds = [];

        foreach ($rows as $row) {
            $classified = $this->classifyRow($row, $input, $seenInFile);

            if (! empty($classified['student_id'])) {
                $claimedStudentIds[] = $classified['student_id'];
            }

            $batch->rows()->create($classified);
        }

        foreach ($this->buildUnassignRows($companyId, $gradeId, $sectionId, $claimedStudentIds) as $unassignRow) {
            $batch->rows()->create($unassignRow);
        }

        $batch->update(['summary' => $this->summarize($batch)]);

        return $batch->fresh('rows');
    }

    /**
     * Aplica el lote. Solo escribe las filas aprobadas y aún no aplicadas.
     *
     * @param  array|null  $decisions  Mapa id_de_fila => bool con lo que el usuario aprobó
     *                                 en pantalla. Si viene null se respeta lo que ya
     *                                 tiene guardado cada fila.
     */
    public function apply(PromotionBatch $batch, ?array $decisions = null): PromotionBatch
    {
        if ($batch->status === 'applied') {
            throw new \RuntimeException(json_encode([
                'message' => 'Este lote ya fue aplicado. Genere una previsualización nueva si necesita repetir el proceso.',
            ]));
        }

        DB::transaction(function () use ($batch, $decisions) {
            if ($decisions !== null) {
                foreach ($batch->rows as $row) {
                    if (array_key_exists($row->id, $decisions)) {
                        $row->is_approved = (bool) $decisions[$row->id];
                        $row->save();
                    }
                }

                $batch->load('rows');
            }

            // Primero se vacía a los que salen. Así, si un alumno del archivo viniera a
            // ocupar el lugar de otro, la sección ya está liberada cuando se le asigna.
            $pending = $batch->rows
                ->where('is_applied', false)
                ->where('is_approved', true)
                ->where('action', '!=', PromotionBatchRow::ACTION_ERROR)
                ->sortBy(fn ($row) => $row->action === PromotionBatchRow::ACTION_UNASSIGN ? 0 : 1);

            $applied = 0;

            foreach ($pending as $row) {
                $this->applyRow($batch, $row);
                $applied++;
            }

            $batch->status = 'applied';
            $batch->applied_rows = $applied;
            $batch->applied_at = now();
            $batch->summary = $this->summarize($batch->fresh('rows'));
            $batch->save();
        });

        return $batch->fresh('rows');
    }

    // ------------------------------------------------------------------
    // Lectura del archivo
    // ------------------------------------------------------------------

    /**
     * Convierte el archivo en filas asociativas con las columnas que conocemos.
     *
     * Busca la fila de encabezados en vez de asumir que es la primera, porque los
     * listados del colegio suelen traer un título arriba.
     */
    private function readFile(UploadedFile $file): array
    {
        $sheets = Excel::toArray(new PromotionFileImport, $file);
        $sheet = $sheets[0] ?? [];

        $headerIndex = null;
        $columns = [];

        foreach ($sheet as $index => $line) {
            $found = $this->matchHeaders($line);

            // Se considera encabezado la primera fila donde aparezcan cédula y nombre.
            if (isset($found['identity_document'], $found['full_name'])) {
                $headerIndex = $index;
                $columns = $found;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \RuntimeException(json_encode([
                'message' => 'No se encontraron las columnas de cédula y nombre en el archivo. Revise que los encabezados estén escritos como "Cédula" y "Nombre del Alumno".',
            ]));
        }

        $rows = [];

        foreach (array_slice($sheet, $headerIndex + 1) as $offset => $line) {
            $data = [];

            foreach ($columns as $field => $columnIndex) {
                $data[$field] = isset($line[$columnIndex]) ? trim((string) $line[$columnIndex]) : '';
            }

            // Fila totalmente vacía: fin de la tabla o hueco intermedio.
            if (($data['identity_document'] ?? '') === '' && ($data['full_name'] ?? '') === '') {
                continue;
            }

            // Número de fila tal como lo ve el usuario en Excel (base 1).
            $data['row_number'] = $headerIndex + $offset + 2;
            $rows[] = $data;
        }

        return $rows;
    }

    /**
     * Ubica en qué posición está cada columna conocida dentro de una fila candidata
     * a encabezado.
     */
    private function matchHeaders(array $line): array
    {
        $found = [];

        foreach ($line as $columnIndex => $value) {
            $header = PromotionNormalizer::name($value);

            if ($header === '') {
                continue;
            }

            foreach (self::COLUMN_ALIASES as $field => $aliases) {
                if (isset($found[$field])) {
                    continue;
                }

                if (in_array($header, $aliases, true)) {
                    $found[$field] = $columnIndex;
                }
            }
        }

        return $found;
    }

    // ------------------------------------------------------------------
    // Emparejamiento
    // ------------------------------------------------------------------

    /**
     * Carga en memoria a los alumnos del colegio, indexados por las tres llaves que
     * se usan para emparejar.
     *
     * Son alrededor de mil quinientos registros por colegio, así que cabe de sobra en
     * memoria y evita hacer una consulta por cada fila del archivo.
     */
    private function buildIndexes(string $companyId): void
    {
        $this->byDocument = [];
        $this->byNameAndBirthday = [];
        $this->byName = [];

        // Nombres repetidos: si dos alumnos comparten nombre normalizado, emparejar por
        // nombre deja de ser seguro y hay que descartar esa llave para ambos.
        $nameCollisions = [];
        $nameBirthdayCollisions = [];

        Student::where('company_id', $companyId)
            ->select(['id', 'identity_document', 'full_name', 'birthday', 'grade_id', 'section_id', 'type_education_id', 'type_document_id', 'is_active'])
            ->cursor()
            ->each(function (Student $student) use (&$nameCollisions, &$nameBirthdayCollisions) {
                $document = PromotionNormalizer::document($student->identity_document);
                $name = PromotionNormalizer::name($student->full_name);

                if ($document !== '') {
                    $this->byDocument[$document] = $student;
                }

                if ($name === '') {
                    return;
                }

                if (isset($this->byName[$name])) {
                    $nameCollisions[$name] = true;
                }
                $this->byName[$name] = $student;

                if ($student->birthday) {
                    $key = $name . '|' . \Carbon\Carbon::parse($student->birthday)->format('Y-m-d');

                    if (isset($this->byNameAndBirthday[$key])) {
                        $nameBirthdayCollisions[$key] = true;
                    }
                    $this->byNameAndBirthday[$key] = $student;
                }
            });

        // Las llaves ambiguas se eliminan: es preferible que la fila salga como
        // "no encontrado" y la revise una persona, a moverle el grado al alumno equivocado.
        foreach (array_keys($nameCollisions) as $name) {
            unset($this->byName[$name]);
        }

        foreach (array_keys($nameBirthdayCollisions) as $key) {
            unset($this->byNameAndBirthday[$key]);
        }
    }

    /**
     * Decide qué hacer con una fila del archivo.
     *
     * El orden de búsqueda va de más a menos confiable: cédula exacta, cédula sin ceros
     * a la izquierda, nombre + fecha de nacimiento, y por último solo nombre. Todo lo que
     * no sea coincidencia por cédula queda marcado para que una persona lo confirme.
     */
    private function classifyRow(array $row, array $input, array &$seenInFile): array
    {
        $document = PromotionNormalizer::document($row['identity_document'] ?? '');
        $name = PromotionNormalizer::name($row['full_name'] ?? '');
        $birthday = PromotionNormalizer::date($row['birthday'] ?? null);

        $result = [
            'row_number' => $row['row_number'] ?? null,
            'identity_document_raw' => $row['identity_document'] ?? null,
            'identity_document_normalized' => $document,
            'full_name_raw' => $row['full_name'] ?? null,
            'payload' => [
                'gender' => PromotionNormalizer::gender($row['gender'] ?? null),
                'birthday' => $birthday,
                'country' => $row['country'] ?? null,
                'state' => $row['state'] ?? null,
                'city' => $row['city'] ?? null,
            ],
            'messages' => [],
            'match_type' => PromotionBatchRow::MATCH_NONE,
            'needs_review' => false,
            'is_approved' => true,
        ];

        if ($document === '' && $name === '') {
            return array_merge($result, [
                'action' => PromotionBatchRow::ACTION_ERROR,
                'is_approved' => false,
                'messages' => ['La fila no tiene cédula ni nombre.'],
            ]);
        }

        // Repetido dentro del mismo archivo: se procesa la primera aparición y se avisa.
        $fileKey = $document !== '' ? 'D:' . $document : 'N:' . $name;

        if (isset($seenInFile[$fileKey])) {
            return array_merge($result, [
                'action' => PromotionBatchRow::ACTION_ERROR,
                'is_approved' => false,
                'messages' => ['Está repetido en este mismo archivo (ya aparece en la fila ' . $seenInFile[$fileKey] . ').'],
            ]);
        }

        $seenInFile[$fileKey] = $row['row_number'] ?? '?';

        [$student, $matchType, $matchMessage] = $this->findStudent($document, $name, $birthday);

        if (! $student) {
            return array_merge($result, $this->buildCreateRow($row, $document, $name, $birthday, $input));
        }

        $result['student_id'] = $student->id;
        $result['match_type'] = $matchType;
        $result['previous_grade_id'] = $student->grade_id;
        $result['previous_section_id'] = $student->section_id;
        $result['previous_grade_name'] = $student->grade_id ? $this->gradeName($student->grade_id) : null;
        $result['previous_section_name'] = $student->section_id ? $this->sectionName($student->section_id) : null;

        $messages = [];

        if ($matchMessage) {
            $messages[] = $matchMessage;
        }

        // Coincidió por nombre, así que la cédula del archivo manda sobre la de la base.
        if ($matchType !== PromotionBatchRow::MATCH_EXACT && $document !== '') {
            $currentDocument = PromotionNormalizer::document($student->identity_document);

            if ($currentDocument !== $document) {
                $conflict = $this->documentBelongsToSomeoneElse($document, $student->id, $input['company_id']);

                if ($conflict) {
                    return array_merge($result, [
                        'action' => PromotionBatchRow::ACTION_ERROR,
                        'is_approved' => false,
                        'needs_review' => true,
                        'messages' => array_merge($messages, [
                            'La cédula ' . $document . ' ya pertenece a otro registro (' . $conflict . '). Puede ser un alumno duplicado: revíselo manualmente antes de continuar.',
                        ]),
                    ]);
                }

                $result['previous_identity_document'] = $student->identity_document;
                $result['needs_review'] = true;
                $result['is_approved'] = false;
                $messages[] = 'Cambia de cédula ' . $student->identity_document . ' a ' . $document . '. Confirme que es correcto.';
            }
        }

        $withdrawal = $this->activeWithdrawal($student->id);
        $sameGrade = $student->grade_id === $input['grade_id'];
        $sameSection = $student->section_id === $input['section_id'];

        if ($withdrawal || ! $student->is_active) {
            $action = PromotionBatchRow::ACTION_REINSTATE;
            $messages[] = $withdrawal
                ? 'Estaba de baja desde el ' . $withdrawal->date . '. Al aplicar se reintegra.'
                : 'Estaba inactivo. Al aplicar se reactiva.';
        } elseif (! $student->grade_id) {
            $action = PromotionBatchRow::ACTION_REINSTATE;
            $messages[] = 'No tenía grado ni sección asignados. Al aplicar queda inscrito de nuevo.';
        } elseif ($sameGrade && $sameSection) {
            $action = PromotionBatchRow::ACTION_STAY;
            $messages[] = 'Ya estaba en este grado y sección.';
        } elseif ($sameGrade) {
            $action = PromotionBatchRow::ACTION_REPEAT;
            $messages[] = 'Repite grado (venía de la sección ' . ($result['previous_section_name'] ?? '-') . ').';
        } else {
            $action = PromotionBatchRow::ACTION_PROMOTE;
        }

        $result['action'] = $action;
        $result['messages'] = $messages;

        return $result;
    }

    /**
     * Busca al alumno en cascada. Devuelve [alumno, tipo de coincidencia, aviso].
     */
    private function findStudent(string $document, string $name, ?string $birthday): array
    {
        if ($document !== '' && isset($this->byDocument[$document])) {
            return [$this->byDocument[$document], PromotionBatchRow::MATCH_EXACT, null];
        }

        // Segundo intento con la cédula sin ceros a la izquierda.
        if ($document !== '') {
            $trimmed = ltrim($document, '0');

            if ($trimmed !== '' && $trimmed !== $document && isset($this->byDocument[$trimmed])) {
                return [$this->byDocument[$trimmed], PromotionBatchRow::MATCH_EXACT, 'La cédula del archivo trae ceros a la izquierda.'];
            }
        }

        if ($name === '') {
            return [null, PromotionBatchRow::MATCH_NONE, null];
        }

        if ($birthday) {
            $key = $name . '|' . $birthday;

            if (isset($this->byNameAndBirthday[$key])) {
                return [
                    $this->byNameAndBirthday[$key],
                    PromotionBatchRow::MATCH_NAME_BIRTHDAY,
                    'No se encontró la cédula, pero coincide el nombre y la fecha de nacimiento.',
                ];
            }
        }

        if (isset($this->byName[$name])) {
            return [
                $this->byName[$name],
                PromotionBatchRow::MATCH_NAME,
                'No se encontró la cédula. Coincide solo por nombre: verifique que sea la misma persona.',
            ];
        }

        return [null, PromotionBatchRow::MATCH_NONE, null];
    }

    /**
     * Arma la fila para un alumno que no existe todavía.
     *
     * Los datos que no vienen en el archivo se completan con lo que se puede deducir
     * (tipo de educación y grado del destino, tipo de documento por la longitud de la
     * cédula, fecha de ingreso del período). Lo que no se puede resolver, como un
     * municipio que no está cargado, se deja vacío y se avisa en lugar de bloquear el alta.
     */
    private function buildCreateRow(array $row, string $document, string $name, ?string $birthday, array $input): array
    {
        $messages = ['No existe en el sistema: se creará como alumno nuevo.'];

        if ($document === '') {
            return [
                'action' => PromotionBatchRow::ACTION_ERROR,
                'is_approved' => false,
                'needs_review' => true,
                'messages' => ['No existe en el sistema y la fila no trae cédula, así que no se puede crear.'],
            ];
        }

        if ($this->documentBelongsToSomeoneElse($document, null, $input['company_id'])) {
            return [
                'action' => PromotionBatchRow::ACTION_ERROR,
                'is_approved' => false,
                'needs_review' => true,
                'messages' => ['La cédula ' . $document . ' ya está registrada con otro nombre. Revise el caso manualmente.'],
            ];
        }

        $geo = $this->resolveGeography($row, $messages);

        if (! $birthday) {
            $messages[] = 'Sin fecha de nacimiento en el archivo: quedará vacía.';
        }

        return [
            'action' => PromotionBatchRow::ACTION_CREATE,
            'match_type' => PromotionBatchRow::MATCH_NONE,
            'needs_review' => true,
            'is_approved' => false,
            'messages' => $messages,
            'payload' => [
                'gender' => PromotionNormalizer::gender($row['gender'] ?? null),
                'birthday' => $birthday,
                'country' => $row['country'] ?? null,
                'state' => $row['state'] ?? null,
                'city' => $row['city'] ?? null,
                'country_id' => $geo['country_id'],
                'state_id' => $geo['state_id'],
                'city_id' => $geo['city_id'],
                'type_document_id' => $this->documentTypeIdFor($document),
            ],
        ];
    }

    /**
     * Genera las filas de los alumnos que estaban en el grado y sección destino y que
     * ningún renglón del archivo reclamó.
     *
     * Al aplicarse quedan sin grado ni sección, pero activos: es el estado que el colegio
     * ya venía usando para "todavía no se ha inscrito". No se desactivan ni se borran,
     * porque lo más probable es que aparezcan en el archivo del grado siguiente.
     */
    private function buildUnassignRows(string $companyId, string $gradeId, string $sectionId, array $claimedStudentIds): array
    {
        $query = Student::where('company_id', $companyId)
            ->where('grade_id', $gradeId)
            ->where('section_id', $sectionId);

        if (! empty($claimedStudentIds)) {
            $query->whereNotIn('id', $claimedStudentIds);
        }

        $rows = [];

        foreach ($query->get() as $student) {
            $rows[] = [
                'row_number' => null,
                'action' => PromotionBatchRow::ACTION_UNASSIGN,
                'match_type' => null,
                'student_id' => $student->id,
                'identity_document_raw' => $student->identity_document,
                'identity_document_normalized' => PromotionNormalizer::document($student->identity_document),
                'full_name_raw' => $student->full_name,
                'previous_grade_id' => $student->grade_id,
                'previous_section_id' => $student->section_id,
                'previous_grade_name' => $this->gradeName($student->grade_id),
                'previous_section_name' => $this->sectionName($student->section_id),
                'needs_review' => false,
                'is_approved' => true,
                'messages' => ['Estaba en este grado y sección pero no viene en el archivo: quedará sin grado ni sección.'],
            ];
        }

        return $rows;
    }

    // ------------------------------------------------------------------
    // Escritura
    // ------------------------------------------------------------------

    /**
     * Aplica una fila ya aprobada.
     *
     * Todo se guarda con Eloquent y no con consultas masivas a propósito: el
     * StudentObserver sincroniza la cédula, el nombre y el estado hacia la tabla users,
     * que es por donde entra el alumno al sistema. Un upsert directo se saltaría ese
     * observador y dejaría al alumno sin poder iniciar sesión con su cédula nueva.
     */
    private function applyRow(PromotionBatch $batch, PromotionBatchRow $row): void
    {
        if ($row->action === PromotionBatchRow::ACTION_CREATE) {
            $this->createStudent($batch, $row);
            $row->is_applied = true;
            $row->save();

            return;
        }

        $student = Student::find($row->student_id);

        if (! $student) {
            $row->action = PromotionBatchRow::ACTION_ERROR;
            $row->messages = array_merge($row->messages ?? [], ['El alumno ya no existe en el sistema.']);
            $row->save();

            return;
        }

        if ($row->action === PromotionBatchRow::ACTION_UNASSIGN) {
            $student->grade_id = null;
            $student->section_id = null;
            $student->save();

            $row->is_applied = true;
            $row->save();

            return;
        }

        $student->type_education_id = $batch->type_education_id;
        $student->grade_id = $batch->grade_id;
        $student->section_id = $batch->section_id;

        // La cédula del archivo es la más reciente: si el alumno sacó su cédula de
        // identidad, se actualiza junto con el tipo de documento.
        if ($row->previous_identity_document && $row->identity_document_normalized) {
            $student->identity_document = $row->identity_document_normalized;
            $student->type_document_id = $this->documentTypeIdFor($row->identity_document_normalized);
        }

        if ($row->action === PromotionBatchRow::ACTION_REINSTATE) {
            $student->is_active = true;
            $this->closeWithdrawal($student->id);
        }

        $student->save();

        $row->is_applied = true;
        $row->save();
    }

    /**
     * Da de alta a un alumno nuevo con su usuario de acceso.
     *
     * Replica lo que hace StudentController::store: primero el usuario (que es por donde
     * se inicia sesión) y después el alumno apuntando a él. La contraseña inicial es la
     * cédula, igual que en el alta manual.
     */
    private function createStudent(PromotionBatch $batch, PromotionBatchRow $row): void
    {
        $payload = $row->payload ?? [];
        $document = $row->identity_document_normalized;
        $fullName = trim((string) $row->full_name_raw);

        $parts = explode(' ', $fullName);
        $name = $parts[0];
        $surname = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';

        $user = User::create([
            'name' => $name,
            'surname' => $surname,
            'identity_document' => $document,
            'password' => Hash::make($document),
            'type_user' => 'student',
            'is_active' => true,
            'company_id' => $batch->company_id,
        ]);

        $student = Student::create([
            'company_id' => $batch->company_id,
            'user_id' => $user->id,
            'type_education_id' => $batch->type_education_id,
            'grade_id' => $batch->grade_id,
            'section_id' => $batch->section_id,
            'identity_document' => $document,
            'full_name' => $fullName,
            'password' => $document,
            'type_document_id' => $payload['type_document_id'] ?? null,
            'gender' => $payload['gender'] ?? null,
            'birthday' => $payload['birthday'] ?? null,
            'country_id' => $payload['country_id'] ?? null,
            'state_id' => $payload['state_id'] ?? null,
            'city_id' => $payload['city_id'] ?? null,
            'real_entry_date' => $batch->entry_date,
            'is_active' => true,
            'first_time' => true,
        ]);

        $row->student_id = $student->id;
    }

    /**
     * Cierra la baja vigente de un alumno que vuelve a inscribirse, sin borrar el
     * registro histórico de que estuvo retirado.
     */
    private function closeWithdrawal(string $studentId): void
    {
        StudentWithdrawal::where('student_id', $studentId)
            ->whereNull('reactivated_at')
            ->update(['reactivated_at' => now()]);
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    private function activeWithdrawal(string $studentId): ?StudentWithdrawal
    {
        return StudentWithdrawal::where('student_id', $studentId)
            ->whereNull('reactivated_at')
            ->first();
    }

    /**
     * Comprueba si una cédula ya está ocupada por otro registro.
     *
     * Mira tanto students como users: users.identity_document tiene un índice único
     * global, así que un choque ahí rompería el alta o el cambio de cédula aunque en
     * students no hubiera conflicto.
     */
    private function documentBelongsToSomeoneElse(string $document, ?string $exceptStudentId, string $companyId): ?string
    {
        $student = Student::where('company_id', $companyId)
            ->where('identity_document', $document)
            ->when($exceptStudentId, fn ($query) => $query->where('id', '!=', $exceptStudentId))
            ->first();

        if ($student) {
            return $student->full_name;
        }

        $userQuery = User::where('identity_document', $document);

        if ($exceptStudentId) {
            $current = Student::find($exceptStudentId);

            if ($current && $current->user_id) {
                $userQuery->where('id', '!=', $current->user_id);
            }
        }

        $user = $userQuery->first();

        return $user ? trim($user->name . ' ' . $user->surname) . ' (usuario del sistema)' : null;
    }

    /**
     * Resuelve país, estado y municipio contra las tablas de geografía.
     *
     * No crea nada: si el municipio no existe, el alumno se da de alta igual con el
     * campo vacío y queda el aviso para revisarlo después. Bloquear el alta por un
     * municipio faltante sería peor que dejar el dato incompleto.
     */
    private function resolveGeography(array $row, array &$messages): array
    {
        $countryName = PromotionNormalizer::place($row['country'] ?? null);
        $stateName = PromotionNormalizer::place($row['state'] ?? null);
        $cityName = PromotionNormalizer::place($row['city'] ?? null);

        $countryId = $countryName !== '' ? $this->findCountryId($countryName) : null;

        if ($countryName !== '' && ! $countryId) {
            $messages[] = 'El país "' . $row['country'] . '" no está registrado: quedará vacío.';
        }

        $stateId = ($stateName !== '' && $countryId) ? $this->findStateId($countryId, $stateName) : null;

        if ($stateName !== '' && ! $stateId) {
            $messages[] = 'El estado "' . $row['state'] . '" no está registrado: quedará vacío.';
        }

        $cityId = ($cityName !== '' && $stateId) ? $this->findCityId($stateId, $cityName) : null;

        if ($cityName !== '' && ! $cityId) {
            $messages[] = 'El municipio "' . $row['city'] . '" no está registrado en el estado indicado: quedará vacío.';
        }

        return ['country_id' => $countryId, 'state_id' => $stateId, 'city_id' => $cityId];
    }

    private function findCountryId(string $normalizedName)
    {
        if (! array_key_exists('all', $this->countryCache)) {
            $this->countryCache['all'] = Country::select('id', 'name')->get()
                ->mapWithKeys(fn ($country) => [PromotionNormalizer::place($country->name) => $country->id])
                ->all();
        }

        return $this->countryCache['all'][$normalizedName] ?? null;
    }

    private function findStateId($countryId, string $normalizedName)
    {
        if (! isset($this->stateCache[$countryId])) {
            $this->stateCache[$countryId] = State::where('country_id', $countryId)->select('id', 'name')->get()
                ->mapWithKeys(fn ($state) => [PromotionNormalizer::place($state->name) => $state->id])
                ->all();
        }

        return $this->stateCache[$countryId][$normalizedName] ?? null;
    }

    private function findCityId($stateId, string $normalizedName)
    {
        if (! isset($this->cityCache[$stateId])) {
            // Hay municipios cargados dos veces con distinta grafía; nos quedamos con el
            // primero de forma estable para que dos cargas iguales den el mismo resultado.
            $this->cityCache[$stateId] = City::where('state_id', $stateId)->orderBy('id')->select('id', 'name')->get()
                ->reduce(function ($carry, $city) {
                    $key = PromotionNormalizer::place($city->name);
                    $carry[$key] = $carry[$key] ?? $city->id;

                    return $carry;
                }, []) ?? [];
        }

        return $this->cityCache[$stateId][$normalizedName] ?? null;
    }

    private function documentTypeIdFor(string $document)
    {
        $name = PromotionNormalizer::guessDocumentTypeName($document);

        if (! isset($this->countryCache['type_documents'])) {
            $this->countryCache['type_documents'] = TypeDocument::select('id', 'name')->get()
                ->mapWithKeys(fn ($type) => [PromotionNormalizer::place($type->name) => $type->id])
                ->all();
        }

        return $this->countryCache['type_documents'][PromotionNormalizer::place($name)] ?? null;
    }

    private function gradeName(?string $gradeId): ?string
    {
        if (! $gradeId) {
            return null;
        }

        return Grade::find($gradeId)?->name;
    }

    private function sectionName(?string $sectionId): ?string
    {
        if (! $sectionId) {
            return null;
        }

        return Section::find($sectionId)?->name;
    }

    /**
     * Deshace un lote ya aplicado y devuelve a cada alumno a donde estaba.
     *
     * Existe porque el error más probable de este proceso es humano: subir el archivo
     * equivocado o elegir mal el grado destino. Sin una forma de deshacerlo, arreglar eso
     * significa reconstruir a mano decenas de inscripciones. Cada fila guardó el grado, la
     * sección y la cédula que el alumno tenía antes, así que la vuelta atrás es exacta.
     *
     * Los alumnos que el lote creó se eliminan junto con su usuario: no existían antes.
     */
    public function revert(PromotionBatch $batch): PromotionBatch
    {
        if ($batch->status !== 'applied') {
            throw new \RuntimeException(json_encode([
                'message' => 'Solo se puede deshacer un lote que haya sido aplicado.',
            ]));
        }

        DB::transaction(function () use ($batch) {
            // Al revés que al aplicar: primero se devuelve a su sitio a quien fue movido y
            // al final se reponen los que habían salido de la sección.
            $rows = $batch->rows
                ->where('is_applied', true)
                ->sortByDesc(fn ($row) => $row->action === PromotionBatchRow::ACTION_UNASSIGN ? 0 : 1);

            foreach ($rows as $row) {
                if ($row->action === PromotionBatchRow::ACTION_CREATE) {
                    $student = Student::find($row->student_id);

                    if ($student) {
                        $userId = $student->user_id;
                        $student->delete();

                        if ($userId) {
                            User::where('id', $userId)->delete();
                        }
                    }

                    $row->is_applied = false;
                    $row->save();

                    continue;
                }

                $student = Student::find($row->student_id);

                if (! $student) {
                    continue;
                }

                $student->grade_id = $row->previous_grade_id;
                $student->section_id = $row->previous_section_id;

                // El tipo de educación se deduce del grado anterior, que es de donde venía.
                if ($row->previous_grade_id) {
                    $previousGrade = Grade::find($row->previous_grade_id);

                    if ($previousGrade) {
                        $student->type_education_id = $previousGrade->type_education_id;
                    }
                }

                // Si el lote le cambió la cédula, se le devuelve la que tenía.
                if ($row->previous_identity_document) {
                    $student->identity_document = $row->previous_identity_document;
                    $student->type_document_id = $this->documentTypeIdFor(
                        PromotionNormalizer::document($row->previous_identity_document)
                    );
                }

                // Si se reintegró a un alumno que estaba de baja, la baja vuelve a quedar abierta.
                if ($row->action === PromotionBatchRow::ACTION_REINSTATE && $batch->applied_at) {
                    StudentWithdrawal::where('student_id', $student->id)
                        ->whereNotNull('reactivated_at')
                        ->where('reactivated_at', '>=', $batch->applied_at)
                        ->update(['reactivated_at' => null]);
                }

                $student->save();

                $row->is_applied = false;
                $row->save();
            }

            $batch->status = 'reverted';
            $batch->applied_rows = 0;
            $batch->save();
        });

        return $batch->fresh('rows');
    }


    /**
     * Cuenta cuántas filas hay de cada acción, para el resumen que se muestra arriba
     * de la previsualización.
     */
    private function summarize(PromotionBatch $batch): array
    {
        $counts = [];

        foreach ($batch->rows as $row) {
            $counts[$row->action] = ($counts[$row->action] ?? 0) + 1;
        }

        $counts['needs_review'] = $batch->rows->where('needs_review', true)->count();

        return $counts;
    }
}
