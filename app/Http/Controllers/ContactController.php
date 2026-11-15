<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentMail;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.client.contact.index');
    }

    public function submit(Request $request)
    {
        // 1. Xác thực dữ liệu đầu vào
        $validatedData = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20|regex:/^[0-9\-\+]{9,15}$/',
            'date'    => 'required|date|after_or_equal:today',
            'message' => 'nullable|string|max:1000',
        ], [
            'name.required'     => 'Vui lòng nhập họ và tên của bạn.',
            'phone.required'    => 'Vui lòng nhập số điện thoại để liên hệ.',
            'phone.regex'       => 'Số điện thoại không đúng định dạng.',
            'date.required'     => 'Vui lòng chọn ngày khám.',
            'date.after_or_equal' => 'Không được chọn ngày trong quá khứ.',
        ]);

        try {
            // Người nhận đặt trong .env: CONTACT_MAIL_TO, CONTACT_MAIL_CC (xem config/contact.php)
            $to = config('contact.to');
            if ($to) {
                $mail = Mail::to($to);
                if ($cc = config('contact.cc')) {
                    $mail->cc($cc);
                }
                $mail->send(new AppointmentMail($validatedData));
            } else {
                // Chưa cấu hình email nhận: ghi lại đăng ký vào log để không mất khách
                Log::warning('Form liên hệ: chưa cấu hình CONTACT_MAIL_TO trong .env, không gửi email', $validatedData);
            }

            // Trả về JSON nếu gọi bằng AJAX
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Đăng ký đặt lịch thành công! Chuyên viên của Medicen sẽ sớm liên hệ với bạn.'
                ], 200);
            }

            return back()->with('success', 'Đăng ký đặt lịch thành công!');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Có lỗi xảy ra trong quá trình gửi. Vui lòng thử lại sau!'
                ], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra!']);
        }
    }
}
