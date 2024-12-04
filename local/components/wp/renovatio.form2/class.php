<?php

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Lcli\Iblock\ElementPropertyTable;

class RenovatioForm2Component extends \CBitrixComponent implements Controllerable
{
    private const DOCTORS_IBLOCK_ID = 8;
    private const MEDICAL_UNITS_IBLOCK_ID = 88;
    private const FACE_TO_FACE_RECEPTION_XML_ID = 'xxx888';
    private const ONLINE_RECEPTION_XML_ID = 'yyy888';

    private const RENOVATIO_DEF_PATIENT_ID = 88888888;
    private const RENOVATIO_API_KEY = '888';
    private const RENOVATIO_API_URL = 'https://app.rnova.org/api/public/';

    private bool $isLog = false;

    public function configureActions(): array
    {
        return [
            'submit' => [
                '-prefilters' => [
                    '\Bitrix\Main\Engine\ActionFilter\Authentication'
                ],
            ],
            'getTime' => [
                '-prefilters' => [
                    '\Bitrix\Main\Engine\ActionFilter\Authentication'
                ],
            ],
        ];
    }

    private function check(array $data, bool $checkMoreData = false): array
    {
        if (
            !isset($data['type'])
            || !isset($data['department'])
            || !isset($data['doctor'])
            || !isset($data['time'])
            || !isset($data['name'])
            || !isset($data['last_name'])
            || !isset($data['second_name'])
            || !isset($data['birthdate'])
            || !isset($data['phone'])
            || !in_array($data['type'], ['ONLINE_RECEPTION', 'FACE_TO_FACE_RECEPTION'])
            || !is_numeric($data['doctor'])
        ) {
            return [];
        }

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'check1' => 1,
                ], true) . PHP_EOL, FILE_APPEND);

        $params = $this->getData();

        $doctorName = null;
        $doctor = (string) $data['doctor'];
        foreach ($params['DOCTORS'] as $doctorV) {
            if ($doctor === $doctorV['RENOVATIO_ID']) {
                $doctorName = $doctorV['NAME'];
                break;
            }
        }
        if ($doctorName === null) {
            return [];
        }

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'check2' => 1,
                ], true) . PHP_EOL, FILE_APPEND);

        $time = (array) json_decode((string) $data['time'], true);

        if (
            !isset($time['clinicId'])
            || !isset($time['date'])
            || !isset($time['start'])
            || !isset($time['end'])
            || !is_numeric($time['clinicId'])
            || !is_string($time['date'])
            || !is_string($time['start'])
            || !is_string($time['end'])
            || !preg_match('/^\d\d\.\d\d\.\d\d\d\d$/', $time['date'])
            || !preg_match('/^\d\d:\d\d$/', $time['start'])
            || !preg_match('/^\d\d:\d\d$/', $time['end'])
        ) {
            return [];
        }

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'check3' => 1,
                ], true) . PHP_EOL, FILE_APPEND);

        $name = (string) $data['name'];
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        if ($name === '') {
            return [];
        }

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'check4' => 1,
                ], true) . PHP_EOL, FILE_APPEND);

        $phone = (string) $data['phone'];
        if (!preg_match('/^\+7 \(\d\d\d\) \d\d\d-\d\d-\d\d$/', $phone)) {
            return [];
        }

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'check5' => 1,
                ], true) . PHP_EOL, FILE_APPEND);

        $res = [
            'doctorName' => $doctorName,
            'departmentName' => '',
            'name' => $name,
            'time' => $time,
        ];

        $departmentCode = (string) $data['department'];
        if (isset($params['DEPARTMENTS'][$departmentCode])) {
            $res['departmentName'] = $params['DEPARTMENTS'][$departmentCode]['NAME'];
        }

        if ($checkMoreData) {

            if ($this->isLog)
                file_put_contents(__FILE__ . '.log', var_export([
                        'check6' => 1,
                    ], true) . PHP_EOL, FILE_APPEND);

            $lastName = (string) $data['last_name'];
            $lastName = trim($lastName);
            if ($lastName === '') {
                return [];
            }
            $res['lastName'] = $lastName;

            if ($this->isLog)
                file_put_contents(__FILE__ . '.log', var_export([
                        'check7' => 1,
                    ], true) . PHP_EOL, FILE_APPEND);

            $secondName = (string) $data['second_name'];
            $secondName = trim($secondName);
            if ($secondName === '') {
                return [];
            }
            $res['secondName'] = $secondName;

            $birthdate = (string) $data['birthdate'];
            if (!preg_match('/^\d\d\.\d\d\.\d\d\d\d$/', $birthdate)) {
                return [];
            }

            if ($this->isLog)
                file_put_contents(__FILE__ . '.log', var_export([
                        'check8' => 1,
                    ], true) . PHP_EOL, FILE_APPEND);
        }

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'check9' => 1,
                    '$res' => $res,
                ], true) . PHP_EOL, FILE_APPEND);

        return $res;
    }

    private function getIdenticalPatient(array $data): string
    {
        return '<a href="/patients/default/detail/id/'
            . $data['patient_id'] . '" target="_blank">'
            . $data['last_name']
            . ' ' . $data['first_name']
            . ' ' . $data['third_name']
            . '</a>';
    }

    public function submitAction(array $data): array
    {
        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'start' => 1,
                    '$data' => $data,
                ], true) . PHP_EOL, FILE_APPEND);

        $checkRes = $this->check($data);
        if (!$checkRes) {
            return ['msg' => ['Ошибка']];
        }

        $patientId = null;
        $isCreatePatient = false;
        $identicalPatients = [];
        $identicalPatientOnCreate = false;

        $nameExpl = explode(' ', $checkRes['name']);
        if (!isset($nameExpl[1])) {

            if ($this->isLog)
                file_put_contents(__FILE__ . '.log', var_export([
                        '!isset($nameExpl[1])' => 1,
                    ], true) . PHP_EOL, FILE_APPEND);

            $getPatientRes = $this->callApiMethod('getPatient', [
                'mobile' => $data['phone'],
                'first_name' => '',
            ]);

            $patientOneAmongSeveral = [];

            if (isset($getPatientRes['data']['patient_id'])) {

                if (strtolower($getPatientRes['data']['first_name']) === strtolower($checkRes['name'])) {

                    $patientId = $getPatientRes['data']['patient_id'];

                    if ($this->isLog)
                        file_put_contents(__FILE__ . '.log', var_export([
                                'res' => 1,
                                '$patientId' => $patientId,
                            ], true) . PHP_EOL, FILE_APPEND);
                }
                else {
                    $identicalPatients[] = $this->getIdenticalPatient($getPatientRes['data']);

                    if ($this->isLog)
                        file_put_contents(__FILE__ . '.log', var_export([
                                'res' => 2,
                                '$identicalPatients' => $identicalPatients,
                            ], true) . PHP_EOL, FILE_APPEND);
                }
            }
            elseif (
                is_array($getPatientRes['data'])
                && $getPatientRes['data']
            ) {
                foreach ($getPatientRes['data'] as $patient) {

                    $identicalPatients[] = $this->getIdenticalPatient($patient);

                    if (strtolower($patient['first_name']) === strtolower($checkRes['name'])) {
                        $patientOneAmongSeveral[] = $patient['patient_id'];
                    }
                }

                if ($this->isLog)
                    file_put_contents(__FILE__ . '.log', var_export([
                            'res' => 3,
                            '$identicalPatients' => $identicalPatients,
                            '$patientOneAmongSeveral' => $patientOneAmongSeveral,
                        ], true) . PHP_EOL, FILE_APPEND);
            }

            if (count($patientOneAmongSeveral) === 1) {

                $patientId = $patientOneAmongSeveral[0];

                if ($this->isLog)
                    file_put_contents(__FILE__ . '.log', var_export([
                            'res' => 4,
                            '$patientId' => $patientId,
                        ], true) . PHP_EOL, FILE_APPEND);
            }

            if ($this->isLog)
                file_put_contents(__FILE__ . '.log', var_export([
                        'res' => 5,
                        '$getPatientRes' => $getPatientRes,
                        '$identicalPatients' => $identicalPatients,
                    ], true) . PHP_EOL, FILE_APPEND);

            if (
                $patientId === null
                && !$patientOneAmongSeveral
            ) {

                if ($this->isLog)
                    file_put_contents(__FILE__ . '.log', var_export([
                            'check_more' => 1,
                            '$patientId' => $patientId,
                        ], true) . PHP_EOL, FILE_APPEND);

                $checkRes = $this->check($data, true);
                if (!$checkRes) {

                    if ($this->isLog)
                        file_put_contents(__FILE__ . '.log', var_export([
                                'check_more' => 2,
                            ], true) . PHP_EOL, FILE_APPEND);

                    return [
                        'msg' => ['Нужно заполнить дополнительные поля'],
                        'needMoreData' => true,
                    ];
                }
                else {

                    $isCreatePatient = true;

                    $createParams = [
                        'last_name' => $checkRes['lastName'],
                        'first_name' => $checkRes['name'],
                        'third_name' => $checkRes['secondName'],
                        'birth_date' => $data['birthdate'],
                        'mobile' => $data['phone'],
                    ];

                    $gateRes = $this->callApiMethod('createPatient', $createParams);

                    if ($this->isLog)
                        file_put_contents(__FILE__ . '.log', var_export([
                                'createPatient' => 1,
                                '$gateRes' => $gateRes,
                            ], true) . PHP_EOL, FILE_APPEND);

                    if (isset($gateRes['data']['patient_id'])) {
                        $patientId = $gateRes['data']['patient_id'];
                    }
                    elseif (
                        isset($gateRes['error'], $gateRes['data']['code'], $gateRes['data']['desc'])
                        && $gateRes['error'] === 1
                        && $gateRes['data']['code'] === 500
                    ) {
                        if ($gateRes['data']['desc'] === 'Такой пациент уже существует') {
                            $identicalPatientOnCreate = true;
                        }
                        else {
                            return [
                                'msg' => [$gateRes['data']['desc']],
                            ];
                        }
                    }

                    if ($this->isLog)
                        file_put_contents(__FILE__ . '.log', var_export([
                                'createPatient' => 2,
                                '$patientId' => $patientId,
                            ], true) . PHP_EOL, FILE_APPEND);
                }
            }
        }

        if ($patientId === null) {
            $patientId = static::RENOVATIO_DEF_PATIENT_ID;

            if ($this->isLog)
                file_put_contents(__FILE__ . '.log', var_export([
                        'RENOVATIO_DEF_PATIENT_ID' => 1,
                    ], true) . PHP_EOL, FILE_APPEND);
        }

        $comment = '<b>Тип приёма</b>: ' . ($data['type'] === 'ONLINE_RECEPTION' ? 'ONLINE' : 'Очный') .
            '<br><b>Доктор</b>: "' . $checkRes['doctorName'] . '" (' . $data['doctor'] . ')' .
            '<br><b>Отделение</b>: "' . $checkRes['departmentName'] . '"' .
            '<br><b>Время</b>: ' . $checkRes['time']['date']
                . ' ' . $checkRes['time']['start']
                . ' - ' . $checkRes['time']['end'] .
            (!$isCreatePatient ? '' : '<br><b>Фамилия</b>: "' . htmlspecialchars($checkRes['lastName']) . '"') .
            '<br><b>Имя</b>: "' . htmlspecialchars($checkRes['name']) . '"' .
            (!$isCreatePatient ? '' : '<br><b>Отчество</b>: "' . htmlspecialchars($checkRes['secondName']) . '"') .
            (!$isCreatePatient ? '' : '<br><b>Дате рождения</b>: ' . $data['birthdate']) .
            '<br><b>Телефон</b>: ' . $data['phone'] .
            ($identicalPatients
                ? '<br><b>Пациенты с таким же номером телефона</b>: ' . implode(', ', $identicalPatients)
                : '');

        if ($identicalPatientOnCreate) {
            $comment .= '<br><b>Системное сообще</b>: Не удалось создать карточку пациента, т.к. такой пациент уже существует';
        }

        $createAppointmentParams = [
            'doctor_id' => $data['doctor'],
            'patient_id' => $patientId,
            'time_start' => $checkRes['time']['date'] . ' ' . $checkRes['time']['start'],
            'time_end' => $checkRes['time']['date'] . ' ' . $checkRes['time']['end'],
            'clinic_id' => $checkRes['time']['clinicId'],
            'check_intersection' => 1,
            'comment' => $comment,
        ];

        if ($data['type'] === 'ONLINE_RECEPTION') {
            $createAppointmentParams['is_telemedicine'] = 1;
        }

        $gateRes = $this->callApiMethod('createAppointment', $createAppointmentParams);

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'createAppointment' => 1,
                    '$gateRes' => $gateRes,
                    '$createAppointmentParams' => $createAppointmentParams,
                ], true) . PHP_EOL, FILE_APPEND);

        if (isset($gateRes['data']) && is_numeric($gateRes['data'])) {

            if ($this->isLog)
                file_put_contents(__FILE__ . '.log', var_export([
                        'good' => 1,
                    ], true) . PHP_EOL, FILE_APPEND);

            return [
                'success' => true,
            ];
        }

        if (
            isset($gateRes['data']['code'], $gateRes['data']['desc'])
            && $gateRes['data']['code'] === 500
        ) {
            $msg = [$gateRes['data']['desc']];
            if ($gateRes['data']['desc'] === 'Визит пересекается с другими визитами врача') {
                $msg[] = 'Выберите другое время';
            }
            return [
                'msg' => $msg,
            ];
        }

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'bad' => 1,
                ], true) . PHP_EOL, FILE_APPEND);

        return ['msg' => ['Ошибка']];
    }

    private function callApiMethodBase(string $method, array $params = []): array
    {
        $params['api_key'] = static::RENOVATIO_API_KEY;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::RENOVATIO_API_URL . $method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return (array) json_decode((string) $output, true);
    }

    private function callApiMethod(string $method, array $params = []): array
    {
        $i = 1;
        while (true) {
            $gateRes = $this->callApiMethodBase($method, $params);
            if (
                !(
                    isset($gateRes['error'], $gateRes['data']['code'], $gateRes['data']['desc'])
                    && $gateRes['error'] === 1
                    && $gateRes['data']['code'] === 401
                    && $gateRes['data']['desc'] === 'Auth error'
                )
                || $i >= 3
            ) {
                return $gateRes;
            }
            $i++;
            sleep(2);
        }
    }

    public function getTimeAction($data)
    {
        $id = (int) $data['id'];
        $gateRes = $this->callApiMethod('getSchedule', ['user_id' => $id]);

        if ($this->isLog)
            file_put_contents(__FILE__ . '.log', var_export([
                    'getTimeAction' => 1,
                    '$gateRes' => $gateRes,
                ], true) . PHP_EOL, FILE_APPEND);

        $res = [];
        if (isset($gateRes['data'][$id]) && is_array($gateRes['data'][$id])) {
            foreach ($gateRes['data'][$id] as $item) {
                $res[] = [
                    'clinic_id' => $item['clinic_id'],
                    'date' => $item['date'],
                    'time_start_short' => $item['time_start_short'],
                    'time_end_short' => $item['time_end_short'],
                ];
            }
        }
        return [
            'res' => $res,
        ];
    }

    private function getData(): array
    {
        Loader::includeModule('iblock');
        
        $query = ElementTable::query()
            ->setCacheTtl(3600 * 24 * 365 * 888)
            ->cacheJoins(true)
            ->setSelect([
                'NAME',
                'CODE',
            ])
            ->setFilter([
                '=ACTIVE' => 'Y',
                '=IBLOCK_ID' => static::MEDICAL_UNITS_IBLOCK_ID,
            ])
            ->setOrder([
                'SORT',
            ]);

        $medicalUnitsActive = [];
        $result = $query->exec();
        while ($row = $result->fetch()) {
            $medicalUnitsActive[$row['CODE']] = $row;
        }

        $query = ElementTable::query()
            ->setCacheTtl(3600 * 24 * 365 * 888)
            ->cacheJoins(true)
            ->setSelect([
                'ELEMENT_ID' => 'ID',
                'NAME',
                'PROP_CODE' => 'prop.CODE',
                'PROP_VALUE' => 'propVal.VALUE',
                'ENUM_XML_ID' => 'propValEnum.XML_ID',
            ])
            ->registerRuntimeField(
                'prop',
                [
                    'data_type' => PropertyTable::class,
                    'reference' => [
                        '=ref.IBLOCK_ID' => [static::DOCTORS_IBLOCK_ID],
                    ],
                ]
            )
            ->registerRuntimeField(
                'propVal',
                [
                    'data_type' => ElementPropertyTable::class,
                    'reference' => [
                        '=ref.IBLOCK_PROPERTY_ID' => 'this.prop.ID',
                        '=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                    ],
                ]
            )
            ->registerRuntimeField(
                'propValEnum',
                [
                    'data_type' => PropertyEnumerationTable::class,
                    'reference' => [
                        '=ref.PROPERTY_ID' => 'this.prop.ID',
                        '=ref.ID' => 'this.propVal.VALUE',
                    ],
                ]
            )
            ->setFilter([
                '=ACTIVE' => 'Y',
                '=IBLOCK_ID' => static::DOCTORS_IBLOCK_ID,
                '=prop.CODE' => ['TYPE', 'DEPARTMENT', 'RENOVATIO_ID'],
            ])
            ->setOrder([
                'SORT',
            ]);

        $doctors = [];
        $doctorsTypes = [];
        $result = $query->exec();
        while ($row = $result->fetch()) {
            if ($row['PROP_CODE'] === 'DEPARTMENT' && !isset($medicalUnitsActive[$row['ENUM_XML_ID']])) {
                continue;
            }
            if ($row['PROP_CODE'] === 'TYPE' && $row['ENUM_XML_ID'] === static::FACE_TO_FACE_RECEPTION_XML_ID) {
                $typeCode = 'FACE_TO_FACE_RECEPTION';
            }
            elseif ($row['PROP_CODE'] === 'TYPE' && $row['ENUM_XML_ID'] === static::ONLINE_RECEPTION_XML_ID) {
                $typeCode = 'ONLINE_RECEPTION';
            }
            else {
                $typeCode = $row['ENUM_XML_ID'];
            }
            if (
                $row['PROP_CODE'] === 'TYPE'
                || $row['PROP_CODE'] === 'DEPARTMENT'
            ) {
                $doctorsTypes[$row['ELEMENT_ID']][$row['PROP_CODE']][$typeCode] = true;
            }
            if (!isset($doctors[$row['ELEMENT_ID']])) {
                $doctors[$row['ELEMENT_ID']] = [
                    'ID' => $row['ELEMENT_ID'],
                    'NAME' => $row['NAME'],
                ];
            }
            if ($row['PROP_CODE'] === 'RENOVATIO_ID') {
                $doctors[$row['ELEMENT_ID']]['RENOVATIO_ID'] = $row['PROP_VALUE'];
            }
        }

        foreach ($doctors as $doctorsKey => $doctorsValue) {
            if (
                !isset($doctorsValue['RENOVATIO_ID'])
                || !is_numeric($doctorsValue['RENOVATIO_ID'])
                || !isset($doctorsTypes[$doctorsKey]['DEPARTMENT'])
                || !$doctorsTypes[$doctorsKey]['DEPARTMENT']
                || !isset($doctorsTypes[$doctorsKey]['TYPE'])
                || !$doctorsTypes[$doctorsKey]['TYPE']
            ) {
                unset($doctors[$doctorsKey], $doctorsTypes[$doctorsKey]);
            }
        }

        $medicalUnits = [];
        foreach ($doctors as $doctorsKey => $doctorsValue) {
            foreach ($doctorsTypes[$doctorsKey]['DEPARTMENT'] as $departmentKey => $departmentValue) {
                if (!isset($medicalUnits[$departmentKey])) {
                    $medicalUnits[$departmentKey] = $medicalUnitsActive[$departmentKey];
                }
                foreach ($doctorsTypes[$doctorsKey]['TYPE'] as $typeKey => $typeValue) {
                    $medicalUnits[$departmentKey]['TYPES'][$typeKey] = true;
                }
            }
        }

        return [
            'DOCTORS' => $doctors,
            'DEPARTMENTS' => $medicalUnits,
            'DOCTORS_TYPES' => $doctorsTypes,
        ];
    }
    
    public function executeComponent()
    {
        if ($this->startResultCache()) {

            $this->arResult['DATA'] = $this->getData();

            $this->includeComponentTemplate();
        }
    }
}