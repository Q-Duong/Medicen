<?php

namespace App\Http\Controllers;

use App\Models\HistoryEdit;
use Illuminate\Http\Request;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
	public function index()
	{
		$getAllTasks = Task::join('users', 'users.id', '=', 'tasks.user_id')
			->join('profiles', 'profiles.id', '=', 'users.profile_id')
			->orderBy('created_at', 'DESC')
			->select(
				'tasks.id',
				'tasks.task_name',
				'tasks.task_description',
				'tasks.task_progress',
				'tasks.task_status',
				'tasks.department',
				'tasks.created_at',
				'user_id',
				'profiles.profile_firstname',
				'profiles.profile_lastname',
			)
			->get();
		$months = [];
		$currentYear = Carbon::now()->format('Y');
		$currentMonth = Carbon::now()->format('F');

		for ($m = 1; $m <= 12; $m++) {
			$months[] = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
		}
		return view('pages.client.task.index', compact('getAllTasks', 'months', 'currentMonth', 'currentYear'));
	}

	public function load()
	{
		$getAllTasks = Task::join('users', 'users.id', '=', 'tasks.user_id')
			->join('profiles', 'profiles.id', '=', 'users.profile_id')
			->orderBy('created_at', 'DESC')
			->select(
				'tasks.id',
				'tasks.task_name',
				'tasks.task_description',
				'tasks.task_progress',
				'tasks.task_status',
				'tasks.department',
				'tasks.created_at',
				'user_id',
				'profiles.profile_firstname',
				'profiles.profile_lastname',
			)
			->get();
		$html = view('pages.client.task.render', compact('getAllTasks'))->render();
		return response()->json(array('success' => true, 'html' => $html));
	}

	public function createOrUpdate(Request $request)
	{
		// $validator = Validator::make($request->all(), $this->validateVartContent(), $this->messageVartContent());
		// if ($validator->fails()) {
		//     return response()->json(array('errors' => true, 'validator' => $validator->errors()));
		// }
		// DB::beginTransaction();
		// try {
		if ($request->type == 'create') {
			$task = new Task();
			$task->task_name = $request->task_name;
			$task->task_description = $request->task_description;
			$task->department = $request->department;
			$task->user_id = Auth::user()->id;
			$task->save();
			$message = 'Đã tạo nhiệm vụ thành công';
			$history_action = 'Tạo nhiệm vụ';
		} else {
			$task = Task::findOrFail($request->id);
			$task->task_name = $request->task_name;
			$task->task_description = $request->task_description;
			$task->user_id = Auth::user()->id;
			$task->save();
			$message = 'Đã cập nhật nhiệm vụ thành công';
			$history_action = 'Cập nhật nhiệm vụ';
		}

		$history = new HistoryEdit();
		$history->order_id = $task->id;
		$history->user_name = Auth::user()->email;
		$history->history_action = $history_action;
		$history->save();

		// 	DB::commit();
		// 	return response()->json(array('success' => true, 'message' => $message));
		// } catch (\Exception $e) {
		// 	DB::rollback();
		// 	return response()->json(array('success' => false, 'route' => '500'));
		// }
	}

	public function updateProgress(Request $request)
	{
		// $validator = Validator::make($request->all(), $this->validateVartContent(), $this->messageVartContent());
		// if ($validator->fails()) {
		//     return response()->json(array('errors' => true, 'validator' => $validator->errors()));
		// }
		// DB::beginTransaction();
		// try {

		$task = Task::findOrFail($request->id);
		$task->task_progress = $request->task_progress;
		$task->save();
		$message = 'Đã cập nhật tiến độ thành công';
		$history_action = 'Cập nhật nhiệm vụ';


		$history = new HistoryEdit();
		$history->order_id = $task->id;
		$history->user_name = Auth::user()->email;
		$history->history_action = $history_action;
		$history->save();

		// 	DB::commit();
		// 	return response()->json(array('success' => true, 'message' => $message));
		// } catch (\Exception $e) {
		// 	DB::rollback();
		// 	return response()->json(array('success' => false, 'route' => '500'));
		// }
	}

	public function updateStatus(Request $request)
	{
		DB::beginTransaction();
		try {
			$task = Task::findOrFail($request->id);
			$task->task_status = 2;
			$task->save();

			DB::commit();
			return response()->json(array('success' => true, 'message' => 'Đã hoàn thành nhiệm vụ'));
		} catch (\Exception $e) {
			DB::rollback();
			return response()->json(array('success' => false, 'route' => '500'));
		}
	}
}
