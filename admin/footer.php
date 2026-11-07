
<?php if (!defined('FA_LOADED')): define('FA_LOADED', true); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php endif; ?>
<style>
body { flex-wrap: wrap; }

.site-footer-wrap {
    background: #ffffff;
    border-top: 3px solid #2d5016;
    padding: 32px 30px 18px;
    margin-top: 40px;
    margin-left: 280px;
    width: calc(100% - 280px);
    flex-shrink: 0;
    box-sizing: border-box;
    transition: margin-left 0.3s ease, width 0.3s ease;
}
body.sidebar-closed .site-footer-wrap {
    margin-left: 0;
    width: 100%;
}
.site-footer-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.8fr 1fr 1fr;
    gap: 36px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e0e8dc;
    margin-bottom: 14px;
}
@media (max-width: 700px) {
    .site-footer-inner { grid-template-columns: 1fr; gap: 20px; }
}
.sf-brand { display: flex; align-items: flex-start; gap: 12px; }
.sf-brand img {
    width: 46px; height: 46px; border-radius: 50%;
    object-fit: cover; border: 2px solid #2d5016; flex-shrink: 0;
}
.sf-brand-text h4 {
    font-size: 0.88rem; font-weight: 700;
    color: #2d5016; margin-bottom: 2px; line-height: 1.3;
}
.sf-brand-text span {
    font-size: 0.72rem; color: #666; display: block;
}
.sf-tagline {
    font-size: 0.74rem; color: #777; margin-top: 10px;
    line-height: 1.6; font-style: italic;
}
/* Col 2 & 3 — Info */
.sf-col h5 {
    font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px;
    color: #2d5016; margin-bottom: 10px;
    padding-bottom: 5px; border-bottom: 1px solid #d8e8c8;
}
.sf-col ul { list-style: none; padding: 0; margin: 0; }
.sf-col ul li {
    font-size: 0.77rem; color: #444;
    padding: 4px 0; display: flex;
    align-items: flex-start; gap: 7px; line-height: 1.4;
}
.sf-col ul li i { color: #2d5016; font-size: 0.7rem; margin-top: 2px; flex-shrink: 0; }
.sf-col ul li strong { color: #2d5016; }
.sf-col a { color: #3d6b22; text-decoration: none; }
.sf-col a:hover { text-decoration: underline; }
/* Bottom bar */
.sf-bottom {
    display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 8px;
    max-width: 1100px; margin: 0 auto;
}
.sf-copy { font-size: 0.68rem; color: #999; }
.sf-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #eef6e4; border: 1px solid #c8e0a8;
    border-radius: 4px; padding: 3px 10px;
    font-size: 0.66rem; font-weight: 700;
    color: #2d5016; text-transform: uppercase; letter-spacing: 0.3px;
}
/* Dark mode */












</style>

<?php $_img = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../images/' : 'images/'; ?>
<div class="site-footer-wrap">
    <div class="site-footer-inner">

        <div>
            <div class="sf-brand">
                <img src="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '' ?>images/logo.png"
                     alt="Alawihao Health Center"
                     onerror="this.style.display='none'">
                <div class="sf-brand-text">
                    <h4>Barangay Alawihao Health Center</h4>
                    <span>Alawihao, Daet, Camarines Norte</span>
                </div>
            </div>
            <p class="sf-tagline">Serving the health of every family in Barangay Alawihao.</p>
        </div>

        <!-- Contact -->
        <div class="sf-col">
            <h5><img src="<?= $_img ?>call.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;margin-right:4px;"> Contact Us</h5>
            <ul>
                <li><img src="<?= $_img ?>fb.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"><a href="https://www.facebook.com/barangay.alawihao" target="_blank" rel="noopener">facebook.com/barangay.alawihao</a></li>
                <li><img src="<?= $_img ?>email.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"><a href="mailto:alawihaohealth@gmail.com">alawihaohealth@gmail.com</a></li>
                <li><img src="<?= $_img ?>location.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;">Alawihao, Daet, Camarines Norte, 4600</li>
            </ul>
        </div>

        <!-- Hours -->
        <div class="sf-col"> 
            <h5><img src="<?= $_img ?>clock.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;margin-right:4px;"> Office Hours</h5>
            <ul>
                <li>Monday – Friday</li>
                <li>8:00 AM – 5:00 PM</li>
                <li>Closed on Saturdays, Sundays &amp; Holidays</li>
            </ul>
        </div>

    </div>
    <div class="sf-bottom">
        <span class="sf-copy">&copy; <?= date('Y') ?> Barangay Alawihao Health Center. All rights reserved.</span>
        <span class="sf-badge"><i class="fa fa-shield-halved"></i> DOH-Accredited Facility</span>
    </div>
</div>
