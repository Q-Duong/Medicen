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
        return $zalo->access_token;
    }

    public function getRefreshTokenDB()
    {
        $zalo = Zalo::first();
        return $zalo->refresh_token;
    }

    public function notificationSchedule($carActive, $obj)
    {
        switch ($obj) {
            case ('drv'):
                $phone = $carActive->car_driver_phone;
                $name = $carActive->car_driver_name;
                break;
            case ('kt1'):
                $phone = $carActive->car_ktv_phone_1;
                $name = $carActive->car_ktv_name_1;
                break;
            case ('kt2'):
                $phone = $carActive->car_ktv_phone_2;
                $name = $carActive->car_ktv_name_2;
                break;
        }
        $response = Http
            ::withHeaders([
                'Content-Type' => 'application/json',
                'access_token' => $this->getAccessTokenDB()
            ])->post(
                'https://business.openapi.zalo.me/message/template',
                [
                    'phone' => $phone,
                    'template_id' => '259945',
                    'template_data' => [
                        'order_code' => $carActive->order_id,
                        'date' => date('d/m/Y', strtotime($carActive->order->orderDetail->ord_start_day)),
                        'car' => $carActive->car_name,
                        'name' => $name,
                        'time' => $carActive->order->orderDetail->ord_time
                    ],
                    'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
                ]
            );

        $jsonData = $response->json();
    }

    public function notificationScheduleCancel($carActive, $obj)
    {
        switch ($obj) {
            case ('drv'):
                $phone = $carActive->car_driver_phone;
                $name = $carActive->car_driver_name;
                break;
            case ('kt1'):
                $phone = $carActive->car_ktv_phone_1;
                $name = $carActive->car_ktv_name_1;
                break;
            case ('kt2'):
                $phone = $carActive->car_ktv_phone_2;
                $name = $carActive->car_ktv_name_2;
                break;
        }
        $response = Http
            ::withHeaders([
                'Content-Type' => 'application/json',
                'access_token' => $this->getAccessTokenDB()
            ])->post(
                'https://business.openapi.zalo.me/message/template',
                [
                    'phone' => $phone,
                    'template_id' => '261885',
                    'template_data' => [
                        'order_code' => $carActive->order_id,
                        'date' => date('d/m/Y', strtotime($carActive->order->orderDetail->ord_start_day)),
                        'car' => $carActive->car_name,
                        'name' => $name,
                        'time' => $carActive->order->orderDetail->ord_time
                    ],
                    'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
                ]
            );

        $jsonData = $response->json();
    }

    public function test_zalo()
    {
        $response = Http
            ::withHeaders([
                'Content-Type' => 'application/json',
                'access_token' => $this->getAccessTokenDB()
            ])->post(
                'https://business.openapi.zalo.me/message/template',
                [
                    'phone' => '84943705326',
                    'template_id' => '259945',
                    'template_data' => [
                        'order_code' => 100,
                        'date' => date('d/m/Y', strtotime(2023 - 05 - 16)),
                        'car' => 1,
                        'name' => 'Quốc Dương',
                        'time' => '7'
                    ],
                    'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
                ]
            );

        $jsonData = $response->json();
        return $jsonData;
    }

    public function test_zalo_cancle()
    {
        $response = Http
            ::withHeaders([
                'Content-Type' => 'application/json',
                'access_token' => $this->getAccessTokenDB()
            ])->post(
                'https://business.openapi.zalo.me/message/template',
                [
                    'phone' => '84943705326',
                    'template_id' => '261885',
                    'template_data' => [
                        'order_code' => 100,
                        'date' => date('d/m/Y', strtotime(2023 - 05 - 16)),
                        'car' => 1,
                        'name' => 'Quốc Dương',
                        'time' => '7'
                    ],
                    'tracking_id' => 'sadlkslfkdslkgldkgfdkjgfdjjjj'
                ]
            );

        $jsonData = $response->json();
        return $jsonData;
    }
}
