const nodemailer = require('nodemailer');

const sendVerificationEmail = async (email, code, electionTitle) => {
  try {
    const transporter = nodemailer.createTransport({
      service: process.env.EMAIL_SERVICE,
      auth: {
        user: process.env.EMAIL_USER,
        pass: process.env.EMAIL_PASS
      }
    });

    const mailOptions = {
      from: process.env.EMAIL_USER,
      to: email,
      subject: `Verification Code for ${electionTitle}`,
      html: `
        <h1>School Voting App</h1>
        <p>Your verification code for the "${electionTitle}" election is:</p>
        <h2 style="color: #4285f4;">${code}</h2>
        <p>This code will expire in 1 hour.</p>
        <p>If you did not request this code, please ignore this email.</p>
      `
    };

    await transporter.sendMail(mailOptions);
    return true;
  } catch (error) {
    console.error('Email sending error:', error);
    return false;
  }
};

module.exports = { sendVerificationEmail };