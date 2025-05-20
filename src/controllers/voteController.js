const crypto = require('crypto');
const jwt = require('jsonwebtoken');
const Election = require('../models/Election');
const Candidate = require('../models/Candidate');
const Vote = require('../models/Vote');
const VoterSession = require('../models/VoterSession');
const { sendVerificationEmail } = require('../utils/emailSender');

// Generate a random 6-digit code
const generateVerificationCode = () => {
  return Math.floor(100000 + Math.random() * 900000).toString();
};

// Verify voter email
exports.verifyVoterEmail = async (req, res) => {
  try {
    const { electionId } = req.params;
    const { email } = req.body;
    
    if (!email) {
      return res.status(400).json({ message: 'Email is required' });
    }
    
    // Check if election exists and is active
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }
    
    if (!election.active) {
      return res.status(400).json({ message: 'This election is not active' });
    }
    
    // Check if email domain is allowed
    const emailDomain = '@' + email.split('@')[1];
    if (!election.allowedDomains.some(domain => emailDomain.endsWith(domain))) {
      return res.status(400).json({ message: 'Email domain not allowed for this election' });
    }
    
    // Check if user has already voted
    const existingVote = await Vote.findOne({ electionId, voterEmail: email });
    if (existingVote) {
      return res.status(400).json({ message: 'You have already voted in this election' });
    }
    
    // Generate verification code
    const verificationCode = generateVerificationCode();
    
    // Create or update voter session
    let voterSession = await VoterSession.findOne({ electionId, email });
    if (voterSession) {
      voterSession.verificationCode = verificationCode;
      voterSession.verified = false;
      voterSession.createdAt = Date.now();
    } else {
      voterSession = new VoterSession({
        electionId,
        email,
        verificationCode
      });
    }
    
    await voterSession.save();
    
    // Send verification email
    const emailSent = await sendVerificationEmail(email, verificationCode, election.title);
    
    if (!emailSent) {
      return res.status(500).json({ message: 'Failed to send verification email' });
    }
    
    res.json({
      message: 'Verification code sent to your email',
      sessionId: voterSession._id
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Confirm voter code
exports.confirmVoterCode = async (req, res) => {
  try {
    const { electionId } = req.params;
    const { sessionId, code } = req.body;
    
    if (!sessionId || !code) {
      return res.status(400).json({ message: 'Session ID and verification code are required' });
    }
    
    // Find voter session
    const voterSession = await VoterSession.findById(sessionId);
    if (!voterSession || voterSession.electionId.toString() !== electionId) {
      return res.status(400).json({ message: 'Invalid session' });
    }
    
    // Check if code is correct
    if (voterSession.verificationCode !== code) {
      return res.status(400).json({ message: 'Invalid verification code' });
    }
    
    // Mark session as verified
    voterSession.verified = true;
    await voterSession.save();
    
    // Create vote token
    const payload = {
      voter: {
        sessionId: voterSession._id,
        email: voterSession.email,
        electionId
      }
    };
    
    try {
      // Generate token synchronously to handle errors properly
      const token = jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '15m' });
      
      res.json({ 
        message: 'Verification successful',
        token: token,  // Standard token field name
        voteToken: token  // Keep for backward compatibility
      });
    } catch (tokenError) {
      console.error('Error generating token:', tokenError);
      return res.status(500).json({ message: 'Error generating authentication token' });
    }
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Cast vote
exports.castVote = async (req, res) => {
  try {
    const { electionId } = req.params;
    const { candidateId } = req.body;
    
    if (!candidateId) {
      return res.status(400).json({ message: 'Candidate ID is required' });
    }
    
    // Check if election exists and is active
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }
    
    if (!election.active) {
      return res.status(400).json({ message: 'This election is not active' });
    }
    
    // Check if candidate exists and belongs to this election
    const candidate = await Candidate.findOne({ _id: candidateId, electionId });
    if (!candidate) {
      return res.status(404).json({ message: 'Candidate not found in this election' });
    }
    
    // Check if voter has already voted
    // Handle both token formats for compatibility
    const voterEmail = req.voter.email || (req.voter.voter && req.voter.voter.email);
    
    if (!voterEmail) {
      return res.status(401).json({ message: 'Invalid voter token structure' });
    }
    
    const existingVote = await Vote.findOne({ 
      electionId, 
      voterEmail: voterEmail 
    });
    
    if (existingVote) {
      return res.status(400).json({ message: 'You have already voted in this election' });
    }
    
    // Create new vote
    const vote = new Vote({
      electionId,
      candidateId,
      voterEmail: voterEmail
    });
    
    await vote.save();
    
    // Delete voter session - handle both token formats
    const sessionId = req.voter.sessionId || (req.voter.voter && req.voter.voter.sessionId);
    if (sessionId) {
      await VoterSession.findByIdAndDelete(sessionId);
    }
    
    res.json({
      message: 'Vote cast successfully',
      voteId: vote._id
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};