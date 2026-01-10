<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - RoPhim</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            margin: 20px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        .content {
            margin: 30px 0;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b35 0%, #ff8a5c 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            transition: all 0.3s ease;
        }
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 53, 0.4);
        }
        .link-text {
            color: #007bff;
            word-break: break-all;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
            .reset-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">RoPhim</div>
            <div class="subtitle">Website xem phim chất lượng cao</div>
        </div>

        <div class="content">
            <div class="greeting">
                Xin chào <strong>{{ $user->name }}</strong>,
            </div>

            <div class="message">
                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại RoPhim.
                Để đặt lại mật khẩu, vui lòng click vào nút bên dưới.
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $resetUrl }}" class="reset-button">
                    Đặt lại mật khẩu ngay
                </a>
            </div>

            <div class="warning">
                <strong>Lưu ý:</strong> Link đặt lại mật khẩu sẽ hết hạn sau 1 giờ. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
            </div>

            <div class="message">
                Nếu nút trên không hoạt động, bạn có thể sao chép và dán link sau vào trình duyệt:
                <br><br>
                <span class="link-text">{{ $resetUrl }}</span>
            </div>
        </div>

        <div class="footer">
            <p>
                Bạn nhận được email này vì đã yêu cầu đặt lại mật khẩu tại RoPhim.<br>
                Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.
            </p>
            <p style="margin-top: 10px;">
                © 2026 RoPhim. Tất cả quyền được bảo lưu.
            </p>
        </div>
    </div>
</body>
</html>
