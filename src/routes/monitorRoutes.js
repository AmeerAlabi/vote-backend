const express = require('express');
const router = express.Router();
const {
  getPublicElectionResults,
  serveMonitorPage
} = require('../controllers/monitorController');

// Serve the live monitoring page for a specific election
router.get('/election/:electionId', serveMonitorPage);

// API endpoint for getting election results (used by the monitoring page)
router.get('/results/:electionId', getPublicElectionResults);

module.exports = router;