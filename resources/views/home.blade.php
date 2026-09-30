@extends('layouts.app')

@section('title', 'Home')

@section('content')
<style>
    .faith-page {
        --navy: #0a1324; --navy-2: #14264a; --gold: #c8a24a; --gold-soft: #e6cf94;
        --paper: #f6f3ec; --ink: #101828; --muted: #5d6878; --line: #e2dccd;
        --ease: cubic-bezier(.2, .8, .2, 1);
        color: var(--ink);
        font-family: 'Source Serif 4', Georgia, serif;
        padding-top: 1.2rem;
    }
    .faith-page a { text-decoration: none; }
    .faith-page .sans { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; }

    /* ---------- Hero ---------- */
    .hero {
        position: relative; overflow: hidden; isolation: isolate;
        min-height: 520px; display: grid; align-content: center; gap: 2.2rem;
        padding: 4rem clamp(1.4rem, 5vw, 4rem) 2.6rem; border-radius: 18px;
        background: linear-gradient(160deg, var(--navy) 0%, var(--navy-2) 100%); color: #fff;
        --mx: 70%; --my: 30%;
    }
    .hero::before {
        content: ""; position: absolute; inset: -30%; z-index: -1;
        background:
            radial-gradient(circle at var(--mx) var(--my), rgba(200, 162, 74, .28), transparent 38%),
            radial-gradient(circle at 15% 85%, rgba(58, 110, 200, .30), transparent 40%);
        animation: drift 18s ease-in-out infinite alternate;
    }
    .hero::after { /* slow-turning ring: thin concentric lines plus a dashed outer edge */
        content: ""; position: absolute; z-index: -1; right: -140px; top: -140px; width: 520px; height: 520px; border-radius: 50%;
        border: 1px dashed rgba(230, 207, 148, .4);
        background: repeating-radial-gradient(circle, transparent 0 47px, rgba(230, 207, 148, .16) 47px 48px);
        animation: spin 90s linear infinite;
    }
    @keyframes drift { to { transform: translate3d(4%, -3%, 0) scale(1.08); } }
    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes rise { to { transform: none; } }
    @keyframes fade { to { opacity: 1; transform: none; } }

    .hero-kicker { margin: 0 0 1rem; color: var(--gold-soft); font-size: .78rem; font-weight: 600; letter-spacing: .22em; text-transform: uppercase; opacity: 0; transform: translateY(8px); animation: fade .8s var(--ease) .1s forwards; }
    .hero-title { margin: 0; font-family: 'Playfair Display', Georgia, serif; font-weight: 800; font-size: clamp(2.8rem, 9vw, 6rem); line-height: 1.02; letter-spacing: -.02em; }
    .hero-title .w { display: inline-block; overflow: hidden; vertical-align: top; padding-bottom: .08em; margin-right: .22em; }
    .hero-title .w > span { display: inline-block; transform: translateY(110%); animation: rise 1s var(--ease) forwards; animation-delay: calc(var(--i) * .13s + .2s); }
    .hero-sub { max-width: 52ch; margin: 1.2rem 0 0; color: #c9d3e3; font-size: 1.12rem; line-height: 1.7; opacity: 0; transform: translateY(10px); animation: fade .9s var(--ease) .75s forwards; }
    .hero-nav { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.8rem; opacity: 0; animation: fade .9s var(--ease) 1s forwards; }
    .btn { display: inline-flex; align-items: center; gap: .5rem; padding: .8rem 1.4rem; border-radius: 999px; font-size: .78rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; transition: background .25s, color .25s, transform .25s var(--ease), border-color .25s; }
    .btn-gold { background: var(--gold); color: var(--navy); }
    .btn-gold:hover { background: var(--gold-soft); transform: translateY(-2px); }
    .btn-line { border: 1px solid rgba(255,255,255,.4); color: #fff; }
    .btn-line:hover { border-color: var(--gold-soft); color: var(--gold-soft); transform: translateY(-2px); }

    .verse { max-width: 60ch; margin: 0; padding: .3rem 0 .3rem 1.4rem; border-left: 2px solid var(--gold); opacity: 0; transform: translateY(10px); animation: fade .9s var(--ease) 1.2s forwards; }
    .verse blockquote { margin: 0; }
    .verse p { margin: 0 0 .45rem; font-style: italic; font-size: 1.05rem; line-height: 1.6; color: #e8edf6; }
    .verse cite { font-size: .8rem; font-style: normal; font-weight: 600; letter-spacing: .12em; color: var(--gold-soft); text-transform: uppercase; }

    /* ---------- Sections ---------- */
    .sec { padding: 4rem 0 0; }
    .sec-head { max-width: 60ch; margin-bottom: 1.8rem; }
    .sec-head h2 { margin: 0; font-family: 'Playfair Display', Georgia, serif; font-weight: 700; font-size: clamp(1.7rem, 4vw, 2.4rem); line-height: 1.15; letter-spacing: -.01em; }
    .sec-head p { margin: .6rem 0 0; color: var(--muted); line-height: 1.7; }
    .sec-head::after { content: ""; display: block; width: 56px; height: 3px; margin-top: 1rem; background: var(--gold); transform-origin: left; transform: scaleX(0); transition: transform .9s var(--ease) .2s; }
    .sec-head.in::after { transform: none; }
    .sec:focus { outline: none; }
    .sec.landed .sec-head::after { animation: pulse 1.1s var(--ease); }
    @keyframes pulse { from { box-shadow: 0 0 0 0 rgba(200, 162, 74, .7); } to { box-shadow: 0 0 0 16px rgba(200, 162, 74, 0); } }

    /* Featured read */
    .feature { display: grid; grid-template-columns: 1fr; border-radius: 18px; overflow: hidden; background: #fff; border: 1px solid var(--line); }
    @media (min-width: 820px) { .feature { grid-template-columns: 5fr 6fr; } }
    .cover { position: relative; display: grid; place-content: center; min-height: 300px; padding: 2rem; text-align: center; overflow: hidden; color: #fff; background: linear-gradient(140deg, var(--navy-2), var(--navy)); }
    .cover::before { content: ""; position: absolute; inset: 0; background: radial-gradient(circle at 50% 120%, rgba(200,162,74,.45), transparent 60%); transition: transform 1.2s var(--ease); }
    .feature:hover .cover::before { transform: scale(1.25); }
    .cover-frame { position: absolute; inset: 1.1rem; border: 1px solid rgba(230,207,148,.5); }
    .cover-title { position: relative; margin: 0; font-family: 'Playfair Display', Georgia, serif; font-size: clamp(2.6rem, 6vw, 3.8rem); font-weight: 800; letter-spacing: .02em; }
    .cover-sub { position: relative; margin: .4rem 0 0; color: var(--gold-soft); font-size: .8rem; letter-spacing: .3em; text-transform: uppercase; }
    .feature-body { display: grid; align-content: center; padding: clamp(1.6rem, 4vw, 3rem); }
    .tag { color: #8a6a1f; font-size: .74rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
    .feature-body h3 { margin: .4rem 0 .8rem; font-family: 'Playfair Display', Georgia, serif; font-size: clamp(1.5rem, 3.6vw, 2.1rem); line-height: 1.2; }
    .feature-body p { margin: 0 0 1.5rem; max-width: 60ch; color: var(--muted); line-height: 1.85; }
    .feature-body .btn { justify-self: start; background: var(--navy); color: #fff; }
    .feature-body .btn:hover { background: var(--navy-2); transform: translateY(-2px); }

    /* Tour cards */
    .tours { display: grid; grid-template-columns: 1fr; gap: 1.2rem; }
    @media (min-width: 640px) { .tours { grid-template-columns: 1fr 1fr; } }
    .tour { position: relative; display: grid; align-content: start; padding: 1.9rem 1.9rem 1.7rem; border-radius: 16px; background: #fff; border: 1px solid var(--line); overflow: hidden; color: inherit; transition: transform .4s var(--ease), box-shadow .4s var(--ease), border-color .4s; }
    .tour::before { content: ""; position: absolute; left: 0; top: 0; height: 4px; width: 100%; background: linear-gradient(90deg, var(--gold), var(--gold-soft)); transform: scaleX(0); transform-origin: left; transition: transform .5s var(--ease); }
    .tour::after { content: ""; position: absolute; right: -60px; bottom: -60px; width: 180px; height: 180px; border-radius: 50%; background: radial-gradient(circle, rgba(200,162,74,.18), transparent 70%); opacity: 0; transition: opacity .5s; }
    .tour:hover { transform: translateY(-6px); border-color: var(--gold); box-shadow: 0 18px 36px rgba(10,19,36,.12); }
    .tour:hover::before { transform: none; }
    .tour:hover::after { opacity: 1; }
    .tour h3 { margin: .35rem 0 .55rem; font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem; line-height: 1.25; }
    .tour p { margin: 0 0 1.3rem; color: var(--muted); line-height: 1.7; font-size: .97rem; }
    .go { display: inline-flex; align-items: center; gap: .5rem; color: var(--navy); font-size: .78rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .go i { display: inline-block; font-style: normal; transition: transform .35s var(--ease); }
    .tour:hover .go i { transform: translateX(6px); }

    /* Scroll reveal */
    .js .reveal { opacity: 0; transform: translateY(28px); transition: opacity .8s var(--ease), transform .8s var(--ease), box-shadow .4s var(--ease), border-color .4s; transition-delay: var(--d, 0s); }
    .js .reveal.in { opacity: 1; transform: none; }
    .js .tour.reveal.in:hover { transform: translateY(-6px); transition-delay: 0s; }

    .foot { margin-top: 4rem; padding: 1.8rem 1rem; border-radius: 18px; background: var(--navy); color: #b9c4d6; text-align: center; font-size: .9rem; }
    .foot strong { display: block; margin-bottom: .3rem; color: var(--gold-soft); font-family: 'Playfair Display', Georgia, serif; font-size: 1.15rem; }

    .faith-page a:focus-visible { outline: 3px solid var(--gold); outline-offset: 3px; }
    @media (prefers-reduced-motion: reduce) {
        .faith-page *, .faith-page *::before, .faith-page *::after { animation: none !important; transition: none !important; }
        .hero-kicker, .hero-sub, .hero-nav, .verse { opacity: 1; transform: none; }
        .hero-title .w > span { transform: none; }
        .js .reveal { opacity: 1; transform: none; }
        .sec-head::after { transform: none; }
    }
</style>

<div class="faith-page">

    <section class="hero" id="top">
        <div>
            <p class="hero-kicker sans">Your weekly guide to faith online</p>
            <h1 class="hero-title" aria-label="The Daily Faith">
                <span class="w" style="--i:0" aria-hidden="true"><span>The</span></span>
                <span class="w" style="--i:1" aria-hidden="true"><span>Daily</span></span>
                <span class="w" style="--i:2" aria-hidden="true"><span>Faith</span></span>
            </h1>
            <p class="hero-sub">Something to read, a service to attend, news to catch up on. We've gathered the best places to go, all in one spot.</p>
            <div class="hero-nav sans">
                <a class="btn btn-gold" href="#read">Start reading</a>
                <a class="btn btn-line" href="#explore">Explore</a>
            </div>
        </div>

        <figure class="verse" aria-label="Talata mula sa Biblia">
            <blockquote lang="tl">
                <p id="verse-text">Ang Panginoon ay aking pastor; hindi ako magkukulang.</p>
                <cite class="sans" id="verse-ref">Awit 23:1, Ang Dating Biblia</cite>
            </blockquote>
        </figure>
    </section>

    <section class="sec" id="read">
        <div class="sec-head reveal sans">
            <h2 style="font-family:'Playfair Display',Georgia,serif">Featured read</h2>
        </div>
        <article class="feature reveal">
            <div class="cover" aria-hidden="true">
                <div class="cover-frame"></div>
                <div>
                    <h3 class="cover-title">Pasugo</h3>
                    <p class="cover-sub sans">God's Message</p>
                </div>
            </div>
            <div class="feature-body">
                <span class="tag sans">Featured read</span>
                <h3>Read God's Message here!</h3>
                <p>There's something grounding about sitting down with God's word, especially when life gets busy. Pasugo: God's Message has been sharing articles, reflections and stories of faith for years, and it's a wonderful place to start or continue your spiritual reading. Whether you have five minutes or an hour, there's always something worth discovering.</p>
                <a class="btn sans" href="https://www.pasugo.com.ph/" target="_blank" rel="noopener">Visit Pasugo <i style="font-style:normal">→</i></a>
            </div>
        </article>
    </section>

    <section class="sec" id="explore">
        <div class="sec-head reveal">
            <h2>Where to go next</h2>
            <p>Four places worth a visit, each opens in a new tab.</p>
        </div>

        <div class="tours">
            <a class="tour reveal" style="--d:.0s" href="https://iglesianicristo.net/directory/" target="_blank" rel="noopener">
                <span class="tag sans">Worship &amp; community</span>
                <h3>Accessible schedule of worship service</h3>
                <p>Not sure when or where the nearest worship service is? This handy directory makes it easy to find a congregation near you, along with service times, so you're never far from a place to gather and worship.</p>
                <span class="go sans">Find a schedule <i>→</i></span>
            </a>
            <a class="tour reveal" style="--d:.1s" href="https://iglesianicristo.net/" target="_blank" rel="noopener">
                <span class="tag sans">Stay informed</span>
                <h3>News about God's Church</h3>
                <p>Curious about what's happening across the Iglesia Ni Cristo community? This is the official home for announcements, updates and stories worth knowing about, kept fresh and easy to browse.</p>
                <span class="go sans">Catch up on news <i>→</i></span>
            </a>
            <a class="tour reveal" style="--d:.2s" href="https://incmedia.org/" target="_blank" rel="noopener">
                <span class="tag sans">Get involved</span>
                <h3>True Christian activities</h3>
                <p>From heartfelt programs to community outreach, INC Media captures the everyday moments of faith in action. It's a great way to see the church's activities up close, wherever you are.</p>
                <span class="go sans">See what's happening <i>→</i></span>
            </a>
            <a class="tour reveal" style="--d:.3s" href="https://incmuseum.net/" target="_blank" rel="noopener">
                <span class="tag sans">Look back</span>
                <h3>Know the true Church's history</h3>
                <p>Ever wondered how it all began? The Iglesia Ni Cristo Museum walks you through the church's rich history, a meaningful visit for anyone curious about where this journey of faith started.</p>
                <span class="go sans">Explore the history <i>→</i></span>
            </a>
        </div>
    </section>

    <footer class="foot reveal">
        <strong>Thanks for stopping by</strong>
        Tap any card above to open it in a new tab.
    </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.faith-page');
    const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    page.classList.add('js');

    // Scroll reveal
    const items = page.querySelectorAll('.reveal, .sec-head');
    if ('IntersectionObserver' in window && !still) {
        const io = new IntersectionObserver(entries => entries.forEach(e => {
            if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
        }), { threshold: .15, rootMargin: '0px 0px -6% 0px' });
        items.forEach(n => io.observe(n));
    } else {
        items.forEach(n => n.classList.add('in'));
    }

    // A different verse each time the page loads (never the same one twice in a row)
    const VERSES = [
        ['Ang Panginoon ay aking pastor; hindi ako magkukulang.', 'Awit 23:1'],
        ['Sapagka\'t nalalaman ko ang mga pagiisip na iniisip ko tungkol sa inyo, sabi ng Panginoon, mga pagiisip ng kapayapaan, at hindi ng kasamaan, upang bigyan kayo ng pagasa sa inyong huling wakas.', 'Jeremias 29:11'],
        ['Huwag kang matakot; sapagka\'t ako\'y sumasaiyo: huwag kang manglupaypay; sapagka\'t ako ang iyong Dios: aking palalakasin ka; oo, aking tutulungan ka.', 'Isaias 41:10'],
        ['Magagawa ko ang lahat ng mga bagay sa kaniya na nagpapalakas sa akin.', 'Filipos 4:13'],
        ['Magsiparito sa akin, ang lahat ng nangapapagal at nangabibigatang lubha, at kayo\'y aking pagpapahingahin.', 'Mateo 11:28'],
        ['Ang Dios ay aming kanlungan at kalakasan, isang katulong na laging masusumpungan sa kabagabagan.', 'Awit 46:1'],
        ['Ang salita mo\'y ilawan sa aking mga paa, at liwanag sa aking landas.', 'Awit 119:105'],
        ['Nguni\'t silang naghihintay sa Panginoon ay magbabagong lakas; sila\'y sasampa na may mga pakpak na gaya ng mga agila; sila\'y magsisitakbo, at hindi manganganyayat.', 'Isaias 40:31'],
        ["Ang Panginoon ay aking liwanag at aking kaligtasan; kanino ako matatakot? Ang Panginoon ay katibayan ng aking buhay; kanino ako mangingilag?", 'Awit 27:1'],
        ["Magalak ka naman sa Panginoon; at ibibigay niya sa iyo ang nasa ng iyong puso.", 'Awit 37:4'],
        ["Ipagkatiwala mo ang iyong pasanin sa Panginoon, at aalalayan ka niya.", 'Awit 55:22'],
        ["Siyang tumatahan sa lihim na dako ng Kataastaasan ay tatahan sa lilim ng Makapangyarihan sa lahat.", 'Awit 91:1'],
        ["Ito ang araw na ginawa ng Panginoon; tayo'y matutuwa at magagalak diyan.", 'Awit 118:24'],
        ["Kaniyang pinagagaling ang mga bagbag na puso, at tinatalian ang kanilang mga sugat.", 'Awit 147:3'],
        ["Ang pagtangis ay magtatagal nang magdamag, nguni't ang kagalakan ay dumarating sa kinaumagahan.", 'Awit 30:5'],
        ["Magtiwala ka sa Panginoon ng buong puso mo, at huwag kang manalig sa iyong sariling kaunawa.", 'Kawikaan 3:5'],
        ["Ipagkatiwala mo sa Panginoon ang iyong mga gawa, at ang iyong mga pagiisip ay matatatag.", 'Kawikaan 16:3'],
        ["Ang pangalan ng Panginoon ay matibay na moog: ang matuwid ay tumatakbo roon, at ligtas.", 'Kawikaan 18:10'],
        ["Iyong iingatan siya sa sakdal na kapayapaan, na ang pagiisip ay nananatili sa iyo: sapagka't siya'y tumitiwala sa iyo.", 'Isaias 26:3'],
        ["Pagka ikaw ay dumaraan sa mga tubig, ako'y sasaiyo; at sa mga ilog, hindi ka nila aapawan.", 'Isaias 43:2'],
        ["Datapuwa't hanapin ninyo muna ang kaniyang kaharian, at ang kaniyang katuwiran; at ang lahat ng mga bagay na ito'y idaragdag sa inyo.", 'Mateo 6:33'],
        ["Magsihingi kayo, at kayo'y bibigyan; magsihanap kayo, at kayo'y makasusumpong; magsituktok kayo, at kayo'y pagbubuksan.", 'Mateo 7:7'],
        ["Kapayapaan ang iniiwan ko sa inyo, aking kapayapaan ang ibinibigay ko sa inyo. Huwag mabagabag ang inyong puso, ni mangilabot man.", 'Juan 14:27'],
        ["Ngayon ang Dios ng pagasa ay puspusin nawa kayo ng buong kagalakan at kapayapaan sa pananampalataya, upang kayo'y managana sa pagasa.", 'Roma 15:13'],
        ["Sapagka't nagsisilakad kami sa pamamagitan ng pananampalataya, hindi sa pamamagitan ng nakikita.", '2 Corinto 5:7'],
        ["At huwag tayong mangapagod sa paggawa ng mabuti: sapagka't sa kapanahunan ay mangagsisiani tayo, kung tayo'y hindi manghihimagod.", 'Galacia 6:9'],
        ["Ang pananampalataya nga ay siyang katunayan ng mga bagay na inaasahan, ang katibayan ng mga bagay na hindi nakikita.", 'Hebreo 11:1'],
        ["Sa bawa't bagay ay may kapanahunan, at panahon sa bawa't panukala sa silong ng langit.", 'Eclesiastes 3:1'],
        ["Bago sa bawa't umaga: dakila ang iyong pagtatapat.", 'Panaghoy 3:23'],
        ["Kayo'y magpakalakas at magpakatapang, huwag kayong matakot; hindi ka niya iiwan, ni pababayaan ka man.", 'Deuteronomio 31:6'],
    ];
    let last = -1;
    try { last = Number(localStorage.getItem('faith-verse')); } catch (e) {}
    let pick;
    do { pick = Math.floor(Math.random() * VERSES.length); } while (pick === last && VERSES.length > 1);
    try { localStorage.setItem('faith-verse', String(pick)); } catch (e) {}
    document.getElementById('verse-text').textContent = VERSES[pick][0];
    document.getElementById('verse-ref').textContent = VERSES[pick][1] + ', Ang Dating Biblia';

    // Eased scrolling for the hero buttons (works whether the page or an inner container scrolls)
    const scroller = node => {
        for (let p = node.parentElement; p; p = p.parentElement) {
            const o = getComputedStyle(p).overflowY;
            if ((o === 'auto' || o === 'scroll') && p.scrollHeight > p.clientHeight) return p;
        }
        return document.scrollingElement;
    };
    const ease = t => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    page.querySelectorAll('a[href^="#"]').forEach(a => a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        const box = scroller(target), isDoc = box === document.scrollingElement;
        const boxTop = isDoc ? 0 : box.getBoundingClientRect().top;
        const end = Math.max(0, box.scrollTop + target.getBoundingClientRect().top - boxTop - 16);
        const land = () => {
            box.style.scrollBehavior = '';
            target.setAttribute('tabindex', '-1');
            target.focus({ preventScroll: true });
            target.classList.remove('landed'); void target.offsetWidth; target.classList.add('landed');
        };
        box.style.scrollBehavior = 'auto';
        if (still) { box.scrollTop = end; land(); return; }
        const start = box.scrollTop, dist = end - start, dur = Math.min(1200, 550 + Math.abs(dist) * .35), t0 = performance.now();
        const step = now => {
            const t = Math.min((now - t0) / dur, 1);
            box.scrollTop = start + dist * ease(t);
            if (t < 1) requestAnimationFrame(step); else land();
        };
        requestAnimationFrame(step);
    }));

    // Soft light that follows the pointer across the hero
    const hero = page.querySelector('.hero');
    if (!still && window.matchMedia('(pointer: fine)').matches) {
        hero.addEventListener('pointermove', e => {
            const r = hero.getBoundingClientRect();
            hero.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
            hero.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
        });
    }
});
</script>
@endpush