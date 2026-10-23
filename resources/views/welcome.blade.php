<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f8f7">
    <meta name="description" content="SiBaset, portal informasi pemetaan sekolah dan barang aset pendidikan Kota Palangka Raya.">
    <title>{{ config('app.name', 'SiBaset') }} — Sistem Informasi Pemetaan Barang dan Aset</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #18181b;
            background: #f8f8f7;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            --ink: #18181b;
            --muted: #71717a;
            --line: #e4e4e7;
            --surface: #ffffff;
            --surface-soft: #f4f4f3;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; min-width: 320px; background: #f8f8f7; }
        button, input { font: inherit; }
        a { color: inherit; text-decoration: none; }
        button, a, input { -webkit-tap-highlight-color: transparent; }
        button:focus-visible, a:focus-visible, input:focus-visible {
            outline: 3px solid #a1a1aa;
            outline-offset: 3px;
        }
        [hidden] { display: none !important; }

        .page-shell { width: min(1200px, calc(100% - 56px)); margin-inline: auto; }
        .site-header {
            min-height: 84px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid rgba(228, 228, 231, .9);
        }
        .brand { display: inline-flex; align-items: center; gap: 12px; min-width: 0; }
        .brand-mark {
            display: grid; place-items: center; width: 44px; height: 44px; flex: 0 0 auto;
            border-radius: 14px; color: #fff; background: var(--ink);
        }
        .brand-name { display: block; font-size: 17px; font-weight: 800; letter-spacing: -.045em; line-height: 1.2; }
        .brand-caption { display: block; margin-top: 5px; color: var(--muted); font-size: 10px; line-height: 1.35; }
        .header-right { display: flex; align-items: center; gap: 24px; }
        .header-nav { display: flex; align-items: center; gap: 22px; color: #52525b; font-size: 12px; font-weight: 600; }
        .header-nav a:hover { color: var(--ink); }
        .button {
            min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            padding: 0 17px; border: 1px solid transparent; border-radius: 12px;
            cursor: pointer; font-size: 12px; font-weight: 700; transition: .18s ease;
        }
        .button-dark { background: var(--ink); color: white; box-shadow: 0 4px 12px rgba(24, 24, 27, .08); }
        .button-dark:hover { background: #3f3f46; transform: translateY(-1px); }
        .button-light { background: rgba(255,255,255,.72); color: #27272a; border-color: var(--line); }
        .button-light:hover { background: #fff; border-color: #a1a1aa; }

        .portal-layout {
            display: grid; grid-template-columns: minmax(0, 1.16fr) minmax(350px, .84fr);
            align-items: stretch; gap: clamp(32px, 5vw, 72px); padding: 48px 0 52px;
        }
        .intro { min-width: 0; padding: 12px 0 0; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 9px; padding: 8px 11px;
            border: 1px solid #e4e4e7; border-radius: 999px; background: rgba(255,255,255,.72);
            color: #52525b; font-size: 10px; font-weight: 700; letter-spacing: .035em;
        }
        .eyebrow-dot { width: 7px; height: 7px; flex: 0 0 auto; border-radius: 50%; background: #27272a; box-shadow: 0 0 0 3px #e4e4e7; }
        h1 {
            max-width: 650px; margin: 23px 0 0; font-size: clamp(36px, 4.35vw, 57px);
            font-weight: 800; letter-spacing: -.065em; line-height: 1.06;
        }
        .hero-copy { max-width: 560px; margin: 20px 0 0; color: #71717a; font-size: 14px; line-height: 1.9; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }

        .map-preview {
            position: relative; min-height: 230px; margin-top: 35px; overflow: hidden;
            border: 1px solid #e4e4e7; border-radius: 22px; background: #eeeeeb;
            isolation: isolate;
        }
        .map-preview::before {
            content: ""; position: absolute; inset: 0; z-index: -1;
            background-image: linear-gradient(rgba(255,255,255,.42) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.42) 1px, transparent 1px);
            background-size: 29px 29px;
        }
        .map-art { position: absolute; inset: 0; width: 100%; height: 100%; }
        .map-label {
            position: absolute; top: 17px; left: 17px; display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 10px; border: 1px solid rgba(228,228,231,.92); border-radius: 10px;
            background: rgba(255,255,255,.9); color: #52525b; font-size: 9px; font-weight: 700; letter-spacing: .1em;
            box-shadow: 0 5px 18px rgba(24,24,27,.04);
        }
        .map-label svg { width: 13px; height: 13px; }
        .map-pin {
            position: absolute; display: grid; place-items: center; width: 27px; height: 27px;
            border: 3px solid rgba(255,255,255,.95); border-radius: 50% 50% 50% 5px;
            background: #27272a; box-shadow: 0 4px 10px rgba(24,24,27,.16); transform: rotate(-45deg);
        }
        .map-pin::after { content: ""; width: 5px; height: 5px; border-radius: 50%; background: white; }
        .map-pin.pin-one { top: 35%; left: 29%; }
        .map-pin.pin-two { top: 55%; left: 54%; }
        .map-pin.pin-three { top: 30%; right: 18%; }
        .map-float-card {
            position: absolute; right: 16px; bottom: 15px; display: flex; align-items: center; gap: 11px;
            min-width: 174px; padding: 11px 13px; border: 1px solid rgba(228,228,231,.9);
            border-radius: 13px; background: rgba(255,255,255,.94); box-shadow: 0 8px 24px rgba(24,24,27,.06);
        }
        .map-float-icon { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: #f4f4f5; color: #3f3f46; }
        .map-float-title { font-size: 11px; font-weight: 700; }
        .map-float-copy { margin-top: 4px; color: #71717a; font-size: 9px; }

        .login-card {
            align-self: start; position: relative; overflow: hidden; padding: clamp(24px, 3vw, 36px);
            border: 1px solid #e4e4e7; border-radius: 24px; background: rgba(255,255,255,.94);
            box-shadow: 0 18px 55px rgba(24,24,27,.055);
        }
        .login-card::before {
            content: ""; position: absolute; top: -90px; right: -70px; width: 210px; height: 210px;
            border: 1px solid rgba(228,228,231,.75); border-radius: 50%; pointer-events: none;
        }
        .login-card::after {
            content: ""; position: absolute; top: -62px; right: -40px; width: 150px; height: 150px;
            border: 1px solid rgba(228,228,231,.75); border-radius: 50%; pointer-events: none;
        }
        .login-topline { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; }
        .login-emblem {
            display: grid; place-items: center; width: 42px; height: 42px; flex: 0 0 auto;
            border: 1px solid #e4e4e7; border-radius: 13px; background: #fafafa; color: #27272a;
        }
        .login-eyebrow { display: block; color: #71717a; font-size: 9px; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; }
        .login-context { display: block; margin-top: 5px; color: #52525b; font-size: 11px; }
        .login-title { position: relative; z-index: 1; margin: 27px 0 0; font-size: 26px; font-weight: 800; letter-spacing: -.045em; line-height: 1.2; }
        .login-copy { margin: 9px 0 0; color: #71717a; font-size: 12px; line-height: 1.7; }
        .login-form { position: relative; z-index: 1; margin-top: 25px; }
        .field-group { margin-top: 17px; }
        .field-group:first-child { margin-top: 0; }
        .field-label { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px; color: #3f3f46; font-size: 11px; font-weight: 700; }
        .field-wrap { position: relative; }
        .field-input {
            width: 100%; height: 46px; padding: 0 13px; border: 1px solid #dedee2; border-radius: 11px;
            background: #fff; color: #18181b; font-size: 12px; transition: border-color .15s, box-shadow .15s;
        }
        .field-input::placeholder { color: #a1a1aa; }
        .field-input:hover { border-color: #a1a1aa; }
        .field-input:focus { outline: none; border-color: #71717a; box-shadow: 0 0 0 3px rgba(161,161,170,.17); }
        .password-input { padding-right: 48px; }
        .password-toggle {
            position: absolute; top: 50%; right: 7px; display: grid; place-items: center;
            width: 34px; height: 34px; border: 0; border-radius: 8px; background: transparent;
            color: #71717a; cursor: pointer; transform: translateY(-50%);
        }
        .password-toggle:hover { background: #f4f4f5; color: #27272a; }
        .password-toggle svg { width: 17px; height: 17px; }
        .login-options { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; }
        .remember-label { display: inline-flex; align-items: center; gap: 8px; color: #52525b; font-size: 10px; cursor: pointer; }
        .remember-label input { width: 14px; height: 14px; accent-color: #27272a; }
        .text-link { border: 0; padding: 0; background: transparent; color: #52525b; font-size: 10px; font-weight: 700; cursor: pointer; }
        .text-link:hover { color: #18181b; text-decoration: underline; text-underline-offset: 3px; }
        .login-submit { width: 100%; margin-top: 22px; min-height: 47px; }
        .login-feedback { margin-top: 13px; padding: 11px 12px; border: 1px solid #e4e4e7; border-radius: 10px; background: #f8f8f7; color: #52525b; font-size: 10px; line-height: 1.6; }
        .login-divider { display: flex; align-items: center; gap: 12px; margin: 22px 0 16px; color: #a1a1aa; font-size: 9px; }
        .login-divider::before, .login-divider::after { content: ""; height: 1px; flex: 1; background: #e4e4e7; }
        .role-pills { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
        .role-pill { display: flex; align-items: center; justify-content: center; gap: 7px; padding: 10px 8px; border: 1px solid #e4e4e7; border-radius: 10px; color: #52525b; font-size: 10px; font-weight: 600; }
        .role-pill svg { width: 14px; height: 14px; }
        .login-note { margin: 15px 0 0; color: #a1a1aa; font-size: 9px; line-height: 1.65; text-align: center; }

        .features-section { padding: 0 0 54px; }
        .section-heading { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 17px; }
        .section-kicker { color: #71717a; font-size: 9px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .section-title { margin: 8px 0 0; font-size: 20px; font-weight: 800; letter-spacing: -.04em; }
        .section-copy { max-width: 390px; color: #71717a; font-size: 11px; line-height: 1.7; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 13px; }
        .feature-card { display: flex; gap: 13px; min-width: 0; padding: 18px; border: 1px solid #e4e4e7; border-radius: 16px; background: rgba(255,255,255,.72); }
        .feature-icon { display: grid; place-items: center; width: 38px; height: 38px; flex: 0 0 auto; border: 1px solid #e4e4e7; border-radius: 11px; background: #fff; color: #3f3f46; }
        .feature-icon svg { width: 18px; height: 18px; }
        .feature-title { margin: 1px 0 0; font-size: 11px; font-weight: 800; }
        .feature-copy { margin: 6px 0 0; color: #71717a; font-size: 10px; line-height: 1.7; }

        .site-footer { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 19px 0 24px; border-top: 1px solid #e4e4e7; color: #a1a1aa; font-size: 10px; }
        .footer-brand { color: #71717a; font-weight: 700; }

        @media (min-width: 1100px) {
            .portal-layout { min-height: 620px; }
            .login-card { margin-top: 0; }
        }
        @media (max-width: 900px) {
            .page-shell { width: min(100% - 36px, 700px); }
            .site-header { min-height: 76px; }
            .header-nav { gap: 15px; }
            .header-right { gap: 16px; }
            .portal-layout { grid-template-columns: minmax(0, 1fr) minmax(300px, .88fr); gap: 25px; padding: 35px 0 42px; }
            h1 { font-size: clamp(34px, 4.7vw, 45px); }
            .login-card { padding: 23px; }
            .map-preview { min-height: 210px; }
        }
        @media (max-width: 700px) {
            .page-shell { width: calc(100% - 32px); }
            .site-header { min-height: 72px; gap: 12px; }
            .brand-mark { width: 40px; height: 40px; border-radius: 12px; }
            .brand-name { font-size: 16px; }
            .brand-caption { max-width: 185px; font-size: 9px; }
            .header-nav { display: none; }
            .header-right { gap: 0; }
            .portal-layout { grid-template-columns: minmax(0, 1fr); gap: 24px; padding: 29px 0 37px; }
            .intro { padding-top: 0; }
            h1 { max-width: 580px; margin-top: 19px; font-size: clamp(35px, 9vw, 48px); }
            .hero-copy { margin-top: 15px; font-size: 12px; line-height: 1.8; }
            .hero-actions { margin-top: 20px; }
            .button { min-height: 42px; padding-inline: 14px; }
            .map-preview { min-height: 190px; margin-top: 26px; border-radius: 18px; }
            .login-card { padding: 24px 21px; border-radius: 20px; }
            .login-title { margin-top: 22px; font-size: 24px; }
            .features-section { padding-bottom: 35px; }
            .section-heading { display: block; margin-bottom: 14px; }
            .section-copy { margin-top: 8px; }
            .feature-grid { grid-template-columns: 1fr; gap: 9px; }
            .feature-card { padding: 15px; }
            .site-footer { align-items: flex-start; flex-direction: column; gap: 7px; padding: 17px 0 22px; }
        }
        @media (max-width: 380px) {
            .page-shell { width: calc(100% - 24px); }
            .brand-caption { max-width: 150px; }
            h1 { font-size: 33px; }
            .login-card { padding: 20px 16px; }
            .login-options { align-items: flex-start; }
            .map-float-card { right: 10px; bottom: 10px; min-width: 155px; padding: 9px; }
            .role-pills { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <header class="site-header page-shell" id="top">
        <a class="brand" href="#top" aria-label="SiBaset, kembali ke beranda">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
                    <path d="M3 7.5 12 12l9-4.5M12 12v9"/>
                </svg>
            </span>
            <span>
                <span class="brand-name">SiBaset</span>
                <span class="brand-caption">Dinas Pendidikan Kota Palangka Raya</span>
            </span>
        </a>

        <div class="header-right">
            <nav class="header-nav" aria-label="Navigasi utama">
                <a href="#top">Beranda</a>
                <a href="#fitur">Fitur</a>
                <a href="#tentang">Tentang SiBaset</a>
            </nav>
            <a class="button button-dark" href="#login">
                Masuk Portal
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    </header>

    <main class="page-shell">
        <section class="portal-layout" id="tentang" aria-label="Beranda dan portal login SiBaset">
            <div class="intro">
                <div class="eyebrow"><span class="eyebrow-dot"></span> PORTAL DIGITAL PENDIDIKAN</div>
                <h1>Pemetaan aset pendidikan, <span style="color:#71717a">lebih terarah.</span></h1>
                <p class="hero-copy">
                    SiBaset membantu menghadirkan informasi persebaran sekolah, inventaris barang,
                    dan proses verifikasi data aset pendidikan dalam satu portal.
                </p>

                <div class="hero-actions">
                    <a class="button button-dark" href="#login">
                        Mulai dari sini
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <a class="button button-light" href="#fitur">
                        Kenali fitur
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </a>
                </div>

                <div class="map-preview" aria-label="Ilustrasi peta persebaran sekolah">
                    <svg class="map-art" viewBox="0 0 680 300" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                        <path d="M-35 62 75 90 133 54 202 86 258 45 327 91 385 47 450 73 514 40 712 77" stroke="#d0d0cb" stroke-width="20" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M-25 147 70 130 127 157 180 125 238 163 296 141 352 173 416 138 474 166 548 127 705 158" stroke="#fff" stroke-width="12" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M30 -20 66 53 37 110 80 175 61 325M176 -20 210 45 185 106 233 165 218 325M333 -30 308 44 343 109 319 181 354 326M497 -25 469 49 515 102 486 185 531 325M624 -10 584 54 617 126 594 206 636 330" stroke="#fafaf9" stroke-width="9" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M-25 230 88 205 157 225 236 195 302 222 382 204 451 226 535 196 702 222" stroke="#d1d1cc" stroke-width="16" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M85 25 140 57 195 30 248 70 306 43 369 78 434 47 491 72 570 39" stroke="#c5c5bf" stroke-width="2" stroke-dasharray="5 8" fill="none"/>
                        <path d="M45 284 133 250 215 278 280 253 352 278 438 248 510 270 605 239" stroke="#c5c5bf" stroke-width="2" stroke-dasharray="5 8" fill="none"/>
                        <path d="M-10 95C95 111 133 186 213 166S348 100 405 152 526 220 690 182" stroke="#b5b5af" stroke-width="2.5" fill="none"/>
                        <path d="M92 0C140 65 181 100 168 155S230 233 262 300" stroke="#b5b5af" stroke-width="2" fill="none"/>
                        <path d="M421 -10C390 56 423 99 461 126S477 215 454 306" stroke="#b5b5af" stroke-width="2" fill="none"/>
                    </svg>
                    <div class="map-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.2"/></svg>
                        ILUSTRASI PETA
                    </div>
                    <span class="map-pin pin-one" aria-hidden="true"></span>
                    <span class="map-pin pin-two" aria-hidden="true"></span>
                    <span class="map-pin pin-three" aria-hidden="true"></span>
                    <div class="map-float-card">
                        <span class="map-float-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                        </span>
                        <span>
                            <span class="map-float-title">Persebaran Sekolah</span>
                            <span class="map-float-copy">Kota Palangka Raya</span>
                        </span>
                    </div>
                </div>
            </div>

            <aside class="login-card" id="login" aria-labelledby="login-title">
                <div class="login-topline">
                    <span class="login-emblem" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg>
                    </span>
                    <span>
                        <span class="login-eyebrow">Portal akses</span>
                        <span class="login-context">Sistem Informasi SiBaset</span>
                    </span>
                </div>

                <h2 class="login-title" id="login-title">Selamat datang kembali</h2>
                <p class="login-copy">Masuk untuk melanjutkan ke ruang kerja SiBaset.</p>

                <form class="login-form" id="login-form">
                    <div class="field-group">
                        <label class="field-label" for="login-email">Email terdaftar</label>
                        <input class="field-input" type="email" id="login-email"
                            placeholder="nama@instansi.go.id" autocomplete="username" required>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="login-password">Kata sandi</label>
                        <div class="field-wrap">
                            <input class="field-input password-input" type="password" id="login-password"
                                placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                            <button class="password-toggle" id="toggle-password" type="button" aria-label="Tampilkan kata sandi" aria-pressed="false">
                                <svg id="password-eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="login-options">
                        <label class="remember-label" for="remember-login">
                            <input type="checkbox" id="remember-login">
                            Ingat saya
                        </label>
                        <button class="text-link" type="button" id="forgot-password">Lupa kata sandi?</button>
                    </div>

                    <button class="button button-dark login-submit" type="submit">
                        Masuk ke SiBaset
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>

                    <div class="login-feedback" id="login-status" role="status" aria-live="polite" hidden></div>
                </form>

                <div class="login-divider">AKSES PORTAL</div>
                <div class="role-pills">
                    <div class="role-pill">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3 20 7v5c0 4.5-3 7-8 9-5-2-8-4.5-8-9V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>
                        Administrator
                    </div>
                    <div class="role-pill">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                        Operator Sekolah
                    </div>
                </div>
                <p class="login-note">Tampilan portal login masih dalam tahap pratinjau. Autentikasi belum dihubungkan.</p>
            </aside>
        </section>

        <section class="features-section" id="fitur" aria-labelledby="features-title">
            <div class="section-heading">
                <div>
                    <div class="section-kicker">Fitur utama</div>
                    <h2 class="section-title" id="features-title">Data penting, dalam satu ruang.</h2>
                </div>
                <p class="section-copy">Dirancang untuk mendukung informasi sekolah, inventaris aset, dan proses verifikasi dalam alur kerja yang lebih terstruktur.</p>
            </div>

            <div class="feature-grid">
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.2"/></svg>
                    </span>
                    <span>
                        <h3 class="feature-title">Peta persebaran sekolah</h3>
                        <p class="feature-copy">Lihat informasi lokasi sekolah dan status koordinatnya melalui peta interaktif.</p>
                    </span>
                </article>
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/></svg>
                    </span>
                    <span>
                        <h3 class="feature-title">Inventaris barang dan aset</h3>
                        <p class="feature-copy">Ruang informasi untuk menata data aset sekolah dan detail inventarisnya.</p>
                    </span>
                </article>
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 20 7v5c0 4.5-3 7-8 9-5-2-8-4.5-8-9V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <span>
                        <h3 class="feature-title">Verifikasi data</h3>
                        <p class="feature-copy">Mendukung alur pemeriksaan perubahan data dan status koordinat sekolah.</p>
                    </span>
                </article>
            </div>
        </section>
    </main>

    <footer class="site-footer page-shell">
        <span class="footer-brand">SiBaset · Sistem Informasi Pemetaan Barang dan Aset</span>
        <span>Dinas Pendidikan Kota Palangka Raya</span>
    </footer>

    <script>
        (() => {
            const password = document.getElementById('login-password');
            const toggle = document.getElementById('toggle-password');
            const form = document.getElementById('login-form');
            const status = document.getElementById('login-status');
            const forgot = document.getElementById('forgot-password');

            toggle?.addEventListener('click', () => {
                const reveal = password.type === 'password';
                password.type = reveal ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', String(reveal));
                toggle.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                toggle.innerHTML = reveal
                    ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a15.9 15.9 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4-.8"/></svg>'
                    : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
            });

            const showNotice = message => {
                status.textContent = message;
                status.hidden = false;
            };

            form?.addEventListener('submit', event => {
                event.preventDefault();
                showNotice('Ini masih pratinjau tampilan. Proses login belum diaktifkan dan data tidak dikirim ke server.');
            });

            forgot?.addEventListener('click', () => {
                showNotice('Pemulihan kata sandi belum tersedia pada tahap pratinjau ini.');
            });
        })();
    </script>
</body>
</html>
