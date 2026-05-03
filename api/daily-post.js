/**
 * Daily Telegram post generator for Easy Help Switzerland
 * Triggered by Vercel cron every day at 9:00 AM UTC
 * POST /api/daily-post  (Authorization: Bearer <CRON_SECRET>)
 */

const TOPICS = [
  { slug: 'b-permit',        title: 'Swiss B permit — what it is and how to get it' },
  { slug: 'anmeldung',       title: 'Anmeldung in Switzerland — 14-day registration rule' },
  { slug: 'health-insurance',title: 'Swiss health insurance (KVG) — how to choose the right provider' },
  { slug: 'apartment',       title: 'Finding an apartment in Zürich — what documents you need' },
  { slug: 'bank-account',    title: 'Opening a bank account in Switzerland as a newcomer' },
  { slug: 'cost-of-living',  title: 'Cost of living in Switzerland — realistic monthly budget' },
  { slug: 'driving-licence', title: 'Converting your foreign driving licence in Switzerland' },
  { slug: 'family-reunion',  title: 'Family reunion in Switzerland — bringing your partner and children' },
  { slug: 'work-permit',     title: 'Work permits in Switzerland — what your employer needs to do' },
  { slug: 'taxes',           title: 'Taxes in Switzerland — what newcomers need to know in year one' },
  { slug: 'c-permit',        title: 'Swiss C permit — when and how you can apply for it' },
  { slug: 'l-permit',        title: 'Swiss L permit — short-term residence explained' },
  { slug: 'kvg-deadline',    title: 'The 3-month health insurance deadline — what happens if you miss it' },
  { slug: 'salary',          title: 'Swiss salaries — how to negotiate and what is a fair offer in Zürich' },
  { slug: 'schools',         title: 'Enrolling your children in Swiss schools — what to prepare' },
  { slug: 'transport',       title: 'Swiss public transport — GA pass, Halbtax, and how to save' },
  { slug: 'german',          title: 'Learning German in Switzerland — the fastest practical approaches' },
  { slug: 'self-employed',   title: 'Becoming self-employed in Switzerland — permits and registration' },
  { slug: 'pension',         title: 'Swiss pension system (AHV/BVG) — what newcomers should understand' },
  { slug: 'emergency',       title: 'Emergency contacts and services in Switzerland every newcomer needs' },
];

const ARTICLE_LINKS = {
  'b-permit':         'https://easyhelpswitzerland.ch/blog/swiss-residence-permit',
  'anmeldung':        'https://easyhelpswitzerland.ch/blog/anmeldung-zurich',
  'health-insurance': 'https://easyhelpswitzerland.ch/blog/swiss-health-insurance',
  'apartment':        'https://easyhelpswitzerland.ch/blog/apartment-zurich',
  'bank-account':     'https://easyhelpswitzerland.ch/blog/bank-account-switzerland',
  'cost-of-living':   'https://easyhelpswitzerland.ch/blog/cost-of-living-switzerland',
  'driving-licence':  'https://easyhelpswitzerland.ch/blog/driving-licence-switzerland',
  'family-reunion':   'https://easyhelpswitzerland.ch/blog/moving-switzerland-family',
  'work-permit':      'https://easyhelpswitzerland.ch/blog/work-permit-switzerland',
};

function getTodaysTopic() {
  const start = new Date(new Date().getFullYear(), 0, 0);
  const dayOfYear = Math.floor((Date.now() - start) / 86_400_000);
  return TOPICS[dayOfYear % TOPICS.length];
}

async function generatePost(topic) {
  const articleLink = ARTICLE_LINKS[topic.slug]
    ? `\n\n📖 Full guide: ${ARTICLE_LINKS[topic.slug]}`
    : '';

  const prompt = `You are writing a daily post for the Telegram channel of Easy Help Switzerland — a relocation consulting service based in Zürich that helps people move to Switzerland with permits, registration, documents and practical guidance.

Write a Telegram post about: "${topic.title}"

Rules:
- 200–280 words total
- Start with a strong hook — a surprising fact, common mistake, or question that grabs attention
- Give 3–4 specific, practical, actionable tips (not vague advice)
- Use emojis sparingly for structure (1–2 per section, not on every line)
- Use *bold* for key terms (Telegram markdown)
- End with this exact CTA (do not change it): "Need personalised help? Book a free consultation at easyhelpswitzerland.ch 🇨🇭"
- Language: English
- Tone: warm, expert, direct — like advice from a knowledgeable friend, not a corporate brochure
- Do NOT use hashtags
- Do NOT start with "Hey" or "Hi everyone"

Write only the post text, nothing else.`;

  const response = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-api-key': process.env.ANTHROPIC_API_KEY,
      'anthropic-version': '2023-06-01',
    },
    body: JSON.stringify({
      model: 'claude-opus-4-5',
      max_tokens: 1024,
      messages: [{ role: 'user', content: prompt }],
    }),
  });

  if (!response.ok) {
    const err = await response.text();
    throw new Error(`Claude API error: ${response.status} ${err}`);
  }

  const data = await response.json();
  const text = data.content[0].text.trim();
  return text + articleLink;
}

async function postToTelegram(text) {
  const url = `https://api.telegram.org/bot${process.env.TELEGRAM_BOT_TOKEN}/sendMessage`;
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      chat_id: '@easyhelpswitzerland',
      text,
      parse_mode: 'Markdown',
      disable_web_page_preview: false,
    }),
  });

  const data = await response.json();
  if (!data.ok) throw new Error(`Telegram error: ${JSON.stringify(data)}`);
  return data.result;
}

export default async function handler(req, res) {
  // Allow GET for manual testing from browser, POST for cron
  if (req.method !== 'GET' && req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  // Verify secret (skip check if CRON_SECRET is not set — for initial testing)
  const cronSecret = process.env.CRON_SECRET;
  if (cronSecret) {
    const auth = req.headers.authorization;
    const querySecret = req.query.secret;
    if (auth !== `Bearer ${cronSecret}` && querySecret !== cronSecret) {
      return res.status(401).json({ error: 'Unauthorized' });
    }
  }

  try {
    const topic = getTodaysTopic();
    const text  = await generatePost(topic);
    const result = await postToTelegram(text);

    return res.status(200).json({
      success: true,
      topic: topic.title,
      message_id: result.message_id,
      preview: text.slice(0, 120) + '…',
    });
  } catch (err) {
    console.error('daily-post error:', err);
    return res.status(500).json({ error: err.message });
  }
}
