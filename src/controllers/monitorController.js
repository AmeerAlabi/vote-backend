const Election = require('../models/Election');
const Candidate = require('../models/Candidate');
const Vote = require('../models/Vote');

// Get live results for a specific election (public access)
const getPublicElectionResults = async (req, res) => {
  try {
    const { electionId } = req.params;

    // Get election details
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }

    // Get all candidates for this election
    const candidates = await Candidate.find({ electionId });

    // Get vote counts for each candidate
    const results = await Promise.all(
      candidates.map(async (candidate) => {
        const voteCount = await Vote.countDocuments({ 
          candidateId: candidate._id,
          electionId: electionId 
        });
        
        return {
          candidateId: candidate._id,
          name: candidate.name,
          bio: candidate.bio,
          photoUrl: candidate.photoUrl,
          voteCount: voteCount
        };
      })
    );

    // Calculate total votes
    const totalVotes = results.reduce((sum, candidate) => sum + candidate.voteCount, 0);

    // Add percentage to each candidate
    const resultsWithPercentage = results.map(candidate => ({
      ...candidate,
      percentage: totalVotes > 0 ? ((candidate.voteCount / totalVotes) * 100).toFixed(2) : 0
    }));

    // Sort by vote count (highest first)
    resultsWithPercentage.sort((a, b) => b.voteCount - a.voteCount);

    res.json({
      success: true,
      election: {
        id: election._id,
        title: election.title,
        description: election.description,
        active: election.active,
        createdAt: election.createdAt
      },
      results: resultsWithPercentage,
      totalVotes,
      lastUpdated: new Date()
    });

  } catch (error) {
    console.error('Error fetching election results:', error);
    res.status(500).json({ message: 'Server error', error: error.message });
  }
};

// Serve the monitoring page for a specific election
const serveMonitorPage = (req, res) => {
  const { electionId } = req.params;
  
  const html = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Live Monitor</title>
    <script src="/socket.io/socket.io.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 1.1em; }
        .live-indicator { 
            background: #28a745; 
            color: white; 
            padding: 8px 15px; 
            border-radius: 20px; 
            font-size: 0.9em;
            display: inline-block;
            margin-top: 15px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        .content { padding: 30px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #007bff;
        }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        .candidate {
            background: #f8f9fa;
            margin: 15px 0;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.2s ease;
        }
        .candidate:hover { transform: translateY(-2px); }
        .candidate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .candidate-name { 
            font-size: 1.3em; 
            font-weight: bold; 
            color: #333;
        }
        .candidate-votes {
            text-align: right;
        }
        .vote-count { 
            font-size: 1.5em; 
            font-weight: bold; 
            color: #007bff; 
        }
        .percentage { 
            color: #666; 
            font-size: 1.1em;
        }
        .progress-bar {
            width: 100%;
            height: 25px;
            background-color: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            border-radius: 15px;
            transition: width 0.8s ease;
            position: relative;
        }
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .loading { 
            text-align: center; 
            padding: 40px; 
            color: #666;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .last-updated {
            text-align: center;
            color: #666;
            font-size: 0.9em;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .share-section {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
        }
        .share-url {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 100%;
            margin: 10px 0;
            font-family: monospace;
        }
        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .copy-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 id="election-title">Election Monitor</h1>
            <p id="election-description">Loading election details...</p>
            <div class="live-indicator">🔴 LIVE RESULTS</div>
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
                <div class="loading">
                    <h3>Loading live results...</h3>
                    <p>Please wait while we fetch the latest data</p>
                </div>
            </div>

            <div class="share-section">
                <h4>📤 Share this live monitor</h4>
                <p>Anyone with this link can watch the live results:</p>
                <input type="text" class="share-url" id="share-url" readonly>
                <br>
                <button class="copy-btn" onclick="copyShareUrl()">Copy Link</button>
            </div>

            <div class="last-updated" id="last-updated">
                Last updated: Never
            </div>
        </div>
    </div>

    <script>
        const electionId = '${electionId}';
        const socket = io();
        let currentResults = null;

        // Set share URL
        document.getElementById('share-url').value = window.location.href;

        // Copy share URL function
        function copyShareUrl() {
            const shareUrl = document.getElementById('share-url');
            shareUrl.select();
            document.execCommand('copy');
            
            const btn = document.querySelector('.copy-btn');
            const originalText = btn.textContent;
            btn.textContent = '✓ Copied!';
            btn.style.background = '#28a745';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '#007bff';
            }, 2000);
        }

        // Load election results
        async function loadResults() {
            try {
                const response = await fetch(\`/api/monitor/results/\${electionId}\`);
                const data = await response.json();
                
                if (data.success) {
                    currentResults = data;
                    updateDisplay(data);
                } else {
                    showError('Election not found or no longer active');
                }
            } catch (error) {
                console.error('Error loading results:', error);
                showError('Failed to load results. Please refresh the page.');
            }
        }

        // Update display with results
        function updateDisplay(data) {
            // Update header
            document.getElementById('election-title').textContent = data.election.title;
            document.getElementById('election-description').textContent = data.election.description;
            
            // Update stats
            document.getElementById('total-votes').textContent = data.totalVotes;
            document.getElementById('total-candidates').textContent = data.results.length;
            
            // Update results
            const container = document.getElementById('results-container');
            
            if (data.results.length === 0) {
                container.innerHTML = '<div class="loading"><h3>No votes yet</h3><p>Be the first to vote!</p></div>';
                return;
            }

            container.innerHTML = data.results.map((candidate, index) => \`
                <div class="candidate">
                    <div class="candidate-header">
                        <div class="candidate-name">
                            \${index === 0 && candidate.voteCount > 0 ? '🏆 ' : ''}\${candidate.name}
                        </div>
                        <div class="candidate-votes">
                            <div class="vote-count">\${candidate.voteCount}</div>
                            <div class="percentage">\${candidate.percentage}%</div>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: \${candidate.percentage}%;"></div>
                    </div>
                </div>
            \`).join('');
            
            // Update last updated time
            document.getElementById('last-updated').textContent = 
                \`Last updated: \${new Date(data.lastUpdated).toLocaleTimeString()}\`;
        }

        // Show error message
        function showError(message) {
            document.getElementById('results-container').innerHTML = 
                \`<div class="error"><h3>Error</h3><p>\${message}</p></div>\`;
        }

        // Join election room for real-time updates
        socket.emit('join-election', electionId);

        // Listen for real-time updates
        socket.on('vote-update', (data) => {
            if (data.electionId === electionId) {
                console.log('New vote received! Updating results...');
                loadResults(); // Reload results when new vote comes in
            }
        });

        // Initial load
        loadResults();

        // Fallback: refresh every 30 seconds
        setInterval(loadResults, 30000);
    </script>
</body>
</html>`;

  res.send(html);
};

module.exports = {
  getPublicElectionResults,
  serveMonitorPage
};