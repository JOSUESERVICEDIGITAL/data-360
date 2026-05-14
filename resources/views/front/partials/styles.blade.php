<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root {
    --blue: #005da8;
    --blue-dark: #004f91;
    --text: #243447;
    --border: #e5eaf0;
    --bg: #f6fbff;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    color: var(--text);
    background: #ffffff;
    line-height: 1.6;
}

a {
    text-decoration: none;
    color: inherit;
}

.container {
    width: min(1180px, 90%);
    margin: auto;
}

/* HEADER */
.header {
    background: #ffffff;
    border-bottom: 1px solid var(--border);
    padding: 12px 0;
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
}

.logo-img {
    height: 100px;
    object-fit: contain;
}

.nav {
    display: flex;
    align-items: center;
    gap: 26px;
}

.nav a {
    color: #4a5a6a;
    font-size: 14px;
    font-weight: 600;
}

.nav a:hover {
    color: var(--blue);
}

.nav .register-btn {
    border: 1px solid var(--blue);
    color: var(--blue);
    padding: 8px 16px;
    border-radius: 5px;
}

.nav .register-btn:hover {
    background: var(--blue);
    color: #ffffff;
}

.mobile-btn {
    display: none;
    border: none;
    background: transparent;
    color: var(--blue);
    font-size: 24px;
    cursor: pointer;
}

/* HERO */
.hero {
    background: linear-gradient(135deg, #eaf6ff, #dff1ff);
    padding: 78px 0 58px;
    text-align: center;
}

.hero h1 {
    color: var(--blue);
    font-size: 34px;
    line-height: 1.2;
    margin-bottom: 18px;
    font-weight: 800;
}

.hero p {
    color: #5d6b7c;
    max-width: 650px;
    margin: 0 auto 24px;
    font-size: 15px;
}

.search-box {
    width: min(470px, 95%);
    margin: 0 auto 18px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #8b98a5;
}

.search-box input {
    width: 100%;
    padding: 14px 16px 14px 42px;
    border: 1px solid #c8d2dc;
    border-radius: 4px;
    font-size: 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
    outline: none;
}

.btn {
    display: inline-block;
    padding: 12px 28px;
    border-radius: 5px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: .2s;
}

.btn-primary {
    background: var(--blue);
    color: #fff;
    border: 1px solid var(--blue);
}

.btn-primary:hover {
    background: var(--blue-dark);
}

.btn-outline {
    border: 2px solid var(--blue);
    color: var(--blue);
    background: transparent;
}

.btn-outline:hover {
    background: var(--blue);
    color: white;
}

/* SECTIONS */
.section {
    padding: 58px 0;
}

.center-text {
    text-align: center;
    max-width: 760px;
    margin: auto;
}

.section h2 {
    color: var(--blue);
    font-size: 27px;
    line-height: 1.25;
    margin-bottom: 16px;
    font-weight: 800;
}

.section p {
    color: #4f5f70;
    font-size: 15px;
}

.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.img-placeholder {
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #425466;
    font-size: 14px;
    text-align: center;
    filter: drop-shadow(0 8px 8px rgba(0, 0, 0, .13));
}

ul {
    padding-left: 18px;
    margin: 18px 0 24px;
    color: #3d4d5e;
    font-size: 14px;
}

li {
    margin-bottom: 9px;
}

.soft-bg {
    background: var(--bg);
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-top: 42px;
}

.card {
    background: #fff;
    padding: 30px 24px;
    border-radius: 8px;
    box-shadow: 0 6px 13px rgba(0, 0, 0, .12);
    min-height: 180px;
}

.card-icon {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #e9f4ff;
    display: grid;
    place-items: center;
    color: var(--blue);
    font-size: 21px;
    margin-bottom: 22px;
}

.card h3 {
    color: var(--blue);
    font-size: 19px;
    margin-bottom: 14px;
}

/* CTA */
.cta {
    background: linear-gradient(135deg, #0065bd, #0056a6);
    color: white;
    text-align: center;
    padding: 72px 0;
}

.cta h2 {
    color: white;
    font-size: 32px;
    margin-bottom: 20px;
}

.cta p {
    color: #e8f3ff;
    max-width: 560px;
    margin: 0 auto 28px;
}

.cta-actions {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}

.btn-white {
    background: white;
    color: var(--blue);
    border: 2px solid white;
}

.btn-white-outline {
    background: transparent;
    color: white;
    border: 2px solid white;
}

/* FOOTER */
footer {
    background: #f6fbff;
    padding: 34px 0 20px;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: start;
    max-width: 760px;
    margin: auto;
}

.footer-logo {
    color: var(--blue);
    text-align: center;
}

.footer-logo p,
.footer-contact p {
    color: #6b7886;
    font-size: 14px;
}

.footer-contact h4 {
    color: var(--blue);
    font-size: 17px;
    margin-bottom: 10px;
}

.copyright {
    border-top: 1px solid #dde6ee;
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    font-size: 13px;
    color: #7b8794;
}

/* RESULT PAGE */
.result-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.result-card {
    background: #fff;
    border-radius: 10px;
    padding: 22px;
    box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
}

.result-card h2,
.result-card h3 {
    color: var(--blue);
    margin-bottom: 12px;
}

.data-list p {
    margin-bottom: 8px;
}

/* MOBILE */
@media (max-width: 900px) {
    .mobile-btn {
        display: block;
    }

    .nav {
        position: absolute;
        top: 66px;
        left: 0;
        right: 0;
        background: white;
        border-bottom: 1px solid var(--border);
        flex-direction: column;
        align-items: flex-start;
        padding: 20px 5%;
        gap: 16px;
        display: none;
    }

    .nav.active {
        display: flex;
    }

    .two-col,
    .cards,
    .footer-grid {
        grid-template-columns: 1fr;
    }

    .hero h1 {
        font-size: 28px;
    }

    .section h2,
    .cta h2 {
        font-size: 25px;
    }
}
</style>