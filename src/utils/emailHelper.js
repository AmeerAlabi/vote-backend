const { Resend } = require('resend');
require('dotenv').config();

const sendVerificationEmail = async (email, code) => {
  try {
    // More detailed logging
    console.log('Environment check:', {
      apiKeyExists: !!process.env.RESEND_API_KEY,
      apiKeyLength: process.env.RESEND_API_KEY?.length,
      apiKeyPrefix: process.env.RESEND_API_KEY?.substring(0, 7), // First 7 chars only
      senderEmail: process.env.SENDER_EMAIL,
      targetEmail: email
    });

    const resend = new Resend(process.env.RESEND_API_KEY);
    
    const emailData = {
      from: process.env.SENDER_EMAIL,
      to: email,
      subject: 'Verify Your Email',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; text-align: center;">
          <h2 style="color: #333;">School Voting App</h2>
          <p style="font-size: 16px; color: #555;">
            Your verification code is:
          </p>
          <h3 style="font-size: 24px; color: #007bff; margin: 20px 0;">${code}</h3>
          <p style="font-size: 14px; color: #777;">
            This code will expire in 24 hours.
          </p>
          <p style="font-size: 14px; color: #777;">
            If you did not request this code, please ignore this email.
          </p>
        </div>
      `,
    };

    console.log('About to send email with data:', emailData);

    const data = await resend.emails.send(emailData);

    console.log('Email sent successfully:', JSON.stringify(data, null, 2));
    return true;
  } catch (error) {
    console.error('Email sending error:', error);
    console.error('Error details:', JSON.stringify(error, null, 2));
    return false;
  }
};

module.exports = { sendVerificationEmail };