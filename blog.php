<?php
// Helper: escape HTML
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Static articles — edited here directly ────────────────────────────────────
$articles = [

  [
    'id'       => 'permits',
    'category' => 'Permits & Residence',
    'image'    => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1200&q=80',
    'readMin'  => 5,
    'title'    => 'Your first Swiss residence permit: what nobody tells you before you arrive',
    'lead'     => 'Moving to Switzerland is one of the most exciting decisions you can make — and one of the most document-heavy. The question most people get wrong from the start is simple: which permit do I actually need? Getting it wrong means delays, fines, or having to restart the process from zero. Here is what you need to know before your first appointment.',
    'body'     => '
<h3>The permit depends on your situation, not just your nationality</h3>
<p>Switzerland has several permit categories. The most common for newcomers are the <strong>L permit</strong> (short-stay, up to one year), the <strong>B permit</strong> (residence permit, renewable annually), and the <strong>C permit</strong> (settlement permit, after 5–10 years). Which one you receive depends on your employment contract, your country of origin, and whether you are coming as an employee, a student, or through family reunification.</p>
<p>EU and EFTA citizens have a simplified path under the Agreement on Free Movement of Persons. Non-EU citizens face a stricter quota system and typically need a confirmed job offer from an employer who has demonstrated that no suitable local candidate was found first.</p>
<h3>The most common mistakes people make</h3>
<ul>
  <li>Waiting too long to register after arrival. You must register at the local Einwohnerkontrolle within 14 days of moving into your address.</li>
  <li>Assuming the employer handles everything. Many companies do not — you are responsible for showing up with the right documents.</li>
  <li>Not knowing that a rental contract is required before you can register — which creates a chicken-and-egg problem for those arriving without pre-arranged housing.</li>
  <li>Underestimating canton-specific differences. Zürich, Geneva, and Basel each have slightly different processes and timelines.</li>
</ul>
<h3>What documents you will typically need</h3>
<p>For most first-time applicants: a valid passport, a rental contract or housing confirmation, an employment contract or proof of sufficient financial means, passport photos, and the completed registration form from the local municipality. For family reunification cases, additional proof of the relationship and the sponsor\'s permit is required.</p>
<p>The process sounds manageable — but in practice, getting all these documents aligned, translated where required, and submitted in the right order is where most people lose time. A single missing document sends you back to square one.</p>',
  ],

  [
    'id'       => 'anmeldung',
    'category' => 'Registration',
    'image'    => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1200&q=80',
    'readMin'  => 4,
    'title'    => 'Anmeldung in Zürich: the one appointment that sets everything else in motion',
    'lead'     => 'Most people moving to Switzerland know they need to register at the local office. What they do not realise is that this single appointment triggers a chain of deadlines — your permit, your health insurance window, your right to open a bank account, even your tax category. Get it right from day one and everything flows. Get it wrong and you spend the next three months untangling the consequences.',
    'body'     => '
<h3>What Anmeldung actually is</h3>
<p>Anmeldung means registration with the municipality where you live. In Zürich, this is done at the Stadthaus or the local Kreisbüro (district office) depending on your neighbourhood. You are registering your place of residence officially with the Swiss state — and this is the act that starts your legal life in Switzerland.</p>
<p>Without Anmeldung you cannot apply for a residence permit, open a bank account, sign a long-term rental contract, or enrol children in school. Everything else waits for this step.</p>
<h3>What you need to bring</h3>
<ul>
  <li>Valid passport or national ID (EU citizens)</li>
  <li>Rental contract or a signed confirmation from your landlord</li>
  <li>Employment contract or proof of financial means (for non-working applicants)</li>
  <li>For families: birth certificates and marriage certificate if applicable</li>
  <li>For non-EU citizens: a visa or relevant entry documentation</li>
</ul>
<h3>The 14-day rule</h3>
<p>You have 14 days from the moment you move into your address to complete the Anmeldung. This is not advisory — it is a legal obligation. Arriving on a Friday and not registering until the following week is fine. Waiting three weeks because you were busy is not, and can result in a fine.</p>
<h3>What happens after the appointment</h3>
<p>You receive a Meldebestätigung (confirmation of registration). This is one of the most important documents you will carry in Switzerland. Keep several copies. You will need it when setting up utilities, opening a bank account, registering a child at school, and in some cases when visiting a doctor for the first time. Your permit application, if needed, usually follows within days or weeks after this step.</p>',
  ],

  [
    'id'       => 'health-insurance',
    'category' => 'Health Insurance',
    'image'    => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
    'readMin'  => 5,
    'title'    => 'Swiss health insurance: you have exactly 3 months — and the clock starts the day you register',
    'lead'     => 'Switzerland has some of the best healthcare in the world. It also has one of the strictest enrolment rules. You have exactly three months from your official registration date to choose and sign up with a health insurer. Miss this window and the canton assigns you a plan — at a premium you did not choose, often higher than what you would have selected yourself. Here is how to choose correctly.',
    'body'     => '
<h3>How the Swiss health insurance system works</h3>
<p>Switzerland uses a system of mandatory basic health insurance (Grundversicherung / assurance de base). Every resident must have it — there is no public option. You choose a private insurer from an approved list, and all basic coverage is legally standardised: the same treatments are covered regardless of which company you choose. What differs is the premium, the deductible (franchise), and the additional model (standard, HMO, Telmed, etc.).</p>
<h3>Choosing your franchise</h3>
<p>The annual deductible (franchise) is the amount you pay out of pocket before insurance covers the rest. It ranges from CHF 300 to CHF 2,500. A higher franchise means lower monthly premiums — which works well if you are generally healthy and do not plan to use medical services often. A lower franchise means you pay less when you do need care, but more each month.</p>
<ul>
  <li>CHF 300 franchise — lowest deductible, highest monthly premium</li>
  <li>CHF 2,500 franchise — highest deductible, lowest monthly premium</li>
  <li>The break-even point is typically around CHF 700–900 in annual medical costs</li>
</ul>
<h3>Supplementary insurance (Zusatzversicherung)</h3>
<p>Basic insurance covers core treatments, but not dental care, private hospital rooms, glasses, or many complementary therapies. If you want these, you need to purchase supplementary insurance separately. This is optional but worth comparing before signing up for basic coverage, as some insurers offer bundled deals.</p>
<h3>Premiums vary by canton and region</h3>
<p>The same plan at the same insurer can cost CHF 450/month in Zürich and CHF 310/month in a rural canton. This is because premiums are set by geographic region based on local healthcare costs. When comparing options, always use your postcode as the reference point. The federal comparison tool Priminfo.admin.ch lets you compare all approved insurers side by side.</p>',
  ],

  [
    'id'       => 'housing',
    'category' => 'Housing',
    'image'    => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?auto=format&fit=crop&w=1200&q=80',
    'readMin'  => 5,
    'title'    => 'Finding an apartment in Zürich: the real competition and how to stand out',
    'lead'     => 'The Swiss rental market — especially in Zürich — is one of the most competitive in Europe. Vacancy rates in the city sit below 0.5%, and a single listing can receive 60 or more applications in the first 48 hours. Most newcomers spend weeks applying before they get a single viewing. But there is a method to this. Knowing how landlords think changes your chances completely.',
    'body'     => '
<h3>What landlords actually look for</h3>
<p>Swiss landlords are risk-averse. Their primary concern is reliable, on-time payment — not personality. The documents they review first are your Betreibungsregisterauszug (debt collection extract), your employment contract, your three most recent salary slips, and a copy of your identity document. A clean debt register and a stable employment contract outweigh almost everything else.</p>
<h3>The documents you need ready before you start</h3>
<ul>
  <li>Debt collection extract (Betreibungsregisterauszug) — request this from the local debt enforcement office (Betreibungsamt) as soon as you register</li>
  <li>Employment contract or a letter from your employer confirming your salary and contract type</li>
  <li>Last 3 months of payslips (or bank statements if self-employed)</li>
  <li>Passport or residence permit</li>
  <li>A short personal cover letter — 5–8 sentences, in German if at all possible</li>
</ul>
<h3>Common mistakes that eliminate applications immediately</h3>
<p>Submitting an incomplete dossier is the fastest way to be rejected. Landlords are reviewing dozens of files at once — any missing document means your file goes in the discard pile. Send everything in a single PDF, in the order they expect: cover letter, ID, permit, employment confirmation, payslips, debt extract.</p>
<p>Another common error: applying for an apartment that costs more than one-third of your net monthly income. Landlords apply this rule strictly. If your net salary is CHF 5,000 and the rent is CHF 2,000, expect rejection even with perfect documents.</p>
<h3>Temporary housing as a strategy</h3>
<p>If you are arriving before you have a rental contract, furnished temporary housing (serviced apartments, expat housing platforms, or furnished sublets) gives you a legal address for Anmeldung while you search more calmly. Trying to apartment-hunt from abroad, under time pressure, in a language you may not speak, is one of the most stressful and inefficient approaches. Arriving, settling, registering, then searching — in that order — gives you a much stronger position.</p>',
  ],

  [
    'id'       => 'work-permit',
    'category' => 'Work & Employment',
    'image'    => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80',
    'readMin'  => 5,
    'title'    => 'Working in Switzerland as a foreigner: permits, contracts and what your HR department might not mention',
    'lead'     => 'Switzerland has one of the highest average salaries in the world. But the path from "you\'ve got the job" to "first payslip deposited" involves more steps than most new employees expect — especially if you are coming from outside the EU. The good news is that the process is predictable. The less good news is that it is entirely your responsibility to navigate it correctly.',
    'body'     => '
<h3>EU/EFTA citizens: the simplified path</h3>
<p>If you hold a passport from an EU or EFTA country, you have the right to work in Switzerland under the Agreement on Free Movement of Persons. You register at the municipality, your employer notifies the cantonal authorities, and within a few weeks you receive your B permit (or L permit for contracts under one year). You do not need a separate work permit — your residence permit covers both.</p>
<h3>Non-EU citizens: the quota system</h3>
<p>If you are coming from outside the EU/EFTA zone, your employer must prove that no suitable candidate from Switzerland or the EU was available for the position before sponsoring you. This is called the priority order (Inländervorrang). If the cantonal labour market authority approves the request, a work and residence permit is issued — but the process can take several months and is subject to annual quotas set by the federal government.</p>
<h3>Your employment contract: what to check</h3>
<ul>
  <li>Gross versus net salary — the difference in Switzerland is significant. Social security deductions (AHV/AVS, pension, unemployment insurance) reduce gross salary by approximately 12–15%</li>
  <li>The 13th month salary — standard in Switzerland but not automatic. Check whether it is included in your monthly salary or paid separately in December</li>
  <li>Probation period — typically 1 to 3 months, during which either party can terminate with 7 days notice</li>
  <li>Non-compete clauses — check the scope and duration carefully, especially in technical or specialist roles</li>
</ul>
<h3>Taxes in your first years</h3>
<p>Most foreign workers in Switzerland are subject to Quellensteuer (withholding tax) — deducted directly from the salary by the employer and paid to the canton. The rate depends on your gross salary, your canton of residence, your civil status, and whether your partner also works. You do not file a tax return in the traditional sense unless your gross income exceeds CHF 120,000 or you have significant outside income. This simplifies life considerably — but also means it is worth checking whether your deduction rate is being calculated correctly from the start.</p>',
  ],

  [
    'id'       => 'family',
    'category' => 'Family & Relocation',
    'image'    => 'https://images.unsplash.com/photo-1536105338741-2956a08f81e4?auto=format&fit=crop&w=1200&q=80',
    'readMin'  => 5,
    'title'    => 'Moving to Switzerland with your family: school, registration and the first 30 days',
    'lead'     => 'Relocating alone is complex. Moving with a partner and children adds several more layers — each person needs to register, each child needs to be enrolled in school, and the sequence of steps matters more than most families realise. If you plan the first 30 days correctly, everything else falls into place. If you do not, you spend weeks backtracking.',
    'body'     => '
<h3>Register the whole family together</h3>
<p>Every family member — including children — must be registered at the Einwohnerkontrolle. Bring the passports and birth certificates for all children. If you are married, bring the marriage certificate. Depending on the canton, these documents may need to be officially translated or apostilled — this is worth checking in advance, as it can add days to your timeline.</p>
<h3>School enrolment for children</h3>
<p>In Switzerland, education is mandatory and free in the public system. Children are assigned to a school based on the family\'s registered address — which is one more reason to complete the Anmeldung before anything else. The school year typically starts in August, but children can join at any point during the year. The school administration will assess which class level is appropriate, which may involve a brief evaluation session, especially for older children.</p>
<ul>
  <li>Primary school (Primarschule): ages 6–12</li>
  <li>Secondary school (Sekundarschule): ages 12–15/16</li>
  <li>Language support: most cantons offer free German (or French/Italian depending on region) integration classes for children who are not yet fluent</li>
</ul>
<h3>Family reunification for non-EU citizens</h3>
<p>If the main permit holder is a non-EU/EFTA citizen, family members joining them in Switzerland must apply for family reunification. The conditions include: the main permit holder must have a B or C permit, the family must have suitable housing (a large enough apartment), and there must be proof of sufficient financial means for the whole family. This process can take several weeks and it is essential to start it before booking flights.</p>
<h3>Healthcare for children</h3>
<p>Each child must have their own health insurance policy — they cannot be added to a parent\'s plan. Children under 18 pay a reduced premium, and many cantons provide additional subsidies for families with lower incomes. The same 3-month enrolment window applies: register your children with an insurer within 3 months of their official registration date.</p>',
  ],

];

