<!DOCTYPE html>
<html dir="ltr" lang="en">
<body style="font-family: sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f9f9f9;">

    <div style="text-align: center; margin-bottom: 20px; background-color: #001f3f; padding: 20px;">
        <img src="{{ $message->embed(public_path('images/logo.jpg')) }}" alt="Clinic Logo" style="width: 150px; height: auto;">
    </div>

    <div style="background-color: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #ddd;">
        <h2 style="color: #001f3f;">Welcome, Dr. {{ $user->first_name }}</h2>
        <p>Your account has been successfully created in our clinic system. You can now log in to the application using the following credentials:</p>

        <div style="background: #f4f4f4; padding: 20px; border-radius: 8px; border-right: 5px solid #001f3f; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 5px 0;"><strong>Password:</strong> <span style="color: #001f3f; font-weight: bold; font-size: 18px;">{{ $password }}</span></p>
        </div>
        

    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
        <p>Best regards,<br><strong>The Clinic Management</strong></p>
    </div>

</body>
</html>