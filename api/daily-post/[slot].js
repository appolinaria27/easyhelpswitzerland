/**
 * Dynamic route: /api/daily-post/morning
 *                /api/daily-post/afternoon
 *                /api/daily-post/evening
 *
 * FIX: Each slot gets a SEQUENTIAL index across all time so morning/afternoon/evening
 * never share the same post, and the cycle is as long as the post pool.
 * With 60 posts × 3 slots/day → any given post repeats only after ~20 days.
 */
import { existsSync, writeFileSync } from 'fs';

const POSTS_EN = [
  { slug: 'b-permit', text: `Most people moving to Switzerland don't realise there are *4 different residence permits* — and picking the wrong one to apply for wastes weeks.

🔵 *B permit* — the standard residence permit for most newcomers. Valid 1 year, renewable. Required if you're working, studying, or joining family here.

🟢 *C permit* — permanent residence. You can apply after 5 or 10 years depending on your nationality. No need to renew every year.

🟡 *L permit* — short-term, for contracts under 12 months. Often overlooked but important if your first contract is temporary.

🔴 *G permit* — for cross-border commuters who live abroad but work in Switzerland.

*Practical tips:*
— EU/EFTA citizens register at the Einwohnerkontrolle and get the B permit almost automatically after finding housing and a job.
— Non-EU citizens need employer sponsorship first — your company applies on your behalf before you arrive.
— The permit type affects your health insurance deadline, banking options, and family reunification rights — so it matters more than most people think.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/swiss-residence-permit` },

  { slug: 'anmeldung', text: `You have *14 days* after moving into your Swiss address to register at the Einwohnerkontrolle. Miss that deadline and you risk a fine — and delays to everything else that follows.

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

📖 Full guide: https://easyhelpswitzerland.ch/blog/anmeldung-zurich` },

  { slug: 'health-insurance', text: `Switzerland has *no public health insurance*. Every resident must buy private health insurance — and you have exactly *90 days* from arrival to do it.

If you miss the 90-day window, your canton assigns you a provider automatically. You lose the right to choose, and the assigned plan is rarely the cheapest.

🏥 *How KVG (basic insurance) works:*
— Basic coverage is identical across all providers — the law defines what's covered
— What differs is the *premium* (monthly cost) and the *customer service*
— Your premium depends on your canton, age, and chosen deductible (franchise)

💡 *How to pay less:*
— Choose a higher *franchise* (deductible): CHF 300 → 2,500. Higher franchise = lower monthly premium. Good if you're healthy and rarely visit the doctor.
— Choose a *Telmed* or *HMO* model instead of the standard model — 10–25% cheaper, but you call a hotline or visit a designated doctor first.
— Compare on *priminfo.admin.ch* — the official federal comparison tool.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/swiss-health-insurance` },

  { slug: 'apartment', text: `The Zürich rental market is one of the most competitive in Europe. Apartments receive 50–200 applications. Here's what actually gets you shortlisted.

🗂 *The documents Swiss landlords expect:*
— *Betreibungsregisterauszug* (debt collection register extract) — shows you have no outstanding debts. Get it from your municipality. Takes 1–3 days.
— Last 3 payslips or employment contract
— Copy of your residence permit or registration confirmation
— Letter of motivation (yes, really — Swiss landlords read them)

*What actually works:*
— Apply within hours of a listing going live — not days
— Write a short, personal motivation letter in German if possible
— Offer to pay 2–3 months deposit upfront if you're new to Switzerland without a Swiss rental history
— Use *homegate.ch*, *immoscout24.ch*, and *comparis.ch* — and set up instant alerts

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/apartment-zurich` },

  { slug: 'bank-account', text: `Opening a bank account in Switzerland as a newcomer is harder than it sounds — but there are options that work from day one.

Traditional banks like UBS often require a residence permit already. That creates a catch-22 for newly arrived expats.

🏦 *What actually works as a newcomer:*
— *Neon* or *Yuh* — Swiss digital banks, open an account with just your passport and registration confirmation. Takes 10 minutes on your phone.
— *Raiffeisen* — more approachable than the big banks for newcomers
— *PostFinance* — accepts customers at an earlier stage than most banks

📋 *What you'll need for any account:*
— Valid passport or ID
— Swiss address (Anmeldebestätigung)
— Residence permit or proof of employment

*Important:* Your Swiss employer will need a Swiss account to pay your salary — so don't wait.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭

📖 Full guide: https://easyhelpswitzerland.ch/blog/bank-account-switzerland` },

  { slug: 'cost-of-living', text: `Switzerland is expensive — but most people are surprised by *where* the money actually goes.

💸 *Realistic monthly costs in Zürich (single person):*
— Rent (1-bedroom): CHF 1,800–2,800
— Health insurance: CHF 350–500
— Groceries: CHF 400–600
— Transport (Halbtax + zones): CHF 150–200
— Phone: CHF 20–40
— Total baseline: *CHF 2,800–4,200/month*

*Where people overspend:*
— Eating out (CHF 25–45 per meal)
— Not using Halbtax — it pays for itself within 2–3 train trips
— Shopping at Migros/Coop when Lidl/Aldi are nearby (20–40% cheaper)

*Where Switzerland surprises:*
— Public transport is excellent and cheaper than owning a car
— Children's allowances add CHF 200–300/child/month

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'driving-licence', text: `If you move to Switzerland with a foreign driving licence, you have *12 months* to convert it — after that, it's no longer valid for driving here.

🚗 *The rules depend on where your licence is from:*

*EU/EEA licence* — Exchange it at the Strassenverkehrsamt. No test required in most cases.

*Non-EU licence (Ukraine, USA, etc.)* — You must take the Swiss theory test, complete driving lessons with a Swiss instructor, and pass the practical driving test.

📋 *What to bring:*
— Your foreign driving licence (original)
— Official translation if not in German/French/Italian
— Swiss registration confirmation
— Passport photo

*Common mistake:* Many newcomers wait too long. Appointment slots fill up fast in Zürich — book in month 1 or 2.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'family-reunion', text: `Moving to Switzerland as a family is one of the most complex relocation scenarios — the rules for your partner and children depend entirely on *your* permit type.

👨‍👩‍👧 *If you hold a B permit (EU/EFTA):*
Your spouse and children under 21 can join you relatively easily. They apply for their own B permits.

👨‍👩‍👧 *If you hold a B permit (non-EU):*
Your salary must meet a minimum threshold, you need adequate housing, and the application goes through the cantonal migration office.

📋 *Documents typically required:*
— Marriage certificate (translated and apostilled)
— Birth certificates for children
— Proof of adequate housing
— Proof of sufficient income

*What people often miss:*
— Children over 18 must apply independently
— The process can take 2–4 months — plan ahead

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'work-permit', text: `Getting a work permit in Switzerland as a non-EU citizen is a two-stage process — and most of the work falls on your *employer*.

*Stage 1 — Employer applies:*
Your Swiss employer submits a work permit application to the cantonal migration authority, proving no suitable Swiss or EU candidate was available. This can take 4–8 weeks.

*Stage 2 — You apply for a visa:*
Once approved, you apply for a D visa at the Swiss embassy in your home country. Then you travel and register within 14 days.

*The most common mistake:* Assuming you can come first and sort the permit later. The permit must be approved *before* you start working.

Switzerland has annual quotas for non-EU work permits — IT, healthcare, and finance have higher approval rates.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'taxes', text: `Swiss taxes surprise most newcomers — not because they're high, but because the *system* works completely differently from what you're used to.

🧾 *Two main systems:*

*Quellensteuer (withholding tax)* — if you're a foreign national without a C permit, your employer deducts tax directly from your salary each month.

*Standard tax return* — once you have a C permit or income exceeds CHF 120,000/year, you file a full tax return.

📍 *Switzerland taxes at 3 levels:*
— Federal (same everywhere)
— Cantonal (varies significantly — Zug pays far less than Geneva)
— Municipal (your specific town)

*Tip:* Your first Swiss tax bill can arrive 12–18 months after you arrive. Set aside 15–25% of your net salary as a buffer.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'c-permit', text: `The Swiss *C permit* (settlement permit) is the closest thing to permanent residency — and most foreigners don't realise they can already qualify.

🟢 *What the C permit gives you:*
— No annual renewal
— Work for any employer without restrictions
— Path to Swiss naturalisation
— Easier access to mortgages

⏱ *When can you apply?*
— EU/EFTA citizens: after *5 years*
— Non-EU from USA, Canada, Australia: after *5 years*
— Other non-EU: after *10 years*
— Spouses of Swiss citizens: after *5 years* of marriage and cohabitation

*The common mistake:* Not applying as soon as you qualify. The C permit doesn't arrive automatically — you must apply at the cantonal migration office.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'salary', text: `Switzerland has some of the highest salaries in the world — but the gap between what you're offered and what the market pays can be CHF 20,000–40,000 per year.

💰 *Realistic salary ranges in Zürich (gross, per year):*
— Software engineer: CHF 100,000–150,000
— Marketing manager: CHF 90,000–120,000
— Accountant: CHF 80,000–110,000
— Nurse: CHF 70,000–90,000
— Project manager: CHF 95,000–130,000

*Key resources:*
— *salarium.ch* — official federal salary comparison
— *lohncheck.ch* — community-reported salaries

⚠️ *What to watch:*
— Swiss employers rarely include the 13th month salary in the headline figure — always ask
— Benefits like meal allowances, transport, and pension contributions are all negotiable

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'schools', text: `Switzerland has one of the best public school systems in the world — and it's completely free for newcomers too.

🏫 *How it works:*
— Compulsory from age 4 (Kindergarten) to 15
— Schools are organised by Gemeinde — your child attends the school in your district
— Instruction is in the local language: German in Zürich, French in Geneva

📋 *To enrol your child:*
— Contact your local Schulkreis
— Bring: passport, Anmeldebestätigung, previous school records, vaccination records

*Good to know:*
— Most Swiss schools offer German integration classes (DaZ) for non-German speakers
— You can enrol mid-year — schools accept new students throughout the year
— Register your children at school at the same time as your Anmeldung

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'transport', text: `Switzerland's public transport is the most punctual and well-connected in the world.

🚆 *Two cards every Swiss resident should know:*

*Halbtax (Half-fare card)* — CHF 185/year. Gives you 50% off almost every train, bus, tram, and boat ticket in Switzerland. Pays for itself within 2–3 intercity trips.

*GA (General Abonnement)* — unlimited travel on the entire Swiss network. From CHF 3,860/year (2nd class). Worth it if you travel between cities regularly for work.

*What newcomers miss:*
— SBB app — buy tickets, see live departures, download tickets offline. Essential.
— *Supersaver tickets* — up to 70% off if you book 1–2 weeks in advance

*Practical start:* Buy the Halbtax in your first week at sbb.ch.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'german', text: `You don't need perfect German to live in Switzerland — but you need *enough*. Here's what actually works.

🗣 *The Swiss reality:*
Swiss German is spoken in everyday life, but standard German (Hochdeutsch) is used in writing and at official offices. As a newcomer, Hochdeutsch is what you need first.

*What works fast:*
— *Tandem language exchange* — find a German speaker who wants to learn your language. Free, and conversation is more effective than textbooks.
— *Volkshochschule (VHS)* — affordable courses (CHF 200–400), structured, in person.
— *Goethe Institut* — recognised certificates (A1–C2) if you need official proof of level.

🎯 *Realistic targets:*
— A2: enough for daily errands and basic appointments
— B1: required for C permit in most cantons
— B2: comfortable at work and socially

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'self-employed', text: `Becoming self-employed in Switzerland is simpler than most people expect — but the permit situation depends on your nationality.

💼 *For EU/EFTA citizens:*
Register with the AHV as self-employed, get your Anmeldung, and you're operating legally. Your B permit covers self-employment.

*For non-EU citizens:*
Your B permit tied to an employer doesn't automatically cover self-employment. You need a new permit application and must demonstrate financial viability.

📋 *Steps to set up (EU/EFTA):*
1. Register with cantonal commercial registry if revenue exceeds CHF 100,000/year
2. Register with AHV compensation office as self-employed (mandatory)
3. Arrange your own health insurance and pension contributions
4. Open a separate business bank account

*Surprise:* AHV contributions as self-employed are higher — around 10% of income.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'pension', text: `Switzerland has one of the most secure pension systems in the world — understand it from day one.

🏦 *The three pillars:*

*1st pillar — AHV (state pension):*
Mandatory for everyone. Contributions come automatically from your salary. If you leave Switzerland, you may claim a refund under certain conditions.

*2nd pillar — BVG (occupational pension):*
Mandatory if you earn over CHF 22,050/year. Both you and your employer contribute. Can be withdrawn when buying property or leaving Switzerland permanently.

*3rd pillar — Private pension (3a):*
Voluntary but powerful. Deposit up to CHF 7,056/year and deduct the *entire amount* from your taxable income — a real saving of CHF 1,500–2,500/year.

💡 *Open a 3a account as soon as you arrive.* VIAC, Frankly, and Finpension offer low-fee options.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'emergency', text: `Knowing who to call in an emergency before you need it is part of settling in properly.

📞 *Essential numbers to save now:*

🚑 Medical emergency: *144*
🚒 Fire: *118*
👮 Police: *117*
🏔 Mountain rescue (REGA): *1414*
☠️ Poison control: *145*
🌍 European emergency: *112*

🏥 *For non-emergencies:*
— *Medgate / Medi24:* 24/7 medical telephone advice, covered by most Swiss health plans. Call before going to the emergency room.
— *Permanence / Notfallpraxis:* Walk-in urgent care clinics. Cheaper than hospital emergency rooms.

*One thing most newcomers don't know:*
Hospital emergency room visits can cost CHF 200–800+ even with insurance. The Medgate hotline often avoids the visit entirely.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'kvg-deadline', text: `You have *90 days* from arriving in Switzerland to choose your health insurance. After that, your canton picks one for you.

❌ *If the canton assigns your insurer:*
— You lose the ability to compare premiums — differences can be CHF 100–200/month
— You may be placed on the standard model (most expensive) rather than Telmed or HMO (10–25% cheaper)

📋 *The 90 days start from:*
— Your date of registration at the Einwohnerkontrolle, OR your date of arrival with a valid permit

*One thing almost nobody tells you:*
Your health insurance coverage is *retroactive* to your arrival date. Even if you sign up on day 89, you're covered from day 1 — but you pay premiums for all months since arrival.

*What to do:* Compare at *priminfo.admin.ch*, pick your franchise and model, sign up online. Takes 20 minutes.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'l-permit', text: `The *L permit* is the most misunderstood Swiss residence permit — and getting it wrong at the start can affect your rights for years.

🟡 *What is the L permit?*
A short-term residence permit for stays *under 12 months*, tied directly to a specific employment contract.

⚠️ *Why it matters more than people think:*
— An L permit *does not count* toward your 5 or 10 years for a C permit (permanent residence)
— It limits your ability to change employers
— If you stay longer than 12 months, you should switch to a B permit — this doesn't happen automatically

*Practical tip:*
If your employer offers you a 12-month contract, ask them to make it open-ended or at least *12 months + 1 day* — that puts you in B permit territory from the start and starts your residency clock correctly.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'haftpflicht', text: `There is one insurance almost every Swiss person has — and most newcomers have never heard of it: *Haftpflichtversicherung* (personal liability insurance).

🛡 *What it covers:*
If you accidentally damage something or injure someone — a cracked phone, a flooded apartment from a burst washing machine hose, a bike accident — your liability insurance pays.

In Switzerland, you are personally responsible for damages you cause. Without this insurance, a single accident can cost CHF 10,000–100,000+.

💰 *Cost:* CHF 80–150/year — one of the best value insurances you can buy.

*Good providers to compare:*
— Assura, TCS, Mobiliar, Smile Direct
— Many offer combined household + liability packages

*Who needs it?*
Everyone. Renters especially — Swiss landlords often require proof of liability insurance before handing over keys.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'sunday-rules', text: `Switzerland has strict rules about Sundays and public holidays — and violating them can quickly make you unpopular with neighbours.

🤫 *The Ruhezeiten (quiet hours) you need to know:*
— No loud noise between *22:00 and 07:00* on any day
— No loud noise between *12:00 and 13:00* (lunchtime) on weekdays in many cantons
— Sundays: the entire day is considered Ruhezeit in most residential areas
— Specifically: no drilling, no lawn mowing, no loud music on Sundays

🛒 *Sunday shopping:*
Almost all shops are closed on Sundays in Switzerland. Exceptions:
— Petrol stations and shops inside them
— Shops at main train stations (HB Zürich is open 7 days)
— Some tourist areas with special permits

*Practical tip:* Do your grocery shopping on Saturday. Running out of something on Sunday means either a petrol station price or going without.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'hausarzt', text: `One of the first things you should do after arriving in Switzerland is find a *Hausarzt* (family doctor / GP) — before you need one.

🏥 *Why it matters:*
— In Switzerland, the GP is your first point of contact for all non-emergency medical issues
— If you have a Telmed or HMO health insurance model, you *must* call or visit your designated doctor first before seeing a specialist
— Finding a GP who accepts new patients can take weeks in larger cities

*How to find one:*
— Ask your employer or neighbours for a recommendation
— Search *doctorfmh.ch* or *healthinfo.ch* for GPs accepting new patients in your area
— Call directly — many doctors don't update online listings, so a phone call is faster

📋 *What to bring to your first appointment:*
— Health insurance card (Krankenkassenkarte)
— Residence permit or Anmeldebestätigung
— Vaccination record from your home country

*Tip:* If you're from Ukraine, many Ukrainian-speaking doctors practice in Zürich — don't hesitate to ask.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'household-insurance', text: `Most Swiss renters have two insurances for their home — and newcomers often skip both until something goes wrong.

🏠 *Hausratsversicherung (household contents insurance):*
Covers your furniture, electronics, clothing, and personal belongings against theft, fire, and water damage. If your apartment floods and your laptop is destroyed — this pays.

Cost: CHF 100–250/year depending on value of contents.

🛡 *Combined with Haftpflicht (liability):*
Most insurers offer a combined household + liability package for CHF 150–300/year. This is the standard approach in Switzerland.

*Good providers:*
— Mobiliar, Zurich, Helvetia, Assura, Smile Direct
— Compare at *comparis.ch*

⚠️ *What's NOT covered by basic household insurance:*
— Earthquakes (Switzerland has specific earthquake insurance available)
— Your car (needs separate vehicle insurance)
— Bikes over CHF 1,000 (add a bike rider to your policy)

*Tip:* Some landlords require proof of household insurance before you move in.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'recycling', text: `Switzerland takes recycling seriously — and if you don't follow the rules, you could face a fine or find your rubbish bags left uncollected on the street.

♻️ *The Swiss recycling system:*

🟡 *General waste (Kehrichtsäcke):*
You must buy official cantonal rubbish bags — in Zürich they're yellow and called "Zürisäcke". Available at Migros, Coop, and post offices. CHF 2–4 per bag. Putting waste in an unofficial bag means it won't be collected.

*What gets separated:*
— 🟦 Paper & cardboard — monthly collection or local drop points
— 🟩 Glass — colour-separated drop points (do NOT put in on Sundays — it's noisy)
— 🟫 PET bottles — at supermarket collection points
— 🔋 Batteries — at Migros/Coop service desks
— 💡 Electronics — at dedicated e-waste collection points or Interdiscount/Media Markt

*The rule most newcomers break:*
Putting recyclables in the yellow Zürisack is wrong — recyclables are free to dispose of at drop points. Only actual rubbish goes in the paid bag.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'dental', text: `Swiss dental care is excellent — and almost completely *not covered* by your basic health insurance.

🦷 *What basic KVG insurance covers:*
— Emergency dental treatment for accidents
— That's essentially it

*What you pay out of pocket:*
— Standard cleaning: CHF 150–250
— Filling: CHF 200–400
— Crown: CHF 1,200–2,500
— Implant: CHF 3,000–5,000+

💡 *How to manage dental costs:*
— Add *dental supplementary insurance* (Zusatzversicherung Zahn) to your policy. Cost: CHF 20–60/month. Worth it if you need regular treatment.
— Dental schools (Zahnärztliche Kliniken) in Zürich, Bern, and Basel offer treatments at 30–50% lower cost, performed by supervised students.
— Many Swiss residents cross the border to Germany or Austria for major dental work — prices are 40–60% lower.

*Tip:* Register with a dentist as soon as you arrive, even for a check-up. Waiting lists exist, and emergency slots fill fast.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'phone-plan', text: `Swiss mobile phone plans are significantly more expensive than in most European countries — unless you know which providers to use.

📱 *The budget-friendly options:*

*Aldi Talk* — uses the Swisscom network (best coverage in Switzerland). Plans from CHF 9.90/month. Excellent value for calls, SMS, and data.

*Yallo* — good data plans, slightly cheaper than the big three.

*Salt* — often has promotional prices, competitive for data-heavy users.

*Wingo* — owned by Swisscom, better prices than Swisscom itself with the same network.

❌ *What to avoid:*
— Standard Swisscom, Sunrise, or Salt contracts at full price — CHF 50–80/month for what you can get for CHF 15–25 elsewhere.

*Practical tip:*
Buy a SIM card on arrival at the airport or any supermarket. Aldi Talk starter packs are available at Aldi and online. You can keep your existing number through number portability.

*Roaming:* Switzerland is not in the EU — check roaming costs before travelling to EU countries with your Swiss SIM.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'rental-deposit', text: `When you rent an apartment in Switzerland, you'll pay a *rental deposit* — and the rules around it are different from most countries.

💰 *How the deposit works:*
— Maximum: 3 months' rent (by law)
— Must be held in a *separate blocked bank account* (Mietkautionskonto) in your name — the landlord cannot access it while you live there
— You can also use a *Mietkautionsversicherung* (deposit guarantee) as an alternative — companies like Firstcaution or SmartCaution charge a small annual fee instead of blocking cash

📋 *Getting your deposit back:*
— The landlord must return it within a reasonable time after you move out (typically 30–60 days)
— Deductions are only allowed for *actual damages* beyond normal wear and tear
— Normal wear and tear (small nail holes, slight scuffing) cannot be charged to you

*The final inspection (Wohnungsabnahme):*
This is a formal walkthrough with the landlord or property manager when you hand back keys. They document the apartment's condition room by room. Attend it, and bring someone with you if possible.

*Tip:* Take timestamped photos of every room on move-in day. This protects you at the end.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'kita-childcare', text: `Childcare in Switzerland is high quality — and one of the most expensive in the world. Planning ahead makes a significant difference.

👶 *Types of childcare:*

*Krippe (daycare centre):* Full-day care from around 3 months old. Run by private providers or municipalities. Cost: CHF 100–170/day before subsidies.

*Tageseltern (day parents):* A registered childminder who looks after a small group at home. Often more flexible and slightly cheaper.

*Tagesstruktur (school-age care):* Before and after school care for primary school children.

💡 *Subsidies — what most newcomers miss:*
Many cantons and municipalities heavily subsidise childcare based on household income. In Zürich, subsidised (subventionierte) Kita spots can reduce costs to CHF 20–70/day.

📋 *How to apply for a subsidised spot:*
1. Register on the municipal childcare platform (Kita-Navigator in Zürich)
2. Join the waiting list — waiting times can be 6–18 months
3. Provide proof of income and employment

*Tip:* Put your name on waiting lists as early as possible — ideally while still pregnant.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'pets', text: `Moving to Switzerland with a pet requires preparation — and a few rules that don't exist in most other countries.

🐾 *Bringing your pet to Switzerland:*

*Dogs and cats from EU countries:*
— Must have a valid EU pet passport
— Must be microchipped
— Must be vaccinated against rabies
— Must arrive via an official entry point

*From non-EU countries (Ukraine, USA, etc.):*
— Rabies titre blood test required (taken at least 30 days after vaccination)
— Waiting period of up to 3 months may apply depending on origin country
— Check current rules at *blv.admin.ch*

🐕 *Swiss dog rules you must know:*
— Dogs must be registered and microchipped within 3 months of arrival
— Register at *anis.ch* (the Swiss pet database)
— Annual dog tax (Hundesteuer) must be paid to your municipality — typically CHF 100–300/year
— Many cantons require dogs to be leashed in public areas

🐈 *Cats:* Must also be registered at anis.ch if born after 2016.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'job-search', text: `Finding a job in Switzerland is different from most countries — personal networks matter more than online applications.

💼 *Where to look:*
— *jobs.ch* and *jobup.ch* — the main Swiss job portals
— *LinkedIn* — essential for professional roles, especially in Zürich's finance and tech sectors
— Company career pages — many Swiss companies post roles there first
— *Xing* — still used in German-speaking Switzerland for professional networking

*The Swiss hiring reality:*
Switzerland has a strong culture of internal hiring and personal referrals. Many roles are filled before they're ever posted publicly.

🤝 *What actually works:*
— Attend industry events and Stammtische (networking evenings)
— Connect with Swiss recruiters on LinkedIn before you need them
— Apply with a Swiss-style CV: concise, with a photo, education listed first
— Write a personalised cover letter in German if possible — it shows effort

⚠️ *For non-EU citizens:*
Your employer needs to sponsor your permit. Focus on companies with international hiring experience and a track record of sponsoring permits.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'naturalization', text: `Swiss citizenship is one of the most coveted in the world — and the path to it is long but achievable.

🇨🇭 *The basic requirements for naturalisation:*
— Minimum *10 years* of residence in Switzerland (years between ages 8–18 count double)
— Hold a C permit (settlement permit)
— Demonstrate integration: language skills, knowledge of Swiss institutions, no criminal record, financial independence

🗣 *Language requirements:*
— Oral: B1 in the local language (German in Zürich)
— Written: A2 minimum, B1 preferred
— Some cantons require a formal language certificate

📋 *The process has three levels:*
1. *Federal level* — basic eligibility check
2. *Cantonal level* — language test, integration assessment
3. *Municipal level* — local committee interview in some areas

*What surprises people:*
— The process varies significantly between cantons and even municipalities
— Zürich canton is considered moderately straightforward
— Some municipalities hold a community vote — yes, your neighbours vote on your citizenship

*Timeline:* From application to citizenship: 1–3 years typically.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'pharmacy', text: `Swiss pharmacies (Apotheken) are more than just a place to pick up prescriptions — they're often your first stop for health advice.

💊 *What pharmacists can do in Switzerland:*
— Recommend and dispense many medications without a doctor's prescription
— Provide detailed advice on symptoms and treatment
— Issue a prescription in some cantons for certain medications

*Categories of medication in Switzerland:*
— Category A: Prescription only (Rezept erforderlich)
— Category B: Prescription required, but pharmacist can issue in some cases
— Category C & D: Available at the pharmacy without prescription
— Category E: Available in supermarkets and petrol stations (painkillers, vitamins)

🕒 *Opening hours:*
Most pharmacies are open Mon–Fri 08:00–18:30 and Saturday until 17:00. Emergency pharmacies (Notfallapotheke) are open nights and Sundays — find the nearest one at *notfallapotheke.ch*.

*Practical tip:* If you take regular medication from home, bring a 3-month supply and ask your new Swiss doctor to prescribe the Swiss equivalent. Some medications have different brand names in Switzerland.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'second-hand', text: `Switzerland has a thriving second-hand market — and for newcomers furnishing an apartment from scratch, it can save CHF 3,000–8,000.

🛋 *Where to find second-hand furniture and goods:*

*Online platforms:*
— *tutti.ch* — Switzerland's largest free classifieds site. Great for furniture, bikes, electronics.
— *ricardo.ch* — auction and fixed-price platform. Good for electronics and collectibles.
— Facebook Marketplace — very active in Zürich, especially in expat groups.

*Physical locations:*
— *Brocki* (second-hand shops run by Caritas and similar organisations) — fixed low prices, everything from furniture to kitchen equipment.
— *Flohmarkt* (flea markets) — Zürich has regular markets at Bürkliplatz, Kanzleiareal, and Milchbuck.

💡 *Best items to buy second-hand in Switzerland:*
— IKEA furniture in good condition (Swiss apartments are well maintained)
— Bikes — new bikes are expensive; a good second-hand bike costs CHF 80–300
— Kitchen appliances
— Children's clothing and toys

*Timing tip:* The best second-hand finds appear at month-end when people are moving. Check tutti.ch daily in the last week of the month.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'internet-utilities', text: `Setting up internet, electricity, and other utilities in Switzerland is straightforward — once you know who to contact.

🌐 *Internet:*
Switzerland has excellent fibre infrastructure. Providers to compare:
— *Sunrise*, *Salt*, *Swisscom* — the big three. Compare at *comparis.ch*
— Speeds of 500 Mbps to 10 Gbps are standard in cities
— Typical cost: CHF 40–70/month for standard fibre

📋 *What you need to sign up:*
— Swiss address and Anmeldebestätigung
— Bank account or credit card for direct debit

*Electricity and water:*
In most Swiss apartments, utilities are either included in the rent (Nebenkosten) or set up through your building. Your landlord or property manager will tell you which electricity provider serves your building — you rarely have a choice.

*Tip:* Ask before signing your lease whether heating, hot water, and electricity are included (Nebenkosten inbegriffen) or billed separately. This affects your real monthly costs significantly.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'etiquette', text: `Switzerland has a distinct culture — and understanding it makes daily life significantly easier and more comfortable.

🇨🇭 *Swiss rules that matter for everyday life:*

🤫 *Quiet hours are taken seriously.* No loud noise after 22:00, no drilling or mowing on Sundays. Violating these norms is one of the fastest ways to create problems with neighbours.

🤝 *Greeting neighbours:* Always say "Grüezi" (hello) in German Switzerland when you meet people in your building. Not greeting people is considered rude.

⏰ *Punctuality:* Being on time means arriving *exactly* on time — or 1–2 minutes early. "Swiss time" is not a stereotype, it's a real expectation in both professional and personal settings.

🗑 *Rubbish and recycling:* Follow the rules precisely. Putting the wrong things in the wrong bin is a genuine social offence.

💬 *Directness:* Swiss people tend to be reserved initially but are direct when they speak. Don't mistake quietness for unfriendliness — it often takes months to build friendships.

🍻 *Socialising:* Once you're in, you're in. Swiss people are loyal and generous friends who take time to warm up.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'remote-work', text: `Working remotely from Switzerland is possible — but it comes with tax and permit implications that most people don't anticipate.

💻 *For employees of Swiss companies working from home:*
Switzerland has no specific restrictions on remote work within the country. Your employment contract and permit cover you.

*For employees of foreign companies living in Switzerland:*
This is where it gets complex.

— If you're employed by a company abroad and living in Switzerland, you may be required to pay Swiss taxes on your income — even if your employer is based elsewhere.
— Switzerland has tax treaties with many countries to avoid double taxation.
— Your permit type affects what's allowed: a B permit holder working for a foreign employer may need to register as self-employed in Switzerland.

🌍 *The cross-border rule:*
If you spend more than 183 days/year in Switzerland, Switzerland claims tax rights on your global income — regardless of where your employer is based.

*Practical advice:*
Get advice from a Swiss tax advisor (Steuerberater) before setting up a remote working arrangement with a foreign employer. The rules vary significantly by country of employer.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'making-friends', text: `One of the hardest parts of relocating to Switzerland is building a social life — because Swiss culture makes it slower than most countries.

🤝 *Why it takes time:*
Swiss people tend to have close friendship groups formed in school and university. They're warm and welcoming, but don't form close friendships quickly. This is normal — not a rejection.

*Where expats actually meet people:*
— *Meetup.com* — Zürich has hundreds of active groups: hiking, languages, cooking, tech, board games
— *InterNations* — the largest expat network, regular events in Zürich
— *Tandem language exchange* — you get a friend and practice German simultaneously
— Sports clubs (Vereine) — Switzerland has a club for almost everything. Joining one is the single best long-term strategy for meeting Swiss people
— Work colleagues — make the effort to join after-work events

💡 *The Verein strategy:*
Switzerland has over 100,000 registered clubs (sport, culture, music, hobby). Joining one puts you in regular contact with the same people over months — the natural way Swiss friendships form.

*Tip:* Language is a real barrier. Even basic German dramatically improves your ability to connect outside expat circles.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'deregistration', text: `When you leave Switzerland, *deregistration* (Abmeldung) is a legal requirement — and it affects your tax, pension, and health insurance.

📋 *How to deregister:*
— Go to your local Einwohnerkontrolle (the same place you registered)
— Bring your passport, permit, and Anmeldebestätigung
— Declare your departure date and destination country
— They'll issue an *Abmeldebestätigung* — keep this document

💰 *Financial steps to take before leaving:*

*Pension (2nd pillar):* If you're leaving Switzerland permanently and moving to a non-EU/EFTA country, you can withdraw your full pension fund balance. This is taxed at a flat rate, but it's usually worth it. Apply to your pension fund at least 3 months before departure.

*Health insurance:* Cancel your KVG policy with 1 month notice before your departure date. You may receive a refund for the remaining months.

*Tax:* You're taxed in Switzerland up to your departure date. File your final tax declaration for the partial year.

*3rd pillar (3a):* Can be withdrawn when leaving Switzerland. Taxed separately at a flat rate.

*Tip:* Don't cancel your Swiss bank account immediately — keep it open for 3–6 months after leaving for outstanding refunds, deposits, and transactions.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'shopping-guide', text: `Switzerland's grocery shopping landscape is very different from most countries — and choosing the right supermarket saves CHF 200–400/month.

🛒 *The supermarket hierarchy:*

*Premium:*
— *Globus Delicatessa* — luxury goods, imported specialties. CHF CHF CHF
— *Coop* and *Migros* premium lines — Swiss quality, higher prices

*Standard:*
— *Migros* and *Coop* — the two dominant Swiss supermarkets. Good quality, moderate prices. Note: Migros doesn't sell alcohol.
— *Denner* — owned by Migros, lower prices, good for basics and alcohol

*Budget:*
— *Lidl* and *Aldi* — 20–40% cheaper than Migros/Coop for basic items. Quality is good. Increasingly common across Switzerland.

💡 *Smart shopping tips:*
— *Migros M-Budget* and *Coop Prix Garantie* lines offer significantly lower prices
— Buy seasonal Swiss produce — it's cheaper and better quality than imported
— Shop at market halls: Zürich's Markthalle and seasonal farmers' markets offer fresh produce at competitive prices
— The *Migros App* and *Coop App* both have digital loyalty programmes and weekly promotions

*Sunday problem:* Almost everything is closed. Do your main shop on Saturday.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'cantonal-differences', text: `Switzerland is 26 cantons — and they are genuinely very different from each other. Where you choose to live affects your taxes, cost of living, and daily culture significantly.

🗺 *Key differences between major cantons:*

💰 *Taxes (lowest to highest roughly):*
Zug → Schwyz → Nidwalden → Obwalden → Uri → Zürich → Bern → Geneva → Basel

*Zug* is famous for extremely low taxes and is home to many international companies and wealthy residents.

🏙 *Character:*
— *Zürich:* Financial capital, international, expensive, excellent infrastructure
— *Basel:* Pharma hub, art scene, near German and French borders
— *Bern:* Federal capital, slower pace, political community
— *Geneva:* International organisations (UN, WHO), French-speaking, very expensive
— *Zug:* Small, wealthy, tax-efficient, many expats

🏡 *Cost of living:*
Geneva and Zürich are the most expensive. Bern and smaller cities like Winterthur or Aarau offer significantly lower rents with easy rail access to the main centres.

*For commuters:* Swiss trains are so efficient that living 30–45 minutes from Zürich in a cheaper canton is a very common and practical choice.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'kids-allowances', text: `Switzerland pays monthly child allowances to families — and most newcomers don't claim everything they're entitled to.

👶 *Familienzulagen (child allowances):*
— For children under 16: CHF 200–310/month per child (varies by canton)
— For children 16–25 in education or vocational training: CHF 250–390/month per child

*Who gets it:*
Anyone working in Switzerland — including non-EU citizens — is entitled to child allowances. They are paid through your employer.

📋 *How to claim:*
— Ask your HR department or employer — they handle the application
— You need: child's birth certificate, residence documents, proof of employment
— Payments are usually included in your monthly salary

*Double-dipping rules:*
If both parents work, only one can claim the allowance per child. The parent with the higher cantonal rate gets priority.

🏦 *Cantonal differences:*
Some cantons pay significantly more than the federal minimum. Check your specific canton's rates at *famzug.ch*.

*Retroactive claims:*
You can claim retroactively for up to 5 years if you weren't receiving allowances you were entitled to. Many newcomers miss this.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'bike-zurich', text: `Zürich is one of Europe's most bike-friendly cities — and cycling is one of the best ways to get around without a car or paying for public transport.

🚲 *Cycling in Zürich:*
— Over 300km of designated cycling paths
— Bikes are allowed on many S-Bahn trains (outside peak hours)
— Vélo-sharing systems: *PubliBike* (subscription or pay-per-ride) available throughout the city

*How to get a bike:*
— New: CHF 400–1,500 at Veloplus, Stöckli, or online
— Second-hand: CHF 80–350 on *tutti.ch* or Brocki shops (Caritas)
— Check if your employer offers a bike leasing scheme — increasingly common

🔐 *Swiss bike theft is common:*
— Always use two locks: one for the frame, one for the wheel
— Register your bike at *velopass.ch* — helps recover stolen bikes
— Consider adding your bike to your household insurance if it's worth over CHF 1,000

📋 *Rules:*
— Lights are mandatory at night (front and rear)
— Helmet is not legally required for adults but strongly recommended
— Cycling on pavements is illegal in Switzerland

*Tip:* The Zürich VeloNacht (a summer night cycling event) is a great way to explore the city and meet people.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'integration', text: `Integration in Switzerland isn't just a cultural expectation — for some permit renewals and the C permit, it's a formal requirement.

📋 *What Swiss authorities assess when evaluating integration:*

— *Language skills:* Are you making progress in German (or French/Italian in other regions)?
— *Financial independence:* Are you supporting yourself without social assistance?
— *Respect for Swiss law and values:* No serious criminal offences
— *Participation in community life:* Employment, schooling, clubs, volunteering

📝 *The integration agreement (Integrationsvereinbarung):*
In some cantons, newcomers from non-EU countries sign a formal integration agreement within the first year. This sets specific goals: language level, employment, children in school.

*What actually helps your integration assessment:*
— Register for a language course and keep the certificates
— Join a local Verein (club) — it's documented participation
— Maintain stable employment
— Your children attending local school counts positively

*When it matters most:*
— B permit renewal after 5 years
— C permit application
— Naturalisation

*Tip:* Document your integration steps — course certificates, membership confirmations, employer letters. Swiss authorities appreciate paperwork.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'public-holidays', text: `Switzerland has public holidays at both national and cantonal level — meaning your days off depend on where you live.

🇨🇭 *National public holidays (all cantons):*
— New Year's Day (1 January)
— National Day (1 August — Swiss national holiday)
— Christmas Day (25 December)

*Plus most cantons observe:*
— Good Friday
— Easter Monday
— Ascension Day
— Whit Monday
— St. Stephen's Day (26 December)

📍 *Canton-specific holidays:*
— *Zürich:* Adds only a few — fewer holidays than most cantons
— *Ticino:* Observes many Catholic holidays — over 15 days total
— *Geneva:* Has its own specific days (Jeûne genevois, Restoration de la République)

💡 *What this means practically:*
— If you work across cantons (e.g., live in Zürich, work in Zug), the holidays of your *workplace* canton apply
— Most Swiss employment contracts specify holiday entitlement separately from public holidays — typically 20–25 days/year plus public holidays

*Tip:* The Swiss school holiday schedule also varies by canton — relevant if you have children.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'healthcare-specialist', text: `In Switzerland, seeing a specialist isn't as simple as booking an appointment directly — and your health insurance model determines how you access them.

🏥 *How specialist access works:*

*Standard insurance model:*
You can book appointments directly with any specialist in Switzerland. More expensive, but maximum flexibility.

*Telmed model (cheaper):*
You must call a medical hotline first (Medgate, Medi24). They assess your situation and, if necessary, refer you to a specialist. You cannot go directly.

*HMO model (cheapest):*
You visit a designated HMO centre (group practice) first. They refer you to specialists if needed.

📋 *Referral letter (Überweisung):*
In all models except standard, you'll receive a referral from your GP or Telmed doctor. Bring this to your specialist appointment.

*Important:* Specialist care in Switzerland is excellent but expensive. Even with insurance, you pay your annual franchise first (CHF 300–2,500) before insurance kicks in.

💡 *Planning tip:*
If you know you'll need specialist care regularly (ophthalmologist, dermatologist, physiotherapist), the standard insurance model may be worth the higher premium — it removes the referral step.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'moving-goods', text: `Moving your belongings to Switzerland? Here's what you need to know to avoid customs duties, delays, and unexpected costs.

📦 *Importing household goods duty-free:*

If you're moving to Switzerland for the *first time* and bringing your personal belongings, you can import them duty-free under the "Übersiedlungsgut" (household removal goods) exemption.

📋 *Conditions:*
— You must have owned the items for at least 6 months
— The goods must arrive within 2 years of your move
— You must provide proof of your new Swiss address and permit

🚛 *What's included:*
— Furniture, clothing, personal electronics, kitchen equipment
— Your car (if you've owned it for 12+ months — specific rules apply)

❌ *What's NOT included:*
— Commercial quantities of goods
— New items purchased specifically for the move
— Alcohol and tobacco above personal limits

*The process:*
Work with a customs broker (Zollagent) or your moving company — they handle the paperwork. A reputable mover experienced with Swiss customs makes this straightforward.

*Budget:* Moving company costs for an international move: CHF 2,000–8,000 depending on volume and origin.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'winter', text: `Winter in Switzerland is beautiful — and it requires practical preparation, especially if you're coming from a warmer country.

❄️ *What Swiss winters look like:*
— Zürich: December–February averages 0–5°C. Snow possible but not guaranteed in the city.
— Alps: Heavy snow from November. Roads can close.
— Ticino and southern Switzerland: Much milder, sometimes barely below freezing.

🧥 *Essential kit for Swiss winter:*
— Waterproof, insulated jacket
— Good quality waterproof boots — Zürich streets get icy and slippery
— Layers: Swiss buildings are well-heated, so you're constantly going in and out
— Ice scraper if you have a car

🚗 *Winter tyres:*
Not legally mandatory in Switzerland (unlike some EU countries), but *highly recommended*. If you're in an accident on snow or ice without winter tyres, your insurance can reduce the payout.

⚡ *Heating:*
Swiss apartments are typically centrally heated (district heating or building boiler). You rarely control individual room temperature. If it's too hot, you open the window — this is standard practice.

🎿 *The upside:*
Switzerland's ski resorts are among the best in the world and accessible by public transport from Zürich in 1–2 hours. A ski day typically costs CHF 70–120 including lift pass, equipment rental, and transport.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'leaving-zurich', text: `Moving *within* Switzerland from one municipality to another? This triggers a bureaucratic process most newcomers don't know about.

📋 *What you must do when you move within Switzerland:*

1. *Deregister* from your current municipality (Abmeldung) at the local Einwohnerkontrolle
2. *Register* at your new municipality (Anmeldung) within 14 days of moving in

*Important:* These are two separate steps at two different offices. The new municipality cannot process your registration until you have the Abmeldebestätigung from the old one. Do them in sequence.

📍 *What changes when you move cantons:*
— Your health insurance premium changes (based on new canton)
— Your tax rate changes (cantonal + municipal rates are different)
— Your car registration must be updated to the new canton (within 3 months)
— Your driver's licence address must be updated

🏥 *Health insurance:*
If moving to a different premium region, contact your insurer. They'll adjust your premium from the date of your new registration. This can save (or cost) CHF 50–150/month.

*Tip:* Moving at end of month reduces double-rent overlap. Most Swiss rental contracts end on the last day of the month.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },
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

  const slot = req.query.slot; // 'morning' | 'afternoon' | 'evening'

  // Deduplication: prevent double-posting if Vercel retries the cron
  const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD (UTC)
  const lockFile = `/tmp/ehs_${slot}_${today}.lock`;
  if (existsSync(lockFile)) {
    console.log(`[daily-post] Duplicate prevented: ${slot} already posted on ${today}`);
    return res.status(200).json({ success: true, skipped: true, reason: 'already_posted', slot, date: today });
  }

  try {
    // Sequential index: each slot gets its own unique post, cycling through all posts
    // before any post repeats. With 60 posts × 3 slots/day = ~20 days before repeat.
    const EPOCH_MS = new Date('2024-01-01').getTime();
    const daysSinceEpoch = Math.floor((Date.now() - EPOCH_MS) / 86_400_000);
    const slotNum = slot === 'morning' ? 0 : slot === 'afternoon' ? 1 : 2;
    const seqIndex = daysSinceEpoch * 3 + slotNum;
    const post = POSTS_EN[seqIndex % POSTS_EN.length];

    const result = await postToTelegram(post.text);

    // Write lock file AFTER successful post so a failed post can be retried
    writeFileSync(lockFile, new Date().toISOString());

    return res.status(200).json({ success: true, lang: 'en', slot, topic: post.slug, message_id: result.message_id });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: err.message });
  }
}
