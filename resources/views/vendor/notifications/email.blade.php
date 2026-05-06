<x-mail::message>
  Data 360

Bonjour 

@foreach ($introLines as $line)
{{ str_replace('Please click the button below to verify your email address.', 'Cliquez sur le bouton ci-dessous pour vérifier votre adresse email.', $line) }}

@endforeach

@isset($actionText)
<x-mail::button :url="$actionUrl" color="primary">
{{ str_replace('Verify Email Address', 'Vérifier mon email', $actionText) }}
</x-mail::button>
@endisset

@foreach ($outroLines as $line)
{{ str_replace('If you did not create an account, no further action is required.', 'Si vous n’avez pas créé de compte, ignorez simplement ce message.', $line) }}

@endforeach

Merci,  
L’équipe Data 360 

@isset($actionText)
<x-slot:subcopy>
Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :

{{ $actionUrl }}
</x-slot:subcopy>
@endisset
</x-mail::message>