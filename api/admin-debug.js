module.exports = async (req, res) => {
  const pw = process.env.ADMIN_PASSWORD;
  let bodyRaw = '';
  try {
    await new Promise((resolve, reject) => {
      const chunks = [];
      req.on('data', c => chunks.push(typeof c === 'string' ? Buffer.from(c) : c));
      req.on('end', () => { bodyRaw = Buffer.concat(chunks).toString('utf8'); resolve(); });
      req.on('error', reject);
    });
  } catch(e) { bodyRaw = 'ERROR: ' + e.message; }

  let parsed = {};
  try { parsed = JSON.parse(bodyRaw); } catch {}

  return res.status(200).json({
    env_password_set: !!pw,
    env_password_length: pw ? pw.length : 0,
    env_password_trimmed_length: pw ? pw.trim().length : 0,
    env_first_char_code: pw ? pw.charCodeAt(0) : null,
    env_last_char_code: pw ? pw.charCodeAt(pw.length - 1) : null,
    body_raw_length: bodyRaw.length,
    body_raw: bodyRaw,
    body_parsed_password: parsed.password || null,
    body_parsed_password_length: parsed.password ? parsed.password.length : 0,
    match: pw && parsed.password ? parsed.password.trim() === pw.trim() : false,
    req_body_type: typeof req.body,
    req_body: req.body,
  });
};
module.exports.config = { api: { bodyParser: false } };
