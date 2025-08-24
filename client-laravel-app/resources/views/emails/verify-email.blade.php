<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - AU-VLP</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        
        .email-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #0F5132 0%, #16a34a 100%);
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 8px 0 0;
            font-size: 16px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .message {
            font-size: 16px;
            line-height: 1.7;
            color: #4b5563;
            margin-bottom: 30px;
        }
        
        .verification-button {
            display: inline-block;
            background: linear-gradient(135deg, #0F5132 0%, #16a34a 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.39);
            transition: all 0.3s ease;
        }
        
        .verification-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(16, 185, 129, 0.5);
        }
        
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .alternative-text {
            font-size: 14px;
            color: #6b7280;
            margin-top: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .alternative-text p {
            margin: 0 0 10px;
        }
        
        .verification-code {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            color: #0F5132;
            background: #ecfdf5;
            padding: 10px 15px;
            border-radius: 6px;
            display: inline-block;
            margin: 10px 0;
            letter-spacing: 2px;
        }
        
        .footer {
            background: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer p {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0;
        }
        
        .footer a {
            color: #16a34a;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }
        
        .benefits {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .benefits h3 {
            color: #166534;
            margin: 0 0 15px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .benefits ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .benefits li {
            color: #166534;
            font-size: 14px;
            margin: 8px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .benefits li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #16a34a;
            font-weight: bold;
        }
        
        .warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #92400e;
        }
        
        .warning strong {
            display: block;
            margin-bottom: 5px;
            color: #b45309;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .verification-button {
                padding: 14px 24px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Welcome to AU-VLP!</h1>
            <p>Africa Union Volunteer Leadership Program</p>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $user->first_name }}!
            </div>
            
            <div class="message">
                <p>Thank you for joining the Africa Union Volunteer Leadership Program. We're excited to have you as part of our community of dedicated volunteers working to make a difference across Africa.</p>
                
                <p>To complete your registration and start your volunteering journey, please verify your email address by clicking the button below:</p>
            </div>
            
            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verification-button">
                    Verify My Email Address
                </a>
            </div>
            
            <!-- Benefits Section -->
            <div class="benefits">
                <h3>What's waiting for you:</h3>
                <ul>
                    <li>Access to volunteer opportunities across 55 African countries</li>
                    <li>Connect with 2,500+ like-minded volunteers</li>
                    <li>Earn certificates and professional recognition</li>
                    <li>Build valuable skills and experience</li>
                    <li>Make a meaningful impact in your community</li>
                </ul>
            </div>
            
            <!-- Security Warning -->
            <div class="warning">
                <strong>Important Security Notice:</strong>
                If you didn't create an account with AU-VLP, please ignore this email. Your email address may have been entered by mistake.
            </div>
            
            <!-- Alternative Method -->
            <div class="alternative-text">
                <p><strong>Having trouble with the button?</strong></p>
                <p>Copy and paste this link into your browser:</p>
                <p style="word-break: break-all; color: #16a34a;">{{ $verificationUrl }}</p>
                
                <hr style="margin: 20px 0; border: 1px solid #e5e7eb;">
                
                <p><strong>This verification link will expire in 24 hours.</strong></p>
                <p>If you need a new verification link, you can request one by visiting our <a href="{{ route('verification.notice') }}" style="color: #16a34a;">email verification page</a>.</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Africa Union Volunteer Leadership Program</strong></p>
            <p>Building tomorrow's leaders through service today</p>
            
            <div class="social-links">
                <a href="#">Website</a> |
                <a href="#">Facebook</a> |
                <a href="#">Twitter</a> |
                <a href="#">LinkedIn</a>
            </div>
            
            <p style="margin-top: 20px;">
                <a href="{{ route('contact') }}">Contact Support</a> | 
                <a href="{{ route('privacy') }}">Privacy Policy</a> | 
                <a href="{{ route('terms') }}">Terms of Service</a>
            </p>
            
            <p style="font-size: 12px; color: #9ca3af; margin-top: 20px;">
                This email was sent to {{ $user->email }}. If you no longer wish to receive emails from us, 
                you can <a href="#" style="color: #9ca3af;">unsubscribe</a> at any time.
            </p>
        </div>
    </div>
</body>
</html>