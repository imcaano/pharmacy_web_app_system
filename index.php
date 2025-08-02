<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaWeb - Modern Pharmacy System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html {
            box-sizing: border-box;
            font-size: 16px;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0f7fa 0%, #f5fafd 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            margin: 0;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
            color: #2dbee5 !important;
            font-size: 1.7rem;
        }
        .nav-link {
            color: #2dbee5 !important;
            font-weight: 500;
            margin-right: 1.2rem;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
        }
        .nav-link i { margin-right: 0.5rem; }
        .hero-section {
            background: linear-gradient(120deg, #f5fafd 60%, #2dbee5 100%);
            border-radius: 0 0 3rem 3rem;
            box-shadow: 0 8px 32px rgba(46,198,242,0.13);
            padding-bottom: 3rem;
        }
        .hero-content {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 3rem;
            width: 100%;
            margin-top: 0.5rem;
        }
        .hero-visual {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            margin-top: 0.5rem;
        }
        .hero-visual .icon-bg {
            background: linear-gradient(135deg, #2dbee5 0%, #0b6e6e 100%);
            border-radius: 50%;
            width: 15rem;
            height: 15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(46,198,242,0.13);
            animation: float 3s ease-in-out infinite;
        }
        .hero-visual i {
            color: #fff;
            font-size: 6rem;
            text-shadow: 0 4px 24px rgba(46,198,242,0.15);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-18px); }
        }
        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2dbee5;
            margin-bottom: 2.2rem;
            line-height: 1.15;
        }
        .cta-btn {
            background: linear-gradient(90deg, #2dbee5 0%, #0b6e6e 100%);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 30px;
            padding: 0.95rem 2.7rem;
            font-size: 1.18rem;
            box-shadow: 0 4px 24px rgba(46,198,242,0.13);
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
            margin-bottom: 3rem;
            text-decoration: none;
        }
        .cta-btn:hover {
            background: linear-gradient(90deg, #0b6e6e 0%, #2dbee5 100%);
            box-shadow: 0 8px 32px rgba(46,198,242,0.18);
            transform: translateY(-2px) scale(1.04);
            color: #fff;
        }
        .feature-icon {
            background: #e0f7fa;
            color: #2dbee5;
            border-radius: 50%;
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .feature-icon:hover {
            box-shadow: 0 4px 16px rgba(46,198,242,0.18);
            transform: scale(1.08);
        }
        .feature-label {
            font-size: 1rem;
            color: #4b4b6b;
            font-weight: 500;
            text-align: center;
        }
        .trusted-section {
            margin: 5rem 0 4.5rem 0;
            text-align: center;
        }
        .trusted-logos {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            margin-top: 2.2rem;
            flex-wrap: wrap;
        }
        .trusted-logo {
            width: 7rem;
            height: 2.5rem;
            background: #e6f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0b6e6e;
            font-size: 1.3rem;
            font-weight: 700;
            opacity: 0.9;
        }
        .stats-section {
            margin: 4.5rem 0 7rem 0;
            display: flex;
            justify-content: center;
            gap: 4rem;
            flex-wrap: wrap;
        }
        .stat-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 2px 16px rgba(46,198,242,0.13);
            padding: 2.7rem 3rem;
            text-align: center;
            min-width: 13rem;
            margin-bottom: 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .stat-value {
            font-size: 2.1rem;
            font-weight: 700;
            color: #2dbee5;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .stat-value i {
            font-size: 2.2rem;
        }
        .stat-label {
            color: #6c6a7c;
            font-size: 1.08rem;
        }

        /* How to Use Section Styles */
        .how-to-use {
            padding: 5rem 0;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        .how-to-use h2 {
            text-align: center;
            color: #0b6e6e;
            font-weight: 700;
            margin-bottom: 3rem;
        }
        .step-card {
            background: #fff;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(11, 110, 110, 0.08);
            transition: transform 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-5px);
        }
        .step-number {
            background: #2dbee5;
            color: white;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .step-title {
            color: #2dbee5;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        .step-desc {
            color: #6c6a7c;
            font-size: 1rem;
            line-height: 1.6;
        }
        .step-icon {
            color: #2dbee5;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .expert-shape {
            position: absolute;
            top: -60px;
            right: -60px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle at 60% 40%, #2dbee5 0%, #f5fafd 100%);
            border-radius: 50%;
            opacity: 0.18;
            z-index: 0;
        }
        .expert-shape2 {
            position: absolute;
            left: -80px;
            bottom: -80px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle at 40% 60%, #2dbee5 0%, #f5fafd 100%);
            border-radius: 50%;
            opacity: 0.13;
            z-index: 0;
        }
        footer {
            background:transparent; color:#2dbee5; font-size:1rem; z-index:2; position:relative; margin-top: 3.5rem;
        }
        @media (max-width: 992px) {
            .hero-content { flex-direction: column; gap: 2rem; }
            .hero-visual { margin-bottom: 1.5rem; }
        }
        @media (max-width: 768px) {
            html { font-size: 15px; }
            .hero-title { font-size: 2rem; }
            .hero-section { padding: 1.5rem 0 1rem 0; margin-bottom: 1.5rem; }
            .feature-icons { flex-direction: column; gap: 2.2rem; margin-top: 2rem; margin-bottom: 2.5rem; }
            .stats-section { flex-direction: column; gap: 2.2rem; margin: 2.5rem 0 4rem 0; }
            .trusted-section { margin: 2.5rem 0 2.5rem 0; }
        }
        @media (max-width: 576px) {
            html { font-size: 14px; }
            .hero-visual .icon-bg { width: 10rem; height: 10rem; }
            .stat-card { padding: 1.2rem 1rem; min-width: 10rem; }
            .trusted-logo { width: 5.5rem; height: 2rem; font-size: 1rem; }
        }
    </style>
</head>
<body style="padding-top:0; margin-top:0;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light px-4" style="margin-bottom:0;">
        <div class="container-xl">
            <a class="navbar-brand" href="#"><i class="fas fa-capsules me-2"></i>PharmaWeb</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="#features"><i class="fas fa-star"></i>Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how"><i class="fas fa-question-circle"></i>How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i>Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="signup.php"><i class="fas fa-user-plus"></i>Sign Up</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center justify-content-center" style="min-height: 80vh;">
        <div class="container-xl">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-5 mb-4 mb-lg-0 d-flex justify-content-center">
                    <div class="icon-bg shadow-lg" style="background: linear-gradient(135deg, #2dbee5 0%, #0b6e6e 100%); border-radius: 50%; width: 15rem; height: 15rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-prescription-bottle-medical" style="color: #fff; font-size: 6rem;"></i>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="p-5 bg-white rounded-4 shadow-lg" style="backdrop-filter: blur(6px);">
                        <span class="badge-modern mb-3" style="background: #e0f7fa; color: #2dbee5; font-weight: 700; border-radius: 20px; padding: 0.5rem 1.5rem; font-size: 1.1rem; letter-spacing: 1px;"><i class="fas fa-shield-alt me-2"></i>Secure & Modern</span>
                        <div class="hero-title mb-3" style="color: #2dbee5; font-size: 2.5rem; font-weight: 800;">Empowering Modern Pharmacies</div>
                        <div class="hero-desc mb-4" style="color: #6c6a7c; font-size: 1.15rem; line-height: 1.7;">Manage your pharmacy, medicines, and prescriptions with ease. Secure, modern, and built for the future of healthcare. Experience seamless order processing, inventory management, and digital prescriptions—all in one place.</div>
                        <a href="signup.php" class="cta-btn mt-2"><i class="fas fa-arrow-right me-2"></i>Get Started</a>
                        <div class="row mt-4">
                            <div class="col-6 col-md-3 text-center mb-3">
                                <div class="feature-icon mx-auto mb-2"><i class="fas fa-pills"></i></div>
                                <div class="feature-label">Medicine Management</div>
                            </div>
                            <div class="col-6 col-md-3 text-center mb-3">
                                <div class="feature-icon mx-auto mb-2"><i class="fas fa-file-medical"></i></div>
                                <div class="feature-label">Digital Prescriptions</div>
                            </div>
                            <div class="col-6 col-md-3 text-center mb-3">
                                <div class="feature-icon mx-auto mb-2"><i class="fas fa-wallet"></i></div>
                                <div class="feature-label">Secure Payments</div>
                            </div>
                            <div class="col-6 col-md-3 text-center mb-3">
                                <div class="feature-icon mx-auto mb-2"><i class="fas fa-user-shield"></i></div>
                                <div class="feature-label">Role-based Access</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Trusted By Section -->
    <section class="trusted-section">
        <div class="container-xl">
            <div class="text-muted mb-2" style="font-weight:600; letter-spacing:1px;">Trusted by</div>
            <div class="trusted-logos">
                <div class="trusted-logo"><i class="fas fa-hospital"></i> Medix</div>
                <div class="trusted-logo"><i class="fas fa-clinic-medical"></i> HealthPro</div>
                <div class="trusted-logo"><i class="fas fa-capsules"></i> Pillar</div>
                <div class="trusted-logo"><i class="fas fa-user-md"></i> DocuCare</div>
            </div>
        </div>
    </section>
    <!-- Stats/Testimonials Section -->
    <section class="stats-section">
        <div class="container-xl d-flex flex-wrap justify-content-center gap-4">
            <div class="stat-card">
                <div class="stat-value"><i class="fas fa-users"></i>1,200+</div>
                <div class="stat-label">Active Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><i class="fas fa-shopping-cart"></i>5,000+</div>
                <div class="stat-label">Orders Processed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><i class="fas fa-star"></i>99%</div>
                <div class="stat-label">Customer Satisfaction</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><i class="fas fa-user-md"></i>300+</div>
                <div class="stat-label">Partnered Doctors</div>
            </div>
        </div>
    </section>
    <!-- How to Use Section -->
    <section class="how-to-use" id="how">
        <div class="container">
            <h2 class="text-center mb-5">How to Use PharmaWeb Like an Expert</h2>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <i class="fas fa-user-plus step-icon"></i>
                        <div class="step-title">Create Your Account</div>
                        <div class="step-desc">Sign up as a pharmacy or customer. Connect your MetaMask wallet for secure transactions and identity verification.</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <i class="fas fa-store step-icon"></i>
                        <div class="step-title">Set Up Your Profile</div>
                        <div class="step-desc">Add your pharmacy details, upload necessary documents, and customize your digital storefront.</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <i class="fas fa-pills step-icon"></i>
                        <div class="step-title">Manage Inventory</div>
                        <div class="step-desc">Add medicines, set prices, track stock levels, and receive low-stock alerts automatically.</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <i class="fas fa-file-medical step-icon"></i>
                        <div class="step-title">Handle Prescriptions</div>
                        <div class="step-desc">Verify digital prescriptions, process orders, and maintain compliance with regulations.</div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">5</div>
                        <i class="fas fa-chart-line step-icon"></i>
                        <div class="step-title">Track Analytics</div>
                        <div class="step-desc">Monitor sales, view customer trends, and generate detailed reports for business insights.</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">6</div>
                        <i class="fas fa-wallet step-icon"></i>
                        <div class="step-title">Process Payments</div>
                        <div class="step-desc">Accept secure payments through MetaMask and traditional payment methods.</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">7</div>
                        <i class="fas fa-truck step-icon"></i>
                        <div class="step-title">Manage Orders</div>
                        <div class="step-desc">Track order status, manage deliveries, and ensure timely fulfillment of prescriptions.</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="step-card">
                        <div class="step-number">8</div>
                        <i class="fas fa-shield-alt step-icon"></i>
                        <div class="step-title">Ensure Compliance</div>
                        <div class="step-desc">Stay compliant with healthcare regulations while maintaining digital records securely.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Bottom Wave Background -->
    <div class="wave-bg wave-bottom" style="z-index:0;">
        <svg width="100%" height="220" viewBox="0 0 1440 220" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="1440" height="220" fill="#e6f0f0"/>
            <path d="M0,120 C360,220 1080,40 1440,180 L1440,220 L0,220 Z" fill="#b8dada" fill-opacity="0.6"/>
        </svg>
    </div>
    <footer class="text-center py-4" style="background:transparent; color:#2dbee5; font-size:1rem; z-index:2; position:relative; margin-top: 3.5rem;">
        Designed by <i class="fas fa-heart" style="color: #2dbee5;"></i> PharmaWeb Team
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 