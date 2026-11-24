{{-- Sony branch live sync verified --}}
@extends('admin_panel.layout.app')

@section('content')
<!-- Font Awesome 6 fallback CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

    /* =============================================
       ROOT & THEME VARIABLES (AUTHENTIC ERP GRADE)
    ============================================= */
    :root {
        --f: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --bg: #f8fafc;
        --card: #ffffff;
        --border: #e2e8f0;
        --border-subtle: #edf2f7;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --text-muted: #64748b;
        --brand-primary: #4f46e5;
        --brand-indigo: #6366f1;
        --brand-emerald: #10b981;
        --brand-amber: #f59e0b;
        --brand-rose: #f43f5e;
        --brand-sky: #0284c7;
        --brand-purple: #8b5cf6;
        --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.02);
        --shadow-md: 0 4px 12px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
        --shadow-lg: 0 10px 25px -3px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 20px;
    }

    /* Base typography & box sizing: Exempt all icon classes from font override */
    .db-wrap {
        font-family: var(--f);
        box-sizing: border-box;
    }
    .db-wrap *:not(i):not([class*="fa-"]):not(.fa):not(.fas):not(.far):not(.fab):not(.fa-solid):not(.fa-regular):not(.fa-brands) {
        font-family: var(--f);
        box-sizing: border-box;
    }
    .db-wrap i,
    .db-wrap .fa,
    .db-wrap .fas,
    .db-wrap .far,
    .db-wrap .fa-solid,
    .db-wrap .fa-regular {
        font-family: "Font Awesome 6 Free" !important;
        font-style: normal;
    }
    .db-wrap .fab,
    .db-wrap .fa-brands {
        font-family: "Font Awesome 6 Brands" !important;
        font-style: normal;
    }

    .db-wrap {
        background: var(--bg);
        min-height: 100vh;
        padding: 1.75rem 2rem 3.5rem;
        color: var(--text-primary);
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    /* =============================================
       TOP HERO & EXECUTIVE CONTROL BAR
    ============================================= */
    .db-hero-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 1.35rem 1.75rem;
        box-shadow: var(--shadow-sm);
    }

    .db-hero-left {
        display: flex;
        align-items: center;
        gap: 1.15rem;
    }

    .db-user-avatar {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.35rem;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
        flex-shrink: 0;
    }

    .db-hero-title h1 {
        font-size: 1.45rem;
        font-weight: 800;
        margin: 0 0 0.2rem;
        color: var(--text-primary);
        letter-spacing: -0.02em;
    }

    .db-hero-title p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .db-role-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 10px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 99px;
        font-size: 0.72rem;
        font-weight: 700;
        border: 1px solid #e0e7ff;
    }

    .db-hero-controls {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    /* Date Filter Dropdown Form */
    .db-period-select {
        background: #f8fafc;
        border: 1px solid var(--border);
        color: var(--text-primary);
        font-size: 0.82rem;
        font-weight: 700;
        padding: 0.55rem 1.1rem;
        border-radius: 10px;
        outline: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .db-period-select:focus, .db-period-select:hover {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .db-btn-refresh {
        background: #f1f5f9;
        color: var(--text-secondary);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0.55rem 0.95rem;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    .db-btn-refresh:hover {
        background: #e2e8f0;
        color: var(--text-primary);
    }

    .db-btn-sync {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 0.55rem 1.15rem;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 3px 10px rgba(99, 102, 241, 0.25);
        transition: all 0.2s;
    }
    .db-btn-sync:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(99, 102, 241, 0.35);
    }

    /* =============================================
       REPORT QUICK LAUNCH PILLS (DYNAMIC PERMISSIONS)
    ============================================= */
    .db-nav-pills {
        display: flex;
        gap: 0.55rem;
        overflow-x: auto;
        padding-bottom: 0.35rem;
        margin-bottom: 1.75rem;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .db-nav-pills::-webkit-scrollbar { display: none; }

    .db-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.48rem 1rem;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 99px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--text-secondary);
        white-space: nowrap;
        text-decoration: none !important;
        transition: all 0.18s ease;
        box-shadow: var(--shadow-sm);
        flex-shrink: 0;
    }
    .db-pill:hover {
        background: #ffffff;
        color: var(--text-primary);
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }
    .db-pill i { font-size: 0.82rem; }

    /* =============================================
       SECTION LABELS
    ============================================= */
    .db-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 1.5rem 0 0.95rem;
    }
    .db-section-title {
        font-size: 0.76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }
    .db-section-badge {
        font-size: 0.7rem;
        font-weight: 700;
        background: #e2e8f0;
        color: var(--text-secondary);
        padding: 2px 8px;
        border-radius: 6px;
    }

    /* =============================================
       BUSINESS CONCLUSION HERO (EXECUTIVE WIDGET)
    ============================================= */
    .db-conclusion-card {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #1e293b 100%);
        border-radius: var(--radius-xl);
        padding: 1.6rem 2rem;
        margin-bottom: 1.75rem;
        color: #ffffff;
        box-shadow: 0 10px 30px -5px rgba(30, 27, 75, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
    }
    .db-conclusion-card::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.22) 0%, transparent 70%);
        pointer-events: none;
    }
    .db-conclusion-grid {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        position: relative;
        z-index: 1;
    }
    .conclusion-left { flex: 1.4; }
    .conclusion-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.92rem;
        font-weight: 800;
        color: #e0e7ff;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.85rem;
    }
    .conclusion-formula {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        font-size: 0.88rem;
        color: rgba(255, 255, 255, 0.82);
    }
    .formula-row strong { font-weight: 700; }
    .formula-result {
        display: flex;
        align-items: baseline;
        gap: 0.75rem;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        font-size: 0.92rem;
        font-weight: 700;
        color: #e0e7ff;
    }
    .formula-result .amt {
        font-size: 1.35rem;
        font-weight: 900;
        color: #34d399;
    }
    .conclusion-divider {
        width: 1px;
        height: 90px;
        background: rgba(255, 255, 255, 0.15);
    }
    .conclusion-right {
        flex: 1;
        text-align: right;
    }
    .conclusion-biz-tag {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #a5b4fc;
        margin-bottom: 0.35rem;
    }
    .conclusion-biz-breakdown {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.75);
        line-height: 1.4;
        margin-bottom: 0.5rem;
    }
    .conclusion-total-val {
        font-size: 2.1rem;
        font-weight: 900;
        color: #ffffff;
        letter-spacing: -0.02em;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    /* =============================================
       KPI STAT CARDS GRID (MODERN CORPORATE GRADIENTS)
    ============================================= */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.15rem;
        margin-bottom: 1.75rem;
    }
    .kpi-card {
        border-radius: var(--radius-lg);
        padding: 1.35rem 1.45rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid rgba(255, 255, 255, 0.15);
        text-decoration: none !important;
        color: #ffffff !important;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.2);
        color: #ffffff !important;
    }
    a.kpi-card {
        cursor: pointer;
        display: flex;
    }
    .kpi-card .kpi-sub {
        font-size: 0.74rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.88);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.4rem;
        width: 100%;
    }
    .kpi-card .kpi-view-report {
        font-size: 0.68rem;
        font-weight: 800;
        opacity: 0.88;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(255, 255, 255, 0.22);
        padding: 2px 8px;
        border-radius: 6px;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        flex-shrink: 0;
    }
    a.kpi-card:hover .kpi-view-report {
        opacity: 1;
        background: rgba(255, 255, 255, 0.35);
        transform: translateX(3px);
    }
    a.biz-box {
        text-decoration: none !important;
        cursor: pointer;
    }
    a.biz-box:hover {
        transform: translateY(-2px);
    }
    .kpi-gradient-purple {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 60%, #818cf8 100%);
    }
    .kpi-gradient-rose {
        background: linear-gradient(135deg, #e11d48 0%, #f43f5e 60%, #fb7185 100%);
    }
    .kpi-gradient-amber {
        background: linear-gradient(135deg, #d97706 0%, #f59e0b 60%, #fbbf24 100%);
    }
    .kpi-gradient-coral {
        background: linear-gradient(135deg, #c026d3 0%, #d946ef 60%, #e879f9 100%);
    }
    .kpi-gradient-emerald {
        background: linear-gradient(135deg, #059669 0%, #10b981 60%, #34d399 100%);
    }
    .kpi-gradient-sky {
        background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 60%, #38bdf8 100%);
    }
    .kpi-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .kpi-label {
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.92);
        line-height: 1.3;
    }
    .kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        backdrop-filter: blur(4px);
        flex-shrink: 0;
    }
    .kpi-value {
        font-size: 1.65rem;
        font-weight: 900;
        color: #ffffff;
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin-bottom: 0.5rem;
    }
    .kpi-sub {
        font-size: 0.74rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.88);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .kpi-sub a {
        color: #ffffff !important;
        text-decoration: underline !important;
        font-weight: 700;
    }

    /* =============================================
       WHITE PANEL CARD BOXES
    ============================================= */
    .panel-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        width: 100%;
        overflow: hidden;
    }
    .panel-hd {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.15rem;
    }
    .panel-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }
    .panel-badge {
        font-size: 0.7rem;
        font-weight: 700;
        background: #f1f5f9;
        color: var(--text-secondary);
        padding: 3px 9px;
        border-radius: 99px;
        border: 1px solid var(--border);
    }
    .panel-body-flex { flex: 1; width: 100%; min-width: 0; }

    /* Layout Grids */
    .grid-3col {
        display: grid;
        grid-template-columns: 2fr 1.15fr 1.15fr;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    .grid-4col {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    .grid-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    /* Business Counters Grid */
    .biz-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .biz-box {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 0.75rem 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
        transition: all 0.2s;
    }
    .biz-box:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: var(--shadow-sm);
    }
    .biz-info {
        min-width: 0;
        flex: 1;
        overflow: hidden;
    }
    .biz-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .biz-val {
        font-size: 1.2rem;
        font-weight: 900;
        color: var(--text-primary);
        line-height: 1.2;
    }
    .biz-lbl {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Rank List */
    .rank-list { display: flex; flex-direction: column; }
    .rank-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 0;
        border-bottom: 1px solid var(--border-subtle);
        gap: 0.5rem;
    }
    .rank-item:last-child { border-bottom: none; }
    .rank-num {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #f1f5f9;
        color: var(--text-secondary);
        font-size: 0.72rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .rank-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    .rank-sub {
        font-size: 0.7rem;
        font-weight: 500;
        color: var(--text-muted);
    }
    .rank-val {
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--text-primary);
        white-space: nowrap;
    }

    /* Legend List */
    .legend-list { display: flex; flex-direction: column; gap: 0.45rem; }
    .legend-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.78rem;
    }
    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .legend-name {
        font-weight: 600;
        color: var(--text-secondary);
        max-width: 140px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .legend-right {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .legend-pct {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--text-muted);
    }
    .legend-amt {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--text-primary);
    }

    /* Activities Feed */
    .act-list { display: flex; flex-direction: column; }
    .act-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0;
        border-bottom: 1px solid var(--border-subtle);
    }
    .act-row:last-child { border-bottom: none; }
    .act-ico {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .act-title { font-size: 0.8rem; font-weight: 700; color: var(--text-primary); }
    .act-sub { font-size: 0.68rem; color: var(--text-muted); }
    .act-amt { font-size: 0.8rem; font-weight: 800; color: var(--text-primary); }
    .act-time { font-size: 0.66rem; color: var(--text-muted); }

    /* Cash & Bank Balances Panel */
    .account-card-panel {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        overflow: hidden;
        margin-bottom: 1.75rem;
        box-shadow: var(--shadow-sm);
    }
    .account-card-header {
        padding: 1.15rem 1.6rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--border);
    }
    .account-list-wrap { padding: 0.5rem 1.6rem; }
    .account-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-subtle);
    }
    .account-item-row:last-child { border-bottom: none; }
    .account-name-group {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .account-title-link {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-primary);
        text-decoration: none;
    }
    .account-title-link:hover { color: #4f46e5; text-decoration: underline; }
    .account-type-pill {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 6px;
        background: #eef2ff;
        color: #4f46e5;
    }
    .account-balance-amt {
        font-size: 1rem;
        font-weight: 900;
        color: #10b981;
    }
    .account-footer-bar {
        background: #f8fafc;
        border-top: 1px solid var(--border);
        padding: 1rem 1.6rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .account-footer-tag {
        font-size: 0.8rem;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .account-footer-total {
        font-size: 1.25rem;
        font-weight: 900;
        color: #10b981;
    }

    /* Responsive */
    @media (max-width: 1440px) {
        .grid-4col { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 1200px) {
        .grid-3col, .grid-2col { grid-template-columns: 1fr; }
        .grid-4col { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .db-wrap { padding: 1rem 1rem 2.5rem; }
        .db-hero-bar { flex-direction: column; align-items: stretch; gap: 1rem; padding: 1.2rem; }
        .kpi-grid { grid-template-columns: 1fr; }
        .db-conclusion-grid { flex-direction: column; align-items: flex-start; gap: 1.25rem; }
        .conclusion-divider { width: 100%; height: 1px; }
        .conclusion-right { text-align: left; }
    }
</style>

<div class="db-wrap">

    {{-- =============================================
         1. HERO BAR WITH EXECUTIVE DATE FILTER
    ============================================= --}}
    <div class="db-hero-bar">
        <div class="db-hero-left">
            <div class="db-user-avatar">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="db-hero-title">
                <h1>Welcome back, {{ Auth::user()->name }}! 👋</h1>
                <p>
                    <span class="db-role-badge"><i class="fas fa-shield-alt"></i> {{ Auth::user()->roles->pluck('name')->first() ?? (Auth::user()->usertype ?? 'User') }}</span>
                    <span>&bull;</span>
                    <span id="db-live-date">{{ date('l, d M Y') }}</span>
                    <span>&bull;</span>
                    <span id="db-live-time" style="font-weight:700; color:var(--text-primary);">{{ date('h:i:s A') }}</span>
                </p>
            </div>
        </div>
        <div class="db-hero-controls">
            {{-- Dynamic Date Range Filter Form --}}
            <form action="{{ url('/home') }}" method="GET" class="d-flex align-items-center gap-2 m-0" id="dbPeriodForm">
                <select name="period" class="db-period-select" onchange="document.getElementById('dbPeriodForm').submit()">
                    <option value="today" {{ ($filterPeriod ?? '') == 'today' ? 'selected' : '' }}>📅 Today</option>
                    <option value="yesterday" {{ ($filterPeriod ?? '') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ ($filterPeriod ?? '') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ ($filterPeriod ?? '') == 'this_month' ? 'selected' : '' }}>📆 This Month</option>
                    <option value="last_month" {{ ($filterPeriod ?? '') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ ($filterPeriod ?? '') == 'this_year' ? 'selected' : '' }}>This Year</option>
                </select>
            </form>

            <a href="{{ url('/home') }}" class="db-btn-refresh" title="Reset Filters"><i class="fas fa-redo-alt"></i></a>

            @if(Auth::user()->usertype == 'admin' || Auth::user()->hasRole('Super Admin'))
                <button class="db-btn-sync" id="btnSyncCloud">
                    <i class="fas fa-cloud-arrow-up"></i> Sync Cloud
                </button>
            @endif
        </div>
    </div>

    {{-- =============================================
         2. PERMISSION-CONTROLLED REPORT QUICK LAUNCH PILLS
    ============================================= --}}
    <div class="db-nav-pills">
        @can('sale.report.view')
            <a href="{{ route('report.sale') }}" class="db-pill"><i class="fa-solid fa-receipt" style="color: var(--brand-emerald);"></i> Sales Report</a>
            <a href="{{ route('report.sales_officer_sale') }}" class="db-pill"><i class="fa-solid fa-user-tie" style="color: var(--brand-indigo);"></i> Sales Officer Report</a>
        @endcan
        @can('purchase.report.view')
            <a href="{{ route('report.purchase') }}" class="db-pill"><i class="fa-solid fa-cart-shopping" style="color: var(--brand-sky);"></i> Purchase Report</a>
        @endcan
        @can('profit.loss.report.view')
            <a href="{{ route('report.profit_loss') }}" class="db-pill"><i class="fa-solid fa-chart-line" style="color: var(--brand-indigo);"></i> Profit & Loss</a>
        @endcan
        @can('executive.report.view')
            <a href="{{ route('report.executive') }}" class="db-pill"><i class="fa-solid fa-crown" style="color: var(--brand-amber);"></i> Executive Report</a>
        @endcan
        @can('recovery.report.view')
            <a href="{{ route('report.recovery') }}" class="db-pill"><i class="fa-solid fa-file-invoice-dollar" style="color: var(--brand-amber);"></i> Recovery Report</a>
        @endcan
        @can('payable.report.view')
            <a href="{{ route('report.payable') }}" class="db-pill"><i class="fa-solid fa-hand-holding-dollar" style="color: var(--brand-rose);"></i> Payable Report</a>
        @endcan
        @can('parties.balance.report.view')
            <a href="{{ route('report.parties_balance') }}" class="db-pill"><i class="fa-solid fa-users" style="color: var(--brand-purple);"></i> Parties Balance</a>
        @endcan
        @can('inventory.onhand.view')
            <a href="{{ route('reports.onhand') }}" class="db-pill"><i class="fa-solid fa-warehouse" style="color: var(--brand-emerald);"></i> On-Hand Stock</a>
        @endcan
        @can('item.stock.report.view')
            <a href="{{ route('report.item_stock') }}" class="db-pill"><i class="fa-solid fa-boxes-stacked" style="color: var(--brand-sky);"></i> Item Stock Report</a>
        @endcan
        @can('balance.sheet.report.view')
            <a href="{{ route('report.balance_sheet') }}" class="db-pill"><i class="fa-solid fa-scale-balanced" style="color: var(--text-muted);"></i> Balance Sheet</a>
        @endcan
    </div>

    {{-- =============================================
         3. EXECUTIVE BUSINESS CONCLUSION (Hero Widget)
         Visible if user has permissions for Receivables, Payables, or Accounts
    ============================================= --}}
    @if($totalReceivables !== null || $totalPayables !== null || $totalCashAndBankBalance !== null || $totalStockValue !== null)
        @php
            $calcLiquidBalance = ($totalReceivables ?? 0) + ($totalCashAndBankBalance ?? 0) - ($totalPayables ?? 0);
            $calcTotalBusinessValue = $calcLiquidBalance + ($totalStockValue ?? 0);
        @endphp
        <div class="db-conclusion-card">
            <div class="db-conclusion-grid">
                <div class="conclusion-left">
                    <div class="conclusion-header">
                        <span style="font-size: 1.25rem;">💡</span>
                        <span>Executive Position & Business Health</span>
                    </div>
                    <div class="conclusion-formula">
                        <div class="formula-row">
                            (Customer Dues 
                            @canany(['recovery.report.view', 'customers.view'])
                                <a href="{{ auth()->user()->can('recovery.report.view') ? route('report.recovery') : route('customers.index') }}" title="Click to view Customer Recovery & Aging Report" style="color: #ffffff; text-decoration: underline; font-weight: 800;">
                                    Rs {{ number_format($totalReceivables ?? 0, 0) }}
                                </a>
                            @else
                                <strong style="color: #ffffff;">Rs {{ number_format($totalReceivables ?? 0, 0) }}</strong>
                            @endcanany
                            + Cash & Bank 
                            @canany(['balance.sheet.report.view', 'chart.of.accounts.view'])
                                <a href="{{ auth()->user()->can('balance.sheet.report.view') ? route('report.balance_sheet') : route('view_all') }}" title="Click to view Balance Sheet & Treasury" style="color: #ffffff; text-decoration: underline; font-weight: 800;">
                                    Rs {{ number_format($totalCashAndBankBalance ?? 0, 0) }}
                                </a>
                            @else
                                <strong style="color: #ffffff;">Rs {{ number_format($totalCashAndBankBalance ?? 0, 0) }}</strong>
                            @endcanany
                            )
                        </div>
                        <div class="formula-row">
                            - Vendor Payables 
                            @canany(['payable.report.view', 'vendors.view'])
                                <a href="{{ auth()->user()->can('payable.report.view') ? route('report.payable') : route('vendors.index') }}" title="Click to view Vendor Payables Report" style="color: #fda4af; text-decoration: underline; font-weight: 800;">
                                    Rs {{ number_format($totalPayables ?? 0, 0) }}
                                </a>
                            @else
                                <strong style="color: #fda4af;">Rs {{ number_format($totalPayables ?? 0, 0) }}</strong>
                            @endcanany
                        </div>
                        <div class="formula-result">
                            <span>= Net Liquid Capital:</span>
                            <span class="amt">Rs {{ number_format($calcLiquidBalance, 0) }}</span>
                        </div>
                    </div>
                </div>

                <div class="conclusion-divider"></div>

                <div class="conclusion-right">
                    @canany(['executive.report.view', 'balance.sheet.report.view'])
                        <a href="{{ auth()->user()->can('executive.report.view') ? route('report.executive') : route('report.balance_sheet') }}" style="text-decoration:none; color:inherit; display:block;" title="Click to view Executive Financial Performance Report">
                    @endcanany
                    <div class="conclusion-biz-tag">TOTAL NET BUSINESS VALUE</div>
                    <div class="conclusion-biz-breakdown">
                        Net Liquid Capital <strong>Rs {{ number_format($calcLiquidBalance, 0) }}</strong><br>
                        + Stock Valuation 
                        @can('item.stock.report.view')
                            <span style="color:#ffffff; text-decoration: underline; font-weight:800;">Rs {{ number_format($totalStockValue ?? 0, 0) }}</span>
                        @else
                            <strong>Rs {{ number_format($totalStockValue ?? 0, 0) }}</strong>
                        @endcan
                    </div>
                    <div class="conclusion-total-val">
                        Rs {{ number_format($calcTotalBusinessValue, 0) }}
                    </div>
                    @canany(['executive.report.view', 'balance.sheet.report.view'])
                        <div style="font-size: 0.70rem; color: rgba(255,255,255,0.85); margin-top: 4px; text-align: right; text-transform: uppercase; font-weight: 700;">Executive Report <i class="fas fa-arrow-right ms-1"></i></div>
                        </a>
                    @endcanany
                </div>
            </div>
        </div>
    @endif

    {{-- =============================================
         4. DYNAMIC TOP KPI CARDS (STRICTLY PERMISSION PROTECTED)
    ============================================= --}}
    <div class="db-section-header">
        <div class="db-section-title"><i class="fas fa-chart-pie text-primary"></i> Key Performance Indicators</div>
        <span class="db-section-badge">{{ $periodLabel ?? 'Current Period' }}</span>
    </div>

    <div class="kpi-grid">
        {{-- Card 1: Total Sales --}}
        @can('sales.view')
            @php
                $salesReportUrl = auth()->user()->can('sale.report.view') 
                    ? route('report.sale') 
                    : (auth()->user()->can('sales.view') ? route('sales.index') : null);
            @endphp
            @if($salesReportUrl)
                <a href="{{ $salesReportUrl }}" class="kpi-card kpi-gradient-purple is-clickable" title="Click to view complete Sales Report">
            @else
                <div class="kpi-card kpi-gradient-purple">
            @endif
                <div class="kpi-top">
                    <span class="kpi-label">Sales Revenue</span>
                    <div class="kpi-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                </div>
                <div class="kpi-value">Rs {{ number_format($salesPeriod ?? 0, 0) }}</div>
                <div class="kpi-sub">
                    <span>Today: <strong>Rs {{ number_format($salesToday ?? 0, 0) }}</strong></span>
                    @if($salesReportUrl)
                        <span class="kpi-view-report">Report <i class="fas fa-arrow-right"></i></span>
                    @endif
                </div>
            @if($salesReportUrl)
                </a>
            @else
                </div>
            @endif
        @endcan

        {{-- Card 2: Total Purchases --}}
        @can('purchases.view')
            @php
                $purchaseReportUrl = auth()->user()->can('purchase.report.view') 
                    ? route('report.purchase') 
                    : (auth()->user()->can('purchases.view') ? route('purchases.index') : null);
            @endphp
            @if($purchaseReportUrl)
                <a href="{{ $purchaseReportUrl }}" class="kpi-card kpi-gradient-rose is-clickable" title="Click to view complete Purchase Report">
            @else
                <div class="kpi-card kpi-gradient-rose">
            @endif
                <div class="kpi-top">
                    <span class="kpi-label">Procurement & Purchases</span>
                    <div class="kpi-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                </div>
                <div class="kpi-value">Rs {{ number_format($purchasesPeriod ?? 0, 0) }}</div>
                <div class="kpi-sub">
                    <span>Today: <strong>Rs {{ number_format($purchasesToday ?? 0, 0) }}</strong></span>
                    @if($purchaseReportUrl)
                        <span class="kpi-view-report">Report <i class="fas fa-arrow-right"></i></span>
                    @endif
                </div>
            @if($purchaseReportUrl)
                </a>
            @else
                </div>
            @endif
        @endcan

        {{-- Card 3: Gross Profit --}}
        @if($grossProfitPeriod !== null)
            @php
                $grossProfitUrl = auth()->user()->can('profit.loss.report.view') 
                    ? route('report.profit_loss') 
                    : (auth()->user()->can('executive.report.view') ? route('report.executive') : null);
            @endphp
            @if($grossProfitUrl)
                <a href="{{ $grossProfitUrl }}" class="kpi-card kpi-gradient-amber is-clickable" title="Click to view complete Profit & Loss Report">
            @else
                <div class="kpi-card kpi-gradient-amber">
            @endif
                <div class="kpi-top">
                    <span class="kpi-label">Gross Margin</span>
                    <div class="kpi-icon"><i class="fa-solid fa-chart-line"></i></div>
                </div>
                <div class="kpi-value">Rs {{ number_format($grossProfitPeriod, 0) }}</div>
                <div class="kpi-sub">
                    <span>Revenue minus Product Cost</span>
                    @if($grossProfitUrl)
                        <span class="kpi-view-report">P&L Report <i class="fas fa-arrow-right"></i></span>
                    @endif
                </div>
            @if($grossProfitUrl)
                </a>
            @else
                </div>
            @endif
        @endif

        {{-- Card 4: Operating Expenses --}}
        @if($expensesPeriod > 0 || Auth::user()->can('expense.voucher.view'))
            @php
                $expensesUrl = auth()->user()->can('expense.voucher.view') 
                    ? route('all_expense_vochers') 
                    : (auth()->user()->can('profit.loss.report.view') ? route('report.profit_loss') : null);
            @endphp
            @if($expensesUrl)
                <a href="{{ $expensesUrl }}" class="kpi-card kpi-gradient-coral is-clickable" title="Click to view Expense Vouchers & Details">
            @else
                <div class="kpi-card kpi-gradient-coral">
            @endif
                <div class="kpi-top">
                    <span class="kpi-label">Expenses Incurred</span>
                    <div class="kpi-icon"><i class="fa-solid fa-file-invoice"></i></div>
                </div>
                <div class="kpi-value">Rs {{ number_format($expensesPeriod ?? 0, 0) }}</div>
                <div class="kpi-sub">
                    <span>Today: <strong>Rs {{ number_format($expensesToday ?? 0, 0) }}</strong></span>
                    @if($expensesUrl)
                        <span class="kpi-view-report">Expenses <i class="fas fa-arrow-right"></i></span>
                    @endif
                </div>
            @if($expensesUrl)
                </a>
            @else
                </div>
            @endif
        @endif

        {{-- Card 5: Net Profit --}}
        @if($netProfitPeriod !== null)
            @php
                $netProfitUrl = auth()->user()->can('profit.loss.report.view') 
                    ? route('report.profit_loss') 
                    : (auth()->user()->can('executive.report.view') ? route('report.executive') : null);
            @endphp
            @if($netProfitUrl)
                <a href="{{ $netProfitUrl }}" class="kpi-card kpi-gradient-emerald is-clickable" title="Click to view complete Profit & Loss Report">
            @else
                <div class="kpi-card kpi-gradient-emerald">
            @endif
                <div class="kpi-top">
                    <span class="kpi-label">Net Operating Profit</span>
                    <div class="kpi-icon"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
                </div>
                <div class="kpi-value">Rs {{ number_format($netProfitPeriod, 0) }}</div>
                <div class="kpi-sub">
                    <span>Gross Profit minus Expenses</span>
                    @if($netProfitUrl)
                        <span class="kpi-view-report">P&L Report <i class="fas fa-arrow-right"></i></span>
                    @endif
                </div>
            @if($netProfitUrl)
                </a>
            @else
                </div>
            @endif
        @endif

        {{-- Card 6: Cash & Bank Liquid --}}
        @if($totalCashAndBankBalance !== null)
            @php
                $cashBankUrl = auth()->user()->can('balance.sheet.report.view') 
                    ? route('report.balance_sheet') 
                    : (auth()->user()->can('chart.of.accounts.view') ? route('view_all') : null);
            @endphp
            @if($cashBankUrl)
                <a href="{{ $cashBankUrl }}" class="kpi-card kpi-gradient-sky is-clickable" title="Click to view Balance Sheet & Treasury Accounts">
            @else
                <div class="kpi-card kpi-gradient-sky">
            @endif
                <div class="kpi-top">
                    <span class="kpi-label">Total Liquid Cash</span>
                    <div class="kpi-icon"><i class="fa-solid fa-wallet"></i></div>
                </div>
                <div class="kpi-value">Rs {{ number_format($totalCashAndBankBalance, 0) }}</div>
                <div class="kpi-sub">
                    <span>Active Cash & Bank Balance</span>
                    @if($cashBankUrl)
                        <span class="kpi-view-report">Balance Sheet <i class="fas fa-arrow-right"></i></span>
                    @endif
                </div>
            @if($cashBankUrl)
                </a>
            @else
                </div>
            @endif
        @endif
    </div>

    {{-- =============================================
         5. SALES ANALYTICS & CATEGORY BREAKDOWN
    ============================================= --}}
    @can('sales.view')
        <div class="db-section-header">
            <div class="db-section-title"><i class="fas fa-chart-area text-success"></i> Sales Intelligence & Performance</div>
        </div>
        <div class="grid-3col">

            {{-- 1. Sales vs Purchase Comparative Trajectory Chart --}}
            <div class="panel-card">
                <div class="panel-hd d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="panel-title"><i class="fas fa-chart-area" style="color:var(--brand-indigo);"></i> Sales & Purchase Trajectory</span>
                    <div class="d-flex align-items-center gap-1">
                        <div class="btn-group btn-group-sm" role="group" id="salesPeriodToggle">
                            <button type="button" class="btn btn-xs btn-primary active" data-period="daily" style="font-size: 11px; padding: 2px 8px; border-radius: 6px 0 0 6px;">Daily</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary" data-period="weekly" style="font-size: 11px; padding: 2px 8px;">Weekly</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary" data-period="monthly" style="font-size: 11px; padding: 2px 8px; border-radius: 0 6px 6px 0;">Monthly</button>
                        </div>
                    </div>
                </div>
                <div class="panel-body-flex">
                    <div style="height: 240px; position: relative;">
                        <canvas id="chartSalesOverview"></canvas>
                    </div>
                    <div class="d-flex align-items-center justify-content-center gap-4 mt-2 pt-2 border-top" style="font-size: 11.5px; font-weight: 600;">
                        <div class="d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:3px; background:#4f46e5; border-radius:2px;"></span>
                            <span style="color:var(--text-secondary);">Sales Revenue</span>
                        </div>
                        @can('purchases.view')
                        <div class="d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:3px; background:#f43f5e; border-radius:2px;"></span>
                            <span style="color:var(--text-secondary);">Purchases</span>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- 2. Sales by Category Doughnut --}}
            <div class="panel-card">
                <div class="panel-hd">
                    <span class="panel-title"><i class="fas fa-pie-chart" style="color:var(--brand-sky);"></i> Category Share</span>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="panel-badge">Top 5</span>
                        @can('sale.report.view')
                            <a href="{{ route('report.sale') }}" style="font-size: 0.72rem; color: var(--brand-sky); font-weight: 700; text-decoration: none;" title="View Complete Sales Report">Report &rarr;</a>
                        @endcan
                    </div>
                </div>
                <div class="panel-body-flex d-flex flex-column align-items-center">
                    <div style="position: relative; width: 140px; height: 140px; margin-bottom: 0.85rem;">
                        <canvas id="chartSalesCat"></canvas>
                    </div>
                    <div class="legend-list w-100" id="catLegend"></div>
                </div>
            </div>

            {{-- 3. Top Products Ranking --}}
            <div class="panel-card">
                <div class="panel-hd">
                    <span class="panel-title"><i class="fas fa-fire" style="color:var(--brand-rose);"></i> Top Selling Products</span>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="panel-badge">By Quantity</span>
                        @can('sale.report.view')
                            <a href="{{ route('report.product_sale_customer_wise') }}" style="font-size: 0.72rem; color: var(--brand-rose); font-weight: 700; text-decoration: none;" title="View Product Sale Analysis">Report &rarr;</a>
                        @endcan
                    </div>
                </div>
                <div class="panel-body-flex">
                    <div class="rank-list">
                        @if(isset($topProducts) && count($topProducts) > 0)
                            @php $r = 1; @endphp
                            @foreach($topProducts as $tp)
                                <div class="rank-item">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rank-num">{{ $r++ }}</div>
                                        <div>
                                            <div class="rank-name">{{ Str::limit($tp->product_name ?: 'Standard Item', 20) }}</div>
                                            <div class="rank-sub">{{ number_format($tp->total_qty) }} sold</div>
                                        </div>
                                    </div>
                                    <div class="rank-val">Rs {{ number_format($tp->total_revenue ?? 0, 0) }}</div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4" style="font-size:0.82rem;">No sales data available.</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    @endcan

    {{-- =============================================
         6. BUSINESS OPERATIONS & CASH FLOW
    ============================================= --}}
    <div class="db-section-header">
        <div class="db-section-title"><i class="fas fa-briefcase text-warning"></i> Operational Metrics & Activity</div>
    </div>
    <div class="grid-4col">

        {{-- Business Master Directory Summary --}}
        <div class="panel-card">
            <div class="panel-hd">
                <span class="panel-title"><i class="fas fa-building" style="color:var(--brand-indigo);"></i> Business Directory</span>
            </div>
            <div class="panel-body-flex">
                <div class="biz-grid">
                    @if($customerscount !== null)
                        @can('customers.view')
                            <a href="{{ route('customers.index') }}" class="biz-box" title="View Customer Directory & Balances">
                        @else
                            <div class="biz-box">
                        @endcan
                            <div class="biz-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fas fa-users"></i></div>
                            <div class="biz-info">
                                <div class="biz-val">{{ number_format($customerscount) }}</div>
                                <div class="biz-lbl">Customers</div>
                            </div>
                        @can('customers.view')
                            </a>
                        @else
                            </div>
                        @endcan
                    @endif
                    @if($vendorCount !== null)
                        @can('vendors.view')
                            <a href="{{ route('vendors.index') }}" class="biz-box" title="View Vendor Directory & Ledgers">
                        @else
                            <div class="biz-box">
                        @endcan
                            <div class="biz-icon" style="background:#f3e8ff; color:#8b5cf6;"><i class="fas fa-truck"></i></div>
                            <div class="biz-info">
                                <div class="biz-val">{{ number_format($vendorCount) }}</div>
                                <div class="biz-lbl">Vendors</div>
                            </div>
                        @can('vendors.view')
                            </a>
                        @else
                            </div>
                        @endcan
                    @endif
                    @if($productCount !== null)
                        @can('products.view')
                            <a href="{{ route('product') }}" class="biz-box" title="View Product Catalog & Inventory">
                        @else
                            <div class="biz-box">
                        @endcan
                            <div class="biz-icon" style="background:#fef3c7; color:#d97706;"><i class="fas fa-box-open"></i></div>
                            <div class="biz-info">
                                <div class="biz-val">{{ number_format($productCount) }}</div>
                                <div class="biz-lbl">Products</div>
                            </div>
                        @can('products.view')
                            </a>
                        @else
                            </div>
                        @endcan
                    @endif
                    @if($employeeCount !== null)
                        @can('users.view')
                            <a href="{{ route('users.index') }}" class="biz-box" title="View Staff / User Accounts">
                        @else
                            <div class="biz-box">
                        @endcan
                            <div class="biz-icon" style="background:#ecfdf5; color:#10b981;"><i class="fas fa-user-tie"></i></div>
                            <div class="biz-info">
                                <div class="biz-val">{{ number_format($employeeCount) }}</div>
                                <div class="biz-lbl">Staff</div>
                            </div>
                        @can('users.view')
                            </a>
                        @else
                            </div>
                        @endcan
                    @endif
                </div>
            </div>
        </div>

        {{-- Cash Flow Receipts vs Payments --}}
        @canany(['vouchers.view', 'expense.voucher.view', 'recovery.report.view', 'payable.report.view'])
        <div class="panel-card">
            <div class="panel-hd">
                <span class="panel-title"><i class="fas fa-exchange-alt" style="color:var(--brand-emerald);"></i> Cash Flow</span>
                <div class="d-flex align-items-center gap-1.5">
                    <span class="panel-badge">In vs Out</span>
                    @can('balance.sheet.report.view')
                        <a href="{{ route('report.balance_sheet') }}" style="font-size: 0.72rem; color: var(--brand-emerald); font-weight: 700; text-decoration: none;" title="View Balance Sheet">Report &rarr;</a>
                    @endcan
                </div>
            </div>
            <div class="panel-body-flex">
                <div style="height: 190px; position: relative;">
                    <canvas id="chartCashFlow"></canvas>
                </div>
            </div>
        </div>
        @endcanany

        {{-- Expense Breakdown --}}
        @canany(['expense.voucher.view', 'profit.loss.report.view', 'executive.report.view'])
        <div class="panel-card">
            <div class="panel-hd">
                <span class="panel-title"><i class="fas fa-receipt" style="color:var(--brand-rose);"></i> Expenses</span>
                @can('expense.voucher.view')
                    <a href="{{ route('all_expense_vochers') }}" style="font-size: 0.72rem; color: var(--brand-rose); font-weight: 700; text-decoration: none;" title="View Expense Vouchers">Vouchers &rarr;</a>
                @endcan
            </div>
            <div class="panel-body-flex d-flex flex-column align-items-center">
                <div style="position: relative; width: 120px; height: 120px; margin-bottom: 0.65rem;">
                    <canvas id="chartExpBreak"></canvas>
                </div>
                <div class="legend-list w-100" id="expLegend"></div>
            </div>
        </div>
        @endcanany

        {{-- Recent Feed (Sales + Purchases) --}}
        <div class="panel-card">
            <div class="panel-hd">
                <span class="panel-title"><i class="fas fa-bell" style="color:var(--brand-amber);"></i> Recent Activity</span>
            </div>
            <div class="panel-body-flex">
                <div class="act-list" style="max-height: 200px; overflow-y: auto;">
                    @if(isset($recentActivities) && count($recentActivities) > 0)
                        @foreach($recentActivities as $act)
                            <div class="act-row">
                                <div class="act-ico" style="background: {{ $act['bg'] }};">
                                    <i class="fa-solid {{ $act['icon'] }}"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div class="act-title text-truncate">{{ $act['title'] }}</div>
                                    <div class="act-sub">{{ $act['subtitle'] }}</div>
                                </div>
                                <div style="text-align: right; flex-shrink:0;">
                                    <div class="act-amt">{{ $act['amount'] }}</div>
                                    <div class="act-time">{{ $act['time'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-3" style="font-size:0.8rem;">No recent activities logged.</div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- =============================================
         7. LIQUID BALANCES & CASH/BANK ACCOUNTS
    ============================================= --}}
    @if(isset($cashAndBankAccounts) && count($cashAndBankAccounts) > 0)
        <div class="db-section-header">
            <div class="db-section-title"><i class="fas fa-landmark text-primary"></i> Treasury & Banking Accounts</div>
        </div>
        <div class="account-card-panel">
            <div class="account-card-header">
                <span style="font-size: 1.15rem;">🏛️</span>
                <span style="font-weight: 800; font-size: 0.95rem; color: var(--text-primary);">Accounts Liquidity Breakdown</span>
            </div>
            <div class="account-list-wrap">
                @foreach($cashAndBankAccounts as $acc)
                    @php 
                        $headName = strtolower($acc->head->name ?? '');
                        $isCash = str_contains($headName, 'cash');
                    @endphp
                    <div class="account-item-row">
                        <div class="account-name-group">
                            @can('chart.of.accounts.view')
                                <a href="{{ route('accounts.ledger', $acc->id) }}" class="account-title-link">{{ $acc->title }}</a>
                            @else
                                <span class="account-title-link">{{ $acc->title }}</span>
                            @endcan
                            <span class="account-type-pill">{{ $isCash ? 'Cash In Hand' : 'Bank Account' }}</span>
                        </div>
                        <div class="account-balance-amt">
                            Rs {{ number_format($acc->current_balance, 0) }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="account-footer-bar">
                <span class="account-footer-tag">TOTAL TREASURY LIQUIDITY</span>
                <span class="account-footer-total">Rs {{ number_format($totalCashAndBankBalance ?? 0, 0) }}</span>
            </div>
        </div>
    @endif

    {{-- =============================================
         8. LOW STOCK ALARM (PERMISSION GUARDED)
    ============================================= --}}
    @can('products.view')
        @if(isset($lowStockProducts) && $lowStockProducts->count() > 0)
            <div class="db-section-header">
                <div class="db-section-title" style="color: var(--brand-rose);"><i class="fas fa-triangle-exclamation"></i> Critical Low Stock Warning</div>
            </div>
            <div class="panel-card" style="margin-bottom: 2rem; border-color: #fecdd3;">
                <div class="panel-hd">
                    <span class="panel-title" style="color: var(--brand-rose);">
                        <i class="fas fa-bell"></i> Immediate Replenishment Required
                        <span style="background:#fff1f2; color:var(--brand-rose); font-size:0.72rem; font-weight:800; padding:2px 10px; border-radius:99px; border:1px solid #fecdd3;">
                            {{ $lowStockProducts->count() }} Alert Products
                        </span>
                    </span>
                    <a href="{{ route('product') }}?status=active" style="font-size:0.78rem; color:var(--brand-rose); font-weight:800; text-decoration:none;">Go To Inventory →</a>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartLowStock"></canvas>
                </div>
            </div>
        @endif
    @endcan

</div>

{{-- Chart.js Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // Live Clock
    setInterval(function () {
        const n = new Date();
        const el = document.getElementById('db-live-time');
        if (el) el.innerText = n.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true });
    }, 1000);

    const salesStats  = @json($salesChartStats ?? ['daily' => ['categories' => [], 'series' => []]]);
    const catData     = @json($salesByCategory ?? []);
    const expData     = @json($expenseBreakdown ?? []);

    const COLORS = ['#4f46e5','#0284c7','#10b981','#f59e0b','#f43f5e','#8b5cf6','#06b6d4'];

    const baseChartOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                padding: 10,
                cornerRadius: 8,
                titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                bodyFont:  { family: 'Plus Jakarta Sans', size: 11 }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, color: '#94a3b8' }
            },
            y: {
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: {
                    font: { family: 'Plus Jakarta Sans', size: 10 },
                    color: '#94a3b8',
                    callback: v => 'Rs ' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                }
            }
        }
    };

    // ── 1. SALES & PURCHASE DUAL LINE CHART (WITH LIVE RANGE SWITCHER) ──────────────────────
    const soCtx = document.getElementById('chartSalesOverview');
    if (soCtx) {
        const ctx = soCtx.getContext('2d');
        const gradientSales = ctx.createLinearGradient(0, 0, 0, 240);
        gradientSales.addColorStop(0, 'rgba(79, 70, 229, 0.25)');
        gradientSales.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

        const gradientPurch = ctx.createLinearGradient(0, 0, 0, 240);
        gradientPurch.addColorStop(0, 'rgba(244, 63, 94, 0.20)');
        gradientPurch.addColorStop(1, 'rgba(244, 63, 94, 0.0)');

        const canSeePurchases = {{ Auth::user()->can('purchases.view') ? 'true' : 'false' }};

        function buildDatasets(periodKey) {
            const periodData = salesStats[periodKey] || { categories: [], sales: [], purchases: [] };
            const ds = [
                {
                    label: 'Sales Revenue',
                    data: periodData.sales || [],
                    borderColor: '#4f46e5',
                    borderWidth: 2.5,
                    backgroundColor: gradientSales,
                    fill: true,
                    tension: 0.38,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ];

            if (canSeePurchases && periodData.purchases && periodData.purchases.length) {
                ds.push({
                    label: 'Purchases',
                    data: periodData.purchases || [],
                    borderColor: '#f43f5e',
                    borderWidth: 2.5,
                    backgroundColor: gradientPurch,
                    fill: true,
                    tension: 0.38,
                    pointBackgroundColor: '#f43f5e',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                });
            }
            return {
                labels: periodData.categories || [],
                datasets: ds
            };
        }

        const initialData = buildDatasets('daily');

        const salesChartInstance = new Chart(ctx, {
            type: 'line',
            data: initialData,
            options: {
                ...baseChartOpts,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    ...baseChartOpts.plugins,
                    tooltip: {
                        ...baseChartOpts.plugins.tooltip,
                        callbacks: { 
                            label: c => ` ${c.dataset.label}: Rs ${parseFloat(c.raw || 0).toLocaleString()}` 
                        }
                    }
                }
            }
        });

        // Toggle Buttons Handler
        const toggleBtns = document.querySelectorAll('#salesPeriodToggle button');
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                toggleBtns.forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary', 'active');

                const chosen = this.getAttribute('data-period');
                const newData = buildDatasets(chosen);
                salesChartInstance.data.labels = newData.labels;
                salesChartInstance.data.datasets = newData.datasets;
                salesChartInstance.update();
            });
        });
    }

    // ── 2. SALES BY CATEGORY DOUGHNUT ─────────────────────
    const catCtx = document.getElementById('chartSalesCat');
    if (catCtx) {
        let labels = catData.length ? catData.map(c => c.category_name) : ['General'];
        let values = catData.length ? catData.map(c => parseFloat(c.total_amount) || 0) : [1];
        const total = values.reduce((a,b) => a+b, 0);

        new Chart(catCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: COLORS,
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: c => ` ${c.label}: Rs ${parseFloat(c.raw || 0).toLocaleString()}` }
                    }
                }
            }
        });

        const el = document.getElementById('catLegend');
        if (el) {
            el.innerHTML = labels.map((l, i) => {
                const pct = total > 0 ? Math.round((values[i]/total)*100) : 0;
                return `<div class="legend-row">
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-dot" style="background:${COLORS[i]};"></span>
                        <span class="legend-name">${l}</span>
                    </div>
                    <div class="legend-right">
                        <span class="legend-pct">${pct}%</span>
                        <span class="legend-amt">Rs ${values[i].toLocaleString()}</span>
                    </div>
                </div>`;
            }).join('');
        }
    }

    // ── 3. CASH FLOW BAR CHART ─────────────────────────────
    const cfCtx = document.getElementById('chartCashFlow');
    if (cfCtx) {
        const payIn  = parseFloat('{{ $paymentInPeriod ?? 0 }}') || 0;
        const payOut = parseFloat('{{ $paymentOutPeriod ?? 0 }}') || 0;

        new Chart(cfCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Inflow (Receipts)', 'Outflow (Payments)'],
                datasets: [{
                    data: [payIn, payOut],
                    backgroundColor: ['#10b981', '#f43f5e'],
                    borderRadius: 8,
                    barThickness: 28
                }]
            },
            options: {
                ...baseChartOpts,
                plugins: {
                    ...baseChartOpts.plugins,
                    tooltip: {
                        callbacks: { label: c => ` Rs ${parseFloat(c.raw || 0).toLocaleString()}` }
                    }
                }
            }
        });
    }

    // ── 4. EXPENSE BREAKDOWN DOUGHNUT ──────────────────────
    const exCtx = document.getElementById('chartExpBreak');
    if (exCtx) {
        const EXP_COLORS = ['#8b5cf6','#0284c7','#0ea5e9','#f59e0b','#cbd5e1'];
        let labels = expData.length ? expData.map(e => e.name || 'General') : ['General'];
        let values = expData.length ? expData.map(e => parseFloat(e.total) || 0) : [1];
        const total = values.reduce((a,b) => a+b, 0);

        new Chart(exCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: EXP_COLORS,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });

        const el = document.getElementById('expLegend');
        if (el) {
            el.innerHTML = labels.map((l, i) => {
                const pct = total > 0 ? Math.round((values[i]/total)*100) : 0;
                return `<div class="legend-row">
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-dot" style="background:${EXP_COLORS[i]};"></span>
                        <span class="legend-name">${l}</span>
                    </div>
                    <div class="legend-right">
                        <span class="legend-pct">${pct}%</span>
                        <span class="legend-amt">Rs ${values[i].toLocaleString()}</span>
                    </div>
                </div>`;
            }).join('');
        }
    }

    // ── 5. LOW STOCK BAR CHART ─────────────────────────────
    const lsCtx = document.getElementById('chartLowStock');
    if (lsCtx) {
        const lsData = @json($lowStockProducts ?? collect());
        if (lsData.length > 0) {
            const lsNames  = lsData.map(p => p.item_name ? p.item_name.substring(0, 22) : 'Product');
            const lsStock  = lsData.map(p => parseFloat(p.current_cartons) || 0);
            const lsAlert  = lsData.map(p => parseFloat(p.alert_carton_quantity) || 0);
            new Chart(lsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: lsNames,
                    datasets: [
                        { label: 'Alert Threshold', data: lsAlert, backgroundColor: 'rgba(244,63,94,0.85)', borderRadius: 5, barPercentage: 0.5 },
                        { label: 'Current Inventory', data: lsStock, backgroundColor: 'rgba(79,70,229,0.85)', borderRadius: 5, barPercentage: 0.5 }
                    ]
                },
                options: {
                    ...baseChartOpts,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: { font: { family:'Plus Jakarta Sans', size:11, weight:'700' }, usePointStyle:true, boxWidth:6 }
                        },
                        tooltip: {
                            callbacks: { label: c => ` ${c.dataset.label}: ${c.raw} cartons` }
                        }
                    },
                    scales: {
                        ...baseChartOpts.scales,
                        y: {
                            ticks: { callback: v => v + ' ctns' }
                        }
                    }
                }
            });
        }
    }

    // ── 6. SYNC BUTTON ─────────────────────────────────────
    const syncBtn = document.getElementById('btnSyncCloud');
    if (syncBtn) {
        syncBtn.addEventListener('click', function () {
            syncBtn.disabled = true;
            syncBtn.innerHTML = '<i class="fa fa-sync-alt fa-spin"></i> Syncing...';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: 'Syncing to Cloud...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            }
            fetch('{{ route('admin.sync_to_cloud') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json()).then(d => {
                syncBtn.disabled = false;
                syncBtn.innerHTML = '<i class="fas fa-cloud-arrow-up"></i> Sync Cloud';
                if (typeof Swal !== 'undefined') {
                    if (d.status === 'success') {
                        Swal.fire({ icon:'success', title:'Sync Successful', text:d.message, confirmButtonColor:'#4f46e5' })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon:'error', title:'Sync Failed', text: d.message || 'Error occurred.', confirmButtonColor:'#4f46e5' });
                    }
                } else {
                    alert(d.message || 'Sync complete');
                    location.reload();
                }
            }).catch(() => {
                syncBtn.disabled = false;
                syncBtn.innerHTML = '<i class="fas fa-cloud-arrow-up"></i> Sync Cloud';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon:'error', title:'Connection Error', text:'Could not reach cloud server.', confirmButtonColor:'#4f46e5' });
                } else {
                    alert('Could not reach cloud server.');
                }
            });
        });
    }

});
</script>
@endsection
