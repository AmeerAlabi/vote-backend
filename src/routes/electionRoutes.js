const express = require('express');
const router = express.Router();
const electionController = require('../controllers/electionController');
const { adminAuth } = require('../middleware/auth');

// Create a new election (admin only)
router.post('/', adminAuth, electionController.createElection);

// Add a candidate to an election (admin only)
router.post('/:electionId/candidates', adminAuth, electionController.addCandidate);

// Get election details (public)
router.get('/:electionId', electionController.getElectionDetails);

// Get election results (admin only)
router.get('/:electionId/results', adminAuth, electionController.getElectionResults);

// Get all elections (admin only)
router.get('/', adminAuth, electionController.getAllElections);

// Update election status (admin only)
router.patch('/:electionId/status', adminAuth, electionController.updateElectionStatus);

module.exports = router;