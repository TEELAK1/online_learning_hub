<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 0;
        }
        
        .content-section {
            padding: 40px 0;
        }
        
        .terms-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 30px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 mb-3">Terms of Service</h1>
                    <p class="lead mb-0">Please read these terms carefully before using our Online Learning Hub platform.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <i class="fas fa-file-contract fa-5x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <!-- Navigation -->
                    <div class="card terms-card sticky-top">
                        <div class="card-body">
                            <h5 class="card-title">Quick Navigation</h5>
                            <nav class="nav flex-column">
                                <a class="nav-link" href="#acceptance">Acceptance of Terms</a>
                                <a class="nav-link" href="#description">Service Description</a>
                                <a class="nav-link" href="#eligibility">Eligibility</a>
                                <a class="nav-link" href="#accounts">User Accounts</a>
                                <a class="nav-link" href="#conduct">User Conduct</a>
                                <a class="nav-link" href="#content">Content & Materials</a>
                                <a class="nav-link" href="#intellectual-property">Intellectual Property</a>
                                <a class="nav-link" href="#payments">Payments & Refunds</a>
                                <a class="nav-link" href="#termination">Termination</a>
                                <a class="nav-link" href="#disclaimers">Disclaimers</a>
                                <a class="nav-link" href="#contact">Contact Information</a>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-9">
                    <!-- Acceptance of Terms -->
                    <div class="card terms-card" id="acceptance">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-handshake me-2 text-primary"></i>Acceptance of Terms
                            </h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Effective Date:</strong> <?php echo date('F j, Y'); ?></p>
                            <p>Welcome to Online Learning Hub ("we," "our," or "us"). These Terms of Service ("Terms") govern your use of our educational platform and services. By accessing or using our platform, you agree to be bound by these Terms.</p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Important:</strong> If you do not agree to these Terms, please do not use our services.
                            </div>
                        </div>
                    </div>

                    <!-- Service Description -->
                    <div class="card terms-card" id="description">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-graduation-cap me-2 text-primary"></i>Service Description
                            </h3>
                        </div>
                        <div class="card-body">
                            <p>Online Learning Hub is an educational platform that provides:</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>For Students</h5>
                                    <ul>
                                        <li>Access to online courses and materials</li>
                                        <li>Interactive learning experiences</li>
                                        <li>Progress tracking and assessments</li>
                                        <li>Communication with instructors</li>
                                        <li>Certificates of completion</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>For Instructors</h5>
                                    <ul>
                                        <li>Course creation and management tools</li>
                                        <li>Student progress monitoring</li>
                                        <li>Assessment and grading features</li>
                                        <li>Communication platforms</li>
                                        <li>Analytics and reporting</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eligibility -->
                    <div class="card terms-card" id="eligibility">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-user-check me-2 text-primary"></i>Eligibility
                            </h3>
                        </div>
                        <div class="card-body">
                            <p>To use our services, you must:</p>
                            <ul>
                                <li>Be at least 13 years old (users under 18 require parental consent)</li>
                                <li>Provide accurate and complete registration information</li>
                                <li>Have the legal capacity to enter into these Terms</li>
                                <li>Not be prohibited from using our services under applicable law</li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Age Restrictions:</strong> Users under 13 are not permitted to use our platform without special arrangements.
                            </div>
                        </div>
                    </div>

                    <!-- User Accounts -->
                    <div class="card terms-card" id="accounts">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-user-cog me-2 text-primary"></i>User Accounts
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5>Account Registration</h5>
                            <ul>
                                <li>You must create an account to access most features</li>
                                <li>Provide accurate, current, and complete information</li>
                                <li>Maintain and update your account information</li>
                                <li>You are responsible for all activities under your account</li>
                            </ul>
                            
                            <h5>Account Security</h5>
                            <ul>
                                <li>Keep your password confidential and secure</li>
                                <li>Notify us immediately of any unauthorized access</li>
                                <li>Use strong passwords and enable two-factor authentication when available</li>
                                <li>Do not share your account credentials with others</li>
                            </ul>
                        </div>
                    </div>

                    <!-- User Conduct -->
                    <div class="card terms-card" id="conduct">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-gavel me-2 text-primary"></i>User Conduct
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-thumbs-up me-2"></i>Acceptable Use</h6>
                                <ul class="mb-0">
                                    <li>Use the platform for educational purposes</li>
                                    <li>Respect other users and instructors</li>
                                    <li>Follow academic integrity guidelines</li>
                                    <li>Provide constructive feedback and participation</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-ban me-2"></i>Prohibited Activities</h6>
                                <ul class="mb-0">
                                    <li>Harassment, bullying, or discriminatory behavior</li>
                                    <li>Cheating, plagiarism, or academic dishonesty</li>
                                    <li>Sharing inappropriate or offensive content</li>
                                    <li>Attempting to hack or disrupt the platform</li>
                                    <li>Violating intellectual property rights</li>
                                    <li>Spamming or commercial solicitation</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Content & Materials -->
                    <div class="card terms-card" id="content">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-file-alt me-2 text-primary"></i>Content & Materials
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5>Platform Content</h5>
                            <p>Our platform contains educational materials, courses, and resources. This content is:</p>
                            <ul>
                                <li>Protected by copyright and other intellectual property laws</li>
                                <li>Licensed to you for personal, non-commercial educational use</li>
                                <li>Subject to the terms of individual course licenses</li>
                            </ul>
                            
                            <h5>User-Generated Content</h5>
                            <p>When you submit content (assignments, posts, comments), you:</p>
                            <ul>
                                <li>Retain ownership of your original content</li>
                                <li>Grant us a license to use, display, and distribute your content on the platform</li>
                                <li>Represent that you have the right to share the content</li>
                                <li>Are responsible for the accuracy and legality of your content</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Intellectual Property -->
                    <div class="card terms-card" id="intellectual-property">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-copyright me-2 text-primary"></i>Intellectual Property
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Our Rights</h5>
                                    <ul>
                                        <li>Platform software and technology</li>
                                        <li>Trademarks and logos</li>
                                        <li>Original course content we create</li>
                                        <li>Platform design and functionality</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Your Rights</h5>
                                    <ul>
                                        <li>Your original assignments and projects</li>
                                        <li>Your personal information and data</li>
                                        <li>Content you create and upload</li>
                                        <li>Your learning achievements</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments & Refunds -->
                    <div class="card terms-card" id="payments">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-credit-card me-2 text-primary"></i>Payments & Refunds
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5>Payment Terms</h5>
                            <ul>
                                <li>Some courses may require payment for access</li>
                                <li>Prices are displayed in USD unless otherwise noted</li>
                                <li>Payment is due at the time of enrollment</li>
                                <li>We accept major credit cards and PayPal</li>
                            </ul>
                            
                            <h5>Refund Policy</h5>
                            <div class="alert alert-info">
                                <ul class="mb-0">
                                    <li><strong>30-Day Guarantee:</strong> Full refund within 30 days of purchase</li>
                                    <li><strong>Partial Completion:</strong> Prorated refunds for courses less than 50% complete</li>
                                    <li><strong>Technical Issues:</strong> Full refund for platform-related problems</li>
                                    <li><strong>Refund Process:</strong> 5-10 business days to process</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Termination -->
                    <div class="card terms-card" id="termination">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-times-circle me-2 text-primary"></i>Termination
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5>Your Right to Terminate</h5>
                            <p>You may terminate your account at any time by:</p>
                            <ul>
                                <li>Contacting our support team</li>
                                <li>Using the account deletion feature in settings</li>
                                <li>Sending a written request to our address</li>
                            </ul>
                            
                            <h5>Our Right to Terminate</h5>
                            <p>We may suspend or terminate your account if you:</p>
                            <ul>
                                <li>Violate these Terms of Service</li>
                                <li>Engage in prohibited activities</li>
                                <li>Fail to pay required fees</li>
                                <li>Provide false information</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Disclaimers -->
                    <div class="card terms-card" id="disclaimers">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2 text-primary"></i>Disclaimers & Limitations
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <h6>Service Availability</h6>
                                <p class="mb-0">Our platform is provided "as is" and "as available." We do not guarantee uninterrupted access or error-free operation.</p>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6>Educational Outcomes</h6>
                                <p class="mb-0">While we strive to provide quality education, we cannot guarantee specific learning outcomes or career advancement.</p>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6>Third-Party Content</h6>
                                <p class="mb-0">We are not responsible for the accuracy or quality of content provided by third-party instructors or external links.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card terms-card" id="contact">
                        <div class="section-header">
                            <h3 class="mb-0">
                                <i class="fas fa-envelope me-2 text-primary"></i>Contact Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <p>If you have questions about these Terms of Service, please contact us:</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Legal Department</h6>
                                    <p>
                                        <i class="fas fa-envelope me-2"></i>teamolh@gmail.com<br>
                                        <i class="fas fa-phone me-2"></i>+977 9864471849<br>
                                        <i class="fas fa-map-marker-alt me-2"></i>Rupendehi, Nepal
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Updates to Terms</h6>
                                    <p>We may update these Terms periodically. Continued use of our platform constitutes acceptance of any changes.</p>
                                    
                                    <h6>Governing Law</h6>
                                    <p>These Terms are governed by the laws of [Your Jurisdiction] without regard to conflict of law principles.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Back to Top -->
                    <div class="text-center mt-4">
                        <a href="#top" class="btn btn-primary">
                            <i class="fas fa-arrow-up me-2"></i>Back to Top
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Return to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
