<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EasyColoc Invitation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #0f172a;">
    <h2>You are invited to join {{ $colocation->name }}</h2>

    <p>
        Use this invitation token:
        <strong>{{ $colocation->invite_token }}</strong>
    </p>

    <p>
        Or open this link:
        <a href="{{ route('colocations.invite', $colocation->invite_token) }}">
            {{ route('colocations.invite', $colocation->invite_token) }}
        </a>
    </p>

    <p>This message was sent from EasyColoc.</p>
</body>
</html>
