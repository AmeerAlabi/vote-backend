<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $election->title }} — Live Results</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,.2); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; text-align: center; }
        .header h1 { font-size: 2.2em; margin-bottom: 10px; }
        .header p { opacity: .9; font-size: 1.1em; }
        .live-indicator { background: #28a745; color: #fff; padding: 8px 15px; border-radius: 20px; font-size: .9em; display: inline-block; margin-top: 15px; animation: pulse 2s infinite; }
        .live-indicator.closed { background: #6c757d; animation: none; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .7; } }
        .content { padding: 30px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #007bff; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        .candidate { background: #f8f9fa; margin: 15px 0; border-radius: 10px; padding: 20px; }
        .candidate-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; gap: 12px; }
        .candidate-identity { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .candidate-photo { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: #dee2e6; }
        .candidate-name { font-size: 1.3em; font-weight: bold; color: #333; }
        .candidate-votes { text-align: right; flex-shrink: 0; }
        .vote-count { font-size: 1.5em; font-weight: bold; color: #007bff; }
        .percentage { color: #666; font-size: 1.1em; }
        .progress-bar { width: 100%; height: 25px; background: #e9ecef; border-radius: 15px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #007bff, #0056b3); border-radius: 15px; transition: width .8s ease; }
        .loading, .error { text-align: center; padding: 40px; color: #666; }
        .error { background: #f8d7da; color: #721c24; border-radius: 10px; }
        .share-section { background: #e3f2fd; padding: 20px; border-radius: 10px; margin-top: 20px; text-align: center; }
        .share-url { background: #fff; padding: 10px; border-radius: 5px; border: 1px solid #ddd; width: 100%; margin: 10px 0; font-family: monospace; }
        .copy-btn { background: #007bff; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .copy-btn:hover { background: #0056b3; }
        .last-updated { text-align: center; color: #666; font-size: .9em; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 id="election-title">{{ $election->title }}</h1>
        <p id="election-description">{{ $election->description }}</p>
        <div class="live-indicator" id="live-indicator">🔴 LIVE RESULTS</div>
    </div>

    <div class="content">
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number" id="total-votes">0</div>
                <div class="stat-label">Total Votes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-candidates">0</div>
                <div class="stat-label">Candidates</div>
            </div>
        </div>

        <div id="results-container">
            <div class="loading"><h3>Loading live results...</h3></div>
        </div>

        <div class="share-section">
            <h4>📤 Share this live monitor</h4>
            <p>Anyone with this link can watch the live results:</p>
            <input type="text" class="share-url" id="share-url" readonly value="{{ url()->current() }}">
            <br>
            <button class="copy-btn" id="copy-btn" type="button">Copy Link</button>
        </div>

        <div class="last-updated" id="last-updated">Last updated: never</div>
    </div>
</div>

<script>
    const RESULTS_URL = @json($resultsUrl, JSON_UNESCAPED_SLASHES);
    const POLL_INTERVAL_MS = @json($pollIntervalMs);

    const el = (id) => document.getElementById(id);
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    async function loadResults() {
        try {
            const response = await fetch(RESULTS_URL, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            render(await response.json());
        } catch (error) {
            console.error('Error loading results:', error);
            el('results-container').innerHTML = '<div class="error"><h3>Error</h3><p>Failed to load results. Retrying…</p></div>';
        }
    }

    function render(data) {
        el('total-votes').textContent = data.totalVotes;
        el('total-candidates').textContent = data.results.length;
        el('live-indicator').textContent = data.election.active ? '🔴 LIVE RESULTS' : '⚫ VOTING CLOSED';
        el('live-indicator').classList.toggle('closed', !data.election.active);

        if (data.results.length === 0) {
            el('results-container').innerHTML = '<div class="loading"><h3>No candidates yet</h3></div>';
        } else {
            el('results-container').innerHTML = data.results.map((candidate, index) => `
                <div class="candidate">
                    <div class="candidate-header">
                        <div class="candidate-identity">
                            ${candidate.photoUrl ? `<img class="candidate-photo" src="${escapeHtml(candidate.photoUrl)}" alt="">` : ''}
                            <div class="candidate-name">${index === 0 && candidate.voteCount > 0 ? '🏆 ' : ''}${escapeHtml(candidate.name)}</div>
                        </div>
                        <div class="candidate-votes">
                            <div class="vote-count">${candidate.voteCount}</div>
                            <div class="percentage">${candidate.percentage}%</div>
                        </div>
                    </div>
                    <div class="progress-bar"><div class="progress-fill" style="width: ${candidate.percentage}%"></div></div>
                </div>
            `).join('');
        }

        el('last-updated').textContent = `Last updated: ${new Date(data.lastUpdated).toLocaleTimeString()}`;
    }

    el('copy-btn').addEventListener('click', async () => {
        const button = el('copy-btn');
        try {
            await navigator.clipboard.writeText(el('share-url').value);
            button.textContent = '✓ Copied!';
        } catch {
            el('share-url').select();
            button.textContent = 'Press Ctrl+C to copy';
        }
        setTimeout(() => { button.textContent = 'Copy Link'; }, 2000);
    });

    loadResults();
    setInterval(loadResults, POLL_INTERVAL_MS);
</script>
</body>
</html>
