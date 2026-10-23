<?php $pageTitle = 'Blog — Rifkins College'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="../assets/css/public.css">
<style>
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
    .blog-post {
        background: var(--white);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform .2s, box-shadow .2s;
        border-top: 4px solid var(--accent);
    }
    .blog-post:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(10,45,77,.14); }
    .blog-post-body { padding: 24px; }
    .blog-tag {
        display: inline-block;
        background: var(--bg-light);
        color: var(--accent);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .8px;
        text-transform: uppercase;
        padding: 3px 10px;
        border-radius: 12px;
        margin-bottom: 12px;
    }
    .blog-post h3 { color: var(--primary); font-size: 1.08rem; line-height: 1.4; margin-bottom: 10px; }
    .blog-post p { font-size: .88rem; color: var(--muted); line-height: 1.6; }
    .blog-meta { font-size: .76rem; color: var(--muted); margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border); }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">Blog</span>
            <h2>Insights, Guides & Student Stories</h2>
            <p class="lead">Practical advice for students choosing a college, plus stories from our community.</p>
        </div>

        <div class="blog-grid">
            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Welcome</span>
                    <h3>Welcome to Rifkins College: Your Journey Begins</h3>
                    <p>A warm welcome to new and prospective students. At Rifkins College, we believe in the power of education to transform lives. With our diverse programmes, expert faculty, and innovative learning environment, we prepare you for success in a rapidly changing world. Explore our campus, meet our community, and let's build your future together.</p>
                    <div class="blog-meta">Rifkins College · 4 min read</div>
                </div>
            </article>

            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Student Story</span>
                    <h3>Pursuing a Diploma in the Coastal City</h3>
                    <p>One student's experience studying at Rifkins College — balancing coursework with life by the ocean, community volunteering, and the eLibrary. From managing group projects to making the most of Mombasa's vibrant culture, this is a firsthand look at how our students grow academically and personally.</p>
                    <div class="blog-meta">Student Voice · 6 min read</div>
                </div>
            </article>

            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Guide</span>
                    <h3>How to Choose the Best College in Mombasa</h3>
                    <p>A four-step professional guide: verify TVETA accreditation, check exam bodies (ICM, ABMA, KNEC), match courses to your career goals, and visit campuses before deciding. Choosing the right college shapes your entire career trajectory — this article breaks down exactly what to look for and what questions to ask.</p>
                    <div class="blog-meta">Career Guide · 8 min read</div>
                </div>
            </article>

            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Languages</span>
                    <h3>Why Learn English? 5 Compelling Reasons</h3>
                    <p>English is the language of opportunity — spoken in over 60 nations and essential for international careers, higher education, and global business. Learn how our Foundation English and Intermediate English courses help students unlock global opportunities.</p>
                    <div class="blog-meta">Languages · 4 min read</div>
                </div>
            </article>

            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Career</span>
                    <h3>Mastering the Art of Beauty Therapy</h3>
                    <p>The beauty industry is worth millions in Kenya and billions globally. A certificate in beauty therapy can kickstart a rewarding career in salons, spas, or your own business. We cover hair braiding, locks, facials, manicure, and makeup — skills that are in constant demand.</p>
                    <div class="blog-meta">Beauty Therapy · 5 min read</div>
                </div>
            </article>

            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Career</span>
                    <h3>Economy Shapers: Supply Chain Management</h3>
                    <p>Logistics and supply chain management influence nearly every facet of modern life. Careers in shipping and logistics are in high demand — especially in a port city like Mombasa. Learn about the ABMA (UK) and CDACC qualifications that open doors.</p>
                    <div class="blog-meta">Logistics · 6 min read</div>
                </div>
            </article>

            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Technology</span>
                    <h3>Why IT Skills Matter More Than Ever</h3>
                    <p>Technology is at the centre of all learning activities at Rifkins College. Our IT & Computing courses cover web design, computer programming, IT support, and AutoCAD — the skills that modern employers demand across every industry.</p>
                    <div class="blog-meta">IT · 5 min read</div>
                </div>
            </article>

            <article class="blog-post">
                <div class="blog-post-body">
                    <span class="blog-tag">Guide</span>
                    <h3>How to Apply to Rifkins College: Step by Step</h3>
                    <p>A clear walkthrough of our admission process — choose a course, check requirements, apply online, pay the Kshs 500 fee via M-Pesa PayBill 4088921, and receive your portal credentials. Takes under 10 minutes from start to finish.</p>
                    <div class="blog-meta">Admissions · 5 min read</div>
                </div>
            </article>
        </div>

        <div class="pub-feature" style="margin-top:50px;text-align:center;background:var(--bg-light);border-left:4px solid var(--accent);">
            <h3>Have a Story to Share?</h3>
            <p>We'd love to feature your journey. Email <a href="mailto:info@rifkinscollege.com" style="color:var(--primary-light);font-weight:700;">info@rifkinscollege.com</a> or WhatsApp <strong>0715 043739</strong>.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>