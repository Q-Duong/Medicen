<?php

namespace App\Http\Controllers;

use App\Models\Zalo;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ZaloController extends Controller
{
    public function getAccessToken($value)
	{
		$response = Http
			::withHeaders([
				'secret_key' => env('ZALO_SECRET_KEY'),
			])->post(
				'https://oauth.zaloapp.com/v4/oa/access_token',
				[
					'code' => $value,
					'app_id' => env('ZALO_APP_ID'),
					'grant_type' => 'authorization_code',
					'code_verifier' => env('CODE_VERIFIER')
				]
			);

		$jsonData = $response->json();
	}

	public function getAccessTokenFromRefreshToken()
	{
		$response = Http
			::withHeaders([
				'Content-Type' => 'application/x-www-form-urlencoded',
				'secret_key' => config("app.zaloSecretKey"),
			])->withBody(http_build_query([
				'refresh_token' => $this->getRefreshTokenDB(),
				'app_id' => config("app.zaloAppId"),
				'grant_type' => 'refresh_token',
			]), 'application/json')->post('https://oauth.zaloapp.com/v4/oa/access_token');

		$jsonData = $response->json();

		$value = collect($jsonData);
		$zalo = Zalo::find(1);
		$zalo->access_token = $value['access_token'];
		$zalo->refresh_token = $value['refresh_token'];
		$zalo->save();
	}

	public function getAccessTokenDB()
	{
		$zalo = Zalo::first();
		$access_token = $zalo->access_token;
		return $access_token;
	}

	public function getRefreshTokenDB()
	{
		$zalo = Zalo::first();
		$refresh_token = $zalo->refresh_token;
		return $refresh_token;
	}
}
