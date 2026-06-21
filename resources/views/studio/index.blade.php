<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wallet Design Studio</title>
    <style>
        :root {
            --bg: #0f1419;
            --panel: #1a2332;
            --border: #2d3a4d;
            --text: #e7ecf3;
            --muted: #8b9cb3;
            --accent: #3b82f6;
            --apple: #555555;
            --google: #4285f4;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .wrap { max-width: 1400px; margin: 0 auto; padding: 24px; }
        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        .sub { color: var(--muted); margin-bottom: 24px; font-size: 0.92rem; }
        .grid { display: grid; grid-template-columns: 1fr 420px; gap: 24px; align-items: start; }
        @media (max-width: 1100px) { .grid { grid-template-columns: 1fr; } }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
        }
        .panel h2 { margin: 0 0 16px; font-size: 1rem; }
        .tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab {
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            padding: 8px 16px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 0.88rem;
        }
        .tab.active { background: var(--accent); border-color: var(--accent); color: #fff; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 0.82rem; color: var(--muted); margin-bottom: 6px; }
        .field input[type="text"],
        .field input[type="number"],
        .field textarea,
        .field select {
            width: 100%;
            background: #0f1419;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 8px;
            padding: 9px 11px;
            font: inherit;
        }
        .field textarea { min-height: 64px; resize: vertical; }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .color-row { display: flex; gap: 10px; align-items: center; }
        .color-row input[type="color"] {
            width: 44px; height: 36px; border: none; background: none; cursor: pointer; padding: 0;
        }
        .section { display: none; }
        .section.active { display: block; }
        .preview-stack { display: flex; flex-direction: column; gap: 18px; position: sticky; top: 20px; }
        .phone {
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 16px 40px rgba(0,0,0,.35);
        }
        .phone-head {
            padding: 10px 14px;
            font-size: 0.75rem;
            color: var(--muted);
            background: rgba(0,0,0,.25);
            display: flex; justify-content: space-between;
        }
        .apple-card, .google-card { padding: 0; }
        .strip {
            min-height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 16px;
        }
        .strip::after {
            content: '';
            position: absolute; inset: 0;
            background: rgba(0,0,0,var(--overlay, .35));
            pointer-events: none;
        }
        .stamps {
            position: relative; z-index: 1;
            display: flex; flex-wrap: wrap; gap: 8px;
            justify-content: center; max-width: 280px;
        }
        .stamp {
            width: 34px; height: 34px; border-radius: 50%;
            border: 2px solid #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem; color: #fff;
        }
        .stamp.done { background: var(--done, #E07B2D); }
        .stamp.empty { background: rgba(255,255,255,.15); }
        .card-body { padding: 14px; }
        .card-body .title { font-weight: 700; margin-bottom: 10px; font-size: 0.95rem; }
        .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.78rem; }
        .fields div { background: rgba(255,255,255,.06); border-radius: 8px; padding: 8px; }
        .fields small { display: block; color: rgba(255,255,255,.65); margin-bottom: 2px; }
        .qr {
            margin-top: 12px; height: 72px; border-radius: 8px;
            background: repeating-linear-gradient(45deg, #fff 0 6px, #111 6px 12px);
            opacity: .85;
        }
        .google-card .card-body { background: var(--g-bg, #8B5E3C); }
        .google-stats {
            display: flex; justify-content: space-around;
            font-size: 0.72rem; margin-top: 8px; opacity: .9;
        }
        .actions { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .btn {
            border: none; border-radius: 10px; padding: 11px 18px;
            cursor: pointer; font: inherit; font-weight: 600;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary { background: transparent; color: var(--text); border: 1px solid var(--border); }
        .hint { font-size: 0.78rem; color: var(--muted); margin-top: 8px; }
        .modal {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65);
            align-items: center; justify-content: center; padding: 20px; z-index: 50;
        }
        .modal.open { display: flex; }
        .modal-box {
            width: min(900px, 100%); max-height: 90vh; overflow: auto;
            background: var(--panel); border: 1px solid var(--border);
            border-radius: 14px; padding: 20px;
        }
        .file-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        .file-tab {
            padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border);
            background: transparent; color: var(--text); cursor: pointer; font-size: 0.8rem;
        }
        .file-tab.active { background: var(--accent); border-color: var(--accent); }
        pre {
            background: #0f1419; border: 1px solid var(--border);
            border-radius: 10px; padding: 14px; overflow: auto;
            font-size: 0.78rem; white-space: pre-wrap; word-break: break-word;
        }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 0.7rem; margin-inline-start: 6px;
        }
        .badge-apple { background: #333; }
        .badge-google { background: #4285f4; }
        .sort-list { list-style: none; padding: 0; margin: 0 0 12px; }
        .sort-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; margin-bottom: 8px;
            background: #0f1419; border: 1px solid var(--border); border-radius: 8px;
            cursor: grab; font-size: 0.85rem;
        }
        .sort-item.dragging { opacity: 0.5; }
        .sort-item input { width: auto; }
        .thumb { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); }
        .status-msg { min-height: 1.2em; }
        .strip.real-strip { background-size: cover !important; background-position: center !important; }
        .card-logo { width: 28px; height: 28px; border-radius: 6px; object-fit: cover; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Wallet Design Studio</h1>
    <p class="sub">صمّم العناصر المسموحة لـ Apple / Google Wallet — معاينة تقريبية + تصدير config و lang</p>

    <div class="tabs" id="platformTabs">
        <button type="button" class="tab active" data-platform="both">Apple + Google</button>
        <button type="button" class="tab" data-platform="apple">Apple فقط</button>
        <button type="button" class="tab" data-platform="google">Google فقط</button>
    </div>

    <div class="grid">
        <div>
            <div class="panel">
                <h2>الإعدادات المشتركة</h2>
                <div class="row2">
                    <div class="field">
                        <label>لغة المعاينة / WALLET_LOCALE</label>
                        <select id="preview_locale">
                            <option value="ar">العربية</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>أعمدة الأختام</label>
                        <input type="number" id="stamp_columns" min="1" max="10">
                    </div>
                </div>
                <div class="row2">
                    <div class="field">
                        <label>أختام مكتملة (معاينة)</label>
                        <input type="number" id="preview_stamps_filled" min="0" max="50">
                    </div>
                    <div class="field">
                        <label>إجمالي الأختام</label>
                        <input type="number" id="preview_stamps_total" min="1" max="50">
                    </div>
                </div>
                <div class="row2">
                    <div class="field">
                        <label>اسم البرنامج</label>
                        <input type="text" id="preview_program">
                    </div>
                    <div class="field">
                        <label>اسم العضو</label>
                        <input type="text" id="preview_member">
                    </div>
                </div>
                <div class="field">
                    <label>المكافآت (معاينة)</label>
                    <input type="number" id="preview_rewards" min="0" max="99">
                </div>
            </div>

            <div class="panel" style="margin-top:16px">
                <h2>الصور</h2>
                <div class="row2">
                    <div class="field">
                        <label>الشعار (logo / icon)</label>
                        <input type="file" id="logoUpload" accept="image/*">
                        <img id="logoPreview" class="thumb" style="display:none;margin-top:8px" alt="">
                    </div>
                    <div class="field">
                        <label>خلفية شريط الأختام</label>
                        <input type="file" id="stripBgUpload" accept="image/*">
                        <img id="stripBgPreview" class="thumb" style="display:none;margin-top:8px" alt="">
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top:16px" id="fieldsPanel">
                <h2>ترتيب الحقول (slots مسموحة)</h2>
                <p class="hint">اسحب لإعادة الترتيب — أزل ✓ لإخفاء الحقل من البطاقة</p>
                <div id="appleFieldsEditor">
                    <strong style="font-size:.85rem;color:var(--muted)">Apple — secondary</strong>
                    <ul class="sort-list" id="appleSecondaryList"></ul>
                    <strong style="font-size:.85rem;color:var(--muted)">Apple — auxiliary</strong>
                    <ul class="sort-list" id="appleAuxiliaryList"></ul>
                </div>
                <div id="googleFieldsEditor" style="margin-top:12px">
                    <strong style="font-size:.85rem;color:var(--muted)">Google — modules</strong>
                    <ul class="sort-list" id="googleModulesList"></ul>
                    <label style="font-size:.85rem;display:flex;gap:8px;align-items:center;margin-top:8px">
                        <input type="checkbox" id="googleRewardsVisible"> إظهار المكافآت (REWARDS)
                    </label>
                </div>
            </div>

            <div class="panel" style="margin-top:16px" id="appleSection">
                <h2>Apple Wallet <span class="badge badge-apple">storeCard</span></h2>
                <div class="row2">
                    <div class="field">
                        <label>لون الخلفية</label>
                        <div class="color-row">
                            <input type="color" id="apple_background_picker">
                            <input type="text" id="apple_background">
                        </div>
                    </div>
                    <div class="field">
                        <label>لون النص</label>
                        <div class="color-row">
                            <input type="color" id="apple_foreground_picker">
                            <input type="text" id="apple_foreground">
                        </div>
                    </div>
                </div>
                <div class="row2">
                    <div class="field">
                        <label>لون التسميات</label>
                        <div class="color-row">
                            <input type="color" id="apple_label_picker">
                            <input type="text" id="apple_label">
                        </div>
                    </div>
                    <div class="field">
                        <label>شفافية خلفية الشريط (0–1)</label>
                        <input type="number" id="apple_strip_overlay" min="0" max="1" step="0.05">
                    </div>
                </div>
                <div class="row2">
                    <div class="field">
                        <label>لون الختم المكتمل</label>
                        <div class="color-row">
                            <input type="color" id="apple_stamp_completed_picker">
                            <input type="text" id="apple_stamp_completed">
                        </div>
                    </div>
                    <div class="field">
                        <label>لون الختم الفارغ</label>
                        <div class="color-row">
                            <input type="color" id="apple_stamp_empty_fill_picker">
                            <input type="text" id="apple_stamp_empty_fill">
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top:16px" id="googleSection">
                <h2>Google Wallet <span class="badge badge-google">Loyalty</span></h2>
                <div class="row2">
                    <div class="field">
                        <label>لون الخلفية</label>
                        <div class="color-row">
                            <input type="color" id="google_background_picker">
                            <input type="text" id="google_background">
                        </div>
                    </div>
                    <div class="field">
                        <label>لون خلفية الشريط</label>
                        <div class="color-row">
                            <input type="color" id="google_strip_bg_picker">
                            <input type="text" id="google_strip_bg">
                        </div>
                    </div>
                </div>
                <div class="row2">
                    <div class="field">
                        <label>لون الختم المكتمل</label>
                        <div class="color-row">
                            <input type="color" id="google_stamp_filled_picker">
                            <input type="text" id="google_stamp_filled">
                        </div>
                    </div>
                    <div class="field">
                        <label>لون الختم الفارغ</label>
                        <div class="color-row">
                            <input type="color" id="google_stamp_empty_picker">
                            <input type="text" id="google_stamp_empty">
                        </div>
                    </div>
                </div>
                <div class="row2">
                    <div class="field">
                        <label>لون حدود الختم</label>
                        <div class="color-row">
                            <input type="color" id="google_stamp_border_picker">
                            <input type="text" id="google_stamp_border">
                        </div>
                    </div>
                    <div class="field">
                        <label>لون نص الإحصائيات</label>
                        <div class="color-row">
                            <input type="color" id="google_stamp_text_picker">
                            <input type="text" id="google_stamp_text">
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label>شفافية خلفية الشريط (0–1)</label>
                    <input type="number" id="google_strip_overlay" min="0" max="1" step="0.05">
                </div>
            </div>

            <div class="panel" style="margin-top:16px">
                <h2>النصوص (lang)</h2>
                <div class="tabs" id="langTabs">
                    <button type="button" class="tab active" data-lang="ar">العربية</button>
                    <button type="button" class="tab" data-lang="en">English</button>
                </div>
                <div id="langFieldsAr" class="section active"></div>
                <div id="langFieldsEn" class="section"></div>
            </div>

            <div class="actions">
                <button type="button" class="btn btn-secondary" id="previewBtn">معاينة الشريط الحقيقي</button>
                <button type="button" class="btn btn-primary" id="exportBtn">تصدير Config + Lang</button>
                <button type="button" class="btn btn-secondary" id="zipBtn">تحميل ZIP</button>
                <button type="button" class="btn btn-secondary" id="testPassBtn">تجربة Apple Pass</button>
            </div>
            <p class="hint status-msg" id="statusMsg"></p>
            <p class="hint">المعاينة تقريبية — Apple و Google يعرضان البطاقة باختلاف بسيط على الجوال.</p>
        </div>

        <div class="preview-stack">
            <div class="phone" id="applePreviewWrap">
                <div class="phone-head"><span>Apple Wallet</span><span>mockup</span></div>
                <div class="apple-card" id="applePreview"></div>
            </div>
            <div class="phone" id="googlePreviewWrap">
                <div class="phone-head"><span>Google Wallet</span><span>mockup</span></div>
                <div class="google-card" id="googlePreview"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="exportModal">
    <div class="modal-box">
        <h2 style="margin-top:0">ملفات التصدير</h2>
        <div class="file-tabs" id="fileTabs"></div>
        <pre id="exportContent"></pre>
        <div class="actions">
            <button type="button" class="btn btn-primary" id="copyBtn">نسخ</button>
            <button type="button" class="btn btn-secondary" id="closeModal">إغلاق</button>
        </div>
    </div>
</div>

<script>
const defaults = @json($defaults);
const langKeys = @json($langKeys);
const appleFieldSlots = @json($appleFieldSlots);
const googleFieldSlots = @json($googleFieldSlots);
const routes = {
    export: @json(route('wallet-studio.export')),
    downloadZip: @json(route('wallet-studio.download-zip')),
    upload: @json(route('wallet-studio.upload')),
    preview: @json(route('wallet-studio.preview')),
    testPass: @json(route('wallet-studio.test-pass')),
};
let platform = defaults.platform || 'both';
let exportFiles = {};
let activeFile = '';
let realStripUrls = { apple: null, google: null };

const state = { ...defaults, lang_ar: { ...defaults.lang_ar }, lang_en: { ...defaults.lang_en } };

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

function $(id) { return document.getElementById(id); }

function bindInputs() {
    const ids = [
        'preview_locale','stamp_columns','preview_stamps_filled','preview_stamps_total',
        'preview_program','preview_member','preview_rewards',
        'apple_strip_overlay','google_strip_overlay'
    ];
    ids.forEach(id => {
        const el = $(id);
        if (!el) return;
        el.value = state[id] ?? '';
        el.addEventListener('input', () => { state[id] = el.type === 'number' ? Number(el.value) : el.value; render(); });
    });

    colorPairs.forEach(([textId, pickerId]) => {
        const text = $(textId);
        const picker = $(pickerId);
        if (!text || !picker) return;
        const val = normalizeHex(state[textId] || '#000000');
        text.value = val;
        picker.value = val;
        picker.addEventListener('input', () => { text.value = picker.value; state[textId] = picker.value; render(); });
        text.addEventListener('input', () => { const v = normalizeHex(text.value); picker.value = v; state[textId] = v; render(); });
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
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
}

function labels() {
    const loc = state.preview_locale === 'en' ? 'lang_en' : 'lang_ar';
    return state[loc];
}

function renderStamps(total, filled, doneColor, emptyStyle) {
    let html = '';
    for (let i = 0; i < total; i++) {
        const done = i < filled;
        html += `<div class="stamp ${done ? 'done' : 'empty'}" style="${done ? '--done:'+doneColor : emptyStyle}">${done ? '✓' : ''}</div>`;
    }
    return html;
}

function isVisible(id, platformKey) {
    return (state[platformKey] || []).includes(id);
}

function fieldHtml(id, L, filled, remaining) {
    const map = {
        stamps: `<div><small>${escapeHtml(L.stamps)}</small>${filled} / ${state.preview_stamps_total}</div>`,
        rewards: `<div><small>${escapeHtml(L.rewards)}</small>${state.preview_rewards || 0}</div>`,
        remaining: `<div><small>${escapeHtml(L.remaining)}</small>${remaining}</div>`,
        member: `<div><small>${escapeHtml(L.member)}</small>${escapeHtml(state.preview_member)}</div>`,
        status: `<div style="grid-column:1/-1"><small>${escapeHtml(L.status)}</small>${filled >= state.preview_stamps_total ? escapeHtml(L.status_completed) : escapeHtml(L.status_in_progress)}</div>`,
    };
    return map[id] || '';
}

function appleFieldsHtml(L, filled, remaining) {
    const primary = fieldHtml('stamps', L, filled, remaining);
    const secondary = (state.apple_secondary_order || []).filter(id => isVisible(id, 'apple_visible_fields')).map(id => fieldHtml(id, L, filled, remaining)).join('');
    const auxiliary = (state.apple_auxiliary_order || []).filter(id => isVisible(id, 'apple_visible_fields')).map(id => fieldHtml(id, L, filled, remaining)).join('');
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

function stripStyle(platform) {
    if (realStripUrls[platform]) {
        return `background-image:url('${realStripUrls[platform]}');background-size:cover;background-position:center;--overlay:0`;
    }
    if (state.strip_bg_url) {
        return `background-image:url('${state.strip_bg_url}');background-size:cover;background-position:center;--overlay:${platform === 'apple' ? state.apple_strip_overlay : (state.google_strip_overlay || 0.35)}`;
    }
    if (platform === 'apple') return `background:${state.apple_background};--overlay:${state.apple_strip_overlay}`;
    return `background:${state.google_strip_bg};--overlay:${state.google_strip_overlay || 0.35}`;
}

function render() {
    const L = labels();
    const filled = Math.min(state.preview_stamps_filled, state.preview_stamps_total);
    const remaining = Math.max(0, state.preview_stamps_total - filled);
    const logoHtml = state.logo_url ? `<img class="card-logo" src="${escapeHtml(state.logo_url)}" alt="">` : '';

    $('applePreview').style.background = state.apple_background;
    $('applePreview').style.color = state.apple_foreground;
    $('applePreview').innerHTML = `
        <div class="strip ${realStripUrls.apple ? 'real-strip' : ''}" style="${stripStyle('apple')}">
            ${realStripUrls.apple ? '' : `<div class="stamps">${renderStamps(Math.min(state.preview_stamps_total, 10), Math.min(filled, 10), state.apple_stamp_completed, '')}</div>`}
        </div>
        <div class="card-body">
            ${logoHtml}
            <div class="title">${escapeHtml(state.preview_program)}</div>
            <div class="fields">${appleFieldsHtml(L, filled, remaining)}</div>
            <div class="qr"></div>
        </div>`;

    $('googlePreview').innerHTML = `
        <div class="strip ${realStripUrls.google ? 'real-strip' : ''}" style="${stripStyle('google')}">
            ${realStripUrls.google ? '' : `<div class="stamps">${renderStamps(Math.min(state.preview_stamps_total, 10), Math.min(filled, 10), state.google_stamp_filled, 'background:'+state.google_stamp_empty)}</div>`}
        </div>
        <div class="card-body" style="--g-bg:${state.google_background};color:#fff">
            ${logoHtml}
            <div class="title">${escapeHtml(state.preview_program)}</div>
            <div class="fields">${googleFieldsHtml(L, filled, remaining)}</div>
            <div class="qr"></div>
            <div style="font-size:.75rem;margin-top:8px;opacity:.85">${escapeHtml(L.barcode_footer)}</div>
        </div>`;

    $('applePreviewWrap').style.display = (platform === 'google') ? 'none' : 'block';
    $('googlePreviewWrap').style.display = (platform === 'apple') ? 'none' : 'block';
    $('appleSection').style.display = (platform === 'google') ? 'none' : 'block';
    $('googleSection').style.display = (platform === 'apple') ? 'none' : 'block';
    $('appleFieldsEditor').style.display = (platform === 'google') ? 'none' : 'block';
    $('googleFieldsEditor').style.display = (platform === 'apple') ? 'none' : 'block';
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
            state[orderKey] = Array.from(list.querySelectorAll('.sort-item')).map(el => el.dataset.id);
            render();
        });
    });

    list.addEventListener('dragover', e => {
        e.preventDefault();
        const dragging = list.querySelector('.dragging');
        const after = Array.from(list.querySelectorAll('.sort-item:not(.dragging)')).find(el => e.clientY <= el.getBoundingClientRect().top + el.offsetHeight / 2);
        if (dragging) list.insertBefore(dragging, after || null);
    });
}

function buildFieldEditors() {
    buildSortList('appleSecondaryList', 'apple_secondary_order', 'apple_visible_fields', appleFieldSlots.secondary);
    buildSortList('appleAuxiliaryList', 'apple_auxiliary_order', 'apple_visible_fields', appleFieldSlots.auxiliary);
    buildSortList('googleModulesList', 'google_modules_order', 'google_visible_fields', googleFieldSlots.filter(s => s.id !== 'rewards'));
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
    const res = await fetch(routes.upload, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: fd,
    });
    const data = await res.json();
    if (kind === 'logo') {
        state.logo_path = data.path;
        state.logo_url = data.url;
        const img = $('logoPreview');
        img.src = data.url; img.style.display = 'block';
    } else {
        state.strip_bg_path = data.path;
        state.strip_bg_url = data.url;
        const img = $('stripBgPreview');
        img.src = data.url; img.style.display = 'block';
        realStripUrls = { apple: null, google: null };
    }
    render();
}

function setStatus(msg) { $('statusMsg').textContent = msg || ''; }

async function downloadBlobResponse(res, filename) {
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
}

document.querySelectorAll('#platformTabs .tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#platformTabs .tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        platform = btn.dataset.platform;
        state.platform = platform;
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
    $('fileTabs').innerHTML = names.map(n => `<button type="button" class="file-tab ${n===activeFile?'active':''}" data-file="${n}">${n}</button>`).join('');
    $('exportContent').textContent = exportFiles[activeFile] || '';
    $('exportModal').classList.add('open');
    document.querySelectorAll('.file-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.file-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeFile = tab.dataset.file;
            $('exportContent').textContent = exportFiles[activeFile] || '';
        });
    });
    setStatus('تم تجهيز ملفات التصدير.');
});

