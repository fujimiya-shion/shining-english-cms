New contact request

Name: {{ $contact->name }}
Email: {{ $contact->email }}
Message:
{{ $contact->message }}

IP: {{ $contact->ip_address ?? 'N/A' }}
User Agent: {{ $contact->user_agent ?? 'N/A' }}

