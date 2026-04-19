<!-- resources/views/emails/welcome.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 520px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15), 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #413ad2 0%, #5b52e8 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
        }

        .app-name {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.5px;
        }

        .content {
            padding: 48px 32px;
            background: #ffffff;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 24px;
            letter-spacing: -0.3px;
        }

        .greeting strong {
            color: #413ad2;
            font-weight: 700;
        }

        .message {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%);
            margin: 32px 0;
        }

        .help-text {
            color: #a0aec0;
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
        }

        .footer {
            text-align: center;
            padding: 24px 32px 32px;
            background: #fafbff;
            border-top: 1px solid #e2e8f0;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .social-link {
            color: #a0aec0;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .social-link:hover {
            color: #413ad2;
        }

        .copyright {
            font-size: 13px;
            color: #a0aec0;
        }

        .copyright a {
            color: #413ad2;
            text-decoration: none;
            font-weight: 500;
        }

        .copyright a:hover {
            text-decoration: underline;
        }

        .contact-email {
            color: #413ad2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .contact-email:hover {
            color: #2a23a8;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 12px;
            }

            .content {
                padding: 32px 20px;
            }

            .greeting {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="app-name">{{ config('app.name') }}</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Welcome <strong>{{ $username }}</strong>! 👋
            </div>

            <div class="message">
                Thank you for joining {{ config('app.name') }}! We're excited to have you on board.<br>
                Your account has been successfully created. You can now log in and start exploring.
            </div>

            <div class="divider"></div>

            <div class="help-text">
                <p>If you didn't create an account with {{ config('app.name') }}, you can safely ignore this email.</p>
                <p style="margin-top: 12px;">Need help? Contact us at <a href="mailto:amjadalzaree2@gmail.com" class="contact-email">amjadalzaree2@gmail.com</a></p>
            </div>
        </div>

        <div class="footer">
            <div class="social-links">
                <a href="#" class="social-link" aria-label="Twitter">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                    </svg>
                </a>
                <a href="#" class="social-link" aria-label="Facebook">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                    </svg>
                </a>
                <a href="#" class="social-link" aria-label="Instagram">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                        <circle cx="18" cy="6" r="1.5"/>
                    </svg>
                </a>
            </div>
            <div class="copyright">
                &copy; {{ date('Y') }} <a href="#">{{ config('app.name') }}</a>. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
