<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận email - RoPhim</title>
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
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #ffd96a 0%, #ffdd8a 100%);
            color: #000;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(255, 217, 106, 0.3);
            transition: all 0.3s ease;
        }
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 217, 106, 0.4);
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 14px;
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
        .link-text {
            color: #007bff;
            word-break: break-all;
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
            .verify-button {
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
            <div class="subtitle">Website xem phim hàng đầu Việt Nam</div>
        </div>

        <div class="content">
            <div class="greeting">
                Xin chào <strong>{{ $user->name }}</strong>,
            </div>

            <div class="message">
                Cảm ơn bạn đã đăng ký tài khoản tại RoPhim! Để hoàn tất quá trình đăng ký và bắt đầu trải nghiệm dịch vụ của chúng tôi, vui lòng xác nhận email của bạn.
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $actionUrl }}" class="verify-button">
                    Xác nhận email ngay
                </a>
            </div>

            <div class="warning">
                <strong>Lưu ý:</strong> Link xác nhận sẽ hết hạn sau 24 giờ. Nếu bạn không yêu cầu đăng ký tài khoản này, vui lòng bỏ qua email này.
            </div>

            <div class="message">
                Nếu nút trên không hoạt động, bạn có thể sao chép và dán link sau vào trình duyệt:
                <br><br>
                <span class="link-text">{{ $actionUrl }}</span>
            </div>
        </div>

        <div class="footer">
            <p>
                Bạn nhận được email này vì đã đăng ký tài khoản tại RoPhim.<br>
                Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.
            </p>
            <p style="margin-top: 10px;">
                © 2026 RoPhim. Tất cả quyền được bảo lưu.
            </p>
        </div>
    </div>
</body>
</html>
