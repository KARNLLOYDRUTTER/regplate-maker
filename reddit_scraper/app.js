const http = require('http');
const https = require('https');

const SUPABASE_URL = process.env.SUPABASE_URL;
const SUPABASE_ANON_KEY = process.env.SUPABASE_ANON_KEY;

function querySupabase(path) {
  return new Promise((resolve, reject) => {
    const url = new URL(`${SUPABASE_URL}/rest/v1/${path}`);
    const options = {
      hostname: url.hostname,
      path: url.pathname + url.search,
      method: 'GET',
      headers: {
        'apikey': SUPABASE_ANON_KEY,
        'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
        'Content-Type': 'application/json',
      }
    };
    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try { resolve(JSON.parse(data)); }
        catch (e) { reject(e); }
      });
    });
    req.on('error', reject);
    req.end();
  });
}

function renderPage(posts, search, subreddit, subreddits) {
  const rows = posts.map(p => `
    <tr>
      <td><span class="badge">${p.subreddit}</span></td>
      <td><a href="${p.url}" target="_blank">${p.title}</a></td>
      <td>${p.relevance_score}</td>
      <td>${p.post_score}</td>
      <td>${p.num_comments}</td>
      <td>${p.created_utc ? p.created_utc.substring(0, 10) : ''}</td>
      <td class="keywords">${Array.isArray(p.matched_keywords) ? p.matched_keywords.slice(0, 3).join(', ') : ''}</td>
    </tr>
  `).join('');

  const subOptions = subreddits.map(s =>
    `<option value="${s}" ${subreddit === s ? 'selected' : ''}>${s}</option>`
  ).join('');

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reddit Business Ideas — You Can Fly Media</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0d0d0d; color: #f0f0f0; }
    header { background: #111; border-bottom: 2px solid #ff5500; padding: 16px 24px; display: flex; align-items: center; gap: 16px; }
    header h1 { font-size: 1.2rem; color: #ff5500; font-weight: 700; letter-spacing: 1px; }
    header span { font-size: 0.85rem; color: #888; }
    .container { max-width: 1400px; margin: 0 auto; padding: 24px; }
    .filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
    .filters input, .filters select { background: #1a1a1a; border: 1px solid #333; color: #f0f0f0; padding: 10px 14px; border-radius: 6px; font-size: 0.9rem; outline: none; }
    .filters input { flex: 1; min-width: 200px; }
    .filters input:focus, .filters select:focus { border-color: #ff5500; }
    .filters button { background: #ff5500; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
    .filters button:hover { background: #e04400; }
    .stats { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .stat { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px; padding: 16px 24px; text-align: center; }
    .stat .num { font-size: 2rem; font-weight: 700; color: #ff5500; }
    .stat .label { font-size: 0.8rem; color: #888; margin-top: 4px; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    thead tr { background: #1a1a1a; border-bottom: 2px solid #ff5500; }
    th { padding: 12px 16px; text-align: left; color: #ff5500; font-weight: 600; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #1e1e1e; transition: background 0.15s; }
    tbody tr:hover { background: #1a1a1a; }
    td { padding: 12px 16px; vertical-align: top; }
    td a { color: #f0f0f0; text-decoration: none; }
    td a:hover { color: #ff5500; }
    .badge { background: #ff5500; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
    .keywords { color: #888; font-size: 0.8rem; }
    .empty { text-align: center; padding: 60px; color: #555; }
  </style>
</head>
<body>
  <header>
    <h1>📡 Reddit Business Ideas</h1>
    <span>Powered by You Can Fly Media</span>
  </header>
  <div class="container">
    <div class="stats">
      <div class="stat"><div class="num">${posts.length}</div><div class="label">Posts Shown</div></div>
    </div>
    <form class="filters" method="GET" action="/">
      <input type="text" name="search" placeholder="Search titles..." value="${search || ''}">
      <select name="subreddit">
        <option value="">All Subreddits</option>
        ${subOptions}
      </select>
      <button type="submit">Filter</button>
      <a href="/"><button type="button">Reset</button></a>
    </form>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Subreddit</th>
            <th>Title</th>
            <th>Score</th>
            <th>Upvotes</th>
            <th>Comments</th>
            <th>Date</th>
            <th>Keywords</th>
          </tr>
        </thead>
        <tbody>
          ${rows.length ? rows : '<tr><td colspan="7" class="empty">No posts found.</td></tr>'}
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>`;
}

const server = http.createServer(async (req, res) => {
  try {
    const urlObj = new URL(req.url, `http://${req.headers.host}`);
    const search = urlObj.searchParams.get('search') || '';
    const subreddit = urlObj.searchParams.get('subreddit') || '';

    let query = 'reddit_posts?select=*&order=relevance_score.desc&limit=200';
    if (subreddit) query += `&subreddit=eq.${encodeURIComponent(subreddit)}`;
    if (search) query += `&title=ilike.${encodeURIComponent(`*${search}*`)}`;

    const posts = await querySupabase(query);
    const allSubs = await querySupabase('reddit_posts?select=subreddit&order=subreddit.asc');
    const subreddits = [...new Set(allSubs.map(r => r.subreddit))].filter(Boolean);

    res.writeHead(200, { 'Content-Type': 'text/html' });
    res.end(renderPage(Array.isArray(posts) ? posts : [], search, subreddit, subreddits));
  } catch (err) {
    res.writeHead(500, { 'Content-Type': 'text/plain' });
    res.end('Server error: ' + err.message);
  }
});

server.listen(process.env.PORT || 3000, () => {
  console.log('Reddit Ideas app running');
});
