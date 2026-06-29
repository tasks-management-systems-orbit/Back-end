<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ORBIT – منصة إدارة المشاريع والفرق المتكاملة">
    <title>ORBIT – منصة إدارة المشاريع</title>

    <!-- Google Fonts: Cairo + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* =========================================
           RESET & BASE
        ========================================= */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --emerald:        #10b981;
            --emerald-light:  #34d399;
            --emerald-dark:   #065f46;
            --emerald-mid:    #059669;
            --emerald-pale:   #d1fae5;
            --emerald-ghost:  #ecfdf5;
            --white:          #ffffff;
            --off-white:      #f8fafc;
            --slate-50:       #f1f5f9;
            --slate-100:      #e2e8f0;
            --slate-300:      #cbd5e1;
            --slate-500:      #64748b;
            --slate-700:      #334155;
            --slate-900:      #0f172a;
            --shadow-xs:      0 1px 2px rgba(0,0,0,.05);
            --shadow-sm:      0 2px 8px rgba(16,185,129,.08);
            --shadow-md:      0 8px 32px rgba(16,185,129,.12);
            --shadow-lg:      0 20px 60px rgba(16,185,129,.18);
            --radius-card:    48px;
            --radius-feature: 24px;
            --radius-btn:     14px;
            --radius-pill:    999px;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Cairo', 'Inter', sans-serif;
            background-color: var(--off-white);
            color: var(--slate-700);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* =========================================
           BACKGROUND CANVAS
        ========================================= */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(16,185,129,.09) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 80% 80%, rgba(6,95,70,.07) 0%, transparent 70%),
                radial-gradient(ellipse 100% 80% at 50% 50%, rgba(209,250,229,.25) 0%, transparent 100%),
                linear-gradient(160deg, #f0fdf4 0%, #f8fafc 50%, #ecfdf5 100%);
            pointer-events: none;
        }

        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .5;
            pointer-events: none;
            z-index: 0;
        }
        .orb-1 {
            width: clamp(300px, 40vw, 600px);
            height: clamp(300px, 40vw, 600px);
            background: radial-gradient(circle, rgba(16,185,129,.18), transparent 70%);
            top: -15%;
            right: -10%;
            animation: floatOrb 14s ease-in-out infinite alternate;
        }
        .orb-2 {
            width: clamp(200px, 30vw, 450px);
            height: clamp(200px, 30vw, 450px);
            background: radial-gradient(circle, rgba(6,95,70,.12), transparent 70%);
            bottom: -10%;
            left: -8%;
            animation: floatOrb 18s ease-in-out infinite alternate-reverse;
        }
        .orb-3 {
            width: clamp(150px, 20vw, 300px);
            height: clamp(150px, 20vw, 300px);
            background: radial-gradient(circle, rgba(52,211,153,.14), transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: floatOrb 22s ease-in-out infinite alternate;
        }

        @keyframes floatOrb {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(3%, 5%) scale(1.06); }
        }

        /* =========================================
           LAYOUT WRAPPER
        ========================================= */
        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(24px, 5vw, 80px) clamp(16px, 4vw, 40px);
        }

        /* =========================================
           MAIN CARD
        ========================================= */
        .main-card {
            width: 100%;
            max-width: 860px;
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            border: 1px solid rgba(255,255,255,.7);
            border-radius: var(--radius-card);
            box-shadow:
                0 2px 4px rgba(0,0,0,.04),
                0 8px 24px rgba(16,185,129,.10),
                0 32px 80px rgba(6,95,70,.12),
                inset 0 1px 0 rgba(255,255,255,.9);
            padding: clamp(40px, 6vw, 72px) clamp(28px, 6vw, 72px);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(32px, 5vw, 52px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Subtle inner glow */
        .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(16,185,129,.5), rgba(52,211,153,.5), transparent);
            border-radius: var(--radius-pill);
        }

        /* =========================================
           LOGO + BRAND
        ========================================= */
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }

        .logo-wrap {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--emerald-dark) 0%, var(--emerald) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 4px 16px rgba(16,185,129,.35),
                inset 0 1px 0 rgba(255,255,255,.15);
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        .logo-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.1) 0%, transparent 60%);
        }

        .logo-img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            position: relative;
            z-index: 1;
        }

        /* SVG fallback logo */
        .logo-svg-fallback {
            width: 44px;
            height: 44px;
            position: relative;
            z-index: 1;
        }

        .brand-name {
            font-family: 'Inter', sans-serif;
            font-size: clamp(30px, 4vw, 40px);
            font-weight: 800;
            letter-spacing: .08em;
            background: linear-gradient(135deg, var(--emerald-dark) 0%, var(--emerald) 55%, var(--emerald-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        /* =========================================
           TAGLINE BADGE
        ========================================= */
        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--emerald-ghost), var(--emerald-pale));
            border: 1px solid rgba(16,185,129,.25);
            color: var(--emerald-dark);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .04em;
            padding: 7px 18px;
            border-radius: var(--radius-pill);
        }

        .badge-pill .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--emerald);
            box-shadow: 0 0 0 3px rgba(16,185,129,.25);
            animation: pulse-dot 2.4s ease infinite;
            flex-shrink: 0;
        }

        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 3px rgba(16,185,129,.25); }
            50%       { box-shadow: 0 0 0 6px rgba(16,185,129,.10); }
        }

        /* =========================================
           HERO TEXT
        ========================================= */
        .hero-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .hero-title {
            font-size: clamp(26px, 4.5vw, 46px);
            font-weight: 800;
            color: var(--emerald-dark);
            line-height: 1.25;
            letter-spacing: -.01em;
        }

        .hero-title .highlight {
            display: inline-block;
            position: relative;
            color: var(--emerald-mid);
        }

        .hero-title .highlight::after {
            content: '';
            position: absolute;
            bottom: 2px;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--emerald-light), var(--emerald));
            border-radius: var(--radius-pill);
            opacity: .5;
        }

        .hero-desc {
            font-size: clamp(15px, 2vw, 17px);
            font-weight: 400;
            color: var(--slate-500);
            line-height: 1.8;
            max-width: 560px;
        }

        /* =========================================
           CTA BUTTON
        ========================================= */
        .cta-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--emerald-dark) 0%, var(--emerald) 60%, var(--emerald-light) 100%);
            color: var(--white);
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            padding: 15px 36px;
            border-radius: var(--radius-btn);
            box-shadow:
                0 4px 16px rgba(16,185,129,.35),
                0 1px 3px rgba(0,0,0,.12);
            transition: transform .22s ease, box-shadow .22s ease;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.15) 0%, transparent 50%);
            transition: opacity .22s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow:
                0 8px 28px rgba(16,185,129,.45),
                0 2px 6px rgba(0,0,0,.12);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-primary .btn-icon {
            font-size: 15px;
            transition: transform .22s ease;
        }

        .btn-primary:hover .btn-icon {
            transform: translateX(-4px);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            color: var(--slate-500);
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            padding: 14px 24px;
            border-radius: var(--radius-btn);
            border: 1px solid var(--slate-200, #e2e8f0);
            transition: color .2s ease, border-color .2s ease, background .2s ease;
            white-space: nowrap;
        }

        .btn-secondary:hover {
            color: var(--emerald-dark);
            border-color: rgba(16,185,129,.4);
            background: var(--emerald-ghost);
        }

        /* =========================================
           DIVIDER
        ========================================= */
        .divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--slate-100), rgba(16,185,129,.2), var(--slate-100), transparent);
        }

        /* =========================================
           FEATURES GRID
        ========================================= */
        .features-heading {
            font-size: 13px;
            font-weight: 600;
            color: var(--slate-300);
            letter-spacing: .10em;
            text-transform: uppercase;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: clamp(10px, 2vw, 16px);
            width: 100%;
        }

        @media (max-width: 600px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .feature-card {
            background: rgba(248,250,252,.7);
            border: 1px solid rgba(226,232,240,.8);
            border-radius: var(--radius-feature);
            padding: clamp(18px, 3vw, 26px) clamp(14px, 2.5vw, 22px);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
            cursor: default;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(16,185,129,.04) 0%, transparent 60%);
            opacity: 0;
            transition: opacity .22s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 8px 24px rgba(16,185,129,.12),
                0 2px 6px rgba(0,0,0,.06);
            border-color: rgba(16,185,129,.3);
            background: rgba(255,255,255,.9);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon-wrap {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--emerald-ghost), var(--emerald-pale));
            border: 1px solid rgba(16,185,129,.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background .22s ease, box-shadow .22s ease;
            position: relative;
            z-index: 1;
        }

        .feature-card:hover .feature-icon-wrap {
            background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(52,211,153,.12));
            box-shadow: 0 4px 12px rgba(16,185,129,.2);
        }

        .feature-icon-wrap i {
            font-size: 18px;
            color: var(--emerald-mid);
        }

        .feature-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--slate-700);
            line-height: 1.4;
            position: relative;
            z-index: 1;
        }

        .feature-desc {
            font-size: 12px;
            font-weight: 400;
            color: var(--slate-500);
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        /* =========================================
           STATS ROW
        ========================================= */
        .stats-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(24px, 5vw, 56px);
            width: 100%;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .stat-value {
            font-family: 'Inter', sans-serif;
            font-size: clamp(22px, 3.5vw, 32px);
            font-weight: 800;
            background: linear-gradient(135deg, var(--emerald-dark), var(--emerald));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--slate-500);
            letter-spacing: .03em;
        }

        .stat-sep {
            width: 1px;
            height: 36px;
            background: linear-gradient(to bottom, transparent, var(--slate-200), transparent);
        }

        /* =========================================
           FOOTER
        ========================================= */
        .card-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            color: var(--slate-300);
            font-weight: 400;
        }

        .card-footer a {
            color: var(--emerald-mid);
            text-decoration: none;
            font-weight: 500;
            transition: color .2s ease;
        }

        .card-footer a:hover {
            color: var(--emerald-dark);
        }

        /* =========================================
           FLOATING ELEMENTS (decorative)
        ========================================= */
        .deco-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(16,185,129,.12);
            pointer-events: none;
        }

        .deco-ring-1 {
            width: 200px;
            height: 200px;
            top: -60px;
            left: -60px;
        }

        .deco-ring-2 {
            width: 140px;
            height: 140px;
            bottom: -40px;
            right: -30px;
            border-color: rgba(52,211,153,.1);
        }

        /* =========================================
           REDUCED MOTION
        ========================================= */
        @media (prefers-reduced-motion: reduce) {
            .orb, .badge-pill .dot { animation: none; }
            .btn-primary, .feature-card { transition: none; }
        }

        /* =========================================
           MOBILE REFINEMENTS
        ========================================= */
        @media (max-width: 480px) {
            .main-card {
                border-radius: 32px;
                padding: 36px 20px;
            }

            .brand {
                flex-direction: column;
                gap: 10px;
            }

            .stat-sep {
                display: none;
            }

            .stats-row {
                gap: 20px;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .cta-wrap {
                flex-direction: column;
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Background -->
    <div class="bg-canvas" aria-hidden="true"></div>
    <div class="orb orb-1" aria-hidden="true"></div>
    <div class="orb orb-2" aria-hidden="true"></div>
    <div class="orb orb-3" aria-hidden="true"></div>

    <!-- Page wrapper -->
    <div class="page-wrapper">
        <main class="main-card" role="main">

            <!-- Decorative rings -->
            <div class="deco-ring deco-ring-1" aria-hidden="true"></div>
            <div class="deco-ring deco-ring-2" aria-hidden="true"></div>

            <!-- ─── BRAND ─── -->
            <div class="brand">
                <div class="logo-wrap">
                    @if (file_exists(public_path('vendor/adminlte/dist/img/logo.png')))
                        <img
                            src="{{ asset('vendor/adminlte/dist/img/logo.png') }}"
                            alt="ORBIT Logo"
                            class="logo-img"
                        >
                    @else
                        <!-- SVG fallback: orbital ring mark -->
                        <svg class="logo-svg-fallback" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="ORBIT">
                            <circle cx="22" cy="22" r="10" stroke="white" stroke-width="2.5" fill="none" opacity=".6"/>
                            <ellipse cx="22" cy="22" rx="20" ry="8" stroke="white" stroke-width="2" fill="none" transform="rotate(-30 22 22)" opacity=".8"/>
                            <circle cx="22" cy="8" r="3.5" fill="white"/>
                        </svg>
                    @endif
                </div>
                <span class="brand-name">ORBIT</span>
            </div>

            <!-- ─── BADGE ─── -->
            <div class="badge-pill">
                <span class="dot"></span>
                منصة إدارة المشاريع والفرق
            </div>

            <!-- ─── HERO TEXT ─── -->
            <div class="hero-section">
                <h1 class="hero-title">
                    نظّم فرقك ومشاريعك<br>
                    <span class="highlight">في مكان واحد</span>
                </h1>
                <p class="hero-desc">
                    منصة متكاملة تجمع بين لوحات كانبان، المهام الفرعية، سلاسل المشاريع،
                    والإشعارات الفورية – كل ما تحتاجه لتحقيق الإنتاجية الحقيقية.
                </p>
            </div>

            <!-- ─── CTA BUTTONS ─── -->
            <div class="cta-wrap">
                <a href="{{ url('/admin/dashboard') }}" class="btn-primary">
                    الانتقال إلى لوحة التحكم
                    <i class="fa-solid fa-arrow-left btn-icon"></i>
                </a>
                <a href="mailto:support@orbit.app" class="btn-secondary">
                    <i class="fa-regular fa-envelope"></i>
                    تواصل معنا
                </a>
            </div>

            <!-- ─── STATS ─── -->
            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-value">100+</span>
                    <span class="stat-label">مشروع متزامن</span>
                </div>
                <div class="stat-sep" aria-hidden="true"></div>
                <div class="stat-item">
                    <span class="stat-value">50+</span>
                    <span class="stat-label">فريق عمل</span>
                </div>
                <div class="stat-sep" aria-hidden="true"></div>
                <div class="stat-item">
                    <span class="stat-value">لحظي</span>
                    <span class="stat-label">تحديث الإشعارات</span>
                </div>
            </div>

            <!-- ─── DIVIDER ─── -->
            <div class="divider" aria-hidden="true"></div>

            <!-- ─── FEATURES ─── -->
            <p class="features-heading">الميزات الرئيسية</p>

            <div class="features-grid" role="list">

                <div class="feature-card" role="listitem">
                    <div class="feature-icon-wrap">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <p class="feature-title">مشاريع غير محدودة</p>
                    <p class="feature-desc">أنشئ وتتبع أي عدد من المشاريع بدون قيود</p>
                </div>

                <div class="feature-card" role="listitem">
                    <div class="feature-icon-wrap">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <p class="feature-title">فرق ومجموعات</p>
                    <p class="feature-desc">إدارة الأعضاء وتحديد الصلاحيات بدقة</p>
                </div>

                <div class="feature-card" role="listitem">
                    <div class="feature-icon-wrap">
                        <i class="fa-solid fa-table-columns"></i>
                    </div>
                    <p class="feature-title">لوحات كانبان</p>
                    <p class="feature-desc">تتبع تقدم العمل بسحب المهام بين الأعمدة</p>
                </div>

                <div class="feature-card" role="listitem">
                    <div class="feature-icon-wrap">
                        <i class="fa-solid fa-link"></i>
                    </div>
                    <p class="feature-title">سلاسل المشاريع</p>
                    <p class="feature-desc">اربط المشاريع المترابطة وتابع تبعياتها</p>
                </div>

                <div class="feature-card" role="listitem">
                    <div class="feature-icon-wrap">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <p class="feature-title">إشعارات فورية</p>
                    <p class="feature-desc">ابقَ على اطلاع بكل تحديث في اللحظة ذاتها</p>
                </div>

                <div class="feature-card" role="listitem">
                    <div class="feature-icon-wrap">
                        <i class="fa-solid fa-chart-bar"></i>
                    </div>
                    <p class="feature-title">تقارير متقدمة</p>
                    <p class="feature-desc">رؤى تحليلية لقياس أداء فرقك ومشاريعك</p>
                </div>

            </div>

            <!-- ─── FOOTER ─── -->
            <footer class="card-footer">
                <span>جميع الحقوق محفوظة &copy; {{ date('Y') }} ORBIT</span>
                <span>·</span>
                <a href="mailto:support@orbit.app">الدعم الفني</a>
            </footer>

        </main>
    </div>

</body>
</html>