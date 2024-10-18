<?php

namespace app\components;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use app\components\CustomException;

class AbsensiCoreAPI 
{
	public function insertAttendance($attendance, $model)
	{
		$attendance = $this->mergeDefaultAttendance($attendance, $model);
		$attendance = $this->populateFields($attendance, $model);
		$attendance = $this->sanitizeEmptyFields($attendance);
		$attendance = $this->sanitizeData($attendance, 'attendance');
		$attendance = $this->changeInfoField($attendance);

		return [$attendance];
	}

	private function mergeDefaultAttendance($attendance, $model)
	{
		if (isset($model->attendance)) {
			$defaultAttendance = [
				'employee_id' => null,
				'start_time' => null,
				'end_time' => null,
				'info' => [
					'in' => false,
					'out' => false,
					'detail' => []
				],
				'department' => [
					'id' => null,
					'name' => null
				],
				'device' => [
					'id' => null,
					'device_code' => null,
					'device_name' => null,
				],
				'work_time' => [
					'id' => null,
					'entry_time' => null,
					'home_time' => null
				],
				'location' => [
					'lat' => null,
					'long' => null,
					'image' => []
				]
			];
			$attendance = array_merge($defaultAttendance, $attendance);
		}

		return $attendance;
	}

	public function populateFields($attendance, $model)
	{
		$employee = $model->getEmployee($attendance['employee_id']);
		$employee_detail = $employee->employee_detail;

		$attendance['employee_name'] = $employee->name;
		$attendance['department']['id'] = $employee_detail['department']['id'];
		$attendance['department']['name'] = $employee_detail['department']['name'];

		$attendance['work_time']['id'] = $employee_detail['work_time']['id'];
		$attendance['work_time']['entry_time'] = $employee_detail['work_time']['entry_time'];
		$attendance['work_time']['home_time'] = $employee_detail['work_time']['home_time'];

		$device = $model->getDevice($attendance['device']['device_code']);
		$attendance['device']['id'] = $device->id;
		$attendance['device']['device_code'] = $device->device_code;
		$attendance['device']['device_name'] = $device->device_name;
	
		return $attendance;
	}

	public function changeInfoField($attendance)
	{
		if (!empty($attendance['start_time']) && $attendance['start_time'] != '00:00:00') {
			$attendance['info']['in'] = true;
		}

		if (!empty($attendance['end_time']) && $attendance['end_time'] != '00:00:00') {
			$attendance['info']['out'] = true;
		}

		if (!empty($attendance['start_time']) && !empty($attendance['work_time']['entry_time'])) {
			$startTime = strtotime($attendance['start_time']);
			$entryTime = strtotime($attendance['work_time']['entry_time']);

			if ($startTime > $entryTime) {
				if (!in_array("Telat Masuk", $attendance['info']['detail'])) {
					$attendance['info']['detail'][] = "Telat Masuk";
				}
			}
		}

		if (!empty($attendance['end_time']) && !empty($attendance['work_time']['home_time']) && $attendance['end_time'] != '00:00:00') {
			$endTime = strtotime($attendance['end_time']);
			$homeTime = strtotime($attendance['work_time']['home_time']);

			if ($endTime < $homeTime) {
				if (!in_array("Pulang Cepat", $attendance['info']['detail'])) {
					$attendance['info']['detail'][] = "Pulang Cepat";
				}
			}
		}

		if (($attendance['end_time'] == '00:00:00' || empty($attendance['end_time'])) && !empty($attendance['start_time']) && date('Y-m-d') != date('Y-m-d', strtotime($attendance['start_time']))) {
			if (!in_array("Tidak Absen Pulang", $attendance['info']['detail'])) {
				$attendance['info']['detail'][] = "Tidak Absen Pulang";
			}
		}

		return $attendance;
	}

	public function insertBlacklist($existing, $employee_id, $date, $start_time)
	{
		$blacklistEntry = [
			'employee_id' => (int) $employee_id,
			'date' => $date,
			'time' => $start_time,
			'change_log' => [
				'created_at' => self::timestamp(),
				'created_by' => Yii::$app->session->get('username')
			]
		];

		$blacklist = $existing->blacklist;
		$blacklist[] = $blacklistEntry;

		return $blacklist;
	}

	public function updateAttendance($attendance, $employee_id, $end_time)
	{
		foreach ($attendance as &$item) {
			if (isset($item['employee_id']) && $item['employee_id'] == $employee_id) {
				$item['end_time'] = $end_time;
				$item = $this->changeInfoField($item);
				break;
			}
		}

		return $attendance;
	}

	public function filterAttendances($data, $filters)
	{
		return array_filter($data, function ($item) use ($filters) {
			foreach ($filters as $key => $value) {
				if ($key === 'has_start_time') {
					if ($value && !isset($item['start_time'])) {
						return false;
					}
					continue;
				}
	
				$keys = explode('.', $key);
				$temp = $item;
	
				foreach ($keys as $k) {
					if (isset($temp[$k])) {
						$temp = $temp[$k];
					} else {
						return false;
					}
				}
	
				if ($temp != $value) {
					return false;
				}
			}
			return true;
		});
	}
}