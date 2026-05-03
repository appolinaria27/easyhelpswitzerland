/**
 * Afternoon post — Ukrainian, slot 1
 * Cron: 0 13 * * * (13:00 UTC = 15:00 Zürich summer)
 */
import { POSTS_UK } from '../lib/posts.js';

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
    const post = POSTS_UK[day % POSTS_UK.length];
    const result = await postToTelegram(post.text);
    return res.status(200).json({ success: true, lang: 'uk', topic: post.slug, message_id: result.message_id });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: err.message });
  }
}
