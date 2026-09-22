@extends('layouts.app')

@section('title', 'Home')

@section('content')
<style>
    /* ===== Uses the same tokens as layouts.app (falls back if not inherited) ===== */
    .faith-page {
        --f-accent: var(--accent, #0a6bd6);
        --f-accent-hover: var(--accent-hover, #0a4a8a);
        --f-navy: #101828;
        --f-ink-soft: var(--text-muted, #64748b);
        --f-surface: var(--surface, #ffffff);
        --f-border: var(--border, #e3e8ee);
        font-family: 'Source Serif 4', Georgia, serif;
        padding-top: 1.4rem;
    }

    .faith-page .masthead { text-align: center; margin-bottom: 1.4rem; padding-top: 0.6rem; }
    .faith-page .masthead h1 { line-height: 1.15; }
    .faith-page .kicker {
        font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        color: var(--f-accent);
        margin-bottom: 0.5rem;
    }
    .faith-page .masthead h1 {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 800;
        font-size: clamp(2rem, 6vw, 3rem);
        margin: 0;
        letter-spacing: -0.01em;
        color: var(--f-navy);
    }
    .faith-page .masthead .tagline {
        font-style: italic;
        color: var(--f-ink-soft);
        font-size: 0.95rem;
        margin-top: 0.4rem;
    }
    .faith-page .rule-thick { height: 3px; background: var(--f-navy); margin: 1.2rem 0 0.5rem; }
    .faith-page .rule-thin { height: 1px; background: var(--f-border); margin-bottom: 2.2rem; }

    .faith-page .intro {
        font-size: 1.05rem;
        color: var(--f-ink-soft);
        max-width: 62ch;
        margin: 0 auto 2.8rem;
        text-align: center;
        line-height: 1.75;
    }

    .faith-page .lead {
        background: var(--f-surface);
        border: 1px solid var(--f-border);
        border-radius: 14px;
        padding: 2.4rem 2.6rem;
        margin-bottom: 2.4rem;
    }
    .faith-page .lead .eyebrow {
        font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;
        color: var(--f-accent);
        font-weight: 700;
        font-size: 0.78rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .faith-page .lead h2 {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 700;
        font-size: clamp(1.5rem, 4vw, 2.1rem);
        margin: 0.2rem 0 0.6rem;
        line-height: 1.2;
        color: var(--f-navy);
    }
    .faith-page .lead .drop::first-letter {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 3.2rem;
        font-weight: 800;
        float: left;
        line-height: 0.8;
        padding-right: 0.35rem;
        padding-top: 0.2rem;
        color: var(--f-accent);
    }
    .faith-page .lead p {
        max-width: 68ch;
        color: #101828;
        margin: 0 0 1.3rem;
        line-height: 1.85;
        text-align: justify;
        hyphens: auto;
    }
    .faith-page .lead a.readmore {
        display: inline-block;
        margin-top: 0.2rem;
        color: var(--f-accent);
        font-weight: 600;
        text-decoration: none;
        border-bottom: 1px solid var(--f-accent);
        font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;
    }
    .faith-page .lead a.readmore:hover { color: var(--f-accent-hover); border-color: var(--f-accent-hover); }

    .faith-page .columns {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.6rem;
    }
    @media (min-width: 640px) {
        .faith-page .columns { grid-template-columns: 1fr 1fr; gap: 1.8rem; }
    }

    .faith-page .story {
        background: var(--f-surface);
        border: 1px solid var(--f-border);
        border-radius: 14px;
        padding: 1.9rem 2rem;
        transition: box-shadow 0.15s ease, transform 0.15s ease;
    }
    .faith-page .story:hover {
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        transform: translateY(-2px);
    }
    .faith-page .story .eyebrow {
        font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: var(--f-accent);
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }
    .faith-page .story h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 700;
        font-size: 1.18rem;
        margin: 0 0 0.5rem;
        line-height: 1.25;
        color: var(--f-navy);
    }
    .faith-page .story p { font-size: 0.96rem; color: var(--f-ink-soft); margin: 0 0 1.1rem; line-height: 1.7; }
    .faith-page .story a.readmore {
        font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;
        color: var(--f-accent);
        font-weight: 600;
        text-decoration: none;
        border-bottom: 1px solid var(--f-accent);
        font-size: 0.9rem;
    }
    .faith-page .story a.readmore:hover { color: var(--f-accent-hover); border-color: var(--f-accent-hover); }

    .faith-page footer {
        text-align: center;
        margin-top: 3rem;
        padding: 1.6rem 0 2.4rem;
        border-top: 1px solid var(--f-border);
        color: var(--f-ink-soft);
        font-size: 0.85rem;
        font-style: italic;
    }
</style>

<div class="faith-page">

    <div class="masthead">
        <div class="kicker">YOUR WEEKLY GUIDE TO FAITH ONLINE</div>
        <h1>The Daily Faith</h1>
        <div class="tagline">A friendly little front page, pointing you to the things that matter most.</div>
    </div>
    <div class="rule-thick"></div>
    <div class="rule-thin"></div>

    <p class="intro">
        Hi there! Whether you're looking for something to read, a service to attend, or just want to stay in the loop, we've gathered the best places to go — all in one spot. Pick a story below and dive in.
    </p>

    <div class="lead">
        <div class="eyebrow">Featured Read</div>
        <h2>Read God's Message Here!</h2>
        <p class="drop">
            There's something grounding about sitting down with God's word, especially when life gets busy. Pasugo — God's Message has been sharing articles, reflections, and stories of faith for years, and it's a wonderful place to start or continue your spiritual reading. Whether you have five minutes or an hour, there's always something worth discovering.
        </p>
        <a class="readmore" href="https://www.pasugo.com.ph/" target="_blank" rel="noopener">Visit Pasugo →</a>
    </div>

    <div class="columns">

        <div class="story">
            <div class="eyebrow">Worship &amp; Community</div>
            <h3>Accessible Schedule of Worship Service</h3>
            <p>Not sure when or where the nearest worship service is? This handy directory makes it easy to find a congregation near you, along with service times — so you're never far from a place to gather and worship.</p>
            <a class="readmore" href="https://iglesianicristo.net/directory/" target="_blank" rel="noopener">Find a schedule →</a>
        </div>

        <div class="story">
            <div class="eyebrow">Stay Informed</div>
            <h3>News About God's Church</h3>
            <p>Curious about what's happening across the Iglesia Ni Cristo community? This is the official home for announcements, updates, and stories worth knowing about — kept fresh and easy to browse.</p>
            <a class="readmore" href="https://iglesianicristo.net/" target="_blank" rel="noopener">Catch up on news →</a>
        </div>

        <div class="story">
            <div class="eyebrow">Get Involved</div>
            <h3>True Christian Activities</h3>
            <p>From heartfelt programs to community outreach, INC Media captures the everyday moments of faith in action. It's a great way to see the church's activities up close, wherever you are.</p>
            <a class="readmore" href="https://incmedia.org/" target="_blank" rel="noopener">See what's happening →</a>
        </div>

        <div class="story">
            <div class="eyebrow">Look Back</div>
            <h3>Know the True Church's History</h3>
            <p>Ever wondered how it all began? The Iglesia Ni Cristo Museum walks you through the church's rich history — a meaningful visit for anyone curious about where this journey of faith started.</p>
            <a class="readmore" href="https://incmuseum.net/" target="_blank" rel="noopener">Explore the history →</a>
        </div>

    </div>

    <footer>Thanks for stopping by — tap any headline above to open it in a new tab.</footer>

</div>
@endsection