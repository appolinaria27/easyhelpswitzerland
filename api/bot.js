/**
 * Unified Telegram bot handler — all 3 daily slots
 * ?slot=morning  → English, 08:00 UTC
 * ?slot=afternoon → Ukrainian, 13:00 UTC
 * ?slot=evening  → English +10 offset, 17:00 UTC
 */

const POSTS_EN = [
  { slug: 'b-permit', text: `Most people moving to Switzerland don't realise there are *4 different residence permits* — and picking the wrong one to apply for wastes weeks.

🔵 *B permit* — the standard residence permit for most newcomers. Valid 1 year, renewable. Required if you're working, studying, or joining family here.
🟢 *C permit* — permanent residence. You can apply after 5 or 10 years depending on your nationality. No need to renew every year.
🟡 *L permit* — short-term, for contracts under 12 months. Often overlooked but important if your first contract is temporary.
🔴 *G permit* — for cross-border commuters who live abroad but work in Switzerland.

*Practical tips:*
— EU/EFTA citizens register at the Einwohnerkontrolle and get the B permit almost automatically after finding housing and a job.
— Non-EU citizens need employer sponsorship first — your company applies on your behalf before you arrive.
— The permit type affects your health insurance deadline, banking options, and family reunification rights.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/swiss-residence-permit` },

  { slug: 'anmeldung', text: `You have *14 days* after moving into your Swiss address to register at the Einwohnerkontrolle. Miss that deadline and you risk a fine — and delays to everything else that follows.

The Anmeldung (municipal registration) is the first domino. Until it's done, you can't get your residence permit, open a bank account properly, or set up health insurance correctly.

📋 *What to bring:*
— Valid passport or ID
— Rental contract or written confirmation from your landlord
— For non-EU citizens: your work contract and employer details
— For families: marriage certificate and children's birth certificates (translated if necessary)

*Common mistakes:*
— Waiting until you feel "settled" — register on day 1 if possible.
— Booking the appointment too late — slots fill up fast in Zürich.
— Forgetting to de-register from your previous country.

After registration you'll receive your *Anmeldebestätigung* — keep it. You'll need it for your bank, insurance, and permit application.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/anmeldung-zurich` },

  { slug: 'health-insurance', text: `Switzerland has *no public health insurance*. Every resident must buy private health insurance — and you have exactly *90 days* from arrival to do it.

If you miss the 90-day window, your canton assigns you a provider automatically. You lose the right to choose, and the assigned plan is rarely the cheapest.

🏥 *How KVG (basic insurance) works:*
— Basic coverage is identical across all providers — the law defines what's covered
— What differs is the *premium* and the customer service
— Your premium depends on your canton, age, and chosen deductible (franchise)

💡 *How to pay less:*
— Choose a higher *franchise*: CHF 300 → 2,500. Higher franchise = lower monthly premium.
— Choose a *Telmed* or *HMO* model — 10–25% cheaper than the standard model.
— Compare on *priminfo.admin.ch* — the official federal comparison tool.

Children under 18 get *subsidies* in most cantons. Apply at your cantonal SVA office.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/swiss-health-insurance` },

  { slug: 'apartment', text: `The Zürich rental market is one of the most competitive in Europe. Apartments receive 50–200 applications. Here's what actually gets you shortlisted.

🗂 *Documents Swiss landlords expect:*
— *Betreibungsregisterauszug* — proves you have no outstanding debts. Get it from your municipality (1–3 days).
— Last 3 payslips or employment contract
— Copy of your residence permit or registration confirmation
— Letter of motivation (Swiss landlords really do read them)

*What actually works:*
— Apply within hours of a listing going live — not days
— Write a short motivation letter in German if possible
— Offer 2–3 months deposit upfront if you have no Swiss rental history
— Use *homegate.ch*, *immoscout24.ch*, and *comparis.ch* with instant alerts

*Avoid:* Applying without the Betreibungsauszug — it's the number one reason applications are rejected.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/apartment-zurich` },

  { slug: 'bank-account', text: `Opening a bank account in Switzerland as a newcomer is harder than it sounds — but there are options that work from day one.

Traditional banks like UBS often require a residence permit or registration confirmation first. That creates a catch-22 for newly arrived expats.

🏦 *What actually works as a newcomer:*
— *Neon* or *Yuh* — Swiss digital banks. Open with just your passport and registration confirmation. Takes 10 minutes on your phone.
— *Raiffeisen* — more approachable than the big banks, especially outside Zürich
— *PostFinance* — accepts customers at an earlier stage than most banks

📋 *What you'll need:*
— Valid passport or ID
— Swiss address (Anmeldebestätigung)
— Residence permit or at least proof of employment

Your Swiss employer needs a Swiss account to pay your salary — don't wait. Set this up in your first week.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/bank-account-switzerland` },

  { slug: 'cost-of-living', text: `Switzerland is expensive — but most people are surprised by *where* the money actually goes.

💸 *Realistic monthly costs in Zürich (single person):*
— Rent (1-bedroom): CHF 1,800–2,800
— Health insurance: CHF 350–500
— Groceries: CHF 400–600
— Transport (Halbtax + zones): CHF 150–200
— Phone: CHF 20–40 (Aldi Talk, Yallo, or Salt)
— Total baseline: *CHF 2,800–4,200/month*

*Where people overspend:*
— Eating out (CHF 25–45 per meal) — cooking saves CHF 400+/month
— Not using Halbtax — it pays for itself within 2–3 train trips
— Shopping at Migros/Coop when Lidl/Aldi are nearby (20–40% cheaper)

*Where Switzerland surprises you:*
— Excellent public transport — cheaper than owning a car
— Children's allowances (Kinderzulagen): CHF 200–300/child/month

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/cost-of-living-switzerland` },

  { slug: 'driving-licence', text: `If you move to Switzerland with a foreign driving licence, you have *12 months* to convert it — after that it's no longer valid here.

🚗 *Rules depend on where your licence is from:*

*EU/EEA licence* — exchange it at the cantonal Strassenverkehrsamt. No test required in most cases.

*Non-EU licence (Ukraine, USA, etc.):*
— Swiss theory test
— Minimum driving lessons with a Swiss instructor
— Practical driving test

📋 *What to bring:*
— Your foreign licence (original)
— Official translation if not in German/French/Italian
— Swiss registration confirmation
— Passport photo + fee (CHF 50–80)

*Common mistake:* Many newcomers wait too long. Book your appointment in month 1 or 2 — slots fill up fast in Zürich.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/driving-licence-switzerland` },

  { slug: 'family-reunion', text: `Moving to Switzerland as a family is one of the most complex relocation scenarios — the rules for your partner and children depend entirely on *your* permit type.

👨‍👩‍👧 *B permit (EU/EFTA):* Your spouse and children under 21 can join you with relatively little friction. They apply for their own B permits.

👨‍👩‍👧 *B permit (non-EU):* Family reunion requires approval. Your salary must meet a minimum threshold and you need adequate housing.

📋 *Documents typically required:*
— Marriage certificate (officially translated and apostilled)
— Birth certificates for children
— Rental contract and proof of sufficient income
— Health insurance confirmation for all family members

*What people often miss:*
— Children over 18 cannot be included — they must apply independently
— The process can take 2–4 months — plan well ahead

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/moving-switzerland-family` },

  { slug: 'work-permit', text: `Getting a work permit in Switzerland as a non-EU citizen is a two-stage process — and most of the work falls on your *employer*, not you.

*Stage 1 — Employer applies on your behalf:*
Your Swiss employer submits an application to the cantonal migration authority, proving no suitable Swiss or EU candidate was available. This takes 4–8 weeks.

*Stage 2 — You apply for a D visa:*
Once approved, you apply at the Swiss embassy in your home country. Then you travel to Switzerland and register within 14 days.

📋 *What your employer needs:*
— Proof of job advertisement in Switzerland and EU
— Your CV and qualifications
— Signed employment contract
— Proof the salary meets the standard Swiss rate

*The most common mistake:* Assuming you can come first and sort the permit later. The permit must be approved *before* you start working.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭
📖 Full guide: https://easyhelpswitzerland.ch/blog/work-permit-switzerland` },

  { slug: 'taxes', text: `Swiss taxes surprise most newcomers — not because they're high, but because the *system* is completely different from what you're used to.

🧾 *Two main systems:*

*Quellensteuer (withholding tax)* — if you're a foreign national without a C permit, your employer deducts tax directly from your salary each month.

*Standard tax return* — once you have a C permit or income exceeds CHF 120,000/year.

📍 *Switzerland taxes at 3 levels:*
— Federal (same everywhere)
— Cantonal (varies a lot — Zug pays far less than Geneva)
— Municipal (your town adds its own rate)

*What newcomers often don't know:*
— Your first Swiss tax bill can arrive 12–18 months after you arrive
— You can deduct professional expenses, commuting costs, and health insurance premiums
— Set aside 15–25% of your net salary as a buffer

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'c-permit', text: `The Swiss *C permit* (settlement permit) is the closest thing to permanent residency — and most foreigners don't realise they can already qualify.

🟢 *What the C permit gives you:*
— No annual renewal
— Right to work for any employer without restrictions
— Path to Swiss naturalisation
— Easier access to mortgages and long-term financial products

⏱ *When can you apply?*
— *EU/EFTA citizens:* after *5 years*
— *Non-EU from USA, Canada, Australia:* after *5 years*
— *Other non-EU citizens:* after *10 years*
— *Spouses of Swiss citizens:* after *5 years* of marriage

📋 *What you need to show:*
— Continuous residence (no long gaps)
— Language skills (A2–B1 depending on canton)
— Financial independence
— Integration into Swiss society

The C permit doesn't arrive automatically — you must apply at the cantonal migration office.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'salary', text: `Switzerland has some of the highest salaries in the world — but the gap between what you're offered and what the market pays can be CHF 20,000–40,000 per year.

💰 *Realistic salary ranges in Zürich (gross, per year):*
— Software engineer: CHF 100,000–150,000
— Marketing manager: CHF 90,000–120,000
— Accountant: CHF 80,000–110,000
— Nurse: CHF 70,000–90,000
— Project manager: CHF 95,000–130,000

*Check your number:*
— *salarium.ch* — official federal tool
— *lohncheck.ch* — community-reported salaries
— *jobs.ch* salary guide — updated annually

⚠️ *Watch out for:*
— Swiss employers rarely include the 13th month salary in their headline figure — always ask
— Quellensteuer takes 15–35% depending on salary and canton
— Benefits (meal allowances, transport, pension, remote work) are all negotiable

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'schools', text: `Switzerland has one of the best public school systems in the world — completely free, including for newcomers.

🏫 *How Swiss schools work:*
— Compulsory from age 4 (Kindergarten) to 15
— Organised by *Gemeinde* — your child attends the school in your district
— Instruction in the local language: German in Zürich, French in Geneva, Italian in Ticino

📋 *To enrol your child:*
— Contact your local school district (Schulkreis) or the school directly
— Bring: passport, registration confirmation, previous school records, vaccination records
— No entrance exams for primary school

*What newcomers often ask:*
— *Language support:* Most schools offer German integration classes (DaZ).
— *Timing:* You can enrol mid-year.
— *International schools:* Private, CHF 25,000–40,000/year — only worth it if your stay is short-term.

Tip: Register your children at school at the same time as your Anmeldung.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'transport', text: `Switzerland's public transport is the most punctual and well-connected in the world. Once you understand it, owning a car in the city becomes optional.

🚆 *Two cards every Swiss resident should know:*

*Halbtax (Half-fare card)* — CHF 185/year. Gives 50% off almost every train, bus, tram, and boat in Switzerland. If you take 2 intercity trips per month, it pays for itself immediately.

*GA (General Abonnement)* — unlimited travel on the entire Swiss public transport network. From CHF 3,860/year (2nd class). Worth it if you travel between cities regularly.

*What newcomers miss:*
— SBB app — buy tickets, see live departures, download offline. Essential.
— *Supersaver tickets* — up to 70% off when booked 1–2 weeks in advance
— Night trains and S-Bahn run until 1–2am on weekends

Buy the Halbtax in your first week at sbb.ch.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'german', text: `You don't need perfect German to live in Switzerland. But you need *enough* — and the fastest way there isn't a language school.

🗣 *The Swiss reality:*
Swiss German (*Schweizerdeutsch*) is spoken daily, but standard German (*Hochdeutsch*) is used in written form and in official and professional settings. Hochdeutsch is what you need first.

*What actually works fast:*
— *Tandem language exchange* — free, and conversation practice beats textbooks. Apps: Tandem, HelloTalk.
— *Volkshochschule (VHS)* — cantonal adult education centres. CHF 200–400 per course, structured, in person.
— *Goethe Institut* — more expensive but recognised certificates (A1–C2).
— *Duolingo + YouTube* — good for the first 2–3 months. Not enough on its own.

🎯 *Realistic targets:*
— A2: enough for daily errands
— B1: required for C permit in most cantons
— B2: comfortable at work and in social settings

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'self-employed', text: `Becoming self-employed in Switzerland is simpler than most people expect — but the permit situation depends entirely on your nationality.

💼 *For EU/EFTA citizens:*
Register with the AHV (social insurance) as self-employed, complete your Anmeldung — and you're operating legally. Your B permit covers self-employment.

*For non-EU citizens:*
— On a B permit tied to an employer: you need a new permit application before going self-employed
— You must demonstrate financial viability and economic benefit to Switzerland

📋 *Steps (EU/EFTA):*
1. Register with the cantonal commercial registry if revenue exceeds CHF 100,000/year
2. Register with the AHV compensation office as self-employed (mandatory)
3. Arrange your own health insurance and pension contributions
4. Open a separate business bank account

*Surprises:*
— Under CHF 100,000/year revenue — no formal company needed
— AHV contributions as self-employed: ~10% of income
— VAT registration required above CHF 100,000/year

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'pension', text: `Switzerland has one of the most secure pension systems in the world — but as a newcomer, you need to understand it from day one.

🏦 *The three pillars:*

*1st pillar — AHV (state pension):*
Mandatory for everyone. Contributions deducted automatically from your salary. If you leave Switzerland before retirement, you may claim a refund under certain conditions.

*2nd pillar — BVG (occupational pension):*
Mandatory if you earn over CHF 22,050/year. Both you and your employer contribute. Can be withdrawn when buying property or leaving Switzerland permanently.

*3rd pillar — Private pension (3a):*
Voluntary, but one of the best tax tools in Switzerland. Deposit up to CHF 7,056/year and deduct the *entire amount* from your taxable income. Real tax saving: CHF 1,500–2,500/year.

💡 Open a *3a account* as soon as you arrive — VIAC, Frankly, or Finpension have low fees.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'emergency', text: `Switzerland is one of the safest countries in the world — but knowing who to call before you need it is part of settling in properly.

📞 *Essential numbers to save now:*
🚑 Medical emergency: *144*
🚒 Fire: *118*
👮 Police: *117*
🏔 Mountain rescue (REGA): *1414*
☠️ Poison control: *145*
🌍 European emergency: *112*

🏥 *For non-emergencies:*
— *Medgate / Medi24:* 24/7 medical phone advice, covered by most Swiss health insurance. Call before going to the emergency room — it saves time and money.
— *Permanence / Notfallpraxis:* Walk-in urgent care clinics in most cities. Cheaper than hospital emergency rooms.

Always have your health insurance card (*Versicherungsausweis*) and residence permit accessible.

Hospital visits can cost CHF 200–800+ even with insurance. The Medgate hotline almost always helps you choose the right next step.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'kvg-deadline', text: `You have *90 days* from arriving in Switzerland to choose your health insurance. After that, your canton picks one for you — and you lose the right to choose.

❌ *If the canton assigns your insurer:*
— You can't compare premiums — differences between providers can be CHF 100–200/month
— You may get the *standard model* (most expensive) instead of Telmed or HMO (10–25% cheaper)
— Franchise is set at the minimum (CHF 300) regardless of what would save you money

📋 *The 90 days start from:*
— Your date of registration at the Einwohnerkontrolle, OR
— Your date of arrival with a valid permit (whichever is earlier)

*Almost nobody tells you this:* Coverage is *retroactive* to your arrival date — sign up on day 89 and you're covered from day 1. But you pay premiums for all months since arrival.

Compare at *priminfo.admin.ch*, pick your franchise and model, sign up directly with the insurer. Takes 20 minutes.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'l-permit', text: `The *L permit* is the most misunderstood Swiss residence permit — and getting it wrong at the start can affect your rights for years.

🟡 *What is the L permit?*
A short-term permit for stays *under 12 months*. Tied directly to a specific contract — expires when your contract ends.

*Who gets an L permit?*
— Workers on fixed-term contracts under 12 months
— Seasonal workers
— Internships or project-based work

⚠️ *Why it matters more than people think:*
— An L permit *does not count* toward the 5 or 10 years needed for a C permit
— It limits your ability to change employers
— If your contract is renewed beyond 12 months, switch to a B permit — this doesn't happen automatically

*Practical tip:*
If your employer offers a 12-month contract, ask them to make it *open-ended* or *12 months + 1 day* — that gets you into B permit territory from the start and starts your residency clock correctly.

Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭` },
];

const POSTS_UK = [
  { slug: 'b-permit', text: `Більшість людей, які переїжджають до Швейцарії, не знають, що існує *4 різних типи дозволів на проживання* — і вибір неправильного коштує тижнів очікування.

🔵 *Дозвіл B* — стандартний дозвіл для більшості новоприбулих. Дійсний 1 рік, поновлюється.
🟢 *Дозвіл C* — постійне проживання. Після 5 або 10 років залежно від громадянства.
🟡 *Дозвіл L* — короткостроковий, для контрактів до 12 місяців.
🔴 *Дозвіл G* — для прикордонних працівників, які живуть за кордоном.

*Практичні поради:*
— Громадяни ЄС/ЄАВТ реєструються в Einwohnerkontrolle та отримують B майже автоматично.
— Громадяни не з ЄС потребують спонсорства роботодавця — компанія подає заявку до вашого приїзду.
— Тип дозволу впливає на терміни медичного страхування, банківські можливості та возз'єднання сім'ї.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/swiss-residence-permit` },

  { slug: 'anmeldung', text: `У вас є *14 днів* після в'їзду за швейцарською адресою, щоб зареєструватися в Einwohnerkontrolle. Пропустите — ризикуєте штрафом та затримками в усьому, що йде далі.

Анмельдунг — перша ланка в ланцюжку. Без неї не можна отримати дозвіл, відкрити рахунок або оформити страховку.

📋 *Що взяти:*
— Дійсний паспорт або посвідчення особи
— Договір оренди або підтвердження від орендодавця
— Для не-ЄС: трудовий договір та дані роботодавця
— Для сімей: свідоцтво про шлюб та свідоцтва про народження дітей

*Поширені помилки:*
— Чекати, поки «влаштуєтесь» — реєструйтесь у перший день.
— Записуватись пізно — місця в Цюриху заповнюються швидко.
— Забути скасувати реєстрацію в попередній країні.

Після реєстрації зберігайте *Anmeldebestätigung* — він знадобиться для банку, страхування та дозволу.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/anmeldung-zurich` },

  { slug: 'health-insurance', text: `У Швейцарії *немає державного медичного страхування*. Кожен резидент зобов'язаний придбати приватну страховку — і у вас є рівно *90 днів* з моменту приїзду.

Якщо пропустити термін, кантон сам призначить страховика. Ви втратите право вибору, а призначений план рідко буває найдешевшим.

🏥 *Як працює KVG (базове страхування):*
— Базове покриття однакове у всіх — закон визначає, що покривається
— Відрізняється премія та якість обслуговування
— Ваша премія залежить від кантону, віку та франшизи

💡 *Як платити менше:*
— Вища *франшиза* (CHF 300 → 2 500) = нижча щомісячна премія
— Модель *Telmed* або *HMO* — на 10–25% дешевше
— Порівнюйте на *priminfo.admin.ch*

Діти до 18 років отримують субсидії в більшості кантонів.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/swiss-health-insurance` },

  { slug: 'apartment', text: `Ринок оренди в Цюриху — один із найконкурентніших у Європі. На одну квартиру надходить 50–200 заявок.

🗂 *Документи, які очікують орендодавці:*
— *Betreibungsregisterauszug* — підтверджує відсутність боргів (1–3 дні в муніципалітеті)
— Останні 3 розрахункові листки або трудовий договір
— Копія дозволу або підтвердження реєстрації
— Мотиваційний лист (орендодавці їх читають)

*Що насправді працює:*
— Подавайте заявку протягом кількох годин після появи оголошення
— Напишіть мотиваційний лист німецькою — навіть базовий рівень — це плюс
— Запропонуйте 2–3 місячних депозити наперед
— Використовуйте *homegate.ch*, *immoscout24.ch*, *comparis.ch* з миттєвими сповіщеннями

Найпоширеніша причина відмови — відсутність Betreibungsauszug.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/apartment-zurich` },

  { slug: 'bank-account', text: `Відкрити рахунок у Швейцарії як новоприбулий складніше, ніж здається — але є варіанти, які працюють з першого дня.

Традиційні банки (UBS тощо) часто вимагають дозвіл або підтвердження реєстрації. Це замкнуте коло для нових емігрантів.

🏦 *Що насправді працює:*
— *Neon* або *Yuh* — відкриваються лише з паспортом та Anmeldebestätigung. 10 хвилин на телефоні.
— *Raiffeisen* — доступніший для новоприбулих, особливо за межами Цюриха
— *PostFinance* — приймає клієнтів раніше, ніж більшість банків

📋 *Що знадобиться:*
— Дійсний паспорт або посвідчення
— Швейцарська адреса (Anmeldebestätigung)
— Дозвіл або підтвердження працевлаштування

Ваш роботодавець потребує швейцарського рахунку для зарплати — не зволікайте.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/bank-account-switzerland` },

  { slug: 'cost-of-living', text: `Швейцарія дорога — але більшість людей дивуються, *куди* насправді йдуть гроші.

💸 *Реальні щомісячні витрати в Цюриху (одна людина):*
— Оренда (1 кімната): CHF 1 800–2 800
— Медичне страхування: CHF 350–500
— Продукти: CHF 400–600
— Транспорт (Halbtax + зони): CHF 150–200
— Телефон: CHF 20–40 (Aldi Talk, Yallo, Salt)
— Базова сума: *CHF 2 800–4 200/місяць*

*Де люди витрачають зайве:*
— Їжа поза домом (CHF 25–45) — готування економить CHF 400+/місяць
— Відмова від Halbtax — окупається за 2–3 поїздки
— Migros/Coop замість Lidl/Aldi (20–40% різниця)

*Де Швейцарія дивує:*
— Відмінний транспорт — дешевше за авто
— Kinderzulagen: CHF 200–300/дитину/місяць

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/cost-of-living-switzerland` },

  { slug: 'driving-licence', text: `Якщо ви переїхали до Швейцарії з іноземними правами, у вас є *12 місяців* на їх заміну — після цього вони недійсні.

🚗 *Правила залежать від країни видачі:*

*Права ЄС/ЄЕЗ* — обміняйте в Strassenverkehrsamt. Зазвичай без іспитів.

*Права не з ЄС (Україна, США тощо):*
— Теоретичний іспит за швейцарськими правилами
— Уроки їзди зі швейцарським інструктором
— Практичний іспит

📋 *Що взяти:*
— Оригінал іноземних прав
— Офіційний переклад (якщо не нім./фр./іт.)
— Підтвердження реєстрації в Швейцарії
— Фото + внесок (CHF 50–80)

Записуйтесь у перші 1–2 місяці — місця заповнюються.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/driving-licence-switzerland` },

  { slug: 'family-reunion', text: `Переїзд до Швейцарії всією сім'єю — один із найскладніших сценаріїв, адже правила залежать від *вашого* типу дозволу.

👨‍👩‍👧 *Дозвіл B (ЄС/ЄАВТ):* Чоловік/дружина та діти до 21 року можуть приєднатися відносно просто. Вони подають заявку на власний B та реєструються.

👨‍👩‍👧 *Дозвіл B (не ЄС):* Потрібен дозвіл. Ваша зарплата має відповідати мінімуму, потрібне відповідне житло.

📋 *Типові документи:*
— Свідоцтво про шлюб (перекладене та апостильоване)
— Свідоцтва про народження дітей
— Договір оренди та підтвердження доходу
— Підтвердження медичного страхування для всіх

*Важливо:*
— Діти старше 18 подають заявку самостійно
— Процес може тривати 2–4 місяці — плануйте заздалегідь

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/moving-switzerland-family` },

  { slug: 'work-permit', text: `Отримання дозволу на роботу для громадян не з ЄС — двоетапний процес, і більша частина роботи лягає на *роботодавця*.

*Етап 1 — Роботодавець подає заявку:*
Ваш швейцарський роботодавець звертається до кантонального міграційного органу, доводячи відсутність підходящого кандидата зі Швейцарії або ЄС. Займає 4–8 тижнів.

*Етап 2 — Ви подаєте заявку на візу D:*
Після схвалення — в посольстві Швейцарії у вашій країні. Потім реєстрація протягом 14 днів після приїзду.

📋 *Що готує роботодавець:*
— Підтвердження оголошення вакансії в Швейцарії та ЄС
— Ваше резюме та кваліфікації
— Підписаний трудовий договір
— Відповідність зарплати стандартній ставці

Найпоширеніша помилка: приїхати спочатку, а потім вирішувати питання дозволу. Дозвіл має бути *до початку роботи*.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭
📖 Повний посібник: https://easyhelpswitzerland.ch/blog/work-permit-switzerland` },

  { slug: 'taxes', text: `Швейцарські податки дивують більшість новоприбулих — не через розмір, а тому що *система* повністю відрізняється.

🧾 *Дві основні системи:*

*Quellensteuer* — якщо ви іноземець без дозволу C, роботодавець утримує податок щомісяця. Декларація зазвичай не потрібна.

*Стандартна декларація* — після отримання C або при доході понад CHF 120 000/рік.

📍 *3 рівні оподаткування:*
— Федеральний (однаковий скрізь)
— Кантональний (Цуг значно менший за Женеву)
— Муніципальний (ваше місто додає свою ставку)

*Що важливо знати:*
— Перший рахунок може надійти через 12–18 місяців після приїзду
— Можна відраховувати витрати на проїзд, страхування та проф. витрати
— Відкладайте 15–25% від чистої зарплати як резерв

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'c-permit', text: `Швейцарський *дозвіл C* — найближче до постійного виду на проживання, і більшість іноземців не усвідомлюють, що вже можуть претендувати.

🟢 *Що дає дозвіл C:*
— Не потрібно поновлювати щороку
— Право працювати на будь-якого роботодавця
— Шлях до швейцарського громадянства
— Легший доступ до іпотеки

⏱ *Коли подавати заявку:*
— Громадяни ЄС/ЄАВТ та деяких інших країн: після *5 років*
— Інші громадяни не з ЄС: після *10 років*
— Подружжя громадян Швейцарії: після *5 років* шлюбу

📋 *Що підтвердити:*
— Безперервне проживання
— Знання мови (A2–B1)
— Фінансова самостійність та інтеграція

Дозвіл C не надається автоматично — потрібно самостійно звертатися до міграційного управління.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'salary', text: `Швейцарія — одна з країн із найвищими зарплатами у світі, але різниця між пропозицією та ринком може становити CHF 20 000–40 000 на рік.

💰 *Реальні діапазони в Цюриху (брутто/рік):*
— Розробник ПЗ: CHF 100 000–150 000
— Менеджер з маркетингу: CHF 90 000–120 000
— Бухгалтер: CHF 80 000–110 000
— Медична сестра: CHF 70 000–90 000
— Проєктний менеджер: CHF 95 000–130 000

*Корисні ресурси:*
— *salarium.ch* — офіційний федеральний інструмент
— *lohncheck.ch* — зарплати від спільноти
— *jobs.ch* — щорічний звіт

⚠️ *Важливо:*
— 13-та зарплата рідко включена в заголовну цифру — завжди уточнюйте
— Quellensteuer: 15–35% залежно від доходу та кантону
— Пільги (їжа, транспорт, пенсія, remote) — все підлягає переговорам

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'schools', text: `Швейцарія має одну з найкращих систем державних шкіл у світі — і вона повністю безкоштовна, навіть для новоприбулих.

🏫 *Як працюють швейцарські школи:*
— Обов'язкова освіта з 4 до 15 років
— Школи за *Gemeinde* — дитина відвідує школу свого району
— Мова навчання: в Цюриху — німецька

📋 *Для запису:*
— Зв'яжіться з місцевим шкільним округом
— Візьміть: паспорт, підтвердження реєстрації, шкільні документи, картку щеплень
— Вступних іспитів немає

*Що запитують новоприбулі:*
— Мовна підтримка: більшість шкіл пропонують DaZ (німецька як друга мова)
— Можна зарахувати дитину протягом навчального року
— Міжнародні школи: CHF 25 000–40 000/рік

Записуйте дітей одночасно з вашим Анмельдунгом.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'transport', text: `Система громадського транспорту Швейцарії — найпунктуальніша у світі. Як тільки зрозумієте її — авто в місті стає непотрібним.

🚆 *Дві картки, які треба знати:*

*Halbtax* — CHF 185/рік. Знижка 50% на майже всі потяги, автобуси, трамваї. Окупається за 2 міжміські поїздки на місяць.

*GA (Загальний абонемент)* — необмежені поїздки по всій Швейцарії. Від CHF 3 860/рік. Вартує, якщо часто їздите між містами.

*Що пропускають новоприбулі:*
— Додаток SBB — квитки, розклад у реальному часі. Обов'язковий.
— *Supersaver* квитки — до 70% знижки при бронюванні за 1–2 тижні
— Нічні потяги та S-Bahn до 1–2 ночі у вихідні

Купіть Halbtax у перший тиждень на sbb.ch.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'german', text: `Вам не потрібна ідеальна німецька, щоб жити в Швейцарії. Але потрібен *достатній рівень* — і найшвидший шлях — не мовна школа.

🗣 *Швейцарська реальність:*
Швейцарська (*Schweizerdeutsch*) — у повсякденному житті. Стандартна (*Hochdeutsch*) — у письмовій формі та в офіційних установах. Починайте з Hochdeutsch.

*Що дає швидкий результат:*
— *Мовний тандем* — безкоштовно, ефективніше за підручники. Додатки: Tandem, HelloTalk.
— *Volkshochschule (VHS)* — CHF 200–400 за курс, структуровано, очно.
— *Інститут Гете* — дорожче, але визнані сертифікати (A1–C2).
— *Duolingo + YouTube* — добре для старту. Самого лише недостатньо.

🎯 *Реальні цілі:*
— A2: щоденні справи та прийоми
— B1: вимагається для дозволу C
— B2: комфортно на роботі та в соціальних ситуаціях

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'self-employed', text: `Стати самозайнятим у Швейцарії простіше, ніж очікують — але ситуація з дозволами залежить від громадянства.

💼 *Для громадян ЄС/ЄАВТ:*
Зареєструйтесь в AHV як самозайнятий, пройдіть Анмельдунг — і ви офіційно ведете діяльність. Дозвіл B покриває самозайнятість.

*Для громадян не з ЄС:*
— На B-дозволі, прив'язаному до роботодавця: потрібна нова заявка на дозвіл
— Треба довести фінансову стійкість та користь для швейцарської економіки

📋 *Кроки (ЄС/ЄАВТ):*
1. Торговий реєстр кантону — якщо дохід понад CHF 100 000/рік
2. Реєстрація в AHV як самозайнятий (обов'язково)
3. Власне медичне страхування та пенсійні внески
4. Окремий бізнес-рахунок

AHV внески як самозайнятого — ~10% від доходу. ПДВ — при доході понад CHF 100 000/рік.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'pension', text: `Швейцарія має одну з найнадійніших пенсійних систем у світі — розумійте її з першого дня.

🏦 *Три стовпи:*

*1-й — AHV (державна пенсія):*
Обов'язковий для всіх. Внески автоматично з зарплати. При виїзді до пенсії — за умов можна повернути внески.

*2-й — BVG (виробнича пенсія):*
Обов'язковий при доході понад CHF 22 050/рік. Ви та роботодавець обидва платять. Можна отримати при купівлі нерухомості або остаточному виїзді.

*3-й — Приватна пенсія (3a):*
Добровільна, але один із найкращих податкових інструментів. До CHF 7 056/рік — вираховується повністю з оподатковуваного доходу. Реальна економія: CHF 1 500–2 500/рік.

💡 Відкрийте рахунок 3a одразу після приїзду — VIAC, Frankly або Finpension.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },

  { slug: 'emergency', text: `Швейцарія — одна з найбезпечніших країн. Але знати, кому дзвонити до того, як знадобиться — частина правильного облаштування.

📞 *Важливі номери — збережіть зараз:*
🚑 Медична допомога: *144*
🚒 Пожежна служба: *118*
👮 Поліція: *117*
🏔 Гірська рятувальна (REGA): *1414*
☠️ Центр з отруєнь: *145*
🌍 Єдиний номер ЄС: *112*

🏥 *Для не екстрених випадків:*
— *Medgate / Medi24:* цілодобова телефонна консультація, покривається більшістю страховок. Телефонуйте перед поїздкою до лікарні.
— *Permanence / Notfallpraxis:* клініки невідкладної допомоги — дешевше за лікарню.

Завжди майте при собі картку медичного страхування та дозвіл на проживання.

Виклик до лікарні може коштувати CHF 200–800+. Дзвінок на Medgate спочатку майже завжди дає правильний наступний крок.

Потрібна персональна допомога? Запишіться на безкоштовну консультацію на easyhelpswitzerland.ch 🇨🇭` },
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

  const slot = req.query.slot || 'morning';
  const day = Math.floor((Date.now() - new Date(new Date().getFullYear(), 0, 0)) / 86_400_000);

  try {
    let post, lang;
    if (slot === 'afternoon') {
      post = POSTS_UK[day % POSTS_UK.length];
      lang = 'uk';
    } else if (slot === 'evening') {
      post = POSTS_EN[(day + 10) % POSTS_EN.length];
      lang = 'en';
    } else {
      post = POSTS_EN[day % POSTS_EN.length];
      lang = 'en';
    }

    const result = await postToTelegram(post.text);
    return res.status(200).json({ success: true, lang, slot, topic: post.slug, message_id: result.message_id });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: err.message });
  }
}
