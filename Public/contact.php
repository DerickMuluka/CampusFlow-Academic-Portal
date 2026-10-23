<?php $pageTitle = 'Contact Us — Rifkins College'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="../assets/css/public.css">
<style>
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 40px;
    }
    .contact-card {
        background: var(--white);
        border-radius: var(--radius);
        padding: 26px;
        box-shadow: var(--shadow);
        border-left: 4px solid var(--accent);
    }
    .contact-card h3 { color: var(--primary); font-size: 1.05rem; margin-bottom: 10px; }
    .contact-card p { color: var(--muted); font-size: .88rem; line-height: 1.7; margin-bottom: 6px; }
    .contact-card strong { color: var(--text); }
    .hours-card {
        background: var(--primary);
        color: var(--white);
        border-radius: var(--radius);
        padding: 26px;
        box-shadow: var(--shadow);
    }
    .hours-card h3 { color: var(--accent); font-size: 1.05rem; margin-bottom: 14px; }
    .hours-card p { color: rgba(255,255,255,.9); font-size: .9rem; line-height: 1.9; }
    .hours-card strong { color: var(--white); }
    .map-wrap {
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-top: 30px;
    }
    .map-wrap iframe { display: block; width: 100%; height: 420px; border: 0; }
    @media (max-width: 800px) {
        .contact-grid { grid-template-columns: 1fr; }
        .map-wrap iframe { height: 300px; }
    }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">Contact Us</span>
            <h2>We're Here to Help</h2>
            <p class="lead">Visit, call, or message us — our team is ready to answer any question about courses, fees, or admission.</p>
        </div>

        <div class="contact-grid">
            <div class="contact-card">
                <h3>Main Campus — Mombasa</h3>
                <p><strong>Address:</strong> Focal Point Building, 3rd Floor, Digo Road, opposite Naivas Supermarket, Mombasa.</p>
                <p><strong>Phone:</strong> <a href="tel:+254715043739" style="color:var(--primary-light);font-weight:700;">0715 043739</a></p>
                <p><strong>Email:</strong> <a href="mailto:info@rifkinscollege.com" style="color:var(--primary-light);font-weight:700;">info@rifkinscollege.com</a></p>
            </div>

            <div class="contact-card">
                <h3>Mariakani Campus</h3>
                <p><strong>Address:</strong> Green Estate, 100m from Mombasa stage, adjacent to Shalimar Hotel.</p>
                <p><strong>Phone:</strong> <a href="tel:+254705799553" style="color:var(--primary-light);font-weight:700;">0705 799553</a></p>
            </div>

            <div class="contact-card">
                <h3>Diani Campus</h3>
                <p><strong>Address:</strong> Corner Ya Beach, Matuga Plaza, 3rd Floor, adjacent to Equity Bank.</p>
                <p><strong>Phone:</strong> <a href="tel:+254717058441" style="color:var(--primary-light);font-weight:700;">0717 058441</a></p>
            </div>

            <div class="hours-card">
                <h3>Opening Hours</h3>
                <p><strong>Working Days:</strong><br>
                Monday – Friday: 8:00 AM – 7:30 PM<br><br>
                <strong>Saturday:</strong> 8:00 AM – 2:00 PM<br><br>
                <strong>Sunday & Public Holidays:</strong> Closed</p>
            </div>
        </div>

        <div class="pub-feature" style="background:var(--bg-light);border-left:4px solid var(--primary);margin-bottom:30px;">
            <h3>Postal Address</h3>
            <p>Rifkins College, P.O. Box 84728, Mombasa 80100, Kenya</p>
        </div>

        <div class="map-wrap">
            <iframe
                src="https://www.google.com/maps?q=Rifkins+College+Digo+Road+Mombasa&output=embed"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

        <div class="pub-cta" style="margin-top:50px;border-radius:var(--radius);">
            <h2>Have a Question?</h2>
            <p>Send us a message on WhatsApp — we usually reply within minutes.</p>
            <a href="https://wa.me/254715043739?text=Hello%20Rifkins%20College%2C%20I%27d%20like%20to%20enquire%20about%20courses." target="_blank" rel="noopener" class="btn-hero btn-hero-primary">Chat on WhatsApp</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>