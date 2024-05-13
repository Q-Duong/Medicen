<?php
if (!function_exists('formatDate')) {
    function formatDate($date)
    {
        $format=explode("/",$date);
		$day = array_shift($format);
        $year = array_pop($format);
        $month = implode(" ", $format);
		$dateFormat= $year."-".$month."-".$day;
    	return $dateFormat;
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice($price)
    {
        $str_price_format = Str::replace([' ','₫','.'], '', $price);
    	return $str_price_format;
    }
}

if (!function_exists('versionResource')) {
    function versionResource($path)
    {
    	return  asset($path."?v=".config("app.resourceVersion"));
    }
}

if (!function_exists('saveImageFileDrive')) {
    function saveImageFileDrive($file)
    {
        $fileData = File::get($file);
        $get_name_file = $file->getClientOriginalName();
        $name_file = current(explode('.', $get_name_file));
        $new_file =  $name_file . rand(0, 99) . '.' . $file->getClientOriginalExtension();
        $fileUploaded = Storage::cloud()->put($new_file, $fileData);
        $getAllFileInDrive = collect(Storage::cloud()->listContents('', true));
        $collectFile = $getAllFileInDrive->where('path', '=', $new_file)->first();
        $response=['fileName' => $new_file, 'virtual_path' => $collectFile['extraMetadata']['virtual_path'] ];
        return $response;
    }
}

if (!function_exists('deleteImageFileDrive')) {
    function deleteImageFileDrive($path)
    {
        Storage::cloud()->delete($path);
        return true;
    }
}