<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to AU-VLP!</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            padding: 40px 20px;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="70" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="70" cy="80" r="2.5" fill="rgba(255,255,255,0.1)"/><circle cx="30" cy="40" r="1.2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.3;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header p {
            margin: 10px 0 0;
            font-size: 18px;
            opacity: 0.9;
        }
        
        .celebration-emoji {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 25px;
        }
        
        .message {
            font-size: 16px;
            line-height: 1.7;
            color: #4b5563;
            margin-bottom: 30px;
        }
        
        .cta-button {
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
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(16, 185, 129, 0.5);
        }
        
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .next-steps {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 30px 25px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        
        .next-steps::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #16a34a, #10b981);
        }
        
        .next-steps h3 {
            color: #166534;
            margin: 0 0 25px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .next-steps h3::before {
            content: '🚀';
            font-size: 20px;
            margin-right: 10px;
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #16a34a;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }
        
        .step:hover {
            transform: translateX(5px);
        }
        
        .step-number {
            background: linear-gradient(135deg, #16a34a 0%, #10b981 100%);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .step-content h4 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #166534;
            font-weight: 600;
        }
        
        .step-content p {
            margin: 0;
            font-size: 14px;
            color: #166534;
            line-height: 1.5;
        }
        
        .stats {
            background: linear-gradient(135deg, #fef7ff 0%, #faf5ff 100%);
            border: 1px solid #e9d5ff;
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            margin: 25px 0;
            position: relative;
            overflow: hidden;
        }
        
        .stats::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.05) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .stats-content {
            position: relative;
            z-index: 1;
        }
        
        .stats h3 {
            color: #7c3aed;
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stats h3::before {
            content: '📊';
            font-size: 20px;
            margin-right: 10px;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: #7c3aed;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b46c1;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .resources {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .resources h3 {
            color: #ea580c;
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .resources h3::before {
            content: '📚';
            font-size: 20px;
            margin-right: 10px;
        }
        
        .resource-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .resource-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: white;
            border-radius: 6px;
            text-decoration: none;
            color: #ea580c;
            font-weight: 500;
            font-size: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .resource-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
        }
        
        .resource-link::before {
            content: '→';
            margin-right: 8px;
            font-weight: bold;
        }
        
        .support-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }
        
        .support-section h3 {
            color: #475569;
            margin: 0 0 15px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .support-section h3::before {
            content: '💬';
            font-size: 20px;
            margin-right: 10px;
        }
        
        .contact-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .contact-option {
            padding: 15px;
            background: white;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .contact-option a {
            color: #16a34a;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        
        .contact-option a:hover {
            text-decoration: underline;
        }
        
        .footer {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 40px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-logo {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 10px;
        }
        
        .footer-tagline {
            color: #6b7280;
            font-size: 16px;
            margin: 0 0 25px;
            font-style: italic;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
            padding: 0;
            list-style: none;
        }
        
        .footer-links a {
            color: #16a34a;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .footer-links a:hover {
            background-color: #f0f9ff;
            text-decoration: underline;
        }
        
        .social-section {
            margin: 25px 0;
            padding: 20px 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .social-section h4 {
            color: #374151;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 15px;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 6px;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .social-links a:hover {
            color: #16a34a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .footer-disclaimer {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 25px;
            line-height: 1.5;
        }
        
        .footer-disclaimer a {
            color: #9ca3af;
            text-decoration: underline;
        }
        
        .footer-disclaimer a:hover {
            color: #6b7280;
        }
        
        @media (max-width: 600px) {
            body { 
                padding: 10px; 
            }
            
            .content { 
                padding: 30px 20px; 
            }
            
            .header { 
                padding: 30px 20px; 
            }
            
            .header h1 { 
                font-size: 28px; 
            }
            
            .stat-grid { 
                grid-template-columns: repeat(2, 1fr); 
            }
            
            .resource-links {
                grid-template-columns: 1fr;
            }
            
            .contact-options {
                grid-template-columns: 1fr;
            }
            
            .footer-links {
                flex-direction: column;
                align-items: center;
            }
            
            .social-links {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <span class="celebration-emoji">🎉</span>
                <h1>Welcome Aboard!</h1>
                <p>Your volunteering journey starts now</p>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $user->getFullNameAttribute() }}!
            </div>
            
            <div class="message">
                <p>Congratulations! Your email has been verified and your AU-VLP account is now active. You're officially part of our amazing community of volunteers who are making a real difference across Africa.</p>
                
                <p>We're thrilled to have you join us in our mission to build tomorrow's leaders through service today. Your commitment to volunteering will help create positive change in communities across the continent.</p>
            </div>
            
            <!-- Statistics -->
            <div class="stats">
                <div class="stats-content">
                    <h3>You're joining something amazing!</h3>
                    <div class="stat-grid">
                        <div class="stat-item">
                            <span class="stat-number">2,500+</span>
                            <span class="stat-label">Active Volunteers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">55</span>
                            <span class="stat-label">Countries</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">150+</span>
                            <span class="stat-label">Organizations</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Active Opportunities</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="button-container">
                <a href="{{ route('dashboard') }}" class="cta-button">
                    🚀 Explore Your Dashboard
                </a>
            </div>
            
            <!-- Next Steps -->
            <div class="next-steps">
                <h3>Your next steps</h3>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Complete Your Profile</h4>
                        <p>Add more details about yourself, upload a profile picture, and tell us about your skills and interests. A complete profile helps organizations find the perfect match for their opportunities.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Browse Opportunities</h4>
                        <p>Discover volunteer opportunities that match your interests, skills, and availability across Africa. Use our advanced filters to find exactly what you're looking for.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Connect & Apply</h4>
                        <p>Join our community forums, connect with other volunteers, and apply for your first opportunity! Our matching system will help connect you with the right projects.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Start Making Impact</h4>
                        <p>Begin your volunteer work, track your hours, earn recognition for your contributions, and build a portfolio of your volunteer achievements.</p>
                    </div>
                </div>
            </div>
            
            <!-- Resources Section -->
            <div class="resources">
                <h3>Quick Start Resources</h3>
                <p style="color: #ea580c; margin-bottom: 15px; font-size: 14px;">Everything you need to get started on your volunteer journey</p>
                <div class="resource-links">
                    <a href="{{ route('profile.edit') }}" class="resource-link">Complete Your Profile</a>
                    <a href="{{ route('opportunities.index') }}" class="resource-link">Browse Opportunities</a>
                    <a href="{{ route('forums.index') }}" class="resource-link">Join Community Forums</a>
                    <a href="{{ route('volunteer.interests') }}" class="resource-link">Update Your Interests</a>
                </div>
            </div>
            
            <!-- Support Section -->
            <div class="support-section">
                <h3>Need Help Getting Started?</h3>
                <p style="color: #64748b; margin-bottom: 20px; font-size: 14px;">Our support team is here to help you every step of the way</p>
                <div class="contact-options">
                    <div class="contact-option">
                        <a href="{{ route('contact') }}">📧 Contact Support</a>
                    </div>
                    <div class="contact-option">
                        <a href="#">💬 Live Chat</a>
                    </div>
                    <div class="contact-option">
                        <a href="#">📚 Help Center</a>
                    </div>
                    <div class="contact-option">
                        <a href="#">🎥 Video Tutorials</a>
                    </div>
                </div>
            </div>
            
            <div class="message">
                <p>Welcome to the AU-VLP family! Together, we're building a brighter future for Africa through service, leadership, and community engagement.</p>
                
                <p><strong>Ready to make your first impact?</strong> Start by exploring opportunities in your area or connecting with other volunteers in our community forums.</p>
                
                <p>Best regards,<br>
                <strong>The AU-VLP Team</strong></p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">AU-VLP</div>
            <div class="footer-tagline">Building tomorrow's leaders through service today</div>
            
            <ul class="footer-links">
                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li><a href="{{ route('opportunities.index') }}">Browse Opportunities</a></li>
                <li><a href="{{ route('profile.show') }}">My Profile</a></li>
                <li><a href="{{ route('forums.index') }}">Community Forums</a></li>
                <li><a href="{{ route('volunteer.history') }}">My Volunteer History</a></li>
            </ul>
            
            <div class="social-section">
                <h4>Stay Connected</h4>
                <div class="social-links">
                    <a href="#">🌐 Website</a>
                    <a href="#">📘 Facebook</a>
                    <a href="#">🐦 Twitter</a>
                    <a href="#">💼 LinkedIn</a>
                    <a href="#">📸 Instagram</a>
                    <a href="#">🎬 YouTube</a>
                </div>
            </div>
            
            <div style="margin: 20px 0; padding: 20px 0; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 14px; color: #6b7280; margin: 10px 0;">
                    <strong>Support & Resources:</strong>
                </p>
                <p style="font-size: 14px; color: #6b7280; margin: 5px 0;">
                    <a href="{{ route('contact') }}">Contact Support</a> | 
                    <a href="{{ route('privacy') }}">Privacy Policy</a> | 
                    <a href="{{ route('terms') }}">Terms of Service</a> | 
                    <a href="#">Help Center</a>
                </p>
            </div>
            
            <div class="footer-disclaimer">
                <p>
                    This email was sent to <strong>{{ $user->email }}</strong> because you registered for an AU-VLP account. 
                    You can manage your email preferences in your 
                    <a href="{{ route('profile.edit') }}">account settings</a>.
                </p>
                <p style="margin-top: 15px;">
                    <strong>Africa Union Volunteer Leadership Program</strong><br>
                    Connecting volunteers with opportunities across Africa<br>
                    © {{ date('Y') }} AU-VLP. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>