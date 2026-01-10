<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả xác thực email</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; text-align: center; padding: 40px; }
        .result { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #ccc; display: inline-block; padding: 32px 48px; }
        .success { color: #28a745; font-size: 1.5rem; }
        .error { color: #dc3545; font-size: 1.2rem; }
    </style>
</head>
<body>
    <div class="result">
        @if($success)
            <div class="success">✅ {{ $message }}</div>
            <a href="/login" id="login-link">Đăng nhập ngay</a>
            <script>
                setTimeout(function() {
                    window.location.href = '/login';
                }, 2500); // Tự động chuyển sau 2.5 giây
            </script>
        @else
            <div class="error">❌ {{ $message }}</div>
        @endif
    </div>
</body>
</html>
