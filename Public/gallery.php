<?php $pageTitle = 'Photo Gallery — Rifkins College'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="../assets/css/public.css">
<style>
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    .gallery-item {
        background: var(--white);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform .2s, box-shadow .2s;
        display: flex;
        flex-direction: column;
    }
    .gallery-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(10,45,77,.14);
    }
    .gallery-item img {
        width: 100%;
        aspect-ratio: 4/3;
        object-fit: cover;
        display: block;
        background: var(--bg-light);
    }
    .gallery-caption {
        padding: 16px 18px;
    }
    .gallery-caption strong {
        display: block;
        color: var(--primary);
        font-size: .95rem;
        margin-bottom: 4px;
    }
    .gallery-caption span {
        font-size: .8rem;
        color: var(--muted);
    }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">Photo Gallery</span>
            <h2>Life at Rifkins College</h2>
            <p class="lead">A glimpse into our campuses, classrooms, and student life in Mombasa.</p>
        </div>

        <div class="gallery-grid">
            <?php
            // Captions — replace images with your own in assets/img/gallery/
            $items = [
                ['Main Campus — Focal Point Building', 'Digo Road, Mombasa', 'gallery-1.jpg'],
                ['Hospitality Practical Session', 'Food & beverage training kitchen', 'gallery-2.jpg'],
                ['IT Computer Lab', 'Hands-on computing practicals', 'gallery-3.jpg'],
                ['Beauty Therapy Class', 'Hairdressing & beauty training', 'gallery-4.jpg'],
                ['Accounting Lecture', 'Accounting & Finance students', 'gallery-5.jpg'],
                ['Graduation Ceremony', 'Celebrating our graduates', 'gallery-6.jpg'],
                ['Mariakani Campus', 'Green Estate, Mariakani', 'gallery-7.jpg'],
                ['Diani Campus', 'Corner Ya Beach, Matuga Plaza', 'gallery-8.jpg'],
                ['Library & Study Area', 'Quiet study spaces', 'gallery-9.jpg'],
                ['Student Life', 'Beyond the classroom', 'gallery-10.jpg'],
                ['Tourism Field Trip', 'Learning outside the campus', 'gallery-11.jpg'],
                ['Career Day', 'Industry connections and guidance', 'gallery-12.jpg'],
            ];
            foreach ($items as $item):
                list($title, $subtitle, $file) = $item;
                // Fallback SVG if the file doesn't exist
                $path = '../assets/img/gallery/' . $file;
                if (!file_exists(__DIR__ . '/../assets/img/gallery/' . $file)) {
                    $path = 'data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 300%22><rect fill=%22%230a2d4d%22 width=%22400%22 height=%22300%22/><text x=%22200%22 y=%22155%22 font-family=%22Arial%22 font-size=%2220%22 fill=%22%23f0a500%22 text-anchor=%22middle%22>Rifkins College</text></svg>';
                }
            ?>
            <div class="gallery-item">
                <img src="<?= htmlspecialchars($path) ?>" alt="<?= htmlspecialchars($title) ?>" loading="lazy">
                <div class="gallery-caption">
                    <strong><?= htmlspecialchars($title) ?></strong>
                    <span><?= htmlspecialchars($subtitle) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="pub-feature" style="margin-top:40px;background:var(--bg-light);border-left:4px solid var(--accent);text-align:center;">
            <h3>Want to See More?</h3>
            <p>Visit us in person at any of our three campuses, or follow us on social media for the latest photos and events.</p>
            <p style="margin-top:12px;"><a href="contact.php" class="btn-hero btn-hero-primary">Get Directions</a></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>