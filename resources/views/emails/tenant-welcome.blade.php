<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $data['company_name'] }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
            <h1 style="color: #fff; margin: 0; font-size: 28px;">Karibu!</h1>
            <p style="color: #fff; margin: 10px 0 0 0; font-size: 16px;">Welcome to {{ $data['company_name'] }}</p>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <p style="margin: 0 0 20px 0;">Dear <strong>{{ $data['tenant_name'] }}</strong>,</p>

            <p style="margin: 0 0 20px 0;">
                We are delighted to welcome you to <strong>{{ $data['company_name'] }}</strong>. Your apartment has been successfully registered in our system.
            </p>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin: 0 0 15px 0; color: #667eea;">Your Login Details</h3>
                <p style="margin: 0 0 10px 0;">
                    <strong>Portal URL:</strong> <a href="http://www.apartments.ecofieldgroup.com" style="color: #667eea;">www.apartments.ecofieldgroup.com</a>
                </p>
                <p style="margin: 0 0 10px 0;">
                    <strong>Phone:</strong> {{ $data['phone'] }}
                </p>
                <p style="margin: 0;">
                    <strong>Note:</strong> You will receive an OTP when logging in.
                </p>
            </div>

            <div style="background: #e8f5e9; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #4caf50;">
                <h3 style="margin: 0 0 15px 0; color: #2e7d32;">Payment Information</h3>
                <p style="margin: 0 0 10px 0;">
                    <strong>Control Number:</strong> {{ $data['control_number'] }}
                </p>
                <p style="margin: 0 0 10px 0;">
                    <strong>Bank:</strong> {{ $data['bank_name'] }}
                </p>
                <p style="margin: 0 0 10px 0;">
                    <strong>Account Number:</strong> {{ $data['account_number'] }}
                </p>
                <p style="margin: 0 0 10px 0;">
                    <strong>Rent Amount:</strong> Tsh {{ number_format($data['rent_amount'], 0) }}
                </p>
                <p style="margin: 0; font-size: 14px; color: #666;">
                    Please use your control number when making payments via bank or mobile money services.
                </p>
            </div>

            <p style="margin: 20px 0;">
                If you have any questions or need assistance, please don't hesitate to contact us.
            </p>

            <p style="margin: 0;">
                Best regards,<br>
                <strong>{{ $data['company_name'] }}</strong> Team
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0; font-size: 12px; color: #666;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
