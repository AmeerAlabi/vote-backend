const mongoose = require('mongoose');

const voterSessionSchema = new mongoose.Schema({
  electionId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Election',
    required: true
  },
  email: {
    type: String,
    required: true,
    lowercase: true,
    trim: true
  },
  verificationCode: {
    type: String,
    required: true
  },
  verified: {
    type: Boolean,
    default: false
  },
  createdAt: {
    type: Date,
    default: Date.now,
    expires: 3600 // Session expires after 1 hour
  }
});

const VoterSession = mongoose.model('VoterSession', voterSessionSchema);

module.exports = VoterSession;