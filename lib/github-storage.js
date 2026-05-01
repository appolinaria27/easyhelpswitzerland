/**
 * github-storage.js
 * Persistent storage backed by GitHub repo file API.
 * Uses native fetch (Node 18+) — no extra dependencies.
 *
 * Env vars required (set in Vercel dashboard):
 *   GITHUB_TOKEN  — Personal Access Token with "repo" scope
 *   GITHUB_REPO   — e.g. "appolinaria27/easyhelpswitzerland"
 *   GITHUB_BRANCH — e.g. "main"  (defaults to "main")
 */

const REPO   = process.env.GITHUB_REPO   || 'appolinaria27/easyhelpswitzerland';
const BRANCH = process.env.GITHUB_BRANCH || 'main';
const BASE   = 'https://api.github.com';

function headers() {
  return {
    Authorization: `token ${process.env.GITHUB_TOKEN}`,
    Accept:        'application/vnd.github.v3+json',
    'Content-Type': 'application/json',
    'User-Agent':  'easyhelp-server',
  };
}

/**
 * Read a JSON file from GitHub.
 * Returns { data, sha } on success, or { data: null, sha: null } if not found.
 */
async function ghRead(path) {
  const url = `${BASE}/repos/${REPO}/contents/${path}?ref=${BRANCH}`;
  const res = await fetch(url, { headers: headers() });
  if (res.status === 404) return { data: null, sha: null };
  if (!res.ok) {
    const txt = await res.text();
    throw new Error(`GitHub read ${path}: ${res.status} ${txt}`);
  }
  const json = await res.json();
  const content = Buffer.from(json.content, 'base64').toString('utf8');
  return { data: JSON.parse(content), sha: json.sha };
}

/**
 * Write (create or update) a JSON file to GitHub.
 */
async function ghWrite(path, data, sha) {
  const url     = `${BASE}/repos/${REPO}/contents/${path}`;
  const content = Buffer.from(JSON.stringify(data, null, 2)).toString('base64');
  const body    = {
    message: `update ${path}`,
    content,
    branch: BRANCH,
  };
  if (sha) body.sha = sha;

  const res = await fetch(url, {
    method:  'PUT',
    headers: headers(),
    body:    JSON.stringify(body),
  });
  if (!res.ok) {
    const txt = await res.text();
    throw new Error(`GitHub write ${path}: ${res.status} ${txt}`);
  }
  return res.json();
}

/**
 * Delete a file from GitHub.
 */
async function ghDelete(path, sha) {
  const url  = `${BASE}/repos/${REPO}/contents/${path}`;
  const body = { message: `delete ${path}`, sha, branch: BRANCH };
  const res  = await fetch(url, {
    method:  'DELETE',
    headers: headers(),
    body:    JSON.stringify(body),
  });
  if (!res.ok && res.status !== 404) {
    const txt = await res.text();
    throw new Error(`GitHub delete ${path}: ${res.status} ${txt}`);
  }
}

/**
 * List files in a GitHub directory.
 * Returns array of { name, path, sha } or [] if dir missing.
 */
async function ghList(dir) {
  const url = `${BASE}/repos/${REPO}/contents/${dir}?ref=${BRANCH}`;
  const res = await fetch(url, { headers: headers() });
  if (res.status === 404) return [];
  if (!res.ok) {
    const txt = await res.text();
    throw new Error(`GitHub list ${dir}: ${res.status} ${txt}`);
  }
  const items = await res.json();
  return Array.isArray(items) ? items.filter(i => i.type === 'file' && i.name.endsWith('.json')) : [];
}

// ── High-level helpers ────────────────────────────────────────────────────────

/**
 * Save a booking file.
 * dir:  e.g. "bookings", "pending-bookings", "free-consultations"
 * name: e.g. "booking-abc123.json" or "consult-abc123.json"
 */
