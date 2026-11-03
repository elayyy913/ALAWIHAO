<?php
session_start();
include 'db_connect.php';

$slug = $_GET['topic'] ?? '';

$articles = require __DIR__ . '/health_articles_data.php';

$article = $articles[$slug] ?? null;
if (!$article) { header("Location: user_dashboard.php"); exit(); }

// ── Computed stats ────────────────────────────────────────────────────────────
$word_count = 0;
foreach ($article['sections'] as $s) {
    if (!empty($s['items'])) foreach ($s['items'] as $i) {
        if (is_array($i)) $word_count += str_word_count(($i['label'] ?? '') . ' ' . ($i['why'] ?? '') . ' ' . ($i['note'] ?? '') . ' ' . ($i['step'] ?? ''));
        else $word_count += str_word_count($i);
    }
    if (!empty($s['content'])) $word_count += str_word_count($s['content']);
    if (!empty($s['myth']))    $word_count += str_word_count($s['myth']) + str_word_count($s['truth']);
    if (!empty($s['rows']))    foreach ($s['rows'] as $r) foreach ($r as $c) $word_count += str_word_count($c);
}
$read_min     = max(2, ceil($word_count / 180));
$section_count = count($article['sections']);
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> | Alawihao Health Center</title>
    <?php $notif_path = ''; include 'notif_assets.php'; ?>
    <style>
        :root {
            --green:   #3d6426;
            --accent:  #6b9148;
            --light:   #a2ce6f;
            --bg:      #F9F9F4;
            --white:   #ffffff;
            --text:    #2D2D2D;
            --muted:   #666666;
            --card-bg: #ffffff;
            --border:  #E1E1D7;
            --sidebar-width: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.65;
            font-size: 14px;
        }

        /* PROGRESS BAR */
        .progress-bar { position: fixed; top: 0; left: 0; height: 3px; background: #2D5016; width: 0%; z-index: 9999; transition: width 0.1s; }

        /* SIDEBAR */
        .sidebar-container { width: var(--sidebar-width) !important; min-width: var(--sidebar-width) !important; height: 100vh; position: fixed; top: 0; left: 0; z-index: 300; overflow-y: auto; transition: transform 0.3s ease; }
        body.sidebar-closed .sidebar-container { transform: translateX(-100%); }

        /* TOPBAR */
        .topbar { background: #ffffff; border-bottom: 2px solid #2D5016; padding: 8px 20px; display: flex; align-items: center; gap: 12px; position: sticky; top: 0; z-index: 200; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); transition: all 0.3s ease; }
        body.sidebar-closed .topbar { margin-left: 0; width: 100%; }
        .topbar .hamburger-btn { position: static !important; top: auto !important; left: auto !important; background: #5A6B47; border: none; cursor: pointer; color: #fff; font-size: 0.9rem; width: 34px; height: 34px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .topbar .hamburger-btn:hover { background: #5A7C3A; }
        .topbar .article-back-link { position: relative; z-index: 1; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; color: #5A6B47; background: #ffffff; border: 1px solid #E1E1D7; border-radius: 4px; text-decoration: none; }
        .topbar .article-back-link:hover { background: #F1F5ED; }
        .topbar .logo-img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #2D5016; flex-shrink: 0; }
        .topbar .page-label { flex: 1; min-width: 0; font-size: 0.88rem; font-weight: 700; color: #2D5016; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: 0.2px; }
        .topbar-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .article-search { position: relative; }
        .article-search-toggle {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            background: transparent; color: #2D5016;
            border: 1px solid transparent; border-radius: 4px;
            cursor: pointer; font-size: 1rem;
        }
        .article-search-toggle:hover { background: #F1F5ED; border-color: #E1E1D7; }
        .article-search-panel {
            position: absolute; top: calc(100% + 10px); right: 0;
            width: min(360px, calc(100vw - 24px)); max-height: 70vh;
            overflow: auto; padding: 14px;
            background: var(--dm-surface, #ffffff); color: var(--dm-text, #2D2D2D);
            border: 1px solid var(--dm-border, #E1E1D7); border-radius: 4px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.16); z-index: 500;
        }
        .article-search-panel[hidden] { display: none; }
        .article-search-panel label { display: block; margin-bottom: 7px; font-size: 0.75rem; font-weight: 700; color: var(--dm-muted, #6b7280); }
        .article-search-input {
            width: 100%; padding: 9px 11px;
            border: 1px solid var(--dm-input-border, #E1E1D7); border-radius: 3px;
            background: var(--dm-input-bg, #ffffff); color: var(--dm-text, #2D2D2D);
            font: inherit; font-size: 0.86rem;
        }
        .article-search-results { display: grid; gap: 2px; margin-top: 8px; }
        .article-search-result { display: block; padding: 8px; border-radius: 3px; color: inherit; font-size: 0.82rem; text-decoration: none; }
        .article-search-result:hover { background: var(--dm-hover, #F1F5ED); color: inherit; }
        .article-search-result[hidden], .article-search-empty[hidden] { display: none; }
        .article-search-empty { margin: 8px; color: var(--dm-muted, #6b7280); font-size: 0.82rem; }

        /* MAIN */
        #main { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); transition: all 0.3s ease; padding: 24px 20px 80px; background: var(--bg); }
        body.sidebar-closed #main { margin-left: 0; width: 100%; }
        .article-wrap { width: 100%; max-width: none; margin: 0; }

        .article-cover {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 0 24px;
        }
        .article-cover img {
            display: block;
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 560px;
            object-fit: contain;
        }
        .article-cover figcaption {
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.72rem;
            text-align: center;
        }

        /* ── PAMPHLET HEADER ── */
        .article-header {
            width: 100%;
            background: #5A6B47;
            color: #FFFFFF;
            border-radius: 0;
            padding: 28px 36px;
            margin: -24px 0 0;
            border-bottom: 4px solid #8DAE74;
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .article-header .header-body { flex: 1; width: 100%; }
        .article-header h1 {
            font-size: 1.45rem;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 6px;
            line-height: 1.3;
            letter-spacing: -0.2px;
        }
        .article-header p {
            font-size: 0.88rem;
            opacity: 0.88;
            line-height: 1.6;
            margin-bottom: 10px;
            max-width: none;
        }
        .source-tag {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 3px;
            padding: 3px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ── META STRIP ── */
        .article-meta {
            width: 100%;
            display: flex; align-items: center; gap: 0;
            background: #F4F4ED;
            padding: 8px 36px;
            margin-bottom: 20px;
            font-size: 0.76rem;
            color: #5A6B47;
            flex-wrap: wrap; gap: 4px;
        }
        .article-meta span {
            display: flex; align-items: center; gap: 5px;
            padding: 2px 12px;
            border-right: 1px solid #E1E1D7;
        }
        .article-meta span:last-child { border-right: none; }
        .article-meta i { opacity: 0.75; }

        /* ── TOC — pamphlet index style ── */
        .toc-box {
            background: white;
            border: 1px solid var(--border);
            border-top: 3px solid #2D5016;
            border-radius: 0;
            padding: 16px 20px;
            margin-bottom: 18px;
        }
        .toc-box h3 {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2D5016;
            font-weight: 700;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e0e0e0;
        }
        .toc-box ol { padding-left: 0; list-style: none; counter-reset: toc-counter; }
        .toc-box ol li {
            counter-increment: toc-counter;
            font-size: 0.83rem;
            color: #2D2D2D;
            padding: 5px 0 5px 28px;
            border-bottom: 1px dotted #ddd;
            position: relative;
            cursor: pointer;
            transition: color 0.15s;
        }
        .toc-box ol li:last-child { border-bottom: none; }
        .toc-box ol li::before {
            content: counter(toc-counter) ".";
            position: absolute; left: 0;
            color: #2D5016;
            font-weight: 700;
            font-size: 0.78rem;
            width: 22px;
        }
        .toc-box ol li:hover { color: #2D5016; text-decoration: underline; }

        /* ── KEY TAKEAWAY ── */
        .key-takeaway {
            background: #F1F5ED;
            border: 1px solid #C8E0A8;
            border-left: 4px solid #2D5016;
            border-radius: 0;
            padding: 14px 18px;
            margin-bottom: 18px;
            display: flex; gap: 12px; align-items: flex-start;
        }
        .key-takeaway-icon { font-size: 1.2rem; flex-shrink: 0; color: #2D5016; margin-top: 1px; }
        .key-takeaway h3 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.8px; color: #2D5016; font-weight: 700; margin-bottom: 4px; }
        .key-takeaway p { font-size: 0.86rem; color: #2D3D20; line-height: 1.65; }

        /* ── SECTION CARD — pamphlet section ── */
        .section-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 0;
            padding: 0;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .section-card h2 {
            font-size: 0.82rem;
            font-weight: 700;
            color: white;
            background: #2D5016;
            margin: 0;
            padding: 9px 16px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            display: flex; align-items: center; gap: 8px;
            border-bottom: 2px solid #8FBF5A;
        }
        .section-card h2::before { display: none; }
        .section-card-body { padding: 16px 18px; }
        .section-reference-image {
            display: block;
            width: min(100%, 500px);
            height: auto;
            margin: 0 auto 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        /* ── DETAILED LIST ── */
        .detailed-item {
            margin-bottom: 0;
            border: none;
            border-bottom: 1px solid #E0E8DC;
            border-radius: 0;
            overflow: hidden;
        }
        .detailed-item:last-child { border-bottom: none; }
        .detailed-label {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 11px 4px;
            background: white;
            cursor: pointer;
            transition: background 0.15s;
        }
        .detailed-label:hover { background: #F1F5ED; }
        .detailed-bullet {
            width: 22px; height: 22px;
            background: #2D5016;
            color: white;
            border-radius: 2px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 0.72rem;
            margin-top: 1px;
        }
        .detailed-text { flex: 1; }
        .detailed-text strong { font-size: 0.88rem; color: #2D2D2D; display: block; font-weight: 600; }
        .detailed-toggle { color: #999; font-size: 0.7rem; transition: transform 0.2s; flex-shrink: 0; margin-top: 4px; }
        .detailed-why {
            padding: 10px 14px 14px 38px;
            background: #F1F5ED;
            border-top: 1px solid #D8E8C8;
            display: none;
        }
        .detailed-why .why-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #2D5016;
            margin-bottom: 5px;
            display: flex; align-items: center; gap: 4px;
            padding-bottom: 4px;
            border-bottom: 1px solid #C8E0A8;
        }
        .detailed-why p { font-size: 0.84rem; color: #2D3D2D; line-height: 1.7; }
        .detailed-item.open .detailed-why { display: block; }
        .detailed-item.open .detailed-toggle { transform: rotate(180deg); }

        /* ── PLAIN LIST ── */
        .plain-list { list-style: none; padding: 0; margin: 0; }
        .plain-list li {
            padding: 8px 10px 8px 30px;
            border-bottom: 1px solid #EDF2ED;
            font-size: 0.87rem;
            color: #2D2D2D;
            position: relative;
            line-height: 1.6;
        }
        .plain-list li:last-child { border-bottom: none; }
        .plain-list li::before {
            content: '▸';
            position: absolute; left: 8px; top: 8px;
            color: #2D5016;
            font-size: 0.75rem;
        }

        /* ── TABLE ── */
        .article-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
        .article-table th {
            background: #2D5016;
            color: white;
            padding: 9px 12px;
            text-align: left;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-right: 1px solid rgba(255,255,255,0.15);
        }
        .article-table th:last-child { border-right: none; }
        .article-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #D8E8C8;
            border-right: 1px solid #e8eee8;
            vertical-align: top;
            color: #2D2D2D;
        }
        .article-table td:last-child { border-right: none; }
        .article-table tr:nth-child(even) td { background: #F1F5ED; }
        .article-table tr:last-child td { border-bottom: none; }
        .article-table tr:hover td { background: #F1F5ED; }

        /* HIGHLIGHT BOX */
        .highlight-box {
            background: #F1F5ED;
            border: 1px solid #C8E0A8;
            border-left: 4px solid #2D5016;
            border-radius: 0;
            padding: 14px 18px;
            font-size: 0.88rem;
            line-height: 1.7;
            color: #2D3D20;
        }
        .highlight-box::before { content: none; }

        /* MYTH / TRUTH*/
        .myth-box {
            border: 1px solid var(--border);
            margin-bottom: 12px;
            border-radius: 0;
            overflow: hidden;
        }
        .myth-label {
            background: #fff8e6;
            border-left: none;
            border-bottom: 1px solid #f0d080;
            padding: 10px 14px;
            border-radius: 0;
            font-style: normal;
            color: #5a3a00;
            font-size: 0.86rem;
            line-height: 1.6;
        }
        .myth-label .badge {
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            background: #d97706; color: white;
            border-radius: 2px; padding: 2px 7px; margin-right: 6px;
            letter-spacing: 0.3px;
        }
        .truth-label {
            background: #F1F5ED;
            border-left: none;
            border-top: none;
            border-radius: 0;
            padding: 10px 14px;
            color: #2A3520;
            font-size: 0.86rem;
            line-height: 1.7;
        }
        .truth-label .badge {
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            background: #3d8c28; color: white;
            border-radius: 2px; padding: 2px 7px; margin-right: 6px;
        }

        /* ── STEPS ── */
        .steps-list { list-style: none; padding: 0; counter-reset: step-counter; }
        .steps-list li {
            counter-increment: step-counter;
            padding: 10px 12px 10px 46px;
            border-bottom: 1px solid #E0E8DC;
            background: white;
            position: relative;
            font-size: 0.87rem;
        }
        .steps-list li:last-child { border-bottom: none; }
        .steps-list li::before {
            content: counter(step-counter);
            position: absolute; left: 12px; top: 10px;
            width: 22px; height: 22px;
            background: #2D5016; color: white;
            border-radius: 2px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem;
        }
        .steps-list li strong { display: block; color: #2D2D2D; margin-bottom: 2px; font-weight: 600; }
        .steps-list li small { color: #5a5a5a; font-size: 0.8rem; }

        /* ── CTA CARD ── */
        .cta-card {
            background: #2D5016;
            border-radius: 0;
            padding: 18px 22px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: white;
            border-left: 5px solid #8FBF5A;
        }
        .cta-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 3px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .cta-text { flex: 1; }
        .cta-text h3 { font-size: 0.88rem; font-weight: 700; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.3px; }
        .cta-text p  { font-size: 0.8rem; opacity: 0.85; line-height: 1.55; margin: 0; }
        .cta-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: white; color: #2D5016;
            font-weight: 700; font-size: 0.8rem;
            padding: 8px 16px; border-radius: 3px;
            text-decoration: none; white-space: nowrap;
            text-transform: uppercase; letter-spacing: 0.3px;
            transition: opacity 0.15s; flex-shrink: 0;
            border: none;
        }
        .cta-btn:hover { opacity: 0.88; }
        @media (max-width: 600px) {
            .article-cover img { max-height: 46vh; }
            .cta-card { flex-direction: column; }
            .cta-btn { width: 100%; justify-content: center; }
        }

        /* ── RELATED ARTICLES ── */
        .related-section { margin-top: 20px; }
        .related-section h3 {
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #2D5016;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #2D5016;
            display: flex; align-items: center; gap: 7px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 10px;
        }
        .related-card {
            background: white;
            border: 1px solid var(--border);
            border-top: 3px solid #2D5016;
            border-radius: 0;
            padding: 12px 14px;
            text-decoration: none;
            color: var(--text);
            display: flex; align-items: flex-start; gap: 10px;
            transition: box-shadow 0.15s;
        }
        .related-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.10); }
        .related-card-icon {
            width: 32px; height: 32px;
            background: #F1F5ED;
            color: #2D5016;
            border: 1px solid #C8E0A8;
            border-radius: 3px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.88rem; flex-shrink: 0;
        }
        .related-card-title { font-size: 0.82rem; font-weight: 600; color: #2D5016; line-height: 1.35; }
        .related-card-sub   { font-size: 0.72rem; color: var(--muted); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.3px; }

        /*FOOTER*/
        .article-footer {
            background: #ffffff;
            border-top: 3px solid #2D5016;
            padding: 36px 20px 20px;
            margin-top: 40px;
            color: #2D2D2D;
        }
        .footer-inner { width: 100%; margin: 0; }

        .footer-top {
            display: grid;
            grid-template-columns: 1.8fr 1fr 1fr;
            gap: 36px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e8dc;
            margin-bottom: 16px;
        }
        @media (max-width: 700px) {
            .footer-top { grid-template-columns: 1fr; gap: 20px; }
            .article-meta { padding-right: 20px; padding-left: 20px; }
        }
        /*identity */
        .footer-brand { display: flex; align-items: flex-start; gap: 12px; }
        .footer-brand img {
            width: 46px; height: 46px; border-radius: 50%;
            object-fit: cover;
            border: 2px solid #2D5016;
            flex-shrink: 0;
        }
        .footer-brand-text h4 {
            font-size: 0.88rem; font-weight: 700; color: #2D5016;
            line-height: 1.3; margin-bottom: 3px;
        }
        .footer-brand-text span {
            font-size: 0.72rem; color: #666;
            display: block; line-height: 1.4;
        }
        .footer-tagline {
            font-size: 0.75rem;
            color: #666;
            margin-top: 10px;
            line-height: 1.6;
            font-style: italic;
        }

        /*info */
        .footer-col h5 {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #2D5016;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #D8E8C8;
        }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li {
            font-size: 0.77rem;
            color: #444;
            padding: 4px 0;
            display: flex;
            align-items: flex-start;
            gap: 7px;
            line-height: 1.4;
        }
        .footer-col ul li i {
            color: #2D5016;
            font-size: 0.72rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .footer-col ul li strong { color: #2D5016; }
        .footer-col a { color: #5A7C3A; text-decoration: none; }
        .footer-col a:hover { text-decoration: underline; }

        /* Bottom bar */
        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .footer-copy {
            font-size: 0.7rem;
            color: #888;
        }
        .footer-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #eef6e4;
            border: 1px solid #C8E0A8;
            border-radius: 4px;
            padding: 3px 10px;
            font-size: 0.68rem;
            color: #2D5016;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /*READING PROGRESS */
        .reading-progress-bar {
            position: fixed;
            bottom: 0; left: 0;
            width: 0%;
            height: 3px;
            background: #8FBF5A;
            z-index: 9998;
            transition: width 0.1s;
        }            border-radius: 0 2px 0 0;
        

        /*CTA CARD*/
        .cta-card {
            background: linear-gradient(135deg, #2D5016, #5A7C3A);
            border-radius: 16px;
            padding: 24px 28px;
            margin-top: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            color: white;
            box-shadow: 0 4px 16px rgba(45,80,22,0.25);
        }
        .cta-icon {
            width: 54px; height: 54px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .cta-text { flex: 1; }
        .cta-text h3 { font-size: 1rem; font-weight: 700; margin-bottom: 4px; }
        .cta-text p  { font-size: 0.85rem; opacity: 0.88; line-height: 1.6; margin: 0; }
        .cta-btn {
            display: inline-flex; align-items: center; gap: 7px;
            background: white; color: #2D5016;
            font-weight: 700; font-size: 0.82rem;
            padding: 9px 18px; border-radius: 999px;
            text-decoration: none; white-space: nowrap;
            transition: opacity 0.2s; flex-shrink: 0;
        }
        .cta-btn:hover { opacity: 0.88; }
        @media (max-width: 600px) {
            .cta-card { flex-direction: column; text-align: center; }
            .cta-btn { width: 100%; justify-content: center; }
        }

        /*RELATED ARTICLES*/
        .related-section { margin-top: 28px; }
        .related-section h3 {
            font-size: 0.82rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #5a7c3a; margin-bottom: 14px;
            display: flex; align-items: center; gap: 7px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            align-items: stretch;
        }
        .related-card {
            width: 100%;
            min-height: 96px;
            background: white;
            border: 1px solid #d8e8c8;
            border-radius: 4px;
            padding: 16px;
            text-decoration: none;
            color: var(--text);
            display: flex; align-items: center; gap: 12px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .related-card:hover { box-shadow: 0 4px 14px rgba(45,80,22,0.12); transform: translateY(-2px); }
        .related-card-icon {
            width: 56px; height: 56px; border-radius: 4px;
            background: #F1F5ED; color: #2D5016;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0; overflow: hidden;
        }
        .related-card > div:last-child { min-width: 0; }
        .related-card-title { font-size: 0.85rem; font-weight: 600; color: #2D5016; line-height: 1.4; }
        .related-card-sub   { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }

        @media (max-width: 900px) {
            .related-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 600px) {
            .related-grid { grid-template-columns: 1fr; }
        }

        /* PRINT */
        @media print {
            .sidebar-container, .topbar, .toc-box, .progress-bar { display: none !important; }
            #main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .article-wrap { max-width: 100%; }
            .detailed-why { display: block !important; }
        }

        /* Match the super-admin dashboard's sage palette across the article. */
        body, #main { background: #F9F9F4; color: #2D2D2D; }
        .progress-bar, .reading-progress-bar { background: #5A6B47; }
        .topbar { border-bottom-color: #5A6B47; }
        .topbar .hamburger-btn { background: #5A6B47; }
        .topbar .hamburger-btn:hover { background: #8DAE74; }
        .sidebar-container > #hamburgerBtn { display: none !important; }
        .topbar .article-back-link { color: #5A6B47; }
        .topbar .article-back-link:hover { background: #F0F5EC; color: #5A6B47; }
        .topbar .logo-img { border-color: #5A6B47; }
        .topbar .page-label, .article-search-toggle { color: #5A6B47; }
        .article-search-toggle:hover { background: #F0F5EC; border-color: #E1E1D7; }
        .article-header { background: #5A6B47; color: #FFFFFF; border-bottom-color: #8DAE74; }
        .article-header h1 { color: #FFFFFF; }
        .article-meta { background: #F4F4ED; color: #5A6B47; }
        .toc-box { border-top-color: #5A6B47; }
        .toc-box h3, .toc-box ol li::before, .toc-box ol li:hover { color: #5A6B47; }
        .key-takeaway { background: #F4F7F0; border-color: #E1E1D7; border-left-color: #5A6B47; }
        .key-takeaway-icon, .key-takeaway h3 { color: #5A6B47; }
        .key-takeaway p { color: #2D2D2D; }
        .section-card h2, .article-table th, .steps-list li::before { background: #5A6B47; }
        .section-card h2 { border-bottom-color: #8DAE74; }
        .detailed-bullet { background: #5A6B47; }
        .detailed-label:hover, .detailed-why, .article-table tr:nth-child(even) td,
        .article-table tr:hover td, .highlight-box, .truth-label { background: #F4F7F0; }
        .detailed-why, .article-table td, .highlight-box, .myth-box,
        .detailed-item, .plain-list li, .steps-list li { border-color: #E1E1D7; }
        .detailed-why .why-label, .plain-list li::before { color: #5A6B47; }
        .detailed-why .why-label, .highlight-box { border-left-color: #5A6B47; }
        .myth-label { background: #F4F7F0; color: #2D2D2D; border-color: #E1E1D7; }
        .truth-label { color: #2D2D2D; }
        .myth-label .badge, .truth-label .badge { background: #5A6B47; color: #FFFFFF; }
        .steps-list li::before { background: #5A6B47; }
        .cta-card { background: #5A6B47; border-left-color: #8DAE74; }
        .cta-btn { color: #5A6B47; }
        .related-section h3 { color: #5A6B47; border-bottom-color: #5A6B47; }
        .related-card, .related-card-icon { border-color: #E1E1D7; }
        .related-card { border-top-color: #5A6B47; }
        .related-card-icon { background: #F4F7F0; color: #5A6B47; }
        .related-card-title { color: #5A6B47; }
        .article-footer { border-top-color: #5A6B47; }
        .footer-brand img { border-color: #5A6B47; }
        .footer-brand-text h4, .footer-col h5, .footer-col ul li i,
        .footer-col ul li strong, .footer-badge { color: #5A6B47; }
        .footer-col a { color: #5A6B47; }
        .footer-col h5 { border-bottom-color: #E1E1D7; }
        .footer-badge { background: #F0F5EC; border-color: #E1E1D7; }

        [data-theme="dark"] .article-header { background: #242826 !important; color: #E8EDE5 !important; border-color: var(--light) !important; }
        [data-theme="dark"] .article-header p { color: #E8EDE5 !important; }
        [data-theme="dark"] .article-header h1 { color: var(--light) !important; }
        [data-theme="dark"] .article-meta,
        [data-theme="dark"] .article-meta span { background: #242826 !important; color: var(--light) !important; }
        [data-theme="dark"] .key-takeaway h3,
        [data-theme="dark"] .related-section h3,
        [data-theme="dark"] .footer-brand-text h4,
        [data-theme="dark"] .footer-col h5,
        [data-theme="dark"] .footer-col ul li i,
        [data-theme="dark"] .footer-col ul li strong,
        [data-theme="dark"] .footer-badge { color: #8DAE74 !important; }
        [data-theme="dark"] .section-card h2,
        [data-theme="dark"] .article-table th,
        [data-theme="dark"] .detailed-bullet,
        [data-theme="dark"] .steps-list li::before,
        [data-theme="dark"] .cta-card { background: #5A6B47 !important; }
        [data-theme="dark"] .section-card h2,
        [data-theme="dark"] .article-table th { color: #FFFFFF !important; border-color: #8DAE74 !important; }
        [data-theme="dark"] .detailed-label,
        [data-theme="dark"] .steps-list li,
        [data-theme="dark"] .myth-label,
        [data-theme="dark"] .truth-label,
        [data-theme="dark"] .related-card {
            background: #242826 !important;
            color: #E8EDE5 !important;
            border-color: #3A403A !important;
        }
        [data-theme="dark"] .detailed-text strong,
        [data-theme="dark"] .steps-list li strong,
        [data-theme="dark"] .steps-list li small,
        [data-theme="dark"] .detailed-why p,
        [data-theme="dark"] .plain-list li,
        [data-theme="dark"] .article-table td,
        [data-theme="dark"] .related-card-title,
        [data-theme="dark"] .related-card-sub,
        [data-theme="dark"] .key-takeaway p,
        [data-theme="dark"] .highlight-box,
        [data-theme="dark"] .myth-label,
        [data-theme="dark"] .truth-label { color: #E8EDE5 !important; }
        [data-theme="dark"] .detailed-why,
        [data-theme="dark"] .key-takeaway,
        [data-theme="dark"] .highlight-box { background: #242826 !important; border-color: #3A403A !important; }
        [data-theme="dark"] .detailed-why .why-label,
        [data-theme="dark"] .toc-box h3,
        [data-theme="dark"] .toc-box li,
        [data-theme="dark"] .topbar .page-label,
        [data-theme="dark"] .article-search-toggle,
        [data-theme="dark"] .key-takeaway-icon,
        [data-theme="dark"] .related-card-icon { color: #8DAE74 !important; }
        [data-theme="dark"] .related-card-icon { background: #2C302C !important; }
        [data-theme="dark"] .myth-label .badge,
        [data-theme="dark"] .truth-label .badge { background: #5A6B47 !important; color: #FFFFFF !important; }
        [data-theme="dark"] .article-footer { background: #1E1E1E !important; border-top-color: #8DAE74 !important; color: #D0D0D0 !important; }
        [data-theme="dark"] .footer-top { border-bottom-color: #2E2E2E !important; }
        [data-theme="dark"] .footer-brand-text span,
        [data-theme="dark"] .footer-tagline,
        [data-theme="dark"] .footer-col ul li,
        [data-theme="dark"] .footer-copy { color: #B8C4B0 !important; }
        [data-theme="dark"] .footer-col a { color: #8DAE74 !important; }
        [data-theme="dark"] .footer-col h5 { border-bottom-color: #2E2E2E !important; }
        [data-theme="dark"] .footer-badge { background: #1A2E14 !important; border-color: #3A6B20 !important; }
        [data-theme="dark"] .topbar {
            background: var(--dm-topbar-bg) !important;
            border-bottom-color: var(--dm-sage) !important;
        }
        [data-theme="dark"] .topbar .article-back-link {
            background: var(--dm-surface) !important;
            border-color: var(--dm-border) !important;
            color: var(--dm-text) !important;
        }
        [data-theme="dark"] .topbar .article-back-link:hover {
            background: var(--dm-hover) !important;
            color: var(--dm-sage) !important;
        }
        [data-theme="dark"] .topbar .hamburger-btn {
            background: var(--light) !important;
            color: var(--green) !important;
        }
        [data-theme="dark"] .topbar .hamburger-btn:hover {
            background: var(--accent) !important;
        }
        [data-theme="dark"] .topbar .logo-img {
            border-color: var(--light) !important;
        }
        [data-theme="dark"] .article-meta img,
        [data-theme="dark"] .key-takeaway-icon img,
        [data-theme="dark"] .why-label img,
        [data-theme="dark"] .related-section h3 img,
        [data-theme="dark"] .footer-col h5 img,
        [data-theme="dark"] .footer-col ul li img { filter: brightness(0) invert(1); }

        body, #main { background: var(--bg); color: var(--text); }
        .progress-bar, .reading-progress-bar { background: var(--green); }
        .topbar { background: var(--white); border-bottom-color: var(--green); }
        .topbar .hamburger-btn { background: var(--green); color: #FFFFFF; }
        .topbar .hamburger-btn:hover { background: var(--accent); }
        .topbar .article-back-link {
            background: var(--white);
            border-color: var(--border);
            color: var(--green);
        }
        .topbar .article-back-link:hover { background: #F0F5EC; }
        .topbar .logo-img { border-color: var(--green); }
        .topbar .page-label, .article-search-toggle,
        .toc-box h3, .toc-box ol li::before, .toc-box ol li:hover,
        .key-takeaway-icon, .key-takeaway h3,
        .detailed-why .why-label, .plain-list li::before,
        .related-section h3, .related-card-title,
        .footer-brand-text h4, .footer-col h5,
        .footer-col ul li i, .footer-col ul li strong,
        .footer-col a, .footer-badge { color: var(--green); }
        .article-header, .cta-card {
            background: var(--green);
            border-color: var(--light);
        }
        .article-header { color: #FFFFFF; }
        .article-header h1, .article-header p, .cta-card { color: #FFFFFF; }
        .article-meta, .key-takeaway, .detailed-why, .highlight-box,
        .myth-label, .truth-label, .related-card-icon, .footer-badge {
            background-color: #F4F7F0;
            border-color: var(--border);
        }
        .key-takeaway { border-left-color: var(--green); }
        .section-card h2, .article-table th, .steps-list li::before,
        .detailed-bullet, .myth-label .badge, .truth-label .badge {
            background: var(--green);
        }
        .section-card h2, .article-table th,
        .myth-label .badge, .truth-label .badge { color: #FFFFFF; }
        .related-card { border-top-color: var(--green); }
        .article-footer {
            background: var(--white);
            border-top-color: var(--green);
            color: var(--text);
        }
        .footer-badge { color: var(--green); }

        [data-theme="dark"] .article-header,
        [data-theme="dark"] .article-footer {
            background: var(--dm-surface) !important;
            border-color: var(--dm-border) !important;
            color: var(--dm-text) !important;
        }
        [data-theme="dark"] .article-header { border-bottom-color: var(--light) !important; }
        [data-theme="dark"] .article-header h1,
        [data-theme="dark"] .article-header p,
        [data-theme="dark"] .article-footer,
        [data-theme="dark"] .key-takeaway p,
        [data-theme="dark"] .detailed-text,
        [data-theme="dark"] .article-table td,
        [data-theme="dark"] .plain-list li,
        [data-theme="dark"] .steps-list li,
        [data-theme="dark"] .myth-label,
        [data-theme="dark"] .truth-label,
        [data-theme="dark"] .related-card,
        [data-theme="dark"] .footer-brand-text span,
        [data-theme="dark"] .footer-tagline,
        [data-theme="dark"] .footer-col ul li,
        [data-theme="dark"] .footer-copy { color: var(--dm-text) !important; }
        [data-theme="dark"] .topbar .hamburger-btn,
        [data-theme="dark"] .section-card h2,
        [data-theme="dark"] .article-table th,
        [data-theme="dark"] .myth-label .badge,
        [data-theme="dark"] .truth-label .badge {
            background: var(--light) !important;
            color: var(--green) !important;
        }
        [data-theme="dark"] .cta-card {
            background: var(--dm-surface2) !important;
            border-color: var(--light) !important;
            color: var(--dm-text) !important;
        }
        [data-theme="dark"] .cta-btn {
            background: var(--light) !important;
            color: var(--green) !important;
        }
        [data-theme="dark"] .article-meta,
        [data-theme="dark"] .key-takeaway,
        [data-theme="dark"] .detailed-why,
        [data-theme="dark"] .highlight-box,
        [data-theme="dark"] .myth-label,
        [data-theme="dark"] .truth-label,
        [data-theme="dark"] .related-card,
        [data-theme="dark"] .related-card-icon,
        [data-theme="dark"] .footer-badge {
            background: var(--dm-surface2) !important;
            border-color: var(--dm-border) !important;
        }
        [data-theme="dark"] .article-meta,
        [data-theme="dark"] .article-meta span,
        [data-theme="dark"] .key-takeaway h3,
        [data-theme="dark"] .key-takeaway-icon,
        [data-theme="dark"] .detailed-why .why-label,
        [data-theme="dark"] .toc-box h3,
        [data-theme="dark"] .toc-box li,
        [data-theme="dark"] .related-section h3,
        [data-theme="dark"] .related-card-title,
        [data-theme="dark"] .footer-brand-text h4,
        [data-theme="dark"] .footer-col h5,
        [data-theme="dark"] .footer-col ul li i,
        [data-theme="dark"] .footer-col ul li strong,
        [data-theme="dark"] .footer-col a,
        [data-theme="dark"] .footer-badge { color: var(--light) !important; }
        [data-theme="dark"] .steps-list li::before,
        [data-theme="dark"] .detailed-bullet { background: var(--light) !important; }

    </style>
</head>
<body class="sidebar-closed">
<div id="progressBar" class="progress-bar"></div>
<div class="reading-progress-bar" id="readingBar"></div>

<div class="sidebar-container">
    <?php include 'user_sidebar.php'; ?>
</div>

<div class="topbar">
    <button class="hamburger-btn" onclick="toggleSidebar()" title="Toggle Sidebar">
        <i class="fa fa-bars"></i>
    </button>
    <a class="article-back-link" href="user_dashboard.php" title="Bumalik sa Health Hub" aria-label="Bumalik sa Health Hub">
        <i class="fa fa-arrow-left"></i>
    </a>
    <img src="images/logo.jpg?v=<?= time() ?>" alt="Brgy Logo" class="logo-img" onerror="this.style.display='none'">
    <span class="page-label"><?= htmlspecialchars($article['title']) ?></span>
    <div class="topbar-actions">
        <div class="article-search" id="articleSearch">
            <button type="button" class="article-search-toggle" id="articleSearchToggle" onclick="toggleArticleSearch()" title="Maghanap ng health topic" aria-label="Maghanap ng health topic" aria-controls="articleSearchPanel" aria-expanded="false">
                <i class="fa fa-search"></i>
            </button>
            <div class="article-search-panel" id="articleSearchPanel" hidden>
                <label for="articleSearchInput">Maghanap ng health topic</label>
                <input class="article-search-input" id="articleSearchInput" type="search" placeholder="Hal. prenatal, bakuna..." autocomplete="off">
                <div class="article-search-results" id="articleSearchResults">
                    <?php foreach ($articles as $topic_key => $topic_data): ?>
                    <a class="article-search-result" href="health_article.php?topic=<?= rawurlencode($topic_key) ?>" data-search-text="<?= htmlspecialchars(strtolower($topic_data['title'] . ' ' . $topic_data['intro']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($topic_data['title']) ?></a>
                    <?php endforeach; ?>
                    <p class="article-search-empty" id="articleSearchEmpty" hidden>Walang nahanap na health topic.</p>
                </div>
            </div>
        </div>
        <?php include 'notif_bell.php'; ?>
    </div>
</div>

<div id="main">
    <!-- HEADER -->
    <div class="article-header">
        <div class="header-body">
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            <p><?= htmlspecialchars($article['intro']) ?></p>
        </div>
    </div>

    <!-- META -->
    <div class="article-meta">
        <span><img src="images/clock.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;margin-right:3px;"> <?= $read_min ?> min basahin</span>
        <span><img src="images/greenbook.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;"> <?= $section_count ?> seksyon</span>
        <span><img src="images/location.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;"> Alawihao Health Center</span>
        <span><i class="fa fa-shield-halved" aria-hidden="true"></i> DOH-verified</span>
    </div>

    <?php if (!empty($article['image'])): ?>
    <figure class="article-cover">
        <img src="<?= htmlspecialchars($article['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>" loading="eager" decoding="async">
        <?php if (!empty($article['image_credit'])): ?>
        <figcaption><?= htmlspecialchars($article['image_credit'], ENT_QUOTES, 'UTF-8') ?> —
            <a href="https://commons.wikimedia.org/wiki/File:Breastfeeding_a_newborn_baby,_Moscow,_Russia.jpg" target="_blank" rel="noopener">tingnan ang source at lisensya</a>
        </figcaption>
        <?php endif; ?>
    </figure>
    <?php endif; ?>

<div class="article-wrap">

    <!-- TOC -->
    <?php if ($section_count > 2): ?>
    <div class="toc-box">
        <h3><i class="fa fa-book-open"></i> Mga Nilalaman</h3>
        <ol>
            <?php foreach ($article['sections'] as $i => $s): ?>
            <li onclick="document.getElementById('sec-<?= $i ?>').scrollIntoView({behavior:'smooth'})">
                <?= htmlspecialchars($s['heading']) ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endif; ?>

    <!-- KEY TAKEAWAY -->
    <?php
        $first = $article['sections'][0];
        $preview = '';
        if ($first['type'] === 'detailed' && !empty($first['items'][0]['label'])) $preview = $first['items'][0]['label'] . ' — ' . substr($first['items'][0]['why'] ?? '', 0, 160) . '...';
        elseif ($first['type'] === 'list'      && !empty($first['items']))           $preview = $first['items'][0];
        elseif ($first['type'] === 'highlight' && !empty($first['content']))         $preview = $first['content'];
        elseif ($first['type'] === 'text'      && !empty($first['content']))         $preview = substr($first['content'], 0, 200) . '...';
    ?>
    <?php if ($preview): ?>
    <div class="key-takeaway">
        <div class="key-takeaway-icon"><img src="images/light.png" alt="" style="width:22px;height:22px;object-fit:contain;"></div>
        <div>
            <h3>Pangunahing Aral</h3>
            <p><?= htmlspecialchars($preview) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTIONS -->
    <?php foreach ($article['sections'] as $idx => $sec): ?>
    <div class="section-card" id="sec-<?= $idx ?>">
        <h2><?= htmlspecialchars($sec['heading']) ?></h2>
        <div class="section-card-body">

        <?php if (!empty($sec['image'])): ?>
            <img class="section-reference-image" src="<?= htmlspecialchars($sec['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($sec['heading'], ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <?php if ($sec['type'] === 'detailed'): ?>
            <?php foreach ($sec['items'] as $k => $item): ?>
            <div class="detailed-item" id="item-<?= $idx ?>-<?= $k ?>">
                <div class="detailed-label" onclick="toggleItem('item-<?= $idx ?>-<?= $k ?>')">
                    <div class="detailed-bullet"><i class="fa fa-check"></i></div>
                    <div class="detailed-text">
                        <strong><?= htmlspecialchars($item['label']) ?></strong>
                    </div>
                    <i class="fa fa-chevron-down detailed-toggle"></i>
                </div>
                <?php if (!empty($item['why'])): ?>
                <div class="detailed-why">
                    <div class="why-label"><i class="fa fa-stethoscope" aria-hidden="true"></i> Bakit? — Batay sa Agham</div>
                    <p><?= htmlspecialchars($item['why']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        <?php elseif ($sec['type'] === 'list'): ?>
            <ul class="plain-list">
                <?php foreach ($sec['items'] as $item): ?>
                <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
            </ul>

        <?php elseif ($sec['type'] === 'highlight'): ?>
            <div class="highlight-box"><?= htmlspecialchars($sec['content']) ?></div>

        <?php elseif ($sec['type'] === 'text'): ?>
            <p style="line-height:1.85;color:var(--text);font-size:0.95rem;background:#F4F7F0;padding:16px 18px;border-radius:10px;border-left:3px solid #E1E1D7;"><?= htmlspecialchars($sec['content']) ?></p>

        <?php elseif ($sec['type'] === 'myth'): ?>
            <div class="myth-box">
                <div class="myth-label"><span class="badge"><i class="fa fa-triangle-exclamation"></i> Sabi-sabi</span><?= htmlspecialchars($sec['myth']) ?></div>
                <div class="truth-label"><span class="badge"><i class="fa fa-circle-check"></i> Ang Totoo</span><?= htmlspecialchars($sec['truth']) ?></div>
            </div>

        <?php elseif ($sec['type'] === 'table'): ?>
            <div style="overflow-x:auto;">
            <table class="article-table">
                <?php foreach ($sec['rows'] as $ri => $row): ?>
                <tr>
                    <?php foreach ($row as $ci => $cell): ?>
                    <?= $ri === 0 ? "<th>" . htmlspecialchars($cell) . "</th>" : "<td>" . htmlspecialchars($cell) . "</td>" ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>

        <?php elseif ($sec['type'] === 'steps'): ?>
            <ol class="steps-list">
                <?php foreach ($sec['items'] as $step): ?>
                <li>
                    <strong><?= htmlspecialchars($step['step']) ?></strong>
                    <?php if (!empty($step['note'])): ?><small><?= htmlspecialchars($step['note']) ?></small><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        </div><!-- .section-card-body -->
    </div>
    <?php endforeach; ?>

    <?php
    // ── Related articles map ────────────────────────────────────────────────
    $all_related = [
        'warning-signs'    => ['icon'=>'fa-triangle-exclamation', 'image'=>'images/pic9.jpg',    'label'=>'Mga Babala Habang Buntis'],
        'prenatal-checkup' => ['icon'=>'fa-stethoscope',          'image'=>'images/pic10.jpg',   'label'=>'Pre-natal Check-up'],
        'pregnancy-dos'    => ['icon'=>'fa-thumbs-up',            'image'=>'images/pic11.jpg',   'label'=>"Pregnancy Do's"],
        'pregnancy-donts'  => ['icon'=>'fa-ban',                  'image'=>'images/pic8.jpg',    'label'=>"Pregnancy Don'ts"],
        'pamahiin'         => ['icon'=>'fa-book-open',            'image'=>'images/pic12.webp',  'label'=>'Sabi-sabi vs. Totoo'],
        'baby-growth'      => ['icon'=>'fa-baby',                 'image'=>'images/pic13.jpg',   'label'=>'Paglaki ni Baby'],
        'newborn-care'     => ['icon'=>'fa-hands-holding-child',  'image'=>'images/pic6.jpg',    'label'=>'Newborn Care'],
        'breastfeeding'    => ['icon'=>'fa-heart-pulse',          'image'=>'images/breastfeeding_cover.jpg', 'label'=>'Eksklusibong Pagpapasuso'],
        'baby-milestones'  => ['icon'=>'fa-chart-line',           'image'=>'images/pic7.webp',   'label'=>'Baby Milestones'],
        'developmental-red-flags' => ['icon'=>'fa-clipboard-check', 'image'=>'images/pic7.webp', 'label'=>'Mga Babala sa Pag-unlad'],
        'child-nutrition'  => ['icon'=>'fa-bowl-food',            'image'=>'images/pic6.jpg',    'label'=>'Nutrisyon ng Bata'],
        'baby-safety'      => ['icon'=>'fa-shield-halved',        'image'=>'images/pic2.jpg',    'label'=>'Kaligtasan ng Baby'],
        'unang-linggo'     => ['icon'=>'fa-calendar-day',         'image'=>'images/pic3.jpg',    'label'=>'Unang Linggo ni Baby'],
        'philhealth'       => ['icon'=>'fa-id-card',              'image'=>'images/pic13.webp',  'label'=>'PhilHealth Benefits'],
        'family-planning'  => ['icon'=>'fa-people-group',         'image'=>'images/pic14.webp',  'label'=>'Family Planning'],
    ];
    // Pick 3 related topics (exclude current)
    $related_keys = array_filter(array_keys($all_related), fn($k) => $k !== $slug);
    $related_keys = array_values($related_keys);
    // Simple seeded selection for variety
    $picks = array_slice($related_keys, 0, 3);
    ?>

    <!-- RELATED ARTICLES -->
    <?php if (!empty($picks)): ?>
    <div class="related-section">
        <h3><img src="images/greenbook.png" alt="" style="width:14px;height:14px;object-fit:contain;vertical-align:middle;"> Related Topics</h3>
        <div class="related-grid">
            <?php foreach ($picks as $key): ?>
            <?php $r = $all_related[$key]; ?>
            <a href="health_article.php?topic=<?= $key ?>" class="related-card">
                <div class="related-card-icon">
                    <?php if (!empty($r['image'])): ?>
                    <img src="<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['label']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:3px;">
                    <?php else: ?>
                    <i class="fa <?= $r['icon'] ?>"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="related-card-title"><?= htmlspecialchars($r['label']) ?></div>
                    <div class="related-card-sub">Read more →</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<div class="article-footer">
    <div class="footer-inner">

        <div class="footer-top">

            <div>
                <div class="footer-brand">
                    <img src="images/logo.jpg?v=<?= time() ?>" alt="Brgy Logo" onerror="this.style.display='none'">
                    <div class="footer-brand-text">
                        <h4>Barangay Alawihao Health Center</h4>
                        <span>Alawihao, Daet, Camarines Norte</span>
                    </div>
                </div>
                <p class="footer-tagline">Serving the health of every family in Barangay Alawihao.</p>
            </div>

            <div class="footer-col">
                <h5><i class="fa fa-address-book"></i> Contact Us</h5>
                <ul>
                    <li><img src="images/fb.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"> <a href="https://www.facebook.com/barangay.alawihao" target="_blank" rel="noopener">facebook.com/barangay.alawihao</a></li>
                    <li><img src="images/email.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"> <a href="mailto:alawihaohealth@gmail.com">alawihaohealth@gmail.com</a></li>
                    <li><img src="images/location.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"> Alawihao, Daet, Camarines Norte, 4600</li>
                </ul>
            </div>

            <div class="footer-col">
                <h5><i class="fa fa-clock"></i> Office Hours</h5>
                <ul>
                    <li>Monday – Friday</li>
                    <li>8:00 AM – 5:00 PM</li>
                    <li>Closed on Saturdays, Sundays &amp; Holidays</li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <span class="footer-copy">&copy; <?= date('Y') ?> Barangay Alawihao Health Center. All rights reserved.</span>
            <span class="footer-badge"><i class="fa fa-shield-halved" aria-hidden="true"></i> DOH-Accredited Facility</span>
        </div>

    </div>
</div>

<script>
    const articleSearch = document.getElementById('articleSearch');
    const articleSearchPanel = document.getElementById('articleSearchPanel');
    const articleSearchToggle = document.getElementById('articleSearchToggle');
    const articleSearchInput = document.getElementById('articleSearchInput');
    const articleSearchEmpty = document.getElementById('articleSearchEmpty');

    function filterArticleTopics() {
        const query = articleSearchInput.value.trim().toLowerCase();
        let matches = 0;
        document.querySelectorAll('.article-search-result').forEach(function (result) {
            const visible = result.dataset.searchText.includes(query);
            result.hidden = !visible;
            if (visible) matches++;
        });
        articleSearchEmpty.hidden = query.length === 0 || matches > 0;
    }

    function toggleArticleSearch() {
        articleSearchPanel.hidden = !articleSearchPanel.hidden;
        articleSearchToggle.setAttribute('aria-expanded', String(!articleSearchPanel.hidden));
        if (!articleSearchPanel.hidden) {
            articleSearchInput.focus();
        } else {
            articleSearchInput.value = '';
            filterArticleTopics();
        }
    }

    articleSearchInput.addEventListener('input', filterArticleTopics);
    articleSearchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            const firstResult = document.querySelector('.article-search-result:not([hidden])');
            if (firstResult) firstResult.click();
        }
    });
    document.addEventListener('click', function (event) {
        if (!articleSearch.contains(event.target) && !articleSearchPanel.hidden) {
            articleSearchPanel.hidden = true;
            articleSearchToggle.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !articleSearchPanel.hidden) {
            articleSearchPanel.hidden = true;
            articleSearchToggle.setAttribute('aria-expanded', 'false');
            articleSearchToggle.focus();
        }
    });

    window.addEventListener('scroll', () => {
        const pct = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) * 100;
        const val = isNaN(pct) ? 0 : Math.min(pct, 100);
        document.getElementById('progressBar').style.width  = val + '%';
        document.getElementById('readingBar').style.width   = val + '%';
    });

    function toggleItem(id) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('open');
    }
</script>
<?php $notif_path = ''; include 'notif_js.php'; ?>
</body>
</html>