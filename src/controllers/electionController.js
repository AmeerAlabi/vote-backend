const Election = require('../models/Election');
const Candidate = require('../models/Candidate');
const Vote = require('../models/Vote');

// Get all candidates (admin only)
exports.getAllCandidates = async (req, res) => {
  try {
    const candidates = await Candidate.find()
      .populate('election', 'title')
      .sort({ createdAt: -1 });
    
    res.status(200).json({
      candidates,
      count: candidates.length
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get candidates for a specific election
exports.getCandidatesByElection = async (req, res) => {
  try {
    const { electionId } = req.params;
    
    // Check if election exists
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }
    
    const candidates = await Candidate.find({ election: electionId })
      .sort({ createdAt: -1 });
    
    res.status(200).json({
      electionId,
      candidates,
      count: candidates.length
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Create a new election
exports.createElection = async (req, res) => {
  try {
    const { title, description, allowedDomains } = req.body;
    
    // Validate input
    if (!title || !description || !allowedDomains || !allowedDomains.length) {
      return res.status(400).json({ message: 'Please provide all required fields' });
    }
    
    // Create new election
    const election = new Election({
      title,
      description,
      allowedDomains,
      createdBy: req.admin.admin.id
    });
    
    await election.save();
    
    res.status(201).json({
      message: 'Election created successfully',
      electionId: election._id,
      votingLink: `${req.protocol}://${req.get('host')}/vote/${election._id}`
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Add a candidate to an election
exports.addCandidate = async (req, res) => {
  try {
    const { electionId } = req.params;
    const { name, bio, photoUrl } = req.body;
    
    // Validate input
    if (!name || !bio) {
      return res.status(400).json({ message: 'Please provide all required fields' });
    }
    
    // Check if election exists
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }
    
    // Check if admin owns this election
    if (election.createdBy.toString() !== req.admin.admin.id) {
      return res.status(403).json({ message: 'Not authorized to modify this election' });
    }
    
    // Create new candidate
    const candidate = new Candidate({
      electionId,
      name,
      bio,
      photoUrl: photoUrl || 'https://via.placeholder.com/150'
    });
    
    await candidate.save();
    
    res.status(201).json({
      message: 'Candidate added successfully',
      candidateId: candidate._id
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get election details
exports.getElectionDetails = async (req, res) => {
  try {
    const { electionId } = req.params;
    
    // Find election
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }
    
    // Find candidates for this election
    const candidates = await Candidate.find({ electionId });
    
    res.json({
      election: {
        id: election._id,
        title: election.title,
        description: election.description,
        allowedDomains: election.allowedDomains,
        createdAt: election.createdAt,
        active: election.active
      },
      candidates: candidates.map(candidate => ({
        id: candidate._id,
        name: candidate.name,
        bio: candidate.bio,
        photoUrl: candidate.photoUrl
      }))
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get election results (admin only)
exports.getElectionResults = async (req, res) => {
  try {
    const { electionId } = req.params;
    
    // Check if election exists
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }
    
    // Check if admin owns this election
    if (election.createdBy.toString() !== req.admin.admin.id) {
      return res.status(403).json({ message: 'Not authorized to view results of this election' });
    }
    
    // Get all candidates
    const candidates = await Candidate.find({ electionId });
    
    // Get vote counts for each candidate
    const results = await Promise.all(
      candidates.map(async (candidate) => {
        const voteCount = await Vote.countDocuments({
          electionId,
          candidateId: candidate._id
        });
        
        return {
          candidateId: candidate._id,
          name: candidate.name,
          voteCount
        };
      })
    );
    
    // Sort by vote count (highest first)
    results.sort((a, b) => b.voteCount - a.voteCount);
    
    // Get total votes
    const totalVotes = await Vote.countDocuments({ electionId });
    
    res.json({
      electionId,
      title: election.title,
      totalVotes,
      results
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get all elections (admin only)
exports.getAllElections = async (req, res) => {
  try {
    // Find all elections created by this admin
    const elections = await Election.find({ createdBy: req.admin.admin.id })
      .sort({ createdAt: -1 });
    
    res.json({
      elections: elections.map(election => ({
        id: election._id,
        title: election.title,
        description: election.description,
        createdAt: election.createdAt,
        active: election.active
      }))
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update election status (active/inactive)
exports.updateElectionStatus = async (req, res) => {
  try {
    const { electionId } = req.params;
    const { active } = req.body;
    
    if (typeof active !== 'boolean') {
      return res.status(400).json({ message: 'Active status must be a boolean' });
    }
    
    // Check if election exists
    const election = await Election.findById(electionId);
    if (!election) {
      return res.status(404).json({ message: 'Election not found' });
    }
    
    // Check if admin owns this election
    if (election.createdBy.toString() !== req.admin.admin.id) {
      return res.status(403).json({ message: 'Not authorized to modify this election' });
    }
    
    // Update election status
    election.active = active;
    await election.save();
    
    res.json({
      message: `Election ${active ? 'activated' : 'deactivated'} successfully`,
      electionId,
      active
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};