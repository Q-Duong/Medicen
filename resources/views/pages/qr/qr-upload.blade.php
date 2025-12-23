<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Upload Danh Sách Bệnh Nhân</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f6f9; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        input[type="file"] { margin: 20px 0; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🖨️ Tạo QR Hàng Loạt</h2>
        <p>Chọn file CSV chứa danh sách bệnh nhân</p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('qr.process') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="excel_file" accept=".xlsx, .xls, .csv" required>
            <br>
            <button type="submit">Tạo mã QR & In</button>
        </form>
    </div>
</body>
</html>