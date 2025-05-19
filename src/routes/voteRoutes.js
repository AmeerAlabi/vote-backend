const express = require('express');
const router = express.Router();
const voteController = require('../controllers/voteController');
const { voterAuth } = require('../middleware/auth');

// Verify voter email
router.post('/:electionId/verify', voteController.verifyVoterEmail);

// Confirm voter code
router.post('/:electionId/confirm', voteController.confirmVoterCode);

// Cast vote
router.post('/:electionId', voterAuth, voteController.castVote);

module.exports = router;