$('previewBtn').addEventListener('click', async () => {
    setStatus('جاري توليد شريط الأختام...');
    const res = await postJson(routes.preview);
    const data = await res.json();
    realStripUrls.apple = data.apple_strip_url || null;
    realStripUrls.google = data.google_strip_url || null;
    render();
    const parts = [];
    if (data.apple_strip_url) parts.push('Apple ✓');
    if (data.google_strip_url) parts.push('Google ✓');
    setStatus(parts.length ? 'تم توليد الشريط: ' + parts.join(' · ') : 'تعذر توليد الشريط (تحقق من GD).');
});

$('zipBtn').addEventListener('click', async () => {
    setStatus('جاري تجهيز ZIP...');
    const res = await postBlob(routes.downloadZip);
    await downloadBlobResponse(res, 'wallet-design.zip');
    setStatus('تم تحميل wallet-design.zip');
});

$('testPassBtn').addEventListener('click', async () => {
    setStatus('جاري توليد Apple Pass...');
    const res = await postBlob(routes.testPass);
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        setStatus(err.message || 'تعذر توليد البطاقة.');
        return;
    }
    await downloadBlobResponse(res, 'studio-preview.pkpass');
    setStatus('تم تحميل studio-preview.pkpass');
});

$('logoUpload')?.addEventListener('change', e => uploadImage(e.target, 'logo'));
$('stripBgUpload')?.addEventListener('change', e => uploadImage(e.target, 'strip'));

$('copyBtn').addEventListener('click', () => navigator.clipboard.writeText($('exportContent').textContent || ''));
$('closeModal').addEventListener('click', () => $('exportModal').classList.remove('open'));

bindInputs();
buildLangFields();
buildFieldEditors();
render();
</script>
</body>
</html>
