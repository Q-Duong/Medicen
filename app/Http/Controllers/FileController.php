<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TempFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
	public function process(Request $request)
	{
		$getFile = $request->file('ord_list_file');
		$getTotalFile = $request->file('ord_total_file_name');
		if ($getFile) {
			foreach ($getFile as $key => $file) {
				$fileUploaded = saveImageFileDrive($file);
				TempFile::create([
					'folder' => $fileUploaded['virtual_path'],
					'filename' => $fileUploaded['fileName'],
				]);
			}
			return $fileUploaded['fileName'];
		}
		if ($getTotalFile) {
			$fileUploaded = saveImageFileDrive($getTotalFile);
			TempFile::create([
				'folder' => $fileUploaded['virtual_path'],
				'filename' => $fileUploaded['fileName'],
			]);
			return $fileUploaded['fileName'];
		}
		return response('Failed upload', 500);
	}

	public function revert(Request $request)
	{
		$tempFile = TempFile::where('filename', $request->getContent())->first();
		if ($tempFile) {
			Storage::cloud()->delete($request->getContent());
			$tempFile->delete();
			return response('Success delete', 200);
		}
		return response('Failed delete', 500);
	}

	public function destroyFileOrder(Request $request)
	{
		$files = '';
		$count = 0;
		$order_detail_id = $request->id;
		$orderDetail = OrderDetail::findOrFail($request->id);
		$orderDetail->ord_list_file = $this->removeElementInArray($orderDetail->ord_list_file, $request->name);
		$orderDetail->ord_list_file_path = $this->removeElementInArray($orderDetail->ord_list_file_path, $request->path);
		$orderDetail->save();
		deleteImageFileDrive($request->name);
		if ($orderDetail->ord_list_file != '') {
			$files = array_combine(explode(',', $orderDetail->ord_list_file), explode(',', $orderDetail->ord_list_file_path));
			$count = count($files);
		}
		$html = view('pages.admin.order.edit_render', compact('files', 'order_detail_id', 'count'))->render();
		return response()->json(array('success' => true, 'html' => $html));
	}

	public function destroyFileTotal(Request $request)
	{
		$orderDetail = OrderDetail::where('id',$request->id)->first();
		$orderDetail->ord_total_file_name = '';
		$orderDetail->ord_total_file_path = '';
		$orderDetail->save();
		deleteImageFileDrive($request->name);
		return response()->json(array('success' => true));
	}

	public function removeElementInArray($arr, $rm)
	{
		$array = explode(',', $arr);
		$pos = array_search($rm, $array);
		if ($pos !== false) {
			unset($array[$pos]);
		}
		return  implode(",", $array);
	}
}