async function saveEntry(dir, name, data) {
  const path = `${dir}/${name}`;
  // Check if file already exists (to get its sha for update)
  const { sha } = await ghRead(path);
  await ghWrite(path, data, sha);
}

/**
 * Load all entries from a directory.
 * Returns array of parsed objects with _file and _type injected.
 */
async function loadDir(dir, type) {
  const files = await ghList(dir);
  const results = await Promise.all(
    files.map(async f => {
      try {
        const { data } = await ghRead(f.path);
        if (!data) return null;
        data._type = type;
        data._file = f.name;
        return data;
      } catch { return null; }
    })
  );
  return results.filter(Boolean).sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
}

/**
 * Load a single entry by internal_booking_id, searching across all dirs.
 * Returns { data, path, sha, type } or null.
 */
async function findEntry(id) {
  const clean = (id || '').replace(/[^a-f0-9]/g, '');
  if (!clean) return null;

  const dirs = [
    { dir: 'bookings',           type: 'paid',    prefix: 'booking-' },
    { dir: 'pending-bookings',   type: 'pending', prefix: 'booking-' },
    { dir: 'free-consultations', type: 'free',    prefix: 'consult-' },
  ];

  for (const { dir, type, prefix } of dirs) {
    const files = await ghList(dir);
    for (const f of files) {
      try {
        const { data, sha } = await ghRead(f.path);
        if (data && data.internal_booking_id === clean) {
          return { data, path: f.path, sha, type };
        }
      } catch {}
    }
  }
  return null;
}

/**
 * Load admin note for a booking id.
 * Stored at admin-data/{id}.json
 */
async function loadNote(id) {
  const clean = (id || '').replace(/[^a-f0-9]/g, '');
  if (!clean) return {};
  const { data } = await ghRead(`admin-data/${clean}.json`);
  return data || {};
}

/**
 * Save admin note for a booking id.
 */
async function saveNote(id, note) {
  const clean = (id || '').replace(/[^a-f0-9]/g, '');
  if (!clean) return;
  const path = `admin-data/${clean}.json`;
  const { sha } = await ghRead(path);
  await ghWrite(path, { ...note, updated_at: new Date().toISOString() }, sha);
}

/**
 * Delete an entry file.
 * path: full repo path, e.g. "bookings/booking-abc123.json"
 * sha:  current SHA of the file
 */
async function deleteEntry(path, sha) {
  await ghDelete(path, sha);
}

/**
 * Load positions catalog from data/positions.json
 */
async function loadPositions() {
  const { data } = await ghRead('data/positions.json');
  return Array.isArray(data) ? data : [];
}

/**
 * Save positions catalog to data/positions.json
 */
async function savePositions(positions) {
  const { sha } = await ghRead('data/positions.json');
  await ghWrite('data/positions.json', positions, sha);
}

/**
 * Load all bills.
 */
async function loadBills() {
  const files = await ghList('bills');
  const results = await Promise.all(
    files.map(async f => {
      try {
        const { data } = await ghRead(f.path);
        return data;
      } catch { return null; }
    })
  );
  return results
    .filter(Boolean)
    .sort((a, b) => {
      if (a.status !== b.status) return a.status === 'open' ? -1 : 1;
      return (b.created_at || '').localeCompare(a.created_at || '');
    });
}

/**
 * Load a single bill by id.
 */
async function loadBill(id) {
  const { data } = await ghRead(`bills/bill-${id}.json`);
  return data;
}

/**
 * Save a bill.
 */
async function saveBill(bill) {
  const path = `bills/bill-${bill.id}.json`;
  const { sha } = await ghRead(path);
  await ghWrite(path, bill, sha);
}

module.exports = {
  ghRead,
  ghWrite,
  ghDelete,
  ghList,
  saveEntry,
  loadDir,
  findEntry,
  loadNote,
  saveNote,
  deleteEntry,
  loadPositions,
  savePositions,
  loadBills,
  loadBill,
  saveBill,
};
