const { Resend } = require('resend');

const sendVerificationEmail = async (email, code, electionTitle) => {
  try {
    const resend = new Resend(process.env.RESEND_API_KEY);
    
    console.log('Sending email with Resend:', {
      apiKeySet: process.env.RESEND_API_KEY ? 'Yes' : 'No',
      from: process.env.SENDER_EMAIL,
      to: email
    });

    const data = await resend.emails.send({
      from: process.env.SENDER_EMAIL,
      to: email,
      subject: `Verification Code for ${electionTitle}`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; text-align: center;">
          <h2 style="color: #333;">School Voting App</h2>
          <p style="font-size: 16px; color: #555;">
            Your verification code for the "${electionTitle}" election is:
          </p>
          <h3 style="font-size: 24px; color: #007bff; margin: 20px 0;">${code}</h3>
          <p style="font-size: 14px; color: #777;">
            This code will expire in 1 hour.
          </p>
          <p style="font-size: 14px; color: #777;">
            If you did not request this code, please ignore this email.
          </p>
        </div>
      `,
    });

    console.log('Email sent successfully:', data);
    return true;
  } catch (error) {
    console.error('Email sending error:', error);
    return false;
  }
};

module.exports = { sendVerificationEmail };