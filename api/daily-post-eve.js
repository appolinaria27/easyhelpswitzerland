/**
 * Evening post — English, slot 2 (offset +10 so different topic from morning)
 * Cron: 0 17 * * * (17:00 UTC = 19:00 Zürich summer)
 * Self-contained — no imports.
 */

const POSTS_EN = [
  {
    slug: 'b-permit',
    text: `Most people moving to Switzerland don't realise there are *4 different residence permits* — and picking the wrong one to apply for wastes weeks.

🔵 *B permit* — the standard residence permit for most newcomers. Valid 1 year, renewable. Required if you're working, studying, or joining family here.

🟢 *C permit* — permanent residence. You can apply after 5 or 10 years depending on your nationality. No need to renew every year.

🟡 *L permit* — short-term, for contracts under 12 months. Often overlooked but important if your first contract is temporary.

🔴 *G permit* — for cross-border commuters who live abroad but work in Switzerland.

*Practical tips:*
— EU/EFTA citizens register at the Einwohnerkontrolle and get the B permit almost automatically after finding housing and a job.
— Non-EU citizens need employer sponsorship first — your company applies on your behalf before you arrive.
— The permit type affects your health insurance deadline, banking options, and family reunification rights — so it matters more than most people think.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/swiss-residence-permit`,
  },
  {
    slug: 'anmeldung',
    text: `You have *14 days* after moving into your Swiss address to register at the Einwohnerkontrolle. Miss that deadline and you risk a fine — and delays to everything else that follows.

The Anmeldung (municipal registration) is the first domino. Until it's done, you can't get your residence permit, open a bank account properly, or set up health insurance correctly.

📋 *What to bring to your appointment:*
— Valid passport or ID
— Rental contract or written confirmation from your landlord
— For non-EU citizens: your work contract and employer details
— For families: marriage certificate and children's birth certificates (translated if necessary)

*Common mistakes:*
— Waiting until you feel "settled" before registering. Register on day 1 if possible.
— Booking the appointment too late — slots fill up, especially in Zürich. Book the moment you sign your lease.
— Forgetting to de-register from your previous country. Switzerland and most EU countries exchange data — gaps cause problems later.

After registration you'll receive your *Anmeldebestätigung* — keep it. You'll need it for your bank, insurance, and permit application.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/anmeldung-zurich`,
  },
  {
    slug: 'health-insurance',
    text: `Switzerland has *no public health insurance*. Every resident must buy private health insurance — and you have exactly *90 days* from arrival to do it.

If you miss the 90-day window, your canton assigns you a provider automatically. You lose the right to choose, and the assigned plan is rarely the cheapest.

🏥 *How KVG (basic insurance) works:*
— Basic coverage is identical across all providers — the law defines what's covered
— What differs is the *premium* (monthly cost) and the *customer service*
— Your premium depends on your canton, age, and chosen deductible (franchise)

💡 *How to pay less:*
— Choose a higher *franchise* (deductible): CHF 300 → 2,500. Higher franchise = lower monthly premium. Good if you're healthy and rarely visit the doctor.
— Choose a *Telmed* or *HMO* model instead of the standard model — 10–25% cheaper, but you call a hotline or visit a designated doctor first.
— Compare on *priminfo.admin.ch* — the official federal comparison tool.

Don't forget: children under 18 get *subsidies* in most cantons. Apply at your cantonal social insurance office (SVA).

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/swiss-health-insurance`,
  },
  {
    slug: 'apartment',
    text: `The Zürich rental market is one of the most competitive in Europe. Apartments receive 50–200 applications. Here's what actually gets you shortlisted.

🗂 *The documents Swiss landlords expect:*
— *Betreibungsregisterauszug* (debt collection register extract) — shows you have no outstanding debts. Get it from your municipality. Takes 1–3 days.
— Last 3 payslips or employment contract
— Copy of your residence permit or registration confirmation
— Letter of motivation (yes, really — Swiss landlords read them)

*What actually works:*
— Apply within hours of a listing going live — not days
— Write a short, personal motivation letter in German if possible (even basic German shows effort)
— Offer to pay 2–3 months deposit upfront if you're new to Switzerland without a Swiss rental history
— Use *homegate.ch*, *immoscout24.ch*, and *comparis.ch* — and set up instant alerts

*Avoid:*
— Applying without the Betreibungsauszug — it's the number one reason applications are rejected
— Skipping the viewing — Swiss landlords expect personal contact

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/apartment-zurich`,
  },
  {
    slug: 'bank-account',
    text: `Opening a bank account in Switzerland as a newcomer is harder than it sounds — but there are options that work from day one.

Traditional banks like UBS or Credit Suisse often require you to already have a residence permit or at least a registration confirmation. That creates a catch-22 for newly arrived expats.

🏦 *What actually works as a newcomer:*
— *Neon* or *Yuh* — Swiss digital banks, open an account with just your passport and registration confirmation. Takes 10 minutes on your phone.
— *Raiffeisen* — more approachable than the big banks for newcomers, especially outside Zürich city
— *PostFinance* — accepts customers at an earlier stage than most banks

📋 *What you'll need for any account:*
— Valid passport or ID
— Swiss address (Anmeldebestätigung from the Einwohnerkontrolle)
— Residence permit or at least proof of employment

*Important:* Your Swiss employer will need a Swiss account to pay your salary — so don't wait. Set this up in your first week, ideally before your first payday.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/bank-account-switzerland`,
  },
  {
    slug: 'cost-of-living',
    text: `Switzerland is expensive — but most people are surprised by *where* the money actually goes. Knowing this before you arrive changes how you budget.

💸 *Realistic monthly costs in Zürich (single person):*
— Rent (1-bedroom): CHF 1,800–2,800
— Health insurance: CHF 350–500
— Groceries: CHF 400–600
— Transport (Halbtax + zones): CHF 150–200
— Phone: CHF 20–40 (Aldi Talk, Yallo, or Salt)
— Total baseline: *CHF 2,800–4,200/month*

*Where people overspend without realising:*
— Eating out (CHF 25–45 per meal) — cooking saves CHF 400+/month
— Not using Halbtax — the half-fare card pays for itself within 2–3 train trips
— Shopping at Migros/Coop when Lidl/Aldi are nearby (20–40% cheaper for basics)
— Ignoring cantonal tax differences — living in Zug vs Zürich can save thousands per year

*Where Switzerland is surprisingly fair:*
— Public transport is excellent and cheaper than owning a car
— Healthcare quality is high — once you understand the franchise system
— Children's allowances (Kinderzulagen) add CHF 200–300/child/month

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/cost-of-living-switzerland`,
  },
  {
    slug: 'driving-licence',
    text: `If you move to Switzerland with a foreign driving licence, you have *12 months* to convert it — after that, it's no longer valid for driving here.

🚗 *The rules depend on where your licence is from:*

*EU/EEA licence* — relatively simple. Exchange it at the cantonal road traffic office (Strassenverkehrsamt). No test required in most cases.

*Non-EU licence (Ukraine, USA, etc.)* — more steps:
— You must take the Swiss theory test
— You need a minimum number of driving lessons with a Swiss instructor
— You sit the practical driving test

📋 *What to bring to the Strassenverkehrsamt:*
— Your foreign driving licence (original)
— An official translation if it's not in German/French/Italian
— Your Swiss registration confirmation
— Passport photo
— Application fee (CHF 50–80 depending on canton)

*Common mistake:* Many newcomers wait too long, assuming 12 months is plenty of time. Appointment slots fill up — especially in Zürich. Book your appointment in month 1 or 2.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/driving-licence-switzerland`,
  },
  {
    slug: 'family-reunion',
    text: `Moving to Switzerland as a family is one of the most complex relocation scenarios — because the rules for your partner and children depend entirely on *your* permit type.

👨‍👩‍👧 *If you hold a B permit (EU/EFTA):*
Your spouse and children under 21 can join you with relatively little friction. They apply for their own B permits and register at the Einwohnerkontrolle.

👨‍👩‍👧 *If you hold a B permit (non-EU):*
Family reunion requires approval. Your salary must meet a minimum threshold, you need adequate housing, and the application goes through the cantonal migration office.

📋 *Documents typically required:*
— Marriage certificate (officially translated and apostilled)
— Birth certificates for children
— Proof of adequate housing (rental contract)
— Proof of sufficient income
— Health insurance confirmation for all family members

*What people often miss:*
— Children over 18 cannot be included in family reunion — they must apply independently
— Your partner may need to show basic language skills (A1 level) in some cantons
— The process can take 2–4 months — plan ahead before your family travels

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/moving-switzerland-family`,
  },
  {
    slug: 'work-permit',
    text: `Getting a work permit in Switzerland as a non-EU citizen is a two-stage process — and most of the work falls on your *employer*, not you.

*Stage 1 — Employer applies on your behalf:*
Your Swiss employer submits a work permit application to the cantonal migration authority. They need to prove that no suitable Swiss or EU candidate was available for the role. This can take 4–8 weeks.

*Stage 2 — You apply for a visa:*
Once the canton approves the work permit, you apply for a D visa at the Swiss embassy in your home country. With visa in hand, you travel to Switzerland and register within 14 days.

📋 *What your employer needs to prepare:*
— Proof of job advertisement in Switzerland and EU
— Your CV and qualifications
— Signed employment contract
— Proof the salary meets the standard Swiss rate for your profession

*Important:* Switzerland has annual quotas for non-EU work permits — they're limited. Certain professions (IT, healthcare, finance) have higher approval rates.

*The most common mistake:* Assuming you can come first and sort the permit later. The permit must be approved *before* you start working.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/work-permit-switzerland`,
  },
  {
    slug: 'taxes',
    text: `Swiss taxes surprise most newcomers — not because they're high, but because the *system* works completely differently from what you're used to.

🧾 *The two main systems:*

*Quellensteuer (withholding tax)* — if you're a foreign national without a C permit, your employer deducts tax directly from your salary each month. You don't file a tax return in most cases.

*Standard tax return* — once you have a C permit or if your income exceeds CHF 120,000/year, you file a full tax return like a Swiss citizen.

📍 *Switzerland taxes at 3 levels:*
— Federal (same everywhere)
— Cantonal (varies significantly — Zug pays far less than Geneva)
— Municipal (your specific town adds its own rate)

*What newcomers often don't know:*
— The tax year is the calendar year, but bills arrive the following year
— You can deduct professional expenses, commuting costs, and health insurance premiums
— Moving from a high-tax to low-tax canton mid-year saves proportionally

*Tip:* Your first Swiss tax bill can arrive 12–18 months after you arrive. Set aside 15–25% of your net salary as a buffer.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'c-permit',
    text: `The Swiss *C permit* (settlement permit) is the closest thing to permanent residency — and most foreigners don't realise they can already qualify for it.

🟢 *What the C permit gives you:*
— No renewal every year or every 5 years
— Right to work for any employer without restrictions
— Path to Swiss naturalisation
— Easier access to mortgages and long-term financial products

⏱ *When can you apply?*
— *EU/EFTA citizens:* after *5 years* of continuous residence
— *Non-EU citizens from certain countries* (USA, Canada, Australia): after *5 years*
— *Other non-EU citizens:* after *10 years*
— *Spouses of Swiss citizens:* after *5 years* of marriage and living together

📋 *What you need to demonstrate:*
— Continuous residence (no long gaps)
— Language skills (usually A2–B1 depending on canton)
— Financial independence (no welfare dependency)
— Integration — some cantons look at community participation, children in local schools

*The common mistake:* Not applying as soon as you qualify. The C permit doesn't arrive automatically — you must apply at the cantonal migration office.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'salary',
    text: `Switzerland has some of the highest salaries in the world — but the gap between what you're offered and what the market pays can be CHF 20,000–40,000 per year.

💰 *Realistic salary ranges in Zürich (gross, per year):*
— Software engineer: CHF 100,000–150,000
— Marketing manager: CHF 90,000–120,000
— Accountant: CHF 80,000–110,000
— Nurse: CHF 70,000–90,000
— Project manager: CHF 95,000–130,000

*Key resources to check your number:*
— *salarium.ch* — official federal salary comparison tool
— *lohncheck.ch* — community-reported salaries
— *jobs.ch* salary guide — updated annually

⚠️ *What to watch out for:*
— Swiss employers rarely include the 13th month salary in their headline figure — always ask if it's included
— The *Quellensteuer* rate takes 15–35% depending on your salary and canton — factor this into your take-home calculation
— Benefits matter: meal allowances, transport, pension contributions, and remote work flexibility are all negotiable

*Rule of thumb:* If your gross salary in Zürich is under CHF 80,000 and you have 3+ years of professional experience, it's worth a conversation.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'schools',
    text: `Switzerland has one of the best public school systems in the world — and it's completely free, including for newcomers. Here's how to navigate enrolment.

🏫 *How Swiss schools work:*
— Public school is compulsory from age 4 (Kindergarten) to 15
— Schools are organised by *Gemeinde* (municipality) — your child attends the school in your district
— Instruction is in the local language: German in Zürich, French in Geneva, Italian in Ticino

📋 *To enrol your child:*
— Contact your local school district (Schulkreis) or the school directly
— Bring: passport, registration confirmation, previous school records, and vaccination records
— No entrance exams for primary school — placement is by age and previous schooling

*What newcomers often ask:*
— *Language support:* Most Swiss schools offer German integration classes (DaZ) for children who don't speak the local language.
— *Timing:* You can enrol mid-year — schools accept new students throughout the year.
— *International schools:* Private, cost CHF 25,000–40,000/year. Worth it only if your stay is short-term or your employer covers it.

*Tip:* Register your children at school at the same time as your Anmeldung.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'transport',
    text: `Switzerland's public transport system is the most punctual and well-connected in the world. Once you understand how to use it, owning a car in the city becomes optional.

🚆 *The two cards every Swiss resident should know:*

*Halbtax (Half-fare card)* — CHF 185/year. Gives you 50% off almost every train, bus, tram, and boat ticket in Switzerland. If you take even 2 intercity trips per month, it pays for itself immediately.

*GA (General Abonnement)* — unlimited travel on the entire Swiss public transport network. From CHF 3,860/year (2nd class). Worth it if you travel between cities regularly for work.

🗺 *How the zone system works:*
Zürich's ZVV network runs on zones. A single zone subscription covers your local commute. The Halbtax makes long-distance trips on top of this affordable.

*What newcomers miss:*
— SBB app — buy tickets, see live departures, download tickets offline. Essential.
— *Supersaver tickets* — up to 70% off if you book 1–2 weeks in advance
— Night trains and S-Bahn run until 1–2am on weekends

*Practical start:* Buy the Halbtax in your first week at sbb.ch.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'german',
    text: `You don't need perfect German to live in Switzerland. But you need *enough* — and the fastest way to get there isn't a language school.

🗣 *The Swiss reality:*
Swiss German (*Schweizerdeutsch*) is spoken in everyday life, but standard German (*Hochdeutsch*) is used in written form, at official offices, and in most professional settings. As a newcomer, Hochdeutsch is what you need first.

*What actually works fast:*
— *Tandem language exchange* — find a German speaker who wants to learn your language. Free, and conversation practice is more effective than textbooks. Apps: Tandem, HelloTalk.
— *Volkshochschule (VHS)* — cantonal adult education centres. Very affordable (CHF 200–400 per course), structured, and in person.
— *Goethe Institut* — more expensive but recognised certificates (A1–C2). Worth it if you need official proof of language level.
— *Duolingo + YouTube* — good for the first 2–3 months. Not enough on its own.

🎯 *Realistic targets:*
— A2: enough for daily errands, basic appointments
— B1: required for C permit in most cantons
— B2: comfortable at work and in social settings

*The fastest path:* Start with an intensive group course (4 weeks, full days), then switch to conversation exchange to maintain it.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'self-employed',
    text: `Becoming self-employed in Switzerland is simpler than most people expect — but the permit situation depends entirely on your nationality.

💼 *For EU/EFTA citizens:*
You can register as self-employed relatively easily. Register with the AHV (social insurance) as self-employed, get your Anmeldung, and you're operating legally. Your B permit covers self-employment.

*For non-EU citizens:*
Your current permit type matters:
— On a B permit tied to an employer: you cannot simply become self-employed without a new permit application
— You need to demonstrate that your self-employment is financially viable and serves the Swiss economic interest

📋 *Steps to set up as self-employed (EU/EFTA):*
1. Register with the cantonal commercial registry if your revenue exceeds CHF 100,000/year
2. Register with the AHV compensation office as self-employed (mandatory)
3. Arrange your own health insurance and pension contributions
4. Open a separate business bank account

*What surprises people:*
— Below CHF 100,000/year revenue you don't need to register a formal company
— AHV contributions as self-employed are higher than as an employee — around 10% of income
— VAT registration is required if revenue exceeds CHF 100,000/year

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'pension',
    text: `Switzerland has one of the most secure pension systems in the world — but as a newcomer, you need to understand it from day one.

🏦 *The three pillars:*

*1st pillar — AHV (state pension):*
Mandatory for everyone. Contributions come automatically from your salary (both you and your employer pay). If you leave Switzerland before retirement, you may claim a refund of your contributions under certain conditions.

*2nd pillar — BVG (occupational pension):*
Mandatory if you earn over CHF 22,050/year. Your employer and you both contribute. This money can be withdrawn when buying property or leaving Switzerland permanently.

*3rd pillar — Private pension (3a):*
Voluntary, but one of the best tax tools in Switzerland. You can deposit up to CHF 7,056/year and deduct the *entire amount* from your taxable income. That's a real tax saving of CHF 1,500–2,500/year.

💡 *What newcomers should do immediately:*
— Open a *3rd pillar (3a) account* as soon as you arrive. Banks like VIAC, Frankly, or Finpension offer low-fee options.
— Check your 2nd pillar statements — you receive them annually from your employer's pension fund.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'emergency',
    text: `Switzerland is one of the safest countries in the world — but knowing who to call in an emergency before you need it is part of settling in properly.

📞 *Essential numbers to save now:*

🚑 *Medical emergency:* 144
🚒 *Fire:* 118
👮 *Police:* 117
🏔 *Mountain rescue (REGA):* 1414
☠️ *Poison control:* 145
🌍 *European emergency:* 112

🏥 *For non-emergencies:*
— *Medgate / Medi24:* 24/7 medical telephone advice, covered by most Swiss health insurance plans. Call before going to the emergency room.
— *Permanence / Notfallpraxis:* Walk-in urgent care clinics in most cities. Cheaper than hospital emergency rooms.

📋 *Documents to always have accessible:*
— Health insurance card (*Versicherungsausweis*)
— Residence permit
— Emergency contact in your home country

*One thing most newcomers don't know:*
Hospital emergency room visits in Switzerland can cost CHF 200–800+ even with insurance, depending on your franchise. Using the Medgate hotline first almost always results in the right next step — and sometimes avoids the visit entirely.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'kvg-deadline',
    text: `You have *90 days* from arriving in Switzerland to choose your health insurance. After that, your canton picks one for you — and you lose the right to choose.

❌ *If the canton assigns your insurer:*
— You lose the ability to compare premiums — differences between providers can be CHF 100–200/month
— You may be placed on the *standard model* (most expensive) rather than Telmed or HMO (10–25% cheaper)
— The franchise (deductible) is set at the minimum — CHF 300 — regardless of whether a higher one would save you money

📋 *The 90 days start from:*
— Your date of registration at the Einwohnerkontrolle, OR
— Your date of arrival with a valid permit (whichever is earlier)

*One thing almost nobody tells you:*
Your health insurance coverage is *retroactive* to your arrival date — so even if you sign up on day 89, you're covered from day 1. But you must pay premiums for all months since arrival.

*What to do now:*
Compare at *priminfo.admin.ch* (official federal tool), pick your franchise and model, and sign up online directly with the insurer. Takes 20 minutes.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
  {
    slug: 'l-permit',
    text: `The *L permit* is the most misunderstood Swiss residence permit — and getting it wrong at the start can affect your rights for years.

🟡 *What is the L permit?*
A short-term residence permit for stays *under 12 months*. It's tied directly to a specific employment contract — it expires when your contract ends.

*Who gets an L permit?*
— Workers on fixed-term contracts under 12 months
— Seasonal workers
— People doing an internship or project-based work

⚠️ *Why it matters more than people think:*
— An L permit *does not count* toward your 5 or 10 years for a C permit (permanent residence)
— It limits your ability to change employers
— If your contract is renewed and you stay longer than 12 months, you should switch to a B permit — this doesn't happen automatically

*Practical tip:*
If your employer offers you a 12-month contract, ask them to make it *open-ended* or at least *12 months + 1 day* — that puts you in B permit territory from the start and starts your residency clock correctly.

This is exactly the kind of detail that seems small but has long-term consequences for your Swiss residency path.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭`,
  },
];

async function postToTelegram(text) {
  const url = `https://api.telegram.org/bot${process.env.TELEGRAM_BOT_TOKEN}/sendMessage`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      chat_id: '@easyhelpswitzerland',
      text,
      parse_mode: 'Markdown',
      disable_web_page_preview: false,
    }),
  });
  const data = await res.json();
  if (!data.ok) throw new Error(`Telegram error: ${JSON.stringify(data)}`);
  return data.result;
}

export default async function handler(req, res) {
  if (req.method !== 'GET' && req.method !== 'POST') return res.status(405).end();

  const cronSecret = process.env.CRON_SECRET;
  if (cronSecret) {
    const ok = req.headers.authorization === `Bearer ${cronSecret}` || req.query.secret === cronSecret;
    if (!ok) return res.status(401).json({ error: 'Unauthorized' });
  }

  try {
    const day = Math.floor((Date.now() - new Date(new Date().getFullYear(), 0, 0)) / 86_400_000);
    // Offset by 10 so evening post is always a different topic than morning
    const post = POSTS_EN[(day + 10) % POSTS_EN.length];
    const result = await postToTelegram(post.text);
    return res.status(200).json({ success: true, lang: 'en', slot: 'evening', topic: post.slug, message_id: result.message_id });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: err.message });
  }
}
