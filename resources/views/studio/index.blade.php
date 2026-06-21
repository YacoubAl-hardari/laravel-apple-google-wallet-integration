<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wallet Design Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --border: #e2e8f0;
            --text: #475569;
            --muted: #94a3b8;
            --accent: #4f46e5;
            --accent-glow: rgba(79, 70, 229, 0.1);
            --input-bg: #f1f5f9;
            --input-border: #cbd5e1;
            --sort-item-bg: #ffffff;
            --sort-item-hover: #f1f5f9;
            --badge-apple-bg: #0f172a;
            --badge-apple-text: #f8fafc;
            --badge-google-bg: #eff6ff;
            --badge-google-text: #2563eb;
            --status-bg: #e0e7ff;
            --status-border: #c7d2fe;
            --modal-bg: #ffffff;
            --file-tab-bg: #f8fafc;
            --tab-bg: #f1f5f9;
            --tab-active-bg: #ffffff;
            --btn-secondary-bg: #ffffff;
            --btn-secondary-text: #334155;
            --btn-secondary-border: #cbd5e1;
            --btn-secondary-hover: #f1f5f9;
            --ui-switcher-bg: #e2e8f0;
            --ui-switcher-btn-active: #ffffff;
            --ui-switcher-btn-active-text: #0f172a;
            --panel-h2-border: #f1f5f9;
            --heading-color: #0f172a;
            --body-label-color: #64748b;

            --apple: #0f172a;
            --google: #4285f4;
            --card-radius: 16px;
        }

        html.dark {
            --bg: #0f172a;
            --panel: #1e293b;
            --border: #334155;
            --text: #cbd5e1;
            --muted: #94a3b8;
            --accent: #818cf8;
            --accent-glow: rgba(129, 140, 248, 0.15);
            --input-bg: #020617;
            --input-border: #334155;
            --sort-item-bg: #1e293b;
            --sort-item-hover: #334155;
            --badge-apple-bg: #f8fafc;
            --badge-apple-text: #0f172a;
            --badge-google-bg: rgba(59, 130, 246, 0.15);
            --badge-google-text: #93c5fd;
            --status-bg: rgba(129, 140, 248, 0.15);
            --status-border: rgba(129, 140, 248, 0.3);
            --modal-bg: #1e293b;
            --file-tab-bg: #0f172a;
            --tab-bg: #0f172a;
            --tab-active-bg: rgba(129, 140, 248, 0.2);
            --btn-secondary-bg: #1e293b;
            --btn-secondary-text: #cbd5e1;
            --btn-secondary-border: #334155;
            --btn-secondary-hover: #334155;
            --ui-switcher-bg: #020617;
            --ui-switcher-btn-active: #334155;
            --ui-switcher-btn-active-text: #f8fafc;
            --panel-h2-border: #334155;
            --heading-color: #f8fafc;
            --body-label-color: #94a3b8;
        }

        * {
            box-sizing: border-box;
            outline: none;
        }

        body {
            margin: 0;
            font-family: 'Outfit', 'Cairo', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        /* Top Header Styling */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            gap: 20px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--border);
            padding-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 2rem;
            font-weight: 700;
            color: var(--heading-color);
        }

        .sub {
            color: var(--muted);
            margin: 0;
            font-size: 0.95rem;
            font-weight: 400;
        }

        /* Header Controls */
        .header-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .theme-btn {
            background: var(--btn-secondary-bg);
            border: 1px solid var(--btn-secondary-border);
            color: var(--btn-secondary-text);
            padding: 8px;
            border-radius: 999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            width: 38px;
            height: 38px;
        }

        .theme-btn:hover {
            background: var(--btn-secondary-hover);
            transform: scale(1.05);
            border-color: var(--accent);
        }

        /* Language Switcher */
        .ui-lang-switcher {
            display: flex;
            background: var(--ui-switcher-bg);
            padding: 4px;
            border-radius: 999px;
            border: 1px solid var(--border);
            transition: background-color 0.3s ease;
        }

        .ui-lang-btn {
            background: transparent;
            border: none;
            color: var(--muted);
            padding: 8px 18px;
            border-radius: 999px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .ui-lang-btn.active {
            background: var(--ui-switcher-btn-active);
            color: var(--ui-switcher-btn-active-text);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* Main Studio Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        /* Professional White Panels */
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
            position: relative;
        }

        .panel h2 {
            margin: 0 0 20px;
            font-size: 1.15rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }

        /* Navigation Tabs inside Panel */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            background: #f1f5f9;
            padding: 6px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .tab {
            border: none;
            background: transparent;
            color: var(--muted);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            flex: 1;
            text-align: center;
            white-space: nowrap;
        }

        .tab.active {
            background: #ffffff;
            color: var(--accent);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Form Inputs & Controls */
        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #334155;
            margin-bottom: 8px;
        }

        .field input[type="text"],
        .field input[type="number"],
        .field textarea,
        .field select {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: var(--text);
            border-radius: 10px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .field input[type="text"]:focus,
        .field input[type="number"]:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .field textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Dynamic Columns grid */
        .row2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 600px) {
            .row2 {
                grid-template-columns: 1fr;
            }
        }

        /* Styled Color Row Wrapper */
        .color-row {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 6px 12px;
            gap: 10px;
            transition: all 0.2s ease;
        }

        .color-row:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .color-row input[type="text"] {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            font-size: 0.9rem;
            color: var(--text);
            width: 100%;
            outline: none !important;
            box-shadow: none !important;
        }

        .color-row input[type="color"] {
            width: 32px;
            height: 32px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: none;
            cursor: pointer;
            padding: 0;
            flex-shrink: 0;
        }

        /* Custom File Upload Styling */
        input[type="file"] {
            display: block;
            width: 100%;
            font-size: 0.85rem;
            color: var(--muted);
        }

        input[type="file"]::file-selector-button {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 500;
            margin-inline-end: 12px;
            transition: all 0.2s ease;
        }

        input[type="file"]::file-selector-button:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* Preview Area & Realistic Phones */
        .preview-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
            position: sticky;
            top: 24px;
        }

        .phone-mockup {
            background: #000000;
            border-radius: 40px;
            border: 12px solid #1e293b;
            box-shadow: 0 20px 45px -12px rgba(0, 0, 0, 0.25), inset 0 0 10px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            position: relative;
            width: 100%;
            aspect-ratio: 9 / 18.2;
            display: flex;
            flex-direction: column;
        }

        .phone-screen {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #000;
            position: relative;
        }

        /* Phone Top Elements */
        .phone-status-bar {
            padding: 12px 20px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent;
            z-index: 10;
        }

        .phone-notch {
            width: 100px;
            height: 24px;
            background: #000;
            border-radius: 0 0 14px 14px;
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 20;
        }

        .phone-icons {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .phone-content {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }

        .phone-home-indicator {
            height: 5px;
            width: 110px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 999px;
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        /* Wallet Cards Mockup styling */
        .apple-card,
        .google-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .apple-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .apple-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .apple-logo {
            width: 24px;
            height: 24px;
            border-radius: 5px;
            object-fit: cover;
        }

        .apple-org-name {
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }

        .apple-pass-type {
            font-size: 0.7rem;
            opacity: 0.65;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* Stamp Strip container */
        .strip {
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 16px;
            overflow: hidden;
            background: #1e293b;
        }

        .strip::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgb(0 0 0 / var(--overlay, 0.35));
            pointer-events: none;
            z-index: 0;
        }

        .stamps {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 8px;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .stamp {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            color: #ffffff;
            transition: all 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .stamp.done {
            background: var(--done, #E07B2D);
            border-color: #ffffff;
        }

        .stamp.empty {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .stamp.stamp-icon {
            background: transparent;
            border: none;
            overflow: hidden;
            padding: 0;
            border-radius: 0;
            width: 36px;
            height: 36px;
            box-shadow: none;
        }

        .stamp.stamp-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .card-body {
            padding: 16px;
            background: rgba(255, 255, 255, 0.03);
        }

        .card-body .title {
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #ffffff;
        }

        /* Fields Grid */
        .fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 0.78rem;
        }

        .fields div {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 8px 10px;
        }

        .fields small {
            display: block;
            color: var(--muted);
            font-size: 0.68rem;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .fields span {
            font-weight: 600;
            color: #ffffff;
        }

        .barcode-section {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .qr {
            width: 100%;
            height: 64px;
            border-radius: 6px;
            background: repeating-linear-gradient(90deg, #ffffff 0px, #ffffff 2px, #111111 2px, #111111 6px, #ffffff 6px, #ffffff 8px, #111111 8px, #111111 10px);
            opacity: 0.9;
        }

        .google-card .card-body {
            background: var(--g-bg, #8B5E3C);
        }

        /* Buttons Styling */
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            flex: 1;
            min-width: 150px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            transform: translateY(-1px);
            border-color: #cbd5e1;
        }

        .hint {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 10px;
            line-height: 1.4;
        }

        .status-msg {
            min-height: 1.5em;
            font-weight: 600;
            color: var(--accent);
            margin-top: 12px;
            padding: 8px 16px;
            border-radius: 8px;
            background: #e0e7ff;
            display: inline-block;
            border: 1px solid #c7d2fe;
        }

        .status-msg:empty {
            display: none;
        }

        /* Modal Redesign */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 100;
            backdrop-filter: blur(8px);
        }

        .modal.open {
            display: flex;
        }

        .modal-box {
            width: min(900px, 100%);
            max-height: 85vh;
            overflow: auto;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .file-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
        }

        .file-tab {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .file-tab.active {
            background: #ffffff;
            color: var(--accent);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        pre {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 10px;
            padding: 16px;
            overflow: auto;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-break: break-all;
            color: #cbd5e1;
            font-family: monospace;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-inline-start: 8px;
        }

        .badge-apple {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }

        .badge-google {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        /* Sort Drag & Drop List */
        .sort-list {
            list-style: none;
            padding: 0;
            margin: 8px 0 16px;
        }

        .sort-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            cursor: grab;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            color: #334155;
        }

        .sort-item:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .sort-item.dragging {
            opacity: 0.4;
            background: #cbd5e1;
        }

        .sort-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            accent-color: var(--accent);
        }

        .thumb {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
        }

        .strip.real-strip,
        .strip.has-strip-bg {
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat;
        }

        /* Custom direction support details */
        html[dir="ltr"] .header,
        html[dir="ltr"] .field label,
        html[dir="ltr"] .phone-status-bar {
            text-align: left;
        }

        html[dir="rtl"] .header,
        html[dir="rtl"] .field label,
        html[dir="rtl"] .phone-status-bar {
            text-align: right;
        }

        /* ================================================================
           DARK MODE — Full Override Block
           Fixes all hardcoded colors so the night theme looks polished
        ================================================================ */
        html.dark body {
            background: #0f172a;
        }

        /* Panels */
        html.dark .panel {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        html.dark .panel h2 {
            color: #f1f5f9;
            border-bottom-color: #334155;
        }

        /* Tabs */
        html.dark .tabs {
            background: #020617;
            border-color: #334155;
        }

        html.dark .tab {
            color: #64748b;
        }

        html.dark .tab.active {
            background: #1e293b;
            color: #818cf8;
            border-color: #334155;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        html.dark .tab:hover:not(.active) {
            background: #1e293b;
            color: #94a3b8;
        }

        /* Form inputs */
        html.dark .field label {
            color: #94a3b8;
        }

        html.dark .field input[type="text"],
        html.dark .field input[type="number"],
        html.dark .field textarea,
        html.dark .field select {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        html.dark .field input[type="text"]::placeholder,
        html.dark .field input[type="number"]::placeholder,
        html.dark .field textarea::placeholder {
            color: #475569;
        }

        html.dark .field select option {
            background: #1e293b;
            color: #e2e8f0;
        }

        /* Color row */
        html.dark .color-row {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark .color-row input[type="color"] {
            border-color: #475569;
        }

        /* File selector button */
        html.dark input[type="file"]::file-selector-button {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark input[type="file"]::file-selector-button:hover {
            background: #334155;
            border-color: #475569;
        }

        /* Buttons */
        html.dark .btn-secondary {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }

        html.dark .btn-secondary:hover {
            background: #334155;
            border-color: #475569;
        }

        html.dark .btn-primary {
            box-shadow: 0 4px 12px rgba(129, 140, 248, 0.25);
        }

        html.dark .btn-primary:hover {
            background: #6366f1;
            box-shadow: 0 6px 20px rgba(129, 140, 248, 0.35);
        }

        /* Status message */
        html.dark .status-msg {
            background: rgba(129, 140, 248, 0.15);
            border-color: rgba(129, 140, 248, 0.35);
            color: #a5b4fc;
        }

        /* Badges */
        html.dark .badge-apple {
            background: #f8fafc;
            color: #0f172a;
            border-color: #e2e8f0;
        }

        html.dark .badge-google {
            background: rgba(59, 130, 246, 0.15);
            color: #93c5fd;
            border-color: rgba(59, 130, 246, 0.3);
        }

        /* Modal */
        html.dark .modal {
            background: rgba(2, 6, 23, 0.75);
        }

        html.dark .modal-box {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
        }

        html.dark .modal-box h2 {
            color: #f1f5f9;
        }

        /* File tabs (inside modal) */
        html.dark .file-tabs {
            background: #020617;
        }

        html.dark .file-tab {
            color: #64748b;
        }

        html.dark .file-tab.active {
            background: #1e293b;
            color: #818cf8;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        /* Sort list items */
        html.dark .sort-item {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark .sort-item:hover {
            background: #334155;
            border-color: #475569;
        }

        html.dark .sort-item.dragging {
            background: #475569;
            opacity: 0.5;
        }

        html.dark .sort-item input[type="checkbox"] {
            border-color: #475569;
        }

        /* Thumb image */
        html.dark .thumb {
            border-color: #334155;
            background: #0f172a;
        }

        /* Phone mockup — brighter frame in dark */
        html.dark .phone-mockup {
            border-color: #334155;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7), 0 0 0 1px #1e293b;
        }

        html.dark .phone-content {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }

        /* Strip area (card preview) */
        html.dark .strip {
            background: #0f172a;
        }

        /* Card field labels (inside phone mockup) — stays white, already ok */
        /* hint text */
        html.dark .hint {
            color: #64748b;
        }

        /* Theme toggle button */
        html.dark .theme-btn {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark .theme-btn:hover {
            background: #334155;
            border-color: #818cf8;
            color: #818cf8;
        }

        /* Language switcher */
        html.dark .ui-lang-switcher {
            background: #020617;
            border-color: #334155;
        }

        html.dark .ui-lang-btn {
            color: #475569;
        }

        html.dark .ui-lang-btn.active {
            background: #1e293b;
            color: #f1f5f9;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        /* Scrollbar in dark mode */
        html.dark ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        html.dark ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        html.dark ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 999px;
        }

        html.dark ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Header border */
        html.dark .header {
            border-bottom-color: #1e293b;
        }

        /* Strong / label elements inside panels */
        html.dark strong {
            color: #94a3b8;
        }

        /* Wrap background subtle gradient in dark */
        html.dark .wrap {
            position: relative;
        }
    </style>

</head>

<body>
    <div class="wrap">
        <header class="header">
            <div>
                <h1 data-t="studio_title">Wallet Design Studio</h1>
                <p class="sub" data-t="studio_subtitle">صمّم العناصر المسموحة لـ Apple / Google Wallet — معاينة تقريبية + تصدير config و lang</p>
            </div>
            <div class="header-controls">
                <button type="button" class="theme-btn" id="themeToggleBtn" onclick="toggleTheme()"></button>
                <div class="ui-lang-switcher">
                    <button type="button" class="ui-lang-btn active" id="uiLangArBtn" onclick="setUiLanguage('ar')">العربية</button>
                    <button type="button" class="ui-lang-btn" id="uiLangEnBtn" onclick="setUiLanguage('en')">English</button>
                </div>
            </div>
        </header>

        <div class="tabs" id="platformTabs">
            <button type="button" class="tab active" data-platform="apple" data-t="tab_apple">Apple Wallet فقط</button>
            <button type="button" class="tab" data-platform="google" data-t="tab_google">Google Wallet فقط</button>
        </div>

        <div class="grid">
            <div>
                <div class="panel">
                    <h2 data-t="shared_settings">الإعدادات المشتركة</h2>
                    <div class="row2">
                        <div class="field">
                            <label data-t="preview_locale_label">لغة المعاينة / WALLET_LOCALE</label>
                            <select id="preview_locale">
                                <option value="ar" data-t="arabic">العربية</option>
                                <option value="en" data-t="english">English</option>
                            </select>
                        </div>
                        <div class="field">
                            <label data-t="stamp_columns_label">أعمدة الأختام</label>
                            <input type="number" id="stamp_columns" min="1" max="10">
                        </div>
                    </div>
                    <div class="row2">
                        <div class="field">
                            <label data-t="preview_stamps_filled_label">أختام مكتملة (معاينة)</label>
                            <input type="number" id="preview_stamps_filled" min="0" max="50">
                        </div>
                        <div class="field">
                            <label data-t="preview_stamps_total_label">إجمالي الأختام</label>
                            <input type="number" id="preview_stamps_total" min="1" max="50">
                        </div>
                    </div>
                    <div class="row2">
                        <div class="field">
                            <label data-t="preview_program_label">اسم البرنامج</label>
                            <input type="text" id="preview_program">
                        </div>
                        <div class="field">
                            <label data-t="preview_member_label">اسم العضو</label>
                            <input type="text" id="preview_member">
                        </div>
                    </div>
                    <div class="field">
                        <label data-t="preview_rewards_label">المكافآت (معاينة)</label>
                        <input type="number" id="preview_rewards" min="0" max="99">
                    </div>
                </div>

                <div class="panel">
                    <h2 data-t="images_title">الصور</h2>
                    <div class="row2">
                        <div class="field">
                            <label data-t="logo_upload_label">الشعار (logo / icon)</label>
                            <input type="file" id="logoUpload" accept="image/*">
                            <img id="logoPreview" class="thumb" style="display:none;margin-top:8px" alt="">
                        </div>
                        <div class="field">
                            <label data-t="strip_upload_label">خلفية شريط الأختام</label>
                            <input type="file" id="stripBgUpload" accept="image/*">
                            <img id="stripBgPreview" class="thumb" style="display:none;margin-top:8px" alt="">
                        </div>
                    </div>
                    <p class="hint" data-t="stamps_hint">الأختام تظهر تلقائياً (دوائر ملونة). ارفع PNG اختياري لاستبدال شكل الختم المكتمل أو غير المكتمل.</p>
                    <div class="row2" style="margin-top:8px">
                        <div class="field">
                            <label data-t="stamp_completed_label">أيقونة الختم المكتمل (PNG)</label>
                            <input type="file" id="stampCompletedUpload" accept="image/png,.png">
                            <img id="stampCompletedPreview" class="thumb" style="display:none;margin-top:8px" alt="">
                        </div>
                        <div class="field">
                            <label data-t="stamp_empty_label">أيقونة الختم غير المكتمل (PNG)</label>
                            <input type="file" id="stampEmptyUpload" accept="image/png,.png">
                            <img id="stampEmptyPreview" class="thumb" style="display:none;margin-top:8px" alt="">
                        </div>
                    </div>
                </div>

                <div class="panel" id="fieldsPanel">
                    <h2 data-t="fields_order_title">ترتيب الحقول (slots مسموحة)</h2>
                    <p class="hint" data-t="fields_order_hint">اسحب لإعادة الترتيب — أزل ✓ لإخفاء الحقل من البطاقة</p>
                    <div id="appleFieldsEditor">
                        <strong style="font-size:.85rem;color:var(--muted)" data-t="apple_secondary_header">Apple — secondary</strong>
                        <ul class="sort-list" id="appleSecondaryList"></ul>
                        <strong style="font-size:.85rem;color:var(--muted)" data-t="apple_auxiliary_header">Apple — auxiliary</strong>
                        <ul class="sort-list" id="appleAuxiliaryList"></ul>
                    </div>
                    <div id="googleFieldsEditor" style="margin-top:12px">
                        <strong style="font-size:.85rem;color:var(--muted)" data-t="google_modules_header">Google — modules</strong>
                        <ul class="sort-list" id="googleModulesList"></ul>
                        <label style="font-size:.85rem;display:flex;gap:8px;align-items:center;margin-top:8px;cursor:pointer">
                            <input type="checkbox" id="googleRewardsVisible"> <span data-t="show_rewards_checkbox_label">إظهار المكافآت (REWARDS)</span>
                        </label>
                    </div>
                </div>

                <div class="panel" id="appleSection">
                    <h2><span data-t="apple_wallet_header">Apple Wallet</span> <span class="badge badge-apple">storeCard</span></h2>
                    <div class="row2">
                        <div class="field">
                            <label data-t="bg_color_label">لون الخلفية</label>
                            <div class="color-row">
                                <input type="color" id="apple_background_picker">
                                <input type="text" id="apple_background">
                            </div>
                        </div>
                        <div class="field">
                            <label data-t="text_color_label">لون النص</label>
                            <div class="color-row">
                                <input type="color" id="apple_foreground_picker">
                                <input type="text" id="apple_foreground">
                            </div>
                        </div>
                    </div>
                    <div class="row2">
                        <div class="field">
                            <label data-t="label_color_label">لون التسميات</label>
                            <div class="color-row">
                                <input type="color" id="apple_label_picker">
                                <input type="text" id="apple_label">
                            </div>
                        </div>
                        <div class="field">
                            <label data-t="strip_overlay_label">شفافية خلفية الشريط (0–1)</label>
                            <input type="number" id="apple_strip_overlay" min="0" max="1" step="0.05">
                        </div>
                    </div>
                    <div class="row2">
                        <div class="field">
                            <label data-t="completed_stamp_color_label">لون الختم المكتمل</label>
                            <div class="color-row">
                                <input type="color" id="apple_stamp_completed_picker">
                                <input type="text" id="apple_stamp_completed">
                            </div>
                        </div>
                        <div class="field">
                            <label data-t="empty_stamp_color_label">لون الختم الفارغ</label>
                            <div class="color-row">
                                <input type="color" id="apple_stamp_empty_fill_picker">
                                <input type="text" id="apple_stamp_empty_fill">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel" id="googleSection">
                    <h2><span data-t="google_wallet_header">Google Wallet</span> <span class="badge badge-google">Loyalty</span></h2>
                    <div class="row2">
                        <div class="field">
                            <label data-t="bg_color_label">لون الخلفية</label>
                            <div class="color-row">
                                <input type="color" id="google_background_picker">
                                <input type="text" id="google_background">
                            </div>
                        </div>
                        <div class="field">
                            <label data-t="strip_bg_color_label">لون خلفية الشريط</label>
                            <div class="color-row">
                                <input type="color" id="google_strip_bg_picker">
                                <input type="text" id="google_strip_bg">
                            </div>
                        </div>
                    </div>
                    <div class="row2">
                        <div class="field">
                            <label data-t="completed_stamp_color_label">لون الختم المكتمل</label>
                            <div class="color-row">
                                <input type="color" id="google_stamp_filled_picker">
                                <input type="text" id="google_stamp_filled">
                            </div>
                        </div>
                        <div class="field">
                            <label data-t="empty_stamp_color_label">لون الختم الفارغ</label>
                            <div class="color-row">
                                <input type="color" id="google_stamp_empty_picker">
                                <input type="text" id="google_stamp_empty">
                            </div>
                        </div>
                    </div>
                    <div class="row2">
                        <div class="field">
                            <label data-t="stamp_border_color_label">لون حدود الختم</label>
                            <div class="color-row">
                                <input type="color" id="google_stamp_border_picker">
                                <input type="text" id="google_stamp_border">
                            </div>
                        </div>
                        <div class="field">
                            <label data-t="stats_text_color_label">لون نص الإحصائيات</label>
                            <div class="color-row">
                                <input type="color" id="google_stamp_text_picker">
                                <input type="text" id="google_stamp_text">
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label data-t="strip_overlay_label">شفافية خلفية الشريط (0–1)</label>
                        <input type="number" id="google_strip_overlay" min="0" max="1" step="0.05">
                    </div>
                </div>

                <div class="panel">
                    <h2 data-t="texts_lang_title">النصوص (lang)</h2>
                    <div class="tabs" id="langTabs">
                        <button type="button" class="tab active" data-lang="ar" data-t="tab_lang_ar">العربية</button>
                        <button type="button" class="tab" data-lang="en" data-t="tab_lang_en">English</button>
                    </div>
                    <div id="langFieldsAr" class="section active"></div>
                    <div id="langFieldsEn" class="section"></div>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn-secondary" id="previewBtn" data-t="btn_real_strip_preview">معاينة الشريط الحقيقي</button>
                    <button type="button" class="btn btn-primary" id="exportBtn" data-t="btn_export_config_lang">تصدير Config + Lang</button>
                    <button type="button" class="btn btn-secondary" id="zipBtn" data-t="btn_download_zip">تحميل ZIP</button>
                    <button type="button" class="btn btn-secondary" id="testPassBtn" data-t="btn_test_apple_pass">تجربة Apple Pass</button>
                    <button type="button" class="btn btn-secondary" id="testGoogleBtn" style="display:none" data-t="btn_test_google_save">تجربة Google Save URL</button>
                </div>
                <div style="text-align: center;"><p class="status-msg" id="statusMsg"></p></div>
                <p class="hint" data-t="preview_disclaimer">المعاينة تقريبية — Apple و Google يعرضان البطاقة باختلاف بسيط على الجوال.</p>
            </div>

            <div class="preview-stack">
                <!-- Apple Wallet Mockup Phone -->
                <div class="phone-mockup" id="applePreviewWrap">
                    <div class="phone-screen">
                        <div class="phone-status-bar">
                            <span class="phone-time" data-t="time_mock">9:41 م</span>
                            <div class="phone-notch"></div>
                            <div class="phone-icons">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h.01"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M5 13a9 9 0 0 1 14 0"/><path d="M1.5 9.5a14 14 0 0 1 21 0"/></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-start:4px;"><rect width="16" height="10" x="2" y="7" rx="2" ry="2"/><line x1="22" x2="22" y1="11" y2="13"/></svg>
                            </div>
                        </div>
                        <div class="phone-content">
                            <div class="apple-card" id="applePreview"></div>
                        </div>
                        <div class="phone-home-indicator"></div>
                    </div>
                </div>

                <!-- Google Wallet Mockup Phone -->
                <div class="phone-mockup" id="googlePreviewWrap">
                    <div class="phone-screen">
                        <div class="phone-status-bar">
                            <span class="phone-time" data-t="time_mock">9:41 م</span>
                            <div class="phone-notch"></div>
                            <div class="phone-icons">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h.01"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M5 13a9 9 0 0 1 14 0"/><path d="M1.5 9.5a14 14 0 0 1 21 0"/></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-start:4px;"><rect width="16" height="10" x="2" y="7" rx="2" ry="2"/><line x1="22" x2="22" y1="11" y2="13"/></svg>
                            </div>
                        </div>
                        <div class="phone-content">
                            <div class="google-card" id="googlePreview"></div>
                        </div>
                        <div class="phone-home-indicator"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal Redesign -->
    <div class="modal" id="exportModal">
        <div class="modal-box">
            <h2 style="margin-top:0; color:#fff;" data-t="export_modal_title">ملفات التصدير</h2>
            <div class="file-tabs" id="fileTabs"></div>
            <pre id="exportContent"></pre>
            <div class="actions">
                <button type="button" class="btn btn-primary" id="copyBtn" data-t="btn_copy">نسخ</button>
                <button type="button" class="btn btn-secondary" id="closeModal" data-t="btn_close">إغلاق</button>
            </div>
        </div>
    </div>

    <script>
        const defaults = @json($defaults);
        const langKeys = @json($langKeys);
        const appleFieldSlots = @json($appleFieldSlots);
        const googleFieldSlots = @json($googleFieldSlots);
        const storageBase = @json($storageBase ?? '/storage');
        const appUrl = @json($appUrl ?? '');
        const routes = {
            export: @json(route('wallet-studio.export')),
            downloadZip: @json(route('wallet-studio.download-zip')),
            upload: @json(route('wallet-studio.upload')),
            preview: @json(route('wallet-studio.preview')),
            testPass: @json(route('wallet-studio.test-pass')),
        };
        let platform = defaults.platform === 'google' ? 'google' : 'apple';
        let exportFiles = {};
        let activeFile = '';
        let realStripUrls = {
            apple: null,
            google: null
        };

        const state = {
            ...defaults,
            lang_ar: {
                ...defaults.lang_ar
            },
            lang_en: {
                ...defaults.lang_en
            }
        };

        const colorPairs = [
            ['apple_background', 'apple_background_picker'],
            ['apple_foreground', 'apple_foreground_picker'],
            ['apple_label', 'apple_label_picker'],
            ['apple_stamp_completed', 'apple_stamp_completed_picker'],
            ['apple_stamp_empty_fill', 'apple_stamp_empty_fill_picker'],
            ['google_background', 'google_background_picker'],
            ['google_strip_bg', 'google_strip_bg_picker'],
            ['google_stamp_filled', 'google_stamp_filled_picker'],
            ['google_stamp_empty', 'google_stamp_empty_picker'],
            ['google_stamp_border', 'google_stamp_border_picker'],
            ['google_stamp_text', 'google_stamp_text_picker'],
        ];

        // Language Translation system for UI
        let currentUiLang = 'ar';
        const uiTranslations = {
            ar: {
                studio_title: "استوديو تصميم البطاقات",
                studio_subtitle: "صمّم وقارن عناصر بطاقتك لـ Apple Wallet و Google Wallet — معاينة حيّة وتصدير فوري",
                tab_apple: "Apple Wallet فقط",
                tab_google: "Google Wallet فقط",
                shared_settings: "الإعدادات العامة للبطاقة",
                preview_locale_label: "لغة المعاينة الافتراضية",
                stamp_columns_label: "عدد أعمدة الأختام في الصف",
                preview_stamps_filled_label: "عدد الأختام المكتملة (للمعاينة)",
                preview_stamps_total_label: "إجمالي عدد الأختام المطلوبة",
                preview_program_label: "اسم برنامج الولاء / المتجر",
                preview_member_label: "اسم العضو (مثال للمعاينة)",
                preview_rewards_label: "رصيد المكافآت المتاحة",
                images_title: "إدارة الصور والأيقونات",
                logo_upload_label: "شعار البطاقة (Logo / Icon)",
                strip_upload_label: "خلفية شريط الأختام (Strip BG)",
                stamps_hint: "تظهر الأختام تلقائياً كدوائر ملونة. يمكنك رفع أيقونات مخصصة بصيغة PNG لاستبدال الشكل الافتراضي.",
                stamp_completed_label: "أيقونة الختم المكتمل (PNG)",
                stamp_empty_label: "أيقونة الختم الفارغ (PNG)",
                fields_order_title: "ترتيب الحقول وال slots المتاحة",
                fields_order_hint: "اسحب الحقول لتغيير ترتيب ظهورها، وأزل علامة الصح ✓ لإخفاء الحقل من البطاقة تماماً.",
                apple_secondary_header: "حقول Apple Wallet الثانوية (Secondary)",
                apple_auxiliary_header: "حقول Apple Wallet المساعدة (Auxiliary)",
                google_modules_header: "حقول Google Wallet (Modules)",
                show_rewards_checkbox_label: "إظهار المكافآت (REWARDS)",
                apple_wallet_header: "مظهر Apple Wallet",
                google_wallet_header: "مظهر Google Wallet",
                bg_color_label: "لون خلفية البطاقة",
                text_color_label: "لون النص الرئيسي",
                label_color_label: "لون تسمية الحقول",
                strip_overlay_label: "شفافية تعتيم خلفية الشريط (0-1)",
                completed_stamp_color_label: "لون الختم المكتمل",
                empty_stamp_color_label: "لون الختم الفارغ",
                strip_bg_color_label: "لون شريط الأختام",
                stamp_border_color_label: "لون حدود الختم",
                stats_text_color_label: "لون نص الإحصائيات",
                texts_lang_title: "النصوص المترجمة داخل البطاقة (Lang)",
                btn_real_strip_preview: "توليد شريط الأختام الفعلي",
                btn_export_config_lang: "تصدير ملفات الإعدادات واللغة",
                btn_download_zip: "تحميل الحزمة ZIP",
                btn_test_apple_pass: "تنزيل بطاقة Apple Pass",
                btn_test_google_save: "حفظ في Google Wallet",
                preview_disclaimer: "ملاحظة: هذه معاينة تقريبية فقط لتسهيل التصميم، قد يختلف المظهر الفعلي قليلاً حسب نظام التشغيل والجهاز.",
                export_modal_title: "ملفات الإعدادات والتصدير الجاهزة",
                btn_copy: "نسخ الكود",
                btn_close: "إغلاق النافذة",
                choose_file: "اختر ملفاً...",
                arabic: "العربية",
                english: "English",
                time_mock: "9:41 م",
                tab_lang_ar: "العربية",
                tab_lang_en: "English",
                toggle_theme_light: "الوضع المضيء",
                toggle_theme_dark: "الوضع الداكن"
            },
            en: {
                studio_title: "Wallet Design Studio",
                studio_subtitle: "Design and preview loyalty cards for Apple Wallet & Google Wallet — live mockup & export",
                tab_apple: "Apple Wallet Only",
                tab_google: "Google Wallet Only",
                shared_settings: "Shared Card Settings",
                preview_locale_label: "Default Preview Language",
                stamp_columns_label: "Stamp Columns per Row",
                preview_stamps_filled_label: "Completed Stamps (Preview)",
                preview_stamps_total_label: "Total Required Stamps",
                preview_program_label: "Program / Merchant Name",
                preview_member_label: "Member Name (Mockup example)",
                preview_rewards_label: "Available Rewards Balance",
                images_title: "Image & Icon Assets",
                logo_upload_label: "Card Logo / Icon",
                strip_upload_label: "Stamp Strip Background",
                stamps_hint: "Stamps appear automatically as colored circles. You can upload custom PNG icons to replace the defaults.",
                stamp_completed_label: "Completed Stamp Icon (PNG)",
                stamp_empty_label: "Empty Stamp Icon (PNG)",
                fields_order_title: "Fields Slots Ordering",
                fields_order_hint: "Drag fields to change their display order. Uncheck ✓ to hide a field from the card preview.",
                apple_secondary_header: "Apple Wallet - Secondary Fields",
                apple_auxiliary_header: "Apple Wallet - Auxiliary Fields",
                google_modules_header: "Google Wallet - Modules",
                show_rewards_checkbox_label: "Show Rewards (REWARDS)",
                apple_wallet_header: "Apple Wallet Styling",
                google_wallet_header: "Google Wallet Styling",
                bg_color_label: "Card Background Color",
                text_color_label: "Main Text Color",
                label_color_label: "Fields Label Color",
                strip_overlay_label: "Strip Background Opacity (0-1)",
                completed_stamp_color_label: "Completed Stamp Color",
                empty_stamp_color_label: "Empty Stamp Color",
                strip_bg_color_label: "Strip Background Color",
                stamp_border_color_label: "Stamp Border Color",
                stats_text_color_label: "Stats Text Color",
                texts_lang_title: "Card Texts & Localization (Lang)",
                btn_real_strip_preview: "Generate Real Stamp Strip",
                btn_export_config_lang: "Export Config & Lang Files",
                btn_download_zip: "Download ZIP Package",
                btn_test_apple_pass: "Download Apple Pass",
                btn_test_google_save: "Save to Google Wallet",
                preview_disclaimer: "Note: This is an approximate preview for design guidance. The actual look may vary slightly on device OS.",
                export_modal_title: "Ready Config & Lang Files",
                btn_copy: "Copy Content",
                btn_close: "Close Window",
                choose_file: "Choose file...",
                arabic: "Arabic",
                english: "English",
                time_mock: "9:41 AM",
                tab_lang_ar: "Arabic",
                tab_lang_en: "English",
                toggle_theme_light: "Light Mode",
                toggle_theme_dark: "Dark Mode"
            }
        };

        const statusMessages = {
            ar: {
                upload_failed: 'فشل رفع الصورة.',
                storage_link_logo: 'تعذّر عرض الشعار — تحقق من storage:link',
                storage_link_strip: 'تعذّر عرض خلفية الشريط — تحقق من storage:link',
                png_only_completed: 'تعذّر عرض أيقونة الختم المكتمل — PNG فقط',
                png_only_empty: 'تعذّر عرض أيقونة الختم غير المكتمل — PNG فقط',
                upload_success: 'تم رفع الصورة.',
                export_ready: 'تم تجهيز ملفات التصدير.',
                generating_strip: 'جاري توليد شريط الأختام...',
                strip_generated: (label) => `تم توليد شريط ${label}.`,
                strip_generation_failed: (label) => `تعذر توليد شريط ${label} (تحقق من GD).`,
                generating_google_url: 'جاري توليد Google Save URL...',
                google_not_configured: 'Google Wallet غير مهيأ — أضف GOOGLE_WALLET_ISSUER_ID و Service Account في .env',
                google_url_failed: 'تعذّر إنشاء Save URL — تأكد أن GOOGLE_WALLET_FALLBACK_LOGO يرجّع صورة PNG/JPG على HTTPS عام',
                google_url_success: 'تم فتح رابط Google Save URL.',
                preparing_zip: 'جاري تجهيز ZIP...',
                zip_downloaded: 'تم تحميل wallet-design.zip',
                generating_pass: 'جاري توليد Apple Pass...',
                pass_downloaded: 'تم تحميل studio-preview.pkpass',
                error_generating_pass: 'تعذر توليد البطاقة.'
            },
            en: {
                upload_failed: 'Upload failed.',
                storage_link_logo: 'Could not display logo — verify storage:link',
                storage_link_strip: 'Could not display strip background — verify storage:link',
                png_only_completed: 'Could not display completed stamp icon — PNG only',
                png_only_empty: 'Could not display empty stamp icon — PNG only',
                upload_success: 'Image uploaded successfully.',
                export_ready: 'Export files prepared.',
                generating_strip: 'Generating stamp strip...',
                strip_generated: (label) => `${label} strip generated successfully.`,
                strip_generation_failed: (label) => `Could not generate ${label} strip (verify GD).`,
                generating_google_url: 'Generating Google Save URL...',
                google_not_configured: 'Google Wallet not configured — add GOOGLE_WALLET_ISSUER_ID and Service Account in .env',
                google_url_failed: 'Could not create Save URL — make sure GOOGLE_WALLET_FALLBACK_LOGO returns a PNG/JPG image on public HTTPS',
                google_url_success: 'Opened Google Save URL.',
                preparing_zip: 'Preparing ZIP...',
                zip_downloaded: 'Downloaded wallet-design.zip',
                generating_pass: 'Generating Apple Pass...',
                pass_downloaded: 'Downloaded studio-preview.pkpass',
                error_generating_pass: 'Could not generate pass.'
            }
        };

        function setUiLanguage(lang) {
            currentUiLang = lang;
            document.documentElement.setAttribute('lang', lang);
            document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
            
            // Toggle active classes on switcher buttons
            $('uiLangArBtn').classList.toggle('active', lang === 'ar');
            $('uiLangEnBtn').classList.toggle('active', lang === 'en');
            
            // Translate UI elements
            const trans = uiTranslations[lang];
            document.querySelectorAll('[data-t]').forEach(el => {
                const key = el.dataset.t;
                if (trans[key]) {
                    el.textContent = trans[key];
                }
            });

            // Update theme switch title attribute
            const themeBtn = $('themeToggleBtn');
            if (themeBtn) {
                const isDark = document.documentElement.classList.contains('dark');
                themeBtn.title = trans[isDark ? 'toggle_theme_light' : 'toggle_theme_dark'];
            }
            
            // Re-render
            render();
        }

        function $(id) {
            return document.getElementById(id);
        }

        function bindInputs() {
            const ids = [
                'preview_locale', 'stamp_columns', 'preview_stamps_filled', 'preview_stamps_total',
                'preview_program', 'preview_member', 'preview_rewards',
                'apple_strip_overlay', 'google_strip_overlay'
            ];
            ids.forEach(id => {
                const el = $(id);
                if (!el) return;
                el.value = state[id] ?? '';
                el.addEventListener('input', () => {
                    state[id] = el.type === 'number' ? Number(el.value) : el.value;
                    render();
                });
            });

            colorPairs.forEach(([textId, pickerId]) => {
                const text = $(textId);
                const picker = $(pickerId);
                if (!text || !picker) return;
                const val = normalizeHex(state[textId] || '#000000');
                text.value = val;
                picker.value = val;
                picker.addEventListener('input', () => {
                    text.value = picker.value;
                    state[textId] = picker.value;
                    render();
                });
                text.addEventListener('input', () => {
                    const v = normalizeHex(text.value);
                    picker.value = v;
                    state[textId] = v;
                    render();
                });
            });

            const rewardsCb = $('googleRewardsVisible');
            if (rewardsCb) {
                rewardsCb.checked = isVisible('rewards', 'google_visible_fields');
                rewardsCb.addEventListener('change', () => {
                    const set = new Set(state.google_visible_fields || []);
                    rewardsCb.checked ? set.add('rewards') : set.delete('rewards');
                    state.google_visible_fields = Array.from(set);
                    render();
                });
            }
        }

        function normalizeHex(v) {
            v = (v || '').trim();
            if (!v.startsWith('#')) v = '#' + v;
            return v.length === 4 ? v : v.slice(0, 7);
        }

        function buildLangFields() {
            ['ar', 'en'].forEach(locale => {
                const container = $('langFields' + (locale === 'ar' ? 'Ar' : 'En'));
                container.innerHTML = langKeys.map(key => `
            <div class="field">
                <label>${key}</label>
                <input type="text" data-lang="${locale}" data-key="${key}" value="${escapeHtml(state['lang_' + locale][key] || '')}">
            </div>
        `).join('');
                container.querySelectorAll('input').forEach(input => {
                    input.addEventListener('input', () => {
                        state['lang_' + input.dataset.lang][input.dataset.key] = input.value;
                        render();
                    });
                });
            });
        }

        function escapeHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        function mediaUrl(url) {
            if (!url) return '';
            const value = String(url).replace(/\\/g, '/');
            const base = appUrl || '';

            if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('data:')) {
                try {
                    const parsed = new URL(value);
                    if (parsed.pathname.startsWith('/storage/') || (base && parsed.origin !== base.replace(/\/$/, ''))) {
                        return base + parsed.pathname;
                    }
                    return value;
                } catch (_) {
                    return value;
                }
            }

            if (value.startsWith('/storage/') || value.startsWith('/')) {
                return base + value;
            }

            return storageBase.replace(/\/$/, '') + '/' + value.replace(/^\/+/, '');
        }

        function cssUrl(url) {
            const resolved = mediaUrl(url);
            if (!resolved) return '';
            return `url('${resolved.replace(/'/g, "%27")}')`;
        }

        function stripClass(platformKey) {
            const classes = ['strip'];
            if (realStripUrls[platformKey]) classes.push('real-strip');
            else if (state.strip_bg_url) classes.push('has-strip-bg');
            return classes.join(' ');
        }

        function labels() {
            const loc = state.preview_locale === 'en' ? 'lang_en' : 'lang_ar';
            return state[loc];
        }

        function renderStamps(total, filled, doneColor, emptyStyle) {
            let html = '';
            const cols = state.stamp_columns || 5;
            // Configure columns inside CSS grid inline
            const gridStyle = `grid-template-columns: repeat(${cols}, auto);`;
            
            for (let i = 0; i < total; i++) {
                const done = i < filled;
                const completedIcon = state.stamp_completed_icon_url;
                const emptyIcon = state.stamp_empty_icon_url;
                if (done && completedIcon) {
                    html += `<div class="stamp stamp-icon"><img src="${escapeHtml(mediaUrl(completedIcon))}" alt=""></div>`;
                } else if (!done && emptyIcon) {
                    html += `<div class="stamp stamp-icon"><img src="${escapeHtml(mediaUrl(emptyIcon))}" alt=""></div>`;
                } else {
                    html +=
                        `<div class="stamp ${done ? 'done' : 'empty'}" style="${done ? '--done:'+doneColor : emptyStyle}">${done ? '✓' : ''}</div>`;
                }
            }
            return `<div class="stamps" style="${gridStyle}">${html}</div>`;
        }

        function isVisible(id, platformKey) {
            return (state[platformKey] || []).includes(id);
        }

        function fieldHtml(id, L, filled, remaining) {
            const map = {
                stamps: `<div><small>${escapeHtml(L.stamps)}</small><span>${filled} / ${state.preview_stamps_total}</span></div>`,
                rewards: `<div><small>${escapeHtml(L.rewards)}</small><span>${state.preview_rewards || 0}</span></div>`,
                remaining: `<div><small>${escapeHtml(L.remaining)}</small><span>${remaining}</span></div>`,
                member: `<div><small>${escapeHtml(L.member)}</small><span>${escapeHtml(state.preview_member)}</span></div>`,
                status: `<div style="grid-column:1/-1"><small>${escapeHtml(L.status)}</small><span>${filled >= state.preview_stamps_total ? escapeHtml(L.status_completed) : escapeHtml(L.status_in_progress)}</span></div>`,
            };
            return map[id] || '';
        }

        function appleFieldsHtml(L, filled, remaining) {
            const primary = fieldHtml('stamps', L, filled, remaining);
            const secondary = (state.apple_secondary_order || []).filter(id => isVisible(id, 'apple_visible_fields')).map(
                id => fieldHtml(id, L, filled, remaining)).join('');
            const auxiliary = (state.apple_auxiliary_order || []).filter(id => isVisible(id, 'apple_visible_fields')).map(
                id => fieldHtml(id, L, filled, remaining)).join('');
            return primary + secondary + auxiliary;
        }

        function googleFieldsHtml(L, filled, remaining) {
            const rows = [];
            rows.push(fieldHtml('stamps', L, filled, remaining));
            (state.google_modules_order || []).forEach(id => {
                if (isVisible(id, 'google_visible_fields')) rows.push(fieldHtml(id, L, filled, remaining));
            });
            if (isVisible('rewards', 'google_visible_fields')) rows.push(fieldHtml('rewards', L, filled, remaining));
            return rows.join('');
        }

        function stripStyle(platformKey) {
            const realUrl = realStripUrls[platformKey];
            if (realUrl) {
                return `background-image: ${cssUrl(realUrl)}; background-size: cover; background-position: center; --overlay: 0`;
            }
            if (state.strip_bg_url) {
                const overlay = platformKey === 'apple' ? state.apple_strip_overlay : (state.google_strip_overlay || 0.35);
                return `background-image: ${cssUrl(state.strip_bg_url)}; background-size: cover; background-position: center; --overlay: ${overlay}`;
            }
            if (platformKey === 'apple')
                return `background: ${state.apple_background}; --overlay: ${state.apple_strip_overlay}`;
            return `background: ${state.google_strip_bg}; --overlay: ${state.google_strip_overlay || 0.35}`;
        }

        function updatePlatformUi() {
            $('applePreviewWrap').style.display = platform === 'google' ? 'none' : 'block';
            $('googlePreviewWrap').style.display = platform === 'apple' ? 'none' : 'block';
            $('appleSection').style.display = platform === 'google' ? 'none' : 'block';
            $('googleSection').style.display = platform === 'apple' ? 'none' : 'block';
            $('appleFieldsEditor').style.display = platform === 'google' ? 'none' : 'block';
            $('googleFieldsEditor').style.display = platform === 'apple' ? 'none' : 'block';
            $('testPassBtn').style.display = platform === 'apple' ? 'inline-block' : 'none';
            $('testGoogleBtn').style.display = platform === 'google' ? 'inline-block' : 'none';
        }

        function render() {
            const L = labels();
            const filled = Math.min(state.preview_stamps_filled, state.preview_stamps_total);
            const remaining = Math.max(0, state.preview_stamps_total - filled);
            const logoHtml = state.logo_url ? `<img class="apple-logo" src="${mediaUrl(state.logo_url)}" alt="">` : '';

            if (platform !== 'google') {
                $('applePreview').style.background = state.apple_background;
                $('applePreview').style.color = state.apple_foreground;
                $('applePreview').innerHTML = `
            <div class="apple-card-header" style="color: ${state.apple_foreground}">
                <div class="apple-header-left">
                    ${logoHtml}
                    <span class="apple-org-name">${escapeHtml(state.preview_program)}</span>
                </div>
                <span class="apple-pass-type" style="color: ${state.apple_label}">Store Card</span>
            </div>
            <div class="${stripClass('apple')}" style="${stripStyle('apple')}">
                ${realStripUrls.apple ? '' : renderStamps(Math.min(state.preview_stamps_total, 10), Math.min(filled, 10), state.apple_stamp_completed, '')}
            </div>
            <div class="card-body">
                <div class="fields" style="--apple-label-color: ${state.apple_label}; --apple-foreground-color: ${state.apple_foreground}">
                    ${appleFieldsHtml(L, filled, remaining)}
                </div>
                <div class="barcode-section">
                    <div class="qr"></div>
                </div>
            </div>`;
            }

            if (platform !== 'apple') {
                const gLogoHtml = state.logo_url ? `<img class="apple-logo" src="${mediaUrl(state.logo_url)}" alt="" style="border-radius: 50%;">` : '';
                $('googlePreview').innerHTML = `
            <div class="apple-card-header" style="background: rgba(0,0,0,0.15); color: #fff;">
                <div class="apple-header-left">
                    ${gLogoHtml}
                    <span class="apple-org-name">${escapeHtml(state.preview_program)}</span>
                </div>
                <span class="apple-pass-type">Loyalty</span>
            </div>
            <div class="${stripClass('google')}" style="${stripStyle('google')}">
                ${realStripUrls.google ? '' : renderStamps(Math.min(state.preview_stamps_total, 10), Math.min(filled, 10), state.google_stamp_filled, 'background:'+state.google_stamp_empty+'; border-color: '+state.google_stamp_border)}
            </div>
            <div class="card-body" style="--g-bg:${state.google_background}; color:#fff">
                <div class="fields">
                    ${googleFieldsHtml(L, filled, remaining)}
                </div>
                <div class="barcode-section">
                    <div class="qr"></div>
                    <div style="font-size:.7rem; opacity:.7; text-align: center;">${escapeHtml(L.barcode_footer)}</div>
                </div>
            </div>`;
            }

            updatePlatformUi();
        }

        function buildSortList(listId, orderKey, visibleKey, slotDefs) {
            const list = $(listId);
            if (!list) return;
            const order = state[orderKey] || [];
            const defs = Object.fromEntries(slotDefs.map(s => [s.id, s.label]));
            list.innerHTML = order.map(id => `
        <li class="sort-item" draggable="true" data-id="${id}">
            <input type="checkbox" ${isVisible(id, visibleKey) ? 'checked' : ''} data-visible="${id}">
            <span>${escapeHtml(defs[id] || id)}</span>
        </li>
    `).join('');

            list.querySelectorAll('input[type=checkbox]').forEach(cb => {
                cb.addEventListener('change', () => {
                    const id = cb.dataset.visible;
                    const set = new Set(state[visibleKey] || []);
                    cb.checked ? set.add(id) : set.delete(id);
                    state[visibleKey] = Array.from(set);
                    render();
                });
            });

            list.querySelectorAll('.sort-item').forEach(item => {
                item.addEventListener('dragstart', () => item.classList.add('dragging'));
                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    state[orderKey] = Array.from(list.querySelectorAll('.sort-item')).map(el => el.dataset
                        .id);
                    render();
                });
            });

            list.addEventListener('dragover', e => {
                e.preventDefault();
                const dragging = list.querySelector('.dragging');
                const after = Array.from(list.querySelectorAll('.sort-item:not(.dragging)')).find(el => e.clientY <=
                    el.getBoundingClientRect().top + el.offsetHeight / 2);
                if (dragging) list.insertBefore(dragging, after || null);
            });
        }

        function buildFieldEditors() {
            buildSortList('appleSecondaryList', 'apple_secondary_order', 'apple_visible_fields', appleFieldSlots.secondary);
            buildSortList('appleAuxiliaryList', 'apple_auxiliary_order', 'apple_visible_fields', appleFieldSlots.auxiliary);
            buildSortList('googleModulesList', 'google_modules_order', 'google_visible_fields', googleFieldSlots.filter(s =>
                s.id !== 'rewards'));
        }

        async function postBlob(url) {
            state.platform = platform;
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(state),
            });
        }

        async function postJson(url) {
            const res = await postBlob(url);
            return res;
        }

        async function uploadImage(input, kind) {
            const file = input.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('image', file);
            fd.append('kind', kind);
            const res = await fetch(routes.upload, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: fd,
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                setStatus(err.message || 'upload_failed');
                return;
            }
            const data = await res.json();
            const url = mediaUrl(data.url || data.path);
            if (kind === 'logo') {
                state.logo_path = data.path;
                state.logo_url = url;
                const img = $('logoPreview');
                img.src = url;
                img.style.display = 'block';
                img.onerror = () => setStatus('storage_link_logo');
            } else if (kind === 'strip') {
                state.strip_bg_path = data.path;
                state.strip_bg_url = url;
                const img = $('stripBgPreview');
                img.src = url;
                img.style.display = 'block';
                img.onerror = () => setStatus('storage_link_strip');
                realStripUrls = {
                    apple: null,
                    google: null
                };
            } else if (kind === 'stamp_completed') {
                state.stamp_completed_icon_path = data.path;
                state.stamp_completed_icon_url = url;
                const img = $('stampCompletedPreview');
                img.src = url;
                img.style.display = 'block';
                img.onerror = () => setStatus('png_only_completed');
                realStripUrls = {
                    apple: null,
                    google: null
                };
            } else if (kind === 'stamp_empty') {
                state.stamp_empty_icon_path = data.path;
                state.stamp_empty_icon_url = url;
                const img = $('stampEmptyPreview');
                img.src = url;
                img.style.display = 'block';
                img.onerror = () => setStatus('png_only_empty');
                realStripUrls = {
                    apple: null,
                    google: null
                };
            }
            setStatus('upload_success');
            render();
        }

        function setStatus(msgKey, param = '') {
            const msgs = statusMessages[currentUiLang] || statusMessages['ar'];
            let message = msgs[msgKey];
            if (typeof message === 'function') {
                message = message(param);
            } else if (!message) {
                // Map the classical Arabic status strings to modern translated messages
                const mapping = {
                    'فشل رفع الصورة.': 'upload_failed',
                    'تعذّر عرض الشعار — تحقق من storage:link': 'storage_link_logo',
                    'تعذّر عرض خلفية الشريط — تحقق من storage:link': 'storage_link_strip',
                    'تعذّر عرض أيقونة الختم المكتمل — PNG فقط': 'png_only_completed',
                    'تعذّر عرض أيقونة الختم غير المكتمل — PNG فقط': 'png_only_empty',
                    'تم رفع الصورة.': 'upload_success',
                    'تم تجهيز ملفات التصدير.': 'export_ready',
                    'جاري توليد شريط الأختام...': 'generating_strip',
                    'جاري توليد Google Save URL...': 'generating_google_url',
                    'Google Wallet غير مهيأ — أضف GOOGLE_WALLET_ISSUER_ID و Service Account في .env': 'google_not_configured',
                    'تم فتح رابط Google Save URL.': 'google_url_success',
                    'جاري تجهيز ZIP...': 'preparing_zip',
                    'تم تحميل wallet-design.zip': 'zip_downloaded',
                    'جاري توليد Apple Pass...': 'generating_pass',
                    'تم تحميل studio-preview.pkpass': 'pass_downloaded',
                    'تعذر توليد البطاقة.': 'error_generating_pass'
                };
                const mappedKey = mapping[msgKey];
                if (mappedKey && msgs[mappedKey]) {
                    message = msgs[mappedKey];
                } else {
                    message = msgKey;
                }
            }
            $('statusMsg').textContent = message || '';
        }

        async function downloadBlobResponse(res, filename) {
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        }

        document.querySelectorAll('#platformTabs .tab').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#platformTabs .tab').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                platform = btn.dataset.platform;
                state.platform = platform;
                realStripUrls = {
                    apple: null,
                    google: null
                };
                render();
            });
        });

        document.querySelectorAll('#langTabs .tab').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#langTabs .tab').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                $('langFieldsAr').classList.toggle('active', btn.dataset.lang === 'ar');
                $('langFieldsEn').classList.toggle('active', btn.dataset.lang === 'en');
            });
        });

        $('exportBtn').addEventListener('click', async () => {
            const res = await postJson(routes.export);
            const data = await res.json();
            exportFiles = data.files || {};
            const names = Object.keys(exportFiles);
            activeFile = names[0] || '';
            $('fileTabs').innerHTML = names.map(n =>
                `<button type="button" class="file-tab ${n===activeFile?'active':''}" data-file="${n}">${n}</button>`
                ).join('');
            $('exportContent').textContent = exportFiles[activeFile] || '';
            $('exportModal').classList.add('open');
            document.querySelectorAll('.file-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.file-tab').forEach(t => t.classList.remove(
                        'active'));
                    tab.classList.add('active');
                    activeFile = tab.dataset.file;
                    $('exportContent').textContent = exportFiles[activeFile] || '';
                });
            });
            setStatus('export_ready');
        });

        $('previewBtn').addEventListener('click', async () => {
            setStatus('generating_strip');
            const res = await postJson(routes.preview);
            const data = await res.json();
            if (platform === 'apple') {
                realStripUrls.apple = data.apple_strip_url ? mediaUrl(data.apple_strip_url) : null;
            } else {
                realStripUrls.google = data.google_strip_url ? mediaUrl(data.google_strip_url) : null;
            }
            render();
            const label = platform === 'apple' ? 'Apple' : 'Google';
            const stripUrl = platform === 'apple' ? data.apple_strip_url : data.google_strip_url;
            if (stripUrl) {
                setStatus('strip_generated', label);
            } else {
                setStatus('strip_generation_failed', label);
            }
        });

        let lastGoogleSaveUrl = null;

        $('testGoogleBtn').addEventListener('click', async () => {
            setStatus('generating_google_url');
            const res = await postJson(routes.preview);
            const data = await res.json();
            if (!data.google_configured) {
                setStatus('google_not_configured');
                return;
            }
            if (!data.google_save_url) {
                setStatus(data.google_error || 'google_url_failed');
                return;
            }
            lastGoogleSaveUrl = data.google_save_url;
            window.open(data.google_save_url, '_blank', 'noopener');
            setStatus('google_url_success');
        });

        $('zipBtn').addEventListener('click', async () => {
            setStatus('preparing_zip');
            const res = await postBlob(routes.downloadZip);
            await downloadBlobResponse(res, 'wallet-design.zip');
            setStatus('zip_downloaded');
        });

        $('testPassBtn').addEventListener('click', async () => {
            setStatus('generating_pass');
            const res = await postBlob(routes.testPass);
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                setStatus(err.message || 'error_generating_pass');
                return;
            }
            await downloadBlobResponse(res, 'studio-preview.pkpass');
            setStatus('pass_downloaded');
        });

        $('logoUpload')?.addEventListener('change', e => uploadImage(e.target, 'logo'));
        $('stripBgUpload')?.addEventListener('change', e => uploadImage(e.target, 'strip'));
        $('stampCompletedUpload')?.addEventListener('change', e => uploadImage(e.target, 'stamp_completed'));
        $('stampEmptyUpload')?.addEventListener('change', e => uploadImage(e.target, 'stamp_empty'));

        $('copyBtn').addEventListener('click', () => navigator.clipboard.writeText($('exportContent').textContent || ''));
        $('closeModal').addEventListener('click', () => $('exportModal').classList.remove('open'));

        bindInputs();
        buildLangFields();
        buildFieldEditors();
        
        // Theme toggle system
        let currentTheme = localStorage.getItem('wallet_studio_theme') || 'light';
        if (currentTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                currentTheme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                currentTheme = 'dark';
            }
            localStorage.setItem('wallet_studio_theme', currentTheme);
            updateThemeIcon();
            // Refresh language translations to update theme switch tooltip
            setUiLanguage(currentUiLang);
        }

        function updateThemeIcon() {
            const btn = $('themeToggleBtn');
            if (!btn) return;
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>`;
            } else {
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>`;
            }
        }

        // Initial setup for UI language and theme icon
        setUiLanguage('ar');
        updateThemeIcon();
    </script>
</body>

</html>
