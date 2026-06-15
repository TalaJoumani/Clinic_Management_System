
<div style="direction: rtl; text-align: right; font-family: sans-serif; padding: 20px; background-color: #ffffff; border: 1px solid #001f3f;">
    
    <!-- إضافة اللوغو -->
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{{ $message->embed(public_path('images/logo.jpg')) }}" alt="Clinic Logo" style="width: 150px; height: auto;">
    </div>

    <h2 style="color: #001f3f;">Your Verification Code</h2>
    <p style="color: #333;">Thank you for signing up with our clinic system. Please use the following code to activate your account:</p>
    
    <div style="background: #001f3f; padding: 20px; text-align: center; font-size: 30px; font-weight: bold; letter-spacing: 10px; color: #ffffff; border-radius: 8px;">
        {{ $otp }}
    </div>
    
    <p style="margin-top: 20px; font-size: 12px; color: #666;">This code is valid for 10 minutes only.</p>
</div>