$articleCount = count($articles);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <title>Guides & Blog — Easy Help Switzerland</title>
  <meta name="description" content="Practical guides on Swiss residence permits, Anmeldung, health insurance, housing, work permits and family relocation — written by relocation experts." />
  <link rel="canonical" href="https://easyhelpswiss.com/blog.php" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://easyhelpswiss.com/blog.php" />
  <meta property="og:title" content="News & Guides — Easy Help Switzerland" />
  <meta property="og:description" content="Latest news on permits, work, living costs and family life in Switzerland." />
  <meta property="og:image" content="https://easyhelpswiss.com/og-image.jpg" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#ffffff;
      --panel:#e8e8e8;
      --panel-soft:#f4f4f2;
      --text:#111111;
      --muted:#4f4f4f;
      --line:rgba(17,17,17,.08);
      --white:#ffffff;
      --blue:#4693e8;
      --blue-dark:#317bcd;
      --hero:#0f1215;
      --radius:34px;
      --radius-lg:42px;
      --shadow:0 10px 28px rgba(0,0,0,.04);
      --container:min(100% - 38px, 1700px);
      --content:min(100% - 90px, 1460px);
    }

    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{
      margin:0;
      background:var(--bg);
      color:var(--text);
      font-family:"Manrope",system-ui,sans-serif;
      -webkit-font-smoothing:antialiased;
      text-rendering:optimizeLegibility;
      overflow-x:hidden;
    }
    img{display:block;max-width:100%}
    a{text-decoration:none;color:inherit}
    button,input,select,textarea{font:inherit}

    .shell{width:var(--container);margin:20px auto 48px}

    .section-heading-bracket{display:grid;grid-template-columns:46px auto 46px;align-items:start;column-gap:18px;width:fit-content}
    .section-bracket{width:14px;height:80px;margin-top:2px;position:relative}
    .section-bracket::before,.section-bracket::after{content:"";position:absolute;width:12px;height:1.5px;background:rgba(17,17,17,.50)}
    .section-bracket::before{top:0}
    .section-bracket::after{bottom:0}
    .section-bracket-left{border-left:1.5px solid rgba(17,17,17,.50)}
    .section-bracket-right{border-right:1.5px solid rgba(17,17,17,.50)}

    .section-title{margin:0;font-weight:300;font-size:clamp(54px,6vw,92px);line-height:.95;letter-spacing:-.06em}

    .micro{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:rgba(17,17,17,.48);display:inline-flex;align-items:center;gap:8px;margin-bottom:20px}

    .hero-wrap{
      position:relative;
      min-height:760px;
      border-radius:44px;
      overflow:hidden;
      color:#fff;
      padding:26px 36px 34px;
      background:
        linear-gradient(180deg, rgba(9,12,15,.48), rgba(9,12,15,.66)),
        radial-gradient(circle at 50% 40%, rgba(255,255,255,.03), transparent 35%),
        url('https://www.elabedu.eu/wp-content/uploads/2025/01/view-of-rosenlaui-with-male-tourist-walking-along-2024-04-01-22-33-37-utc-1-scaled.jpg') center/cover no-repeat;
    }

    .hero-top{display:grid;grid-template-columns:220px 1fr auto;align-items:center;gap:24px;font-size:15px;position:relative;z-index:2}
    .brand{display:flex;align-items:center;gap:14px;font-weight:500}
    .brand svg{width:36px;height:52px;stroke:#fff;fill:none;stroke-width:1.5;opacity:.92}
    .nav{display:flex;justify-content:center;gap:40px;color:rgba(255,255,255,.9);flex-wrap:wrap}
    .nav a{font-weight:400;position:relative}
    .nav a::after{content:"";position:absolute;left:0;right:0;bottom:-6px;height:1px;background:rgba(255,255,255,.8);transform:scaleX(0);transition:.25s ease}
    .nav a:hover::after{transform:scaleX(1)}
    .hero-right{display:flex;align-items:center;gap:18px;white-space:nowrap;font-size:16px}

    .hero-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:100%;max-width:900px;padding:0 20px;text-align:center;z-index:1}
    .hero-title{font-family:"Cormorant Garamond",serif;font-weight:500;font-size:clamp(92px,12vw,220px);line-height:.82;letter-spacing:-.06em;margin:0}
    .hero-title span{display:block;font-size:clamp(54px,5vw,110px);margin-top:10px}
    .hero-sub{margin-top:20px;font-size:18px;color:rgba(255,255,255,.86);max-width:68ch}

    .hero-bottom{position:absolute;left:36px;right:36px;bottom:28px;display:flex;justify-content:space-between;align-items:flex-end;gap:20px;z-index:2}
    .hero-features{display:flex;gap:38px;flex-wrap:wrap;color:rgba(255,255,255,.95)}
    .hero-feature{display:flex;gap:12px;font-size:18px;line-height:1.1;max-width:220px}
    .hero-feature .dot{width:15px;height:15px;border-radius:50%;background:#fff;color:#111;display:grid;place-items:center;font-size:10px;flex:0 0 auto;margin-top:2px}
    .hero-years{display:flex;align-items:flex-start;gap:12px;color:#fff;text-align:left}
    .hero-years strong{font-family:"Cormorant Garamond",serif;font-size:86px;line-height:.8;font-weight:500}
    .hero-years span{max-width:140px;font-size:18px;line-height:1.05;color:rgba(255,255,255,.9);padding-top:8px}

    .blog-shell{padding:70px 0 0;background:#fff}
    .blog-layout{width:var(--content);margin:0 auto;display:grid;grid-template-columns:minmax(0, 1.1fr) 360px;gap:34px;align-items:start}
    .content-column{min-width:0}
    .sidebar{position:sticky;top:24px;display:grid;gap:22px}

    .glass-card{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:28px;box-shadow:0 10px 30px rgba(0,0,0,.08), inset 0 1px 0 rgba(255,255,255,.22);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}
    .sidebar-card{padding:28px;background:radial-gradient(circle at 20% 20%, rgba(193,232,241,.55), transparent 35%),radial-gradient(circle at 80% 70%, rgba(70,147,232,.18), transparent 30%),linear-gradient(180deg, #f7fafb 0%, #eef5f7 100%)}
    .sidebar-card h3{margin:0 0 14px;font-size:30px;line-height:1.02;letter-spacing:-.05em;font-weight:500}
    .sidebar-card p{margin:0;color:#303030;line-height:1.45;font-size:16px}
    .toc,.related-list{display:grid;gap:10px}
    .toc a,.related-item{padding:12px 0;border-bottom:1px solid rgba(17,17,17,.08);transition:.25s ease;display:block}
    .toc a:hover,.related-item:hover{transform:translateX(4px)}
    .toc a{font-size:15px;line-height:1.4;color:#222}
    .toc .toc-cat{display:block;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#7a8a99;margin-bottom:3px}
    .related-item span{display:block;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin-bottom:6px}
    .related-item strong{display:block;font-size:17px;line-height:1.35}

    .btn-blue,.btn-outline{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:18px 30px;font-weight:600;cursor:pointer;transition:.25s ease;border:0}
    .btn-blue{background:linear-gradient(135deg,#4693e8 0%,#6fb3f2 100%);color:#fff;box-shadow:0 8px 20px rgba(70,147,232,.22), inset 0 1px 0 rgba(255,255,255,.22)}
    .btn-blue:hover{background:linear-gradient(135deg,#317bcd 0%,#5aa6ec 100%);transform:translateY(-1px)}
    .btn-outline{border:1px solid var(--line);background:#fff;color:#111}

    .article-block{margin-bottom:36px;scroll-margin-top:100px}
    .article-card{overflow:hidden;background:#fff;border-radius:34px;border:1px solid rgba(17,17,17,.06);box-shadow:0 14px 38px rgba(0,0,0,.05)}
    .article-cover{position:relative;aspect-ratio:16/8;overflow:hidden;background:#d8e4ec}
    .article-cover img{width:100%;height:100%;object-fit:cover;transition:transform .6s ease}
    .article-card:hover .article-cover img{transform:scale(1.04)}
    .article-cover::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,.06), rgba(0,0,0,.22))}
    .article-body{padding:34px}
    .article-meta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99}
    .article-body h2{margin:0 0 18px;font-size:clamp(30px,3vw,48px);line-height:.95;letter-spacing:-.06em;font-weight:400;font-family:"Cormorant Garamond",serif}
    .article-lead{margin:0 0 26px;font-size:19px;line-height:1.72;color:var(--muted)}
    .article-footer{margin-top:30px;padding-top:22px;border-top:1px solid rgba(17,17,17,.08);display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between}
    .tag-row{display:flex;flex-wrap:wrap;gap:10px}
    .tag{padding:8px 12px;border-radius:999px;background:rgba(17,17,17,.05);font-size:13px;color:#333}
    .source-badge{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#7a8a99;padding:6px 14px;border-radius:999px;border:1px solid rgba(17,17,17,.08)}

    .empty-state{padding:80px 40px;text-align:center;color:#7a8a99;border-radius:34px;border:1px dashed rgba(17,17,17,.10)}
    .empty-state h3{font-size:28px;font-weight:400;margin:0 0 12px}

    .cta-band{width:var(--content);margin:28px auto 0;padding:46px;border-radius:38px;background:radial-gradient(circle at 20% 20%, rgba(193,232,241,.55), transparent 35%),radial-gradient(circle at 80% 70%, rgba(70,147,232,.18), transparent 30%),linear-gradient(180deg, #f7fafb 0%, #eef5f7 100%);display:grid;grid-template-columns:1.2fr .8fr;gap:30px;align-items:center}
    .cta-band h2{margin:0 0 12px;font-size:clamp(42px,4vw,72px);line-height:.95;font-weight:300;letter-spacing:-.06em}
    .cta-band p{margin:0;max-width:56ch;color:#303030;line-height:1.55}
    .cta-actions{display:flex;justify-content:flex-end;gap:14px;flex-wrap:wrap}

    .contact-footer{width:100%;margin-top:22px;padding:28px 32px;background:#02070d;color:#ffffff;border-top:1px solid rgba(255,255,255,.08);display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;align-items:center;font-size:14px;border-radius:0 0 38px 38px}
    .contact-footer .footer-brand{display:flex;align-items:center;gap:12px;font-size:18px;color:#ffffff;justify-self:start}
    .contact-footer .footer-brand svg{width:26px;height:40px;stroke:#ffffff;fill:none;stroke-width:1.3}
    .contact-footer .footer-center{text-align:center;color:rgba(255,255,255,.72);justify-self:center}
    .contact-footer .footer-right{text-align:right;color:rgba(255,255,255,.72);justify-self:end}

    .nav-lang-mobile{display:none}
    .lang-switch{display:flex;gap:8px;font-size:16px}
    .lang-switch button{border:0;background:transparent;color:rgba(255,255,255,.7);cursor:pointer;padding:0}
    .lang-switch button.active{color:var(--blue)}

    .burger{display:none;flex-direction:column;justify-content:center;gap:5px;padding:8px;background:transparent;border:0;cursor:pointer;flex-shrink:0;z-index:10}
    .burger span{display:block;width:22px;height:2px;background:rgba(255,255,255,.85);border-radius:2px;transition:transform .25s,opacity .25s}
    .burger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
    .burger.open span:nth-child(2){opacity:0;transform:scaleX(0)}
    .burger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

    @media(max-width:1180px){
      .hero-top{grid-template-columns:1fr;justify-items:start}
      .nav{justify-content:flex-start;gap:18px}
      .hero-right{justify-self:start;flex-wrap:wrap}
      .hero-center{position:relative;inset:auto;transform:none;display:block;text-align:left;padding-top:140px}
      .hero-bottom{position:relative;left:auto;right:auto;bottom:auto;margin-top:90px;flex-direction:column;align-items:flex-start}
      .hero-wrap{min-height:auto;padding-bottom:34px}
      .blog-layout,.cta-band{grid-template-columns:1fr}
      .sidebar{position:relative;top:0}
      .cta-actions{justify-content:flex-start}
    }
    @media(max-width:780px){
      :root{--container:min(100% - 18px, 1700px);--content:min(100% - 20px, 1460px)}
      .hero-wrap{padding:18px 18px 22px;border-radius:28px}
      .section-title{font-size:52px}
      .article-body{padding:24px 20px}
      .cta-band{padding:26px 22px;border-radius:28px}
      .contact-footer{grid-template-columns:1fr;text-align:left;border-radius:0 0 28px 28px}
      .contact-footer .footer-center,.contact-footer .footer-right{text-align:left;justify-self:start}
    }
    @media(max-width:768px){
      .burger{display:flex}
      .hero-top{display:flex !important;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0;position:relative}
      .nav{display:none;flex-basis:100%;flex-direction:column;gap:0;background:none;border:none;padding:4px 0 8px;margin-top:6px}
      .nav.open{display:flex}
      .nav a{padding:13px 4px;font-size:17px;font-weight:500;color:#fff;border-bottom:1px solid rgba(255,255,255,.15)}
      .nav a::after{display:none}
      .nav-lang-mobile{display:flex;gap:8px;padding:14px 4px 2px;align-items:center}
      .nav-lang-mobile button{background:transparent;border:1px solid rgba(255,255,255,.30);border-radius:8px;color:rgba(255,255,255,.65);font-size:12px;font-weight:600;letter-spacing:.06em;padding:5px 12px;cursor:pointer;transition:all .2s;font-family:inherit}
      .nav-lang-mobile button.active{background:var(--blue);border-color:var(--blue);color:#fff}
      .hero-right{display:none}
      .hero-center{padding-top:60px}
      .hero-title{font-size:clamp(48px,12vw,80px) !important}
      .hero-title span{font-size:clamp(32px,8vw,56px) !important}
      .hero-sub{font-size:15px;max-width:100%}
      .hero-years{display:none}
      .hero-bottom{margin-top:28px}
      .hero-features{gap:14px}
      .hero-feature{font-size:15px}
    }
    @media(max-width:480px){
      :root{--container:min(100% - 16px,1700px)}
      .hero-title{font-size:clamp(38px,11vw,58px) !important}
      .hero-title span{font-size:clamp(26px,8vw,44px) !important}
      .whatsapp-float span:last-child{display:none}
      .whatsapp-float{padding:14px;border-radius:50%;gap:0;width:52px;height:52px;justify-content:center}
    }
  </style>
</head>
<body>
  <div class="shell">
    <section class="hero-wrap" id="top">
      <div class="hero-top">
        <a class="brand" href="index.html" aria-label="Easy Help Switzerland home">
          <svg viewBox="0 0 32 48" aria-hidden="true">
            <path d="M4 44V10l10-8 10 8v34"></path>
            <path d="M14 44V22l10-8v30"></path>
          </svg>
          <div>
            <div style="font-size:18px" data-i18n="brand_name">Easy Help</div>
            <div style="font-size:12px;color:rgba(255,255,255,.65);letter-spacing:.12em;text-transform:uppercase" data-i18n="brand_sub">Switzerland</div>
          </div>
        </a>

        <button class="burger" id="burgerBtn" type="button" aria-label="Open menu"><span></span><span></span><span></span></button>
        <nav class="nav" id="mainNav">
          <a href="index.html" data-i18n="nav_home">Home</a>
          <a href="index.html#services" data-i18n="nav_objects">Services</a>
          <a href="#top" data-i18n="blog_nav_guides">Guides</a>
          <a href="free-consultation.php" data-i18n="booking_nav_free_consultation">Free consultation</a>
          <a href="index.html#contact" data-i18n="nav_contact">Contacts</a>
          <div class="nav-lang-mobile">
            <button type="button" data-lang="en" class="active">EN</button>
            <button type="button" data-lang="es">ES</button>
            <button type="button" data-lang="de">DE</button>
            <button type="button" data-lang="uk">UA</button>
          </div>
        </nav>

        <div class="hero-right">
          <div class="lang-switch">
            <button type="button" data-lang="en" class="active">EN</button>
            <button type="button" data-lang="es">ES</button>
            <button type="button" data-lang="de">DE</button>
            <button type="button" data-lang="uk">UA</button>
          </div>
        </div>
      </div>

      <div class="hero-center">
        <div>
          <div class="micro" data-i18n="blog_hero_micro">News & Guides</div>
          <h1 class="hero-title" data-i18n="blog_hero_title_main">Switzerland <span data-i18n="blog_hero_title_sub">news</span></h1>
          <div class="hero-sub" data-i18n="blog_hero_sub">Practical, up-to-date guides on Swiss permits, registration, health insurance, taxes, and family relocation — written by relocation experts.</div>
        </div>
      </div>

      <div class="hero-bottom">
        <div class="hero-features">
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="blog_hero_feature_1">Permits, registration &amp; insurance guides</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="blog_hero_feature_2">Written by relocation experts</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="blog_hero_feature_3">Updated regularly with new articles</div></div>
        </div>

        <div class="hero-years">
          <strong><?= $articleCount ?></strong>
          <span data-i18n="blog_hero_guides_label">guides available</span>
        </div>
      </div>
    </section>

    <section class="blog-shell">
      <div class="blog-layout">
        <main class="content-column">

<?php foreach ($articles as $i => $a): ?>
          <section class="article-block" id="<?= e($a['id']) ?>">
            <article class="article-card">
              <div class="article-cover">
                <img
                  src="<?= e($a['image']) ?>"
                  alt="<?= e($a['title']) ?>"
                  loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                  onerror="this.src='https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=900&q=80'"
                >
              </div>
              <div class="article-body">
                <div class="article-meta">
                  <span><?= e($a['category']) ?></span>
                  <span>•</span>
                  <span><?= e($a['readMin']) ?> min read</span>
                </div>
                <h2><?= e($a['title']) ?></h2>
                <p class="article-lead"><?= e($a['lead']) ?></p>
                <div class="article-full-body" style="line-height:1.75;font-size:17px;color:#2c2c2c;margin-bottom:28px">
                  <?= $a['body'] ?>
                </div>
                <div class="article-footer">
                  <div class="tag-row">
                    <span class="tag"><?= e($a['category']) ?></span>
                  </div>
                  <a href="free-consultation.php" class="btn-blue" data-i18n="sidebar_support_cta">
                    Book a free consultation →
                  </a>
                </div>
              </div>
            </article>
          </section>
<?php endforeach; ?>

        </main>

        <aside class="sidebar">
          <div class="sidebar-card glass-card">
            <div class="micro" data-i18n="sidebar_on_page_label">Today's articles</div>
            <h3 data-i18n="sidebar_toc_title">Latest news</h3>
            <div class="toc">
<?php foreach ($articles as $a): ?>
              <a href="#<?= e($a['id']) ?>">
                <span class="toc-cat"><?= e($a['category']) ?></span>
                <?= e(mb_strimwidth($a['title'], 0, 72, '…')) ?>
              </a>
<?php endforeach; ?>
            </div>
          </div>

          <div class="sidebar-card glass-card">
            <div class="micro" data-i18n="sidebar_related_label">Related services</div>
            <h3 data-i18n="sidebar_related_title">How we can help</h3>
            <div class="related-list">
              <a href="free-consultation.php" class="related-item">
                <span data-i18n="sidebar_related_consultation_label">Consultation</span>
                <strong data-i18n="sidebar_related_consultation_title">Book a free consultation for your relocation plan</strong>
              </a>
              <a href="index.html#services" class="related-item">
                <span data-i18n="sidebar_related_permits_label">Permits</span>
                <strong data-i18n="sidebar_related_permits_title">Support with residence permits and registration steps</strong>
              </a>
              <a href="index.html#services" class="related-item">
                <span data-i18n="sidebar_related_insurance_label">Insurance</span>
                <strong data-i18n="sidebar_related_insurance_title">Get help choosing the right health insurance setup</strong>
              </a>
            </div>
          </div>

          <div class="sidebar-card glass-card">
            <div class="micro" data-i18n="sidebar_support_label">Need support?</div>
            <h3 data-i18n="sidebar_support_title">Prefer personal guidance?</h3>
            <p data-i18n="sidebar_support_text">If you want help with registration, permits, housing documents, or health insurance, book a consultation and get a clear step-by-step plan.</p>
            <div style="margin-top:18px"><a href="free-consultation.php" class="btn-blue" data-i18n="sidebar_support_cta">Book consultation</a></div>
          </div>

        </aside>
      </div>

      <section class="cta-band">
        <div>
          <div class="micro" data-i18n="cta_label">Next step</div>
          <h2 data-i18n="cta_title">Make your relocation easier from day one</h2>
          <p data-i18n="cta_text">Save time, avoid missed deadlines, and move through permits, registration, and insurance with a clear structure and personal support.</p>
        </div>
        <div class="cta-actions">
          <a href="free-consultation.php" class="btn-blue" data-i18n="cta_book">Book consultation</a>
          <a href="index.html" class="btn-outline" data-i18n="cta_home">Back to homepage</a>
        </div>
      </section>
    </section>

    <footer class="contact-footer">
      <div class="footer-brand">
        <svg viewBox="0 0 32 48" aria-hidden="true"><path d="M4 44V10l10-8 10 8v34"></path><path d="M14 44V22l10-8v30"></path></svg>
        <span data-i18n="footer_brand">Easy Help Switzerland</span>
      </div>
      <div class="footer-center">
        <span data-i18n="footer_copy">© 2026 Easy Help Switzerland — all rights reserved.</span><br>
        <a href="privacy.php" style="color:rgba(255,255,255,.45);font-size:12px" data-i18n="footer_privacy">Privacy Policy</a>
        &nbsp;·&nbsp;
        <a href="terms.php" style="color:rgba(255,255,255,.45);font-size:12px" data-i18n="footer_terms">Terms</a>
        &nbsp;·&nbsp;
        <a href="refund-policy.php" style="color:rgba(255,255,255,.45);font-size:12px" data-i18n="footer_refund">Refund Policy</a>
      </div>
      <div class="footer-right" data-i18n="footer_tagline">Practical relocation support in Switzerland</div>
    </footer>
  </div>

  <a class="whatsapp-float" href="https://wa.me/41764497581" target="_blank" rel="noopener" aria-label="WhatsApp">
    <span class="wa-icon-wrap"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></span>
    <span><strong style="display:block;font-size:14px" data-i18n="wa_label">WhatsApp</strong><small style="opacity:.75;font-size:12px" data-i18n="wa_write">Write to us</small></span>
  </a>
  <style>
    .whatsapp-float{position:fixed;left:18px;bottom:18px;z-index:999;display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:999px;background:#25D366;color:#fff;text-decoration:none;box-shadow:0 14px 32px rgba(0,0,0,.18);transition:transform .2s,box-shadow .2s}
    .whatsapp-float:hover{transform:translateY(-2px);box-shadow:0 18px 40px rgba(37,211,102,.4)}
    .wa-icon-wrap{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  </style>
  <script src="site.js"></script>
  <script>
    (() => {
      const burger = document.getElementById('burgerBtn');
      const nav    = document.getElementById('mainNav');
      if (!burger || !nav) return;
      burger.addEventListener('click', () => { burger.classList.toggle('open'); nav.classList.toggle('open'); });
      nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { burger.classList.remove('open'); nav.classList.remove('open'); }));
    })();
  </script>
</body>
</html